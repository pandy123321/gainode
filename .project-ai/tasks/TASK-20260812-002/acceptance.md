# Acceptance: STAGE-01 Backend Domain Objects — 10 模块 Model/DAO/Service 骨架搭建

> STAGE-01 状态：**IN_PROGRESS**。前置「Machine Contract 第一批（MC1）」已通过独立审核（GAINODE-MC1-IR，记录 541/542/543）并经 Owner Signoff 置 FROZEN，本 STAGE 已授权启动。

## STAGE-01 进度记录

### 2026-08-14 — MC1 8 冻结核心实体的 Model/DAO/Service 骨架（第一批）

完成 MC1 冻结的 8 个核心实体的 Model/DAO/Service 骨架（24 个文件），全部映射自冻结 DDL
`0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql`，
状态枚举严格取自 05 §4 canonical（无自创状态）。

| 模块 | 实体（冻结 DDL） | Model/DAO/Service 目录 |
|---|---|---|
| Robot/Reward | `robots` / `robot_rewards` | `library/{model,dao,service}/robot/` |
| APT Ledger | `apt_accounts` / `apt_ledger_entries` | `library/{model,dao,service}/ledger/` |
| Prediction | `prediction_markets` / `prediction_orders` | `library/{model,dao,service}/prediction/` |
| OTC | `otc_orders` | `library/{model,dao,service}/otc/` |
| Power | `power_positions` | `library/{model,dao,service}/power/` |

关键工程约束已落实：
- 8 表主键 Snowflake / bigint unsigned → Model 设 `$incrementing=false` + `$keyType='string'`
- `apt_ledger_entries` append-only → `$timestamps=false` + `UPDATED_AT=null`（无 updated_time 列）
- 领域状态常量定义在 Model 类（非 Service 硬编码），枚举与 MC1 Freeze 严格一致
- 每个 Service 声明 `@authoritative_writer`；状态转移矩阵未实现，一律 FAIL_CLOSED（待 Machine Contract 第二批）
- 验证：`php -l` 24/24 通过；class-loading 冒烟 24/24 通过

下一批（待续）：
- 剩余 6 模块骨架（Auth/KYC、User/Eligibility、Affiliate/Agent、AI 运营、Approval/Parameter、Support/Audit）
- 8 冻结实体的业务逻辑 / 状态转移矩阵（Machine Contract 第二批：Event Catalog + Ledger Mutation Contract）
- 非核心实体 DDL（OtcTrade、PowerTransaction、RobotUpgradeOrder 等）、`sys_route` 路由、`ErrorDict` 错误码

### 2026-08-15 — P1 修复：Ledger append-only 机械强制（回复 IR 记录 603/604/607）

独立审核（记录 603）指出 `apt_ledger_entries` 的 append-only 此前仅为注释约定；记录 604 指出 Eloquent
Builder 可绕过 Model/DAO 覆写直接 UPDATE/DELETE；记录 607 进一步指出 Eloquent Builder 的 `__call()` 会把
未定义方法转发到底层 Query Builder，从而 `updateOrInsert/truncate/incrementEach/decrementEach` 仍可从
`Model::query()` 入口绕过。三轮修复（不扩大 STAGE-01 范围、不改 MC1 Frozen DDL、不改共用基类、不改其他业务 Model）：
- `AptLedgerEntryModel`：`save()` 在已落盘实例（`$this->exists`）直接抛 `RunException`；`delete()` 抛异常。
- `AptLedgerEntryAppendOnlyBuilder`（新增，注入自 `AptLedgerEntryModel::newEloquentBuilder()`）：显式阻断
  Eloquent Builder 的 `update/upsert/increment/decrement/touch/delete/forceDelete`，并显式阻断 Query Builder 的
  `updateOrInsert/truncate/incrementEach/decrementEach`；同时以 `DESTRUCTIVE_METHODS` deny set + `__call()` 兜底，
  任何落入 deny set 的方法在转发到底层 Query Builder 前一律 fail-closed。
