-- =============================================================================
-- Power 账本分录 — power_ledger_entries（append-only，独立 Power 流水）
-- Owner 决策：CR-20260818-003（2026-08-18）
-- =============================================================================
-- 背景：
--   MC1 Freeze（MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md §4 第 46 行）明确提到
--   「不删除/覆盖历史 Trade、APT Ledger、Power Ledger」，但 MC1 冻结的 8 核心实体中
--   只有 `power_positions`（scalar 持仓）与 `apt_ledger_entries`（APT 账本，asset 仅
--   APT-I），**没有独立的 Power 流水表**。Power 的逐笔消耗/冻结/释放/恢复无法审计、无法冲正。
--
-- 本迁移新增 `power_ledger_entries`，对标 `apt_ledger_entries` 的 append-only 防护模型：
--   - 经济字段（除 state / audit_event_id / object_version 外）一经写入永不覆盖。
--   - 无 updated_time 列；state 是唯一可变列，仅由 Power 模块 Authoritative Writer 流转，
--     且必须同时追加 append-only 审计事件并更新 audit_event_id。
--   - 冲正（reversal）通过新增分录 + reversal_of 指向原分录，不删不覆盖原文。
--
-- 与 apt_ledger_entries 的差异：
--   - 主键 user_id（Power 以用户为粒度，对齐 power_positions.user_id 主键），非 account_id。
--   - 无 asset 列（Power 无 APT-I/APT-C 之分）。
--   - quantity 精度 decimal(18,4)，与 power_positions 的 Power 字段精度一致（非 APT 的 36,18）。
--   - object_version 直接内联（APT 账本是 MC2 补列 CR-20260815-001；本表新建即含）。
--
-- entry_type 取值：varchar(64)，具体业务事件码（POWER_CONSUMED / POWER_RELEASED /
--   POWER_FROZEN / POWER_UNFROZEN / POWER_RESTORED / POWER_REVERSAL 等）待 Power
--   Mutation Contract 冻结后对齐 Event Catalog，本迁移不冻结具体枚举。
--
-- 状态：本表为新增表（非修改 MC1 冻结 DDL）。Power 变更逻辑（consume/recover）仍
--   FAIL_CLOSED 直到 Power Mutation Contract 冻结；本表仅提供 append-only 落盘能力。
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `power_ledger_entries` (
  `power_ledger_entry_id` bigint unsigned NOT NULL COMMENT 'Power 分录ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID(power_positions.user_id)',
  `quantity` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '变动数量(正数，方向见 entry_direction)',
  `entry_direction` tinyint NOT NULL DEFAULT '1' COMMENT '分录方向: 1=入账(CREDIT，释放/恢复) -1=出账(DEBIT，消耗/冻结)',
  `entry_type` varchar(64) NOT NULL DEFAULT '' COMMENT '分录类型(业务事件码，与 Event Catalog 对齐，TBC)',
  `state` enum('pending','posted','reversed','disputed') NOT NULL DEFAULT 'pending' COMMENT '分录状态(唯一可变列，仅 Authoritative Writer 流转)',
  `source_object_type` varchar(64) NOT NULL DEFAULT '' COMMENT '来源对象类型(如 otc_order/robot_upgrade_order)',
  `source_object_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '来源对象ID',
  `journal_batch_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '日记账批次ID(同批次多分录)',
  `reversal_of` bigint unsigned NOT NULL DEFAULT '0' COMMENT '冲正引用：指向被冲正原分录 power_ledger_entry_id(0=非冲正)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(写操作去重)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID(state 流转的证据)',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁，每次状态流转+1)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  PRIMARY KEY (`power_ledger_entry_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_state` (`state`),
  KEY `idx_source` (`source_object_type`,`source_object_id`),
  KEY `idx_batch` (`journal_batch_id`),
  KEY `idx_reversal` (`reversal_of`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Power账本分录(append-only：经济字段不可UPDATE/DELETE，仅 state 流转，更正走 reversal_of 追加，不删原文)';
