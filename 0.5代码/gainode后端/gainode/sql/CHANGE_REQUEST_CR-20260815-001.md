# Change Request — CR-20260815-001

> 项目：Gainode
> 状态：APPROVED（待 Independent Review 最终确认）
> 依据：Machine Contract 变更控制（Freeze 文档 §7）

---

## CHANGE_REQUEST_ID

`CR-20260815-001`

## REQUESTED_CHANGE

对已冻结的 MC1 核心实体 `apt_ledger_entries` **新增一列** `object_version`（乐观锁并发控制版本号），使该表满足 Machine Contract Batch 2 通用不变量「每个 transfer 必须附带 object_version 乐观锁校验（If-Match）」。

具体 DDL（forward-only，一次性加列，禁止内置 DROP COLUMN）：

```sql
ALTER TABLE `apt_ledger_entries`
  ADD COLUMN `object_version` int unsigned NOT NULL DEFAULT '0'
  COMMENT '并发控制版本号(乐观锁，每次状态流转+1)' AFTER `audit_event_id`;
```

## REASON

`apt_ledger_entries` 是 MC1 冻结的 8 个核心实体中**唯一缺少 `object_version` 列**的表（其余 7 表均已含 `object_version`）。

Machine Contract Batch 2 的 A.0 通用不变量 #2 要求「每个 transfer 必须附带 object_version 乐观锁校验」，而 Ledger 的 Ledger Mutation Field Contract（A.1.1）此前仅白名单 `state` + `audit_event_id` 两列受控可变，与「必须用 object_version 乐观锁」自相矛盾。

本变更源于 **Independent Review IR 638 P1-2（方案 A）**，用于消除该矛盾，使 Ledger 与其余 7 实体统一遵循乐观锁不变量。

## IMPACT

- **有效 Schema 变更**：`apt_ledger_entries` 从 MC1 schema 变为「MC1 + object_version」。
- 不影响任何已有数据（`NOT NULL DEFAULT '0'`，历史行版本号回填 0）。
- 不改动 MC1 已冻结历史 DDL（`20260813_machine_contract_batch1_8_core_entities.sql` 保持原样）。
- Ledger Mutation Field Contract 白名单由两列扩为三列：`state` + `audit_event_id` + `object_version`。

## AFFECTED_FILES

- `sql/20260815_machine_contract_batch2_ledger_object_version.sql`（新增，本 CR 的迁移载体）
- `.project-ai/tasks/TASK-20260815-001/design.md`（A.1.1 白名单 + 迁移说明）
- `sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`（§3.1 Ledger Mutation Field Contract + §8 验收）
- `.project-ai/manifest.yaml`（machine_contract_batch2_freeze 登记）

## AFFECTED_STAGES

- STAGE-01（后端领域对象）：Ledger Service 状态流转实现必须基于 `object_version` CAS 乐观锁。

## OWNER_DECISION

`APPROVED`（Owner 于 IR 638 P1-2 选择「方案 A：受控 metadata mutation + object_version 乐观锁」）。

## INDEPENDENT_REVIEW_REQUIRED

`YES`（State Machine Gate；随 MC2 Freeze Candidate 一并提交审核）。

## NEW_FREEZE_VERSION / TARGET

- `BASE_FREEZE = MC1`
- `NEW_FREEZE_TARGET = MC2`（Machine Contract Batch 2）

## MIGRATION

`20260815_machine_contract_batch2_ledger_object_version.sql`

## 不变约束

1. MC1 历史 SQL 文件**完全不变**（`MC1_HISTORICAL_FILE_DIFF = 0`）。
2. 迁移 forward-only，禁止内置 `DROP COLUMN`；若目标列已存在则 fail-fast。
3. `object_version` 每次成功状态流转 `+1`，乐观锁 `WHERE ledger_entry_id=? AND object_version=? AND state=?`，`affected_rows ≠ 1` 即 `OBJECT_VERSION_CONFLICT`（fail-closed，禁止重试覆盖）。