- `AptLedgerEntryDao`：覆写 `delete/deleteAll/update/updateAll/updateOrCreate`，全部 fail-closed 抛 `RunException`。
- 结果：ORM 正常路径（Model 实例 + Eloquent Builder 含 `__call` 兜底 + DAO）仅允许 INSERT（追加）；reversal 反向分录与未冻结
  state 流转仍为 CONTRACT GAP（FAIL_CLOSED）。
- Protection boundary：显式取得底层 Query Builder（`toBase()`/`getQuery()`）与 `DB::table()` / PDO raw SQL 直连属
  DB 层边界，应用层不封堵；需数据库级硬约束时另走 Change Request（DB Trigger / DB Role）。
- 未修改 MC1 Frozen DDL。

### 2026-08-15 — P2 修复：deny set 安全声明准确性 + Ledger 回归测试（回复 IR 记录 609/610）

独立审核（记录 609/610，commit `f6871bb`）判定上一轮 `__call()` 绕过 P1 已 CLOSED（P0=0、P1=0），
但给出 2 个 P2：

1. **P2-1**：`DESTRUCTIVE_METHODS` 被描述为「全量」并声称可「防止未来 Illuminate 升级新增 mutation API
   静默绕过」——实际上静态 deny set 做不到，且 v10.38.1 的 `Query\Builder` 已存在未列入的 destructive API
   `updateFrom()`（PostgreSQL `UPDATE ... FROM ...`；当前 Gainode MySQL 不可执行，但已证明 deny set ≠ 全量）。
2. **P2-2**：高风险 Ledger immutable guard 连续三轮靠人工枚举才逐步发现绕过，仍缺少已提交的 regression test 证据。

本轮修复（不改 MC1 Frozen DDL、不改共用基类、不改其他业务 Model、不重写现有 Builder 方案）：
- `AptLedgerEntryAppendOnlyBuilder`：`DESTRUCTIVE_METHODS` 新增 `updatefrom`，并新增 `updateFrom(array $values)`
  显式覆写 fail-closed；更正 docblock，明确 deny set 为「当前锁定 v10.38.1 已审核」清单、**不再声称静态 deny set
  可自动识别未来升级新增 API**，改为依赖 `tests/ledger` 的 dependency mutation-surface contract 测试人工 disposition。
- `AptLedgerEntryModel` / `LedgerService`：同步更正 Protection boundary 与 deny set 表述（不再夸大）。
- 新增可执行回归测试 `0.5代码/gainode后端/gainode/tests/ledger/LedgerAppendOnlyMutationMatrixTest.php`（独立 CLI，
  无需 PHPUnit），覆盖：Builder injection（`AptLedgerEntryModel::query()` instanceof `AptLedgerEntryAppendOnlyBuilder`）、
  mutation matrix（INSERT/read 允许，Model 实例 / Eloquent Builder 显式覆写与 `__call` 兜底 / DAO 覆写全部 destructive
  mutation REJECT）、拒绝后数据完整性（`ROW_COUNT_DELTA=0`、经济字段不变）、以及 dependency mutation-surface contract
  （以锁定 illuminate 版本为输入，枚举 `Query\Builder` 公开 mutation method 与 disposition 表对照，出现未 disposition
  的新 write method 即 FAIL 要求人工复核）。
- `acceptance.md`：`append-only 机制完整实现` 仍保持 `[ ]`，仅记录 `ORM_NORMAL_PATH_APPEND_ONLY_GUARD = VERIFIED_PASS`。

### 2026-08-15 — P2 修复（第二轮）：regression test 的 mutation-surface contract 改为 fail-closed version gate（回复 IR 记录 614）

独立审核（记录 614，commit `0a43d42`）判定上轮 P2 修复方向正确，但 `tests/ledger/LedgerAppendOnlyMutationMatrixTest.php`
里的「dependency mutation-surface contract」仍有 3 个 P2：

