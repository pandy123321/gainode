-- =============================================================================
-- Machine Contract 第二批 — audit_events 审计事件表 DDL（State Transition Freeze CANDIDATE）
-- =============================================================================
-- 变更原因：
--   Machine Contract 第一批（MC1）冻结了 8 个核心实体 DDL + Canonical State Freeze，
--   并在 §3.6 中留下 CONTRACT GAP：「审计事件表 schema 待 Event Catalog / Ledger
--   Mutation Contract 阶段正式冻结」。本文件为该 CONTRACT GAP 的落地方案。
--
-- 影响范围：
--   本文件在默认主库 `webman` 中创建 1 张 V2.0 审计事件表 `audit_events`
--   （全新表，不触碰任何 V1.x 表）。append-only（无 UPDATE/DELETE）。
--
-- 权威契约：
--   Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md
--   §3 AuditLog 对象（字段对齐）。append-only 语义来自 MC1 §3.6 审计不变量。
--
-- 与 V1.x 的关键差异：
--   1. 主键：Snowflake ID（bigint unsigned），不使用 AUTO_INCREMENT。
--   2. append-only：无 updated_time 列；一事件一行，顺序可重建。
--   3. 独立表：不与 V1.x 遗留 `sys_operation_logs` 合并（语义不同）。
--   4. before/after_snapshot_id 引用 parameter_snapshots（复用参数快照，非独立 diff 机制）。
--
-- 执行方式：forward-only migration（一次性建表，禁止内置 DROP/CREATE）。
--   - 首次执行创建 1 张新表。
--   - 若目标表已存在则失败（fail-fast），绝不删除已有数据。
--   - 重跑判定通过 information_schema / migration version 记录「已应用」，而非重建。
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE `audit_events` (
  `audit_event_id` bigint unsigned NOT NULL COMMENT '审计事件ID(Snowflake，主键)',
  `event_code` varchar(64) NOT NULL DEFAULT '' COMMENT '事件码(对齐 Event Catalog)',
  `actor_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '操作者ID(user_id 或系统=0)',
  `actor_role` varchar(32) NOT NULL DEFAULT '' COMMENT '操作者角色(05 §8 RBAC)',
  `target_object_type` varchar(64) NOT NULL DEFAULT '' COMMENT '目标对象类型(如 apt_ledger_entries)',
  `target_object_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '目标对象ID',
  `before_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '变更前快照ID(引用 parameter_snapshots.snapshot_id)',
  `after_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '变更后快照ID(引用 parameter_snapshots.snapshot_id)',
  `outcome` varchar(32) NOT NULL DEFAULT '' COMMENT '结果(SUCCESS/FAILED/REJECTED)',
  `reason_code` varchar(64) NOT NULL DEFAULT '' COMMENT '原因码',
  `request_id` varchar(64) NOT NULL DEFAULT '' COMMENT '请求ID',
  `approval_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审批ID',
  `case_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联案件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  PRIMARY KEY (`audit_event_id`),
  KEY `idx_target` (`target_object_type`,`target_object_id`),
  KEY `idx_actor` (`actor_id`),
  KEY `idx_event_code` (`event_code`),
  KEY `idx_created_time` (`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审计事件表(append-only，无UPDATE/DELETE)';
