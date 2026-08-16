# Developer Snapshot — S01-P01-MC2 (IR 686 修复复审)

> 只读审核快照。锁定的是 Git commit `2795e38` 的 tree，不含当前工作树后续的 V3 策划文档修改（`fd7968b`）。

```text
REVIEW_ID = GAINODE-S01P01-MC2-IR-20260816-001
PROJECT = Gainode
WORKSPACE = E:\github\sports
FORMAL_STAGE = STAGE-01
PACKAGE_ID = S01-P01-MC2-REVIEW-LOCK
REVIEW_ROUND = 7（IR 629/638/659/679/682/686 之后）

BASE_COMMIT = 7e6f828a9566b7382dae6aa7c918a63d0747b79a
SNAPSHOT_COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
REVIEW_RANGE = 7e6f828..2795e38
HEAD_AT_REVIEW = fd7968b50b3f39c47affe38969a16875ab504687
BRANCH = feature/gainode-v3-serial-development

PACKAGE_SHA256 = 7789e3933113e7c29e89d85e608885b99cf8704b667127540ef54ec0b88b25a2
SNAPSHOT_LOCKED = YES
SNAPSHOT_CREATED_AT = 2026-08-16T03:42+08:00
```

## SNAPSHOT_PATHS（2795e38 变更的 5 个文件）

| 文件 | git blob (SHA1) | SHA256 | bytes |
|---|---|---|---|
| `.project-ai/manifest.yaml` | `d096756f5f2463969c5fb158d9ba3e5ba38db08a` | `1ec816820785193617b5a2b3e70e21c565648fc5b612c72a24209fd91cd382ec` | 21119 |
| `.project-ai/tasks/TASK-20260815-001/acceptance.md` | `877bb4db5e226b5b6526fa0b80b3d8b9aadd3909` | `93a581f709a0ed05151890b2c9f3fd7408e079964e2fb01d7faf586bb6a3c5d0` | 19025 |
| `.project-ai/tasks/TASK-20260815-001/design.md` | `28262c424d0c1baf4fbd22b29629007116e7e476` | `a0f173fb409474224f3b70bd04ebd404566fb91bb00d7e2e633d8ba6a18803cf` | 69293 |
| `.project-ai/tasks/TASK-20260815-001/requirement.md` | `68bfd3d2d4be06e23921fd80b74ccec41851f7db` | `9c81d3c0b9bd07aeacefe4c0616c0d11b71341cbe7bc55de5642bb4ed7e50bfd` | 4616 |
| `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md` | `df4075107be74e04e960689e203936983f37a407` | `91ce07adc3d4342d353ee96afa6a38ebe14044bab775366ed45d541143c95f55` | 41273 |

## 快照锁定声明

```text
SNAPSHOT_LOCKED = YES
PACKAGE_ID = S01-P01-MC2-REVIEW-LOCK
SNAPSHOT_COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
NEXT_PACKAGE_OVERLAP = NO（下一包 S01-P02 = 2B-1 非核心实体状态合同，不触碰本包 5 文件）
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW = NO
```

## 依赖文件（存在于 2795e38，但非本 commit 变更，仅作引用完整性校验）

```text
0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_audit_events.sql   (blob febf1cbc)
0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_ledger_object_version.sql  (blob e0c7c085)
0.5代码/gainode后端/gainode/sql/CHANGE_REQUEST_CR-20260815-001.md  (blob d103edbd)
```

## 机器校验结果（QUALITY-01 独立执行）

```text
MC1 DDL 含 apt_accounts.object_version（第 32 列） = YES
MC1 DDL 含 balance_apt_i/c + frozen_apt_i/c = YES
MC1 DDL 含 aggregate_dispute_hold 列 = NO（推导投影，非存储列）
Freeze 文档残留活跃 PRE_L5 = NO（仅历史 provenance 记录）
ACCOUNT_LOCK_CONFLICT 作为公共错误码 = NO（INTERNAL_ONLY）
DIFF 未截断（189 行 / 完整读取） = YES
5 文件 SHA256 与 git tree 逐字节一致 = YES
```