1. **P2-1**：contract 声称 `VERIFIED_PASS`，但该 contract（prefix 启发式）在已提交的测试里从未真正执行/验证；
   且测试运行时在 version gate 处即 FAIL（`10.38.1` vs `10.38.1.0`），结论与代码/运行时证据不符。
2. **P2-2**：mutation-surface contract 用 `QUERY_WRITE_PREFIXES` 前缀启发式枚举 `Query\Builder` 的 write method，
   无法可靠覆盖未来新增 API / 继承方法变化；且文档引用了一个不存在的 `LedgerMutationContractTest.php`。
3. **P2-3**：`acceptance.md` 写 `ORM_NORMAL_PATH_APPEND_ONLY_GUARD = VERIFIED_PASS` 缺乏运行时证据背书
   （`VERIFIED_PASS` 需真实通过的测试结果，而非「测试文件存在」）。

本轮修复（不改 MC1 Frozen DDL、不改共用基类、不改其他业务 Model、不重写 Builder 方案）：
- `LedgerAppendOnlyMutationMatrixTest.php`：删除 prefix 启发式 `QUERY_WRITE_PREFIXES`，改为 fail-closed **version gate**：
  `LOCKED_ILLUMINATE_DATABASE_VERSION = '10.38.1'`；实际版本经 `Composer\InstalledVersions::getPrettyVersion()`
  （兜底 `getVersion()`，再兜底 `composer.lock`）读取，`normalizeVersion()` 归一化（去 `v/V` 前缀、去尾部多余 `.0`
  段，如 `10.38.1.0` → `10.38.1`）后比对；版本不一致即 FAIL，要求人工复核 Eloquent/Query Builder mutation surface 并
  disposition 后方可解除。不再假设升级自动安全。
- 修正 `snapshot()`：拒绝后数据完整性改为校验全部 16 个 immutable 字段（此前仅 4 个），每个 REJECT 后输出
  `ROW_COUNT_DELTA=0` + `IMMUTABLE_FIELD_DELTA=0`。
- 保留独立 CLI 而非改 PHPUnit test case 的正式原因（evidence-first）：经核查代码，本后端**当前未安装 PHPUnit**——
  `composer.json` 无 `require-dev phpunit`、`vendor/bin/` 无 `phpunit`、项目根无 `phpunit.xml`（仅 vendor 依赖包自带）。
  `context.md`/`rules/coding.md` 声明的「PHPUnit 10+，tests/Unit|Integration|Feature」属目标治理标准，尚未在本后端落地；
  落地 PHPUnit 需新增依赖 + `phpunit.xml` + CI Test Gate 接线，属独立基础设施任务，且「引入未经批准依赖」被本轮禁止事项
  明确排除。故本轮以 CLI 接入默认测试命令：`composer.json` 新增 `"test": "php tests/ledger/LedgerAppendOnlyMutationMatrixTest.php"`
  （`composer test` 或直接 `php tests/ledger/...` 均可执行），并在下方记录运行时证据；PHPUnit 落地列入后续 TODO（见 通用验收）。
- 运行时验证结果（真实执行，exit code=0）：
  - `php -l`：`LedgerAppendOnlyMutationMatrixTest.php` 与 `AptLedgerEntryAppendOnlyBuilder.php` 均无语法错误。
  - `php tests/ledger/LedgerAppendOnlyMutationMatrixTest.php` → `RESULT: pass=67 fail=0`，`ALL PASS`。
  - 覆盖：[1] 类加载 4 PASS；[2] Builder injection 3 PASS（`Model::query()` instanceof `AptLedgerEntryAppendOnlyBuilder`）；
    [3] deny set 与 disposition 契约（`DESTRUCTIVE_METHODS` ≡ `LEDGER_DENY`，含 `updatefrom`；每个 DENY 映射到真实框架方法；
    ALLOW_APPEND 未被 deny）；[4] version gate PASS（锁定 `10.38.1` / 实际 `10.38.1`）；[5] mutation matrix 全部 PASS
    （INSERT/READ 允许；Model/Builder/DAO 全部 destructive mutation REJECT，且每个 REJECT 后 `ROW_COUNT_DELTA=0`、
    `IMMUTABLE_FIELD_DELTA=0`、全 16 字段）。
