-- =============================================================================
-- Machine Contract 第二批 — apt_ledger_entries 补充 object_version 乐观锁列
-- （State Transition Freeze CANDIDATE，IR 638 P1-2 方案 A）
-- =============================================================================
-- 变更原因：
--   Machine Contract 第一批（MC1）冻结的 8 个核心实体中，apt_ledger_entries 是唯一
--   缺少 `object_version` 并发控制列的表（其余 7 表均已含 object_version）。
--   Machine Contract 第二批的 A.0 通用不变量要求「每个 transfer 必须附带 object_version
--   乐观锁校验（If-Match）」。为使 Ledger 满足该不变量、消除「必须用 object_version」
--   与「无 object_version 字段」的自相矛盾，新增本 dated migration 补列。
--
-- 权威契约：
--   .project-ai/tasks/TASK-20260815-001/design.md A.0（通用不变量 #2）+ A.1.1（Ledger
--   Mutation Field Contract，白名单三列：state / audit_event_id / object_version）。
--   Independent Review IR 638 P1-2（方案 A：Ledger 自身使用 object_version）。
--
-- 影响范围：
--   仅对 `apt_ledger_entries` 执行一次 ADD COLUMN。不改动任何已有数据；不改 MC1 已冻结
--   历史 DDL（20260813_machine_contract_batch1_8_core_entities.sql 保持原样）。
--
-- 状态：CANDIDATE（冻结候选，已落盘 sql/ 日期文件，未 FROZEN）。冻结前可修改。
--
-- 执行方式：forward-only migration（一次性加列，禁止内置 DROP COLUMN）。
--   - 首次执行添加 1 列。
--   - 若目标列已存在则失败（fail-fast），绝不删除/重建已有数据。
--   - 重跑判定通过 information_schema / migration version 记录「已应用」，而非重复加列。
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `apt_ledger_entries`
  ADD COLUMN `object_version` int unsigned NOT NULL DEFAULT '0'
  COMMENT '并发控制版本号(乐观锁，每次状态流转+1)' AFTER `audit_event_id`;