- `acceptance.md`：`append-only 机制完整实现` 仍保持 `[ ]`；`ORM_NORMAL_PATH_APPEND_ONLY_GUARD = VERIFIED_PASS` 现以
  已提交测试的运行时结果 `pass=67 fail=0`（exit code=0）背书；reversal 反向分录与未冻结 state 流转仍为 CONTRACT GAP（FAIL_CLOSED）。

### 2026-08-15 — P2 修复（第三轮）：composer test 接线入 Commit + 原始 Runtime Evidence（回复 IR 记录 615）

独立审核（记录 615，commit `23d6d8b`）判定 dependency version gate 与 16-field snapshot 代码方向已 CLOSED（P0=0、P1=0），
但仍有 2 个 P2：

1. **P2-1**：`acceptance.md` 声称已修改 `composer.json` 接入 `composer test`，但 Commit `23d6d8b` 的 changed files
   与完整 Diff 均无 `composer.json`——根因是 `0.5代码/gainode后端/` 整体被 `.gitignore` 忽略，`composer.json` 未纳入提交，
   故 `COMPOSER_TEST_WIRING` 在上一 Commit 不可判定为已实现。
2. **P2-2**：`VERIFIED_PASS` 的运行证据仍只有 acceptance 自述（`pass=67 fail=0`），缺少原始 Runtime Evidence
   （控制台原始输出 / test log / exit code），属「记录/陈述」而非 L1 证据。

本轮修复（不改 MC1 Frozen DDL、不改 Ledger production guard、不重写 Builder、不装 PHPUnit）：
- `composer.json`：以 `git add -f` 纳入提交（`0.5代码/gainode后端/` 被 `.gitignore` 整体忽略，仅选择性 force-add 业务文件）。
  `scripts.test` = `php tests/ledger/LedgerAppendOnlyMutationMatrixTest.php`，未覆盖任何既有 script；`composer test` 实测通过。
- 新增原始运行证据文件 `tests/ledger/LedgerAppendOnlyMutationMatrixTest.result.txt`（UTF-8），逐字记录：
  `php -l`（2 文件均无语法错误）、`php tests/ledger/...`（`RESULT: pass=67 fail=0` / `ALL PASS`，覆盖 [1] 类加载 /
  [2] Builder injection / [3] deny set 契约 / [4] version gate PASS（锁定 `10.38.1`=实际 `10.38.1`）/ [5] mutation matrix
  全部 destructive mutation REJECT 且 `ROW_COUNT_DELTA=0`、`IMMUTABLE_FIELD_DELTA=0`）、`composer test`（同 67 PASS / 0 FAIL）、
  以及 exit code `0`。
- `acceptance.md`：新增本记录；`ORM_NORMAL_PATH_APPEND_ONLY_GUARD = VERIFIED_PASS` 现以已提交的原始运行证据文件背书。

## 验收方法

- 代码审查（Code Review）：逐模块检查分层约定、状态机完整性
- DDL 审查：检查 Migration 阶段一纪律（日期命名、顶部注释）
- 静态分析：`php -l` 语法检查通过

## Machine Contract 第一批 — 前置验收

- [x] DB DDL（8 核心实体）已创建（`0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql`，forward-only，无 DROP）— 独立审核通过（GAINODE-MC1-IR 记录 541/542/543）
- [x] Canonical State Freeze 正式 FROZEN（2026-08-13，Owner Signoff；8 实体状态枚举与 05 canonical 一致，见 `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`）
- [x] DDL 文件遵循日期命名约定（`YYYYMMDD_description.sql`）
- [x] DDL 文件顶部有变更原因和影响范围注释

## 逐模块验收清单

### Auth/KYC 补全
- [ ] MFA 模型/DAO/Service 已创建
- [ ] OTP 模型/DAO/Service 已创建
- [ ] KYC 多级状态机已定义（pending → submitted → review → approved | rejected | supplement_requested）
- [ ] `sql/` 对应 DDL 文件已创建

### User/Eligibility
- [ ] FeatureEntitlement 模型/DAO/Service 已创建
- [ ] Global P 计算已实现（服务端统一评估）
- [ ] 地区准入规则已实现
- [ ] `sql/` 对应 DDL 文件已创建

### Robot/Reward（全新模块）
- [x] RobotModel 已创建（56 级数据模型，`robots`）
- [x] RobotRewardModel 已创建（canonical: candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed，`robot_rewards`）
- [ ] RobotUpgradeOrderModel 已创建（非 MC1 冻结实体，待下一批）
- [ ] Power Cap 联动逻辑已实现（业务逻辑，待下一批）
- [ ] 状态机完整（所有合法转换路径定义）— 状态常量已定义，转移矩阵待 MC 第二批
- [x] `sql/` 对应 DDL 文件已创建（`robots` / `robot_rewards` 已由 MC1 batch1 DDL 覆盖）
- [ ] API 路由已插入 `sys_route` 表

### APT Ledger 改造
- [ ] append-only 机制完整实现（reversal 追加反向分录，不删不覆盖原文）— ORM 正常路径（Model 实例 `save()/delete()` + `AptLedgerEntryAppendOnlyBuilder` 显式阻断 Eloquent Builder `update/upsert/increment/decrement/touch/delete/forceDelete` 与 Query Builder `updateOrInsert/truncate/incrementEach/decrementEach/updateFrom`，并以 `DESTRUCTIVE_METHODS` deny set（当前锁定 v10.38.1 已审核清单）+ `__call()` 兜底 + `AptLedgerEntryDao` 覆写 `delete/deleteAll/update/updateAll/updateOrCreate`）已机械阻断删除/覆盖，并有 `tests/ledger/LedgerAppendOnlyMutationMatrixTest.php` 回归测试背书（`ORM_NORMAL_PATH_APPEND_ONLY_GUARD = VERIFIED_PASS`）；reversal 反向分录与未冻结 state 流转仍为 CONTRACT GAP（FAIL_CLOSED，待 Ledger Mutation Contract 冻结）；DB 层硬约束（Trigger/Role）待 Change Request
- [ ] 四账分离模型（05 AptAccount）: 1.APT数量账(balance_apt_i/c + frozen_apt_i/c) 2.参考估值账 3.功能货币收入账 4.Reward/预算账 — 仅第 1 项「数量账」骨架已建（`AptAccountModel`）
- [ ] 现有 wallet 表的迁移计划已制定（不直接破坏现有数据）
- [x] `sql/` 对应 DDL 文件已创建（`apt_accounts` / `apt_ledger_entries` 已由 MC1 batch1 DDL 覆盖）

### Prediction（全新模块）
- [ ] Market/Fixture 模型已创建 — `PredictionMarketModel`（`prediction_markets`）已建；`Fixture` 非 MC1 冻结实体，待下一批
- [x] PredictionOrder 模型已创建（canonical: submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected，`prediction_orders`）
- [ ] Settlement 引擎已实现（赛果确认 → 结算）（业务逻辑，待下一批）
- [ ] Refund/Correction 机制已实现（业务逻辑，待下一批）
- [x] `sql/` 对应 DDL 文件已创建（`prediction_markets` / `prediction_orders` 已由 MC1 batch1 DDL 覆盖）
- [ ] API 路由已插入 `sys_route` 表

### OTC/Power（全新模块）
- [x] OtcOrder 模型已创建（draft → review → matching → partial → completed | cancelled | expired | rejected | disputed，`otc_orders`）
- [ ] OtcTrade 模型已创建（撮合记录，非 MC1 冻结实体，待下一批）
- [ ] PowerAccount 模型已创建（append-only）— MC1 冻结的为 scalar `power_positions`，已建 `PowerPositionModel`；append-only Power 账本（PowerTransaction）待下一批
- [ ] PowerTransaction 模型已创建（consume/recover/convert，非 MC1 冻结实体，待下一批）
- [x] `sql/` 对应 DDL 文件已创建（`otc_orders` / `power_positions` 已由 MC1 batch1 DDL 覆盖）
- [ ] API 路由已插入 `sys_route` 表

### Affiliate/Agent（全新模块）
- [ ] Agent 表结构骨架已创建（05: NOT DEFINED — 枚举列 VARCHAR 暂存，等待 Contract Freeze）
- [ ] Referral 表结构骨架已创建（05: NOT DEFINED）
- [ ] AgentEarning 表结构骨架已创建（05: NOT DEFINED）
- [ ] AgentPortal 7 页面后端 API 已定义
- [ ] `sql/` 对应 DDL 文件已创建
- [ ] API 路由已插入 `sys_route` 表

### AI 运营（全新模块）
- [ ] AISignal 模型已创建（generated → validated → published | rejected）
- [ ] AIRecommendation 模型已创建（draft → reviewed → published | dismissed）
- [ ] SimulationRun 模型已创建（queued → running → completed | failed | cancelled）
- [ ] AI 运营驾驶舱/市场分析/竞猜助手/客服助手 API 已定义
- [ ] `sql/` 对应 DDL 文件已创建
- [ ] API 路由已插入 `sys_route` 表

### Approval/Parameter
- [ ] ApprovalWorkflow 模型/DAO/Service 已创建
- [ ] Actor-level SoD 已强制（`candidate.created_by_actor_id != approval.approved_by_actor_id`）
- [ ] ParameterRelease 版本化生命周期已实现（canonical: draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived。05:854-865）
- [ ] Snapshot 机制已实现
- [ ] `sql/` 对应 DDL 文件已创建

### Support/Audit
- [ ] Ticket/TicketMessage/TicketAttachment 模型已创建
- [ ] 审计追踪增强（request_id/object_id/approval_id 多维度查询）
- [ ] Outbox Pattern 已实现（通知解耦、去重、重试）
- [ ] `sql/` 对应 DDL 文件已创建

### arbitrage 改造
- [ ] `confirmed_profit → reference_profit → mapped_apt_budget` 数据流已打通
- [ ] C 端 API 中无任何 arbitrage 信号暴露
- [ ] `ArbitrageTask` 进程保留，仅作为后台输入源
- [ ] 10 张 arb 表保留

## 通用验收

- [ ] 所有 Model 继承 `support\extend\Model`
- [ ] 所有 Service 继承 `support\extend\Service`
- [ ] 所有 DAO 继承 `support\extend\Dao`
- [ ] 业务状态常量定义在 Model 类中（非 Service 硬编码）
- [ ] 所有写操作含 idempotency_key
- [ ] `php -l` 语法检查全通过
- [ ] 后端测试框架落地 PHPUnit 10+（`tests/Unit|Integration|Feature`）—— 当前 Ledger append-only 回归测试以独立 CLI 接入 `composer test`（`tests/ledger/LedgerAppendOnlyMutationMatrixTest.php`，runtime `pass=67 fail=0`）；PHPUnit 依赖、`phpunit.xml` 与 CI Test Gate 落地待独立基础设施任务（本轮禁止「引入未经批准依赖」）
- [ ] 无跨层调用（Controller → DAO/Model 直接操作）
- [ ] DDL 文件遵循日期命名约定（`YYYYMMDD_description.sql`）
- [ ] DDL 文件顶部有变更原因和影响范围注释
- [ ] 错误码统一在 `library/dict/ErrorDict.php` 中定义
