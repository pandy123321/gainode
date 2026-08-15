# 07 · Gainode 开发执行计划、Agent 派发规则与功能验收

> 版本：V3.1 · Single Developer Serial Packages + Independent Quality Snapshot Review
> 文档状态：`FROZEN_FOR_EXECUTION`
> 冻结 ID：`GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.1-20260816`
> 冻结日期：2026-08-16（Asia/Shanghai）
> 生效项目：Gainode 体育预测、竞猜与内部套利经济引擎
> 唯一工作区：`E:\github\sports`
> 后端基线：PHP 8.2 + Webman 2.1 + Workerman；不迁移 Go
> 目标：让执行 Agent 不需要自行设计项目路线，只需按本文件规定的工作包、文件范围、实现顺序、验证命令和停止条件执行

---

## 0. 项目身份锁与绝对禁止项

### 0.1 冻结执行基线

本文件 V3.1 是 Gainode 当前唯一、最新且已冻结的开发步骤基线。Development Agent、Quality Agent 和后续复审 Agent 必须按本文件定义的 Formal Stage、Package ID、包顺序、范围、停止条件、提审节奏和 Gate 条件执行。

```text
LATEST_EXECUTION_PLAN = Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md
LATEST_EXECUTION_PLAN_VERSION = V3.1
EXECUTION_PLAN_STATUS = FROZEN_FOR_EXECUTION
EXECUTION_PLAN_FREEZE_ID = GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.1-20260816
DEVELOPMENT_AGENT_MUST_FOLLOW_FROZEN_PLAN = YES
QUALITY_AGENT_MUST_REVIEW_AGAINST_FROZEN_PLAN = YES
OLDER_EXECUTION_PLAN_STATUS = SUPERSEDED_DO_NOT_EXECUTE
PLAN_CHANGE_CONTROL = OWNER_APPROVAL_REQUIRED
```

冻结含义：

- 不得自行新增、删除、合并、拆分、跳过或重排本文件定义的工作包。
- 不得自行改变 Package 的输入、允许路径、锁定路径、非目标、验收条件或 Stage Gate。
- Quality Agent 必须逐 Package 锁定快照和审核；每个 Formal Stage 必须单独提交 Stage Gate 审核。
- Development Agent 在快照锁定且下一包路径不重叠、不消费未冻结合同时仍可继续，不得因 Quality 排队而无条件等待。
- 修正文案、补充证据或关闭已确认 Finding，可以按当前 Package 的最小范围执行；改变业务、经济、状态、API、DDL、权限、依赖、正式参数或执行路线时，必须先生成 Change Request 并获得 Owner 明确批准。
- 任何批准后的计划修改必须升级版本、重新生成 Freeze ID、更新冻结凭证并同步 `.project-ai/bootstrap.md`、`.project-ai/context.md` 和 `.project-ai/manifest.yaml`；未完成这些步骤的新草案不得用于开发或审核。

每次执行前必须先验证：

```text
EXPECTED_WORKSPACE = E:\github\sports
EXPECTED_GIT_TOPLEVEL = E:/github/sports
EXPECTED_PROJECT = Gainode
EXPECTED_PRODUCT = AI 体育分析 + Football Pre-match 1X2 竞猜 + APT/Robot/Power/OTC
EXPECTED_BACKEND = PHP 8.2 + Webman 2.1 + Workerman
ARBITRAGE_DISPOSITION = 仅保留为内部 AI 经济引擎，不向 C 端暴露 BetBurger 信号、利润或仓位
```

任一不匹配时输出 `WORKSPACE_IDENTITY_MISMATCH` 并停止，禁止切换到或修改任何其他项目。

永久禁止：

- 不修改 `E:\github\AItradeos\一键交易转账\bnbchange` 或其他仓库。
- 不把后端改成 Go、Java、Node.js 或其他语言。
- 不把 Gainode 改写成链上项目；V2 的 APT 为中心化数量账，APT-C 仍为 Future/CLOSED。
- 不把旧 Figma、旧 Flutter、旧 Admin 代码或历史文档反推成当前业务规则。
- 不删除、覆盖或重做已经完成且已冻结的代码；只有绑定当前提交的有效 Finding 才能触发最小修复。
- 不执行生产 DDL、生产数据写入、生产部署、密钥访问、Signer、链上广播或真实价值开放。

---

## 1. 唯一权威来源与冲突处理

执行顺序固定如下：

1. `01_PRODUCT_FUNCTIONAL_BASELINE.md`：产品范围、P0/P1/Future。
2. `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`：APT、Robot、Reward、Power、OTC、Prediction 和四账分离。
3. `03_MOBILE_H5_HIFI_PROTOTYPE_SPEC_V2.4.md`：Mobile/H5 Page ID 和流程。
4. `04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md`：Admin Page ID、角色页面和流程。
5. `05_DATA_STATE_PERMISSION_API_CONTRACT.md`：对象、字段、状态、权限、API。
6. `06_PARAMETER_DICTIONARY.md`：参数结构和 TBC/null/closed 规则。
7. 本文件：开发顺序、工作包、验证、提审和验收。
8. `08_VISUAL_DESIGN_SYSTEM_V2.4.md`、`design-system/12_FIGMA_FRONTEND_DEVELOPMENT_BASELINE_V1.0.md` 和 Gainode2.0 Figma：视觉实现基线。
9. `.project-ai/**`：当前 Stage、冻结状态、任务、审核记录和代码治理。

冲突时不得自行选择。执行 Agent 必须输出：

```text
CONFLICT_STATUS = OPEN
CONFLICT_SOURCES =
HIGHER_AUTHORITY_SOURCE =
AFFECTED_OBJECTS =
AFFECTED_WORK_PACKAGE =
SAFE_WORK_THAT_CAN_CONTINUE =
OWNER_DECISION_REQUIRED =
```

涉及业务、经济、状态、API、数据库、权限、依赖、正式文案或 Stage 边界的冲突，必须生成 Change Request；不得把推测写进代码。

---

## 2. 截至 2026-08-15 的真实进度

### 2.1 已完成并冻结，禁止重做

| 成果 | 证据 | 状态 |
|---|---|---|
| STAGE-00 规划与文档基线 | `.project-ai/bootstrap.md`、Independent Review | COMPLETE |
| MC1：8 核心实体 DDL + canonical state | `sql/20260813_machine_contract_batch1_8_core_entities.sql`、MC1 Freeze | FROZEN |
| MC1 8 实体 Model/DAO/Service 骨架 | commit `5fb3d01`，24 个文件 | IMPLEMENTED |
| Ledger ORM append-only 防护 | Model + Builder + DAO | IMPLEMENTED |
| Ledger 回归入口 | `composer test` → 67 pass / 0 fail 的已记录证据 | PASS_AT_RECORDED_REVISION |
| MC2 Owner 裁决 | 22 项 + 2 项财务硬骨头 | COMPLETE |
| IR 686 修复 + MC2 最终复审 | 实现修订 `2795e38`；Round 7 = APPROVED（IR GAINODE-S01P01-MC2-IR-20260816-001） | FROZEN（0 P0 / 0 P1 / 0 blocking P2；1 非阻塞 P3） |
| S01-P02 2B-1 状态合同补齐 | commit `2707938`，task `TASK-20260816-001` | DESIGNED（Result/Settlement 矩阵待 gate；6 实体 enum 待 Owner 裁决） |

已完成的 8 个实体：

```text
apt_accounts
apt_ledger_entries
robots
robot_rewards
prediction_markets
prediction_orders
otc_orders
power_positions
```

执行 Agent不得重新创建这些表、Model、DAO 或 Service，不得为了“统一风格”重构它们。

### 2.2 当前未完成

- 2B-1 非核心实体的 DDL、Model/DAO/Service（Result/Settlement enum 已冻结可建；6 缺 enum 实体 SettlementBatch/RefundCase/CorrectionCase/OtcTrade/RobotUpgradeOrder/ConsentReceipt 待 Owner 裁决 enum，FAIL_CLOSED 不建表）。
- 2B-1 Result/Settlement 转移矩阵的 Independent Review（State Machine gate）。
- 2B-2 审批、参数、通知、会话、KYC、风险、工单合同与骨架。
- 未落表投影的服务端计算层。
- Affiliate/Agent、AI Operations 的正式机器合同与结构。
- OpenAPI 3.1、Environment Freeze、正式 API 路由和统一响应。
- 后端业务逻辑、状态机、幂等、并发、账本联动、Outbox。
- H5/Admin V2 正式开发目录与全量页面联调。
- Flutter App 工程。
- Sandbox E2E、迁移演练、发布就绪；生产仍为 NO-GO。

### 2.3 当前第一动作

```text
CURRENT_FORMAL_STAGE = STAGE-01_BACKEND_DOMAIN_OBJECTS
CURRENT_DEVELOPER_PACKAGE = S01-P02-2B1-STATE-CONTRACT
MC2_REVIEW_RESULT = APPROVED（Round 7，IR GAINODE-S01P01-MC2-IR-20260816-001）
S01_P02_REVIEW_ID = GAINODE-S01P02-2B1-IR-20260816-001
S01_P02_REVIEW_STATUS = SUBMITTED_PENDING（review package commit 4d8f1fe；ChatGPT bridge 待恢复）
S01_P02_STATUS = COMPLETED_DESIGNED（Result/Settlement 矩阵 DESIGNED + 6 实体 Owner Decision Matrix，commit c2d57ce）
```

不得把 commit message 中的“close IR 686 findings”写成“Independent Review 已通过”。

---

## 3. 开发 Agent 与质量 Agent 的执行节奏

### 3.1 角色分离

- 全项目只允许一个 Development Agent 串行修改产品代码。
- Quality Agent 默认只读，只写 `.project-ai/reviews/**` 审核报告，不修改产品代码。
- Development Agent 对 Finding 先复核再修复；Quality Agent 负责独立复审，执行者不能自关 Finding。
- Quality Agent 不得把自己的建议写进 Development Agent 的提交；任何获授权的质量修复也必须使用独立分支和 `origin:quality` 提交，默认流程不启用该例外。

### 3.2 不等待审核的条件

Development Agent 完成一个包后，先生成快照并等待 Quality 返回：

```text
SNAPSHOT_LOCKED = YES
SNAPSHOT_COMMIT = <sha>
SNAPSHOT_PATHS = <exact paths>
NEXT_PACKAGE_OVERLAP = NO
```

四项满足后，Development Agent 可立即开始下一个已定义、路径不重叠、且不消费未冻结合同的工作包，不需要等待审核结论。

Development Agent 必须等待的情况：

- 下一包依赖当前包的合同被置为 `FROZEN`。
- 下一包会修改 Quality 已锁定的同一文件。
- 当前 Finding 可能导致资金、状态、API 或数据库合同变化。
- Snapshot/commit/hash 不匹配。
- 下一包未在本文件定义。

注意：

```text
DEV_NEXT_PACKAGE_ALLOWED != CURRENT_PACKAGE_MERGE_APPROVED
QUALITY_APPROVED != PRODUCTION_APPROVED
SNAPSHOT_LOCKED != FINDINGS_CLOSED
```

### 3.3 提交和提审不得混淆

Development Agent 提交信息：

```text
origin:developer stage:<stage-id> package:<package-id> <summary>
```

Quality Agent 报告文件：

```text
.project-ai/reviews/<review-id>-QUALITY-REVIEW.md
```

每个快照只审核明确的 `BASE_COMMIT..SNAPSHOT_COMMIT`。Development Agent 后续提交不自动进入旧审核范围。

---

## 4. 所有工作包统一执行模板

Development Agent 对每个工作包严格执行以下 12 步，不得自行调整顺序：

1. 校验工作区、分支、HEAD、工作树和当前 Stage。
2. 读取本工作包列出的 `INPUTS`，不读取历史文档补业务。
3. 输出 `REUSE_MATRIX`：`KEEP / EXTEND / NEW / FORBIDDEN_TO_TOUCH`。
4. 输出准确的 `ALLOWED_PATHS`、`LOCKED_PATHS`、`NON_GOALS`。
5. 先写或验证机器合同，再写代码；合同未冻结时实现必须 fail-closed。
6. 按本工作包文件/对象顺序逐项实施；完成一项立即运行局部验证。
7. 完成包级静态检查、测试和 `git diff --check`。
8. 自审：业务规则、状态、幂等、并发、权限、账本、通知、安全、历史兼容。
9. 生成单一提交；禁止混入无关格式化、依赖升级或历史重构。
10. 生成快照、变更清单、测试证据、未执行项和风险清单。
11. 锁定 Snapshot 后交 Quality；报告必须附可直接使用的审核提示词和审核范围。
12. 若下一包满足 §3.2，继续下一包；否则只停止受依赖部分，不扩大范围。

每包交接文件必须包含：

```text
PROJECT = Gainode
WORKSPACE = E:\github\sports
FORMAL_STAGE =
PACKAGE_ID =
BASE_COMMIT =
SNAPSHOT_COMMIT =
PRODUCT_CODE_CHANGED =
MODIFIED_FILES =
COMPLETED_ITEMS =
NOT_IMPLEMENTED =
VALIDATIONS_RUN =
VALIDATIONS_NOT_RUN =
KNOWN_LIMITATIONS =
SNAPSHOT_LOCKED =
NEXT_PACKAGE =
NEXT_PACKAGE_OVERLAP =
DEV_NEXT_PACKAGE_ALLOWED =
CURRENT_PACKAGE_MERGE_APPROVED = NO
PRODUCTION_APPROVED = NO
```

---

## 5. 正式 Stage 总览

| Formal Stage | 目标 | 结束条件 |
|---|---|---|
| STAGE-00 | 产品、原型、架构和 MC1 基线 | 已完成 |
| STAGE-01 | 机器合同、DDL、全量领域对象/投影骨架 | 全部包通过审核，未冻结合同引用为 0 |
| STAGE-02 | OpenAPI、环境合同和后端 P0 业务闭环 | 后端 P0 API、状态、账本、权限、Outbox 测试通过 |
| STAGE-03 | H5 + Admin 增量升级与逐流程联调 | P0 页面、7 语言、三尺寸、API 联调通过 |
| STAGE-04 | Flutter App | P0 Page ID、状态、视觉和 API 联调通过 |
| STAGE-05 | Sandbox E2E 与迁移演练 | 15 个极端场景、账本守恒、回滚演练通过 |
| STAGE-06 | 发布就绪审查 | 安全、性能、观测、运维材料就绪；仍不自动部署生产 |

---

## 6. STAGE-01 · 机器合同与后端领域对象

### S01-P01 · MC2 修复快照重提与冻结

**输入**

- MC2 实现修订 `2795e38`（即使规划文档随后产生新提交，复审仍绑定此实现修订）
- `.project-ai/tasks/TASK-20260815-001/**`
- `sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`
- 两个 MC2 dated SQL 和 `CHANGE_REQUEST_CR-20260815-001.md`

**只允许**

- 更新提审状态、证据包和审核后确认正确的最小修复。
- 不实现业务状态机，不解除任何 Service 的 FAIL_CLOSED。

**固定步骤**

1. 验证 `2795e38` Git 对象存在，并核对该提交的 5 个变更文件；不得因当前分支已有规划文档修改而 reset。
2. 从 `2795e38` 的 Git tree 或独立只读 worktree 生成单一提审包，禁止把后续规划提交或工作树脏文件混入 MC2 实现快照。
3. 检查 IR 686 四项在 design、Freeze、acceptance 三处一致。
4. 运行 Markdown/SQL 交叉引用检查、`git diff --check` 和 Secret Scan。
5. 生成未截断提审包，绑定 `IMPLEMENTATION_COMMIT=2795e38`、manifest、SHA-256 和 review range。
6. Quality 复审后逐条 adjudicate；有效 Finding 在从 `2795e38` 建立的独立修复分支上做最小修复并新包重提。
7. 只有 Final Review = APPROVED 后，才用独立文档状态提交把 MC2 更新为 FROZEN；不得改写原审核提交。

**停止条件**

- 复审要求改变 Owner 裁决、经济逻辑或 MC1 DDL：生成 Change Request。
- 工具继续截断：不得宣称 Review Complete。

### S01-P02 · 2B-1 状态合同补齐

**新建 task，不修改 S01-P01 锁定文件。**

固定对象：

```text
Result
Settlement
SettlementBatch
RefundCase
CorrectionCase
OtcTrade
RobotUpgradeOrder
ConsentReceipt
AuditEvent
```

`AuditEvent` 的字段与 DDL 已在 MC2 候选中存在，本包只复核并引用，禁止重新设计或新建第二份 `audit_events` DDL。

固定步骤：

1. 从 05 §3 复制最低字段；Result/Settlement 状态只复制 05 §4。
2. 对 SettlementBatch、RefundCase、CorrectionCase、OtcTrade、RobotUpgradeOrder、ConsentReceipt 缺失的 canonical enum 生成逐项 Owner Decision 表。
3. 每项表必须列：初态、合法转移、终态、触发者、Authoritative Writer、失败态、重试、幂等、审计、账本副作用。
4. 未经 Owner 确认的 enum 标 `CONTRACT_GAP / FAIL_CLOSED`；禁止先建 ENUM 表。
5. Owner 确认后只修改 05 对应对象/状态章节，并创建 2B-1 Freeze Candidate。
6. 独立审核通过后置 FROZEN。

### S01-P03 · 2B-1 DDL 与 Model/DAO/Service 骨架

**前置：S01-P02 对应对象合同已 FROZEN。**

目标文件按对象一一对应：

```text
sql/YYYYMMDD_machine_contract_batch2b1_*.sql  # 仅缺失对象；排除已存在 audit_events 与 ledger object_version DDL
library/model/prediction/{Result,Settlement,SettlementBatch,RefundCase,CorrectionCase}Model.php
library/dao/prediction/{Result,Settlement,SettlementBatch,RefundCase,CorrectionCase}Dao.php
library/service/prediction/{Result,Settlement,SettlementBatch,RefundCase,CorrectionCase}Service.php
library/model/otc/OtcTradeModel.php
library/dao/otc/OtcTradeDao.php
library/service/otc/OtcTradeService.php
library/model/robot/RobotUpgradeOrderModel.php
library/dao/robot/RobotUpgradeOrderDao.php
library/service/robot/RobotUpgradeOrderService.php
library/model/policy/ConsentReceiptModel.php
library/dao/policy/ConsentReceiptDao.php
library/service/policy/ConsentReceiptService.php
library/model/audit/AuditEventModel.php
library/dao/audit/AuditEventDao.php
library/service/audit/AuditEventService.php
```

实现规则：

- DDL forward-only，不改 MC1 历史文件；Snowflake bigint unsigned，decimal 用 `decimal(...)`，禁止 float。
- `audit_events` 复用 `20260815_machine_contract_batch2_audit_events.sql`；不得重复 CREATE TABLE，只有正式 Change Request 才能新增后续 ALTER 文件。
- 每个 Model 映射冻结表名/主键/时间列/enum；不得加入未冻结字段。
- DAO 只提供查询；Service 是唯一写入者并标 `@authoritative_writer`。
- AuditEvent 使用已验证的 append-only Builder/DAO 防护模式，但复制前必须把表名和测试矩阵改为 audit_events；不得直接复用错误的 ledger 字段。
- 本阶段仅骨架与 fail-closed guard，不实现结算/退款/撮合业务。

验证：

```text
php -l <all changed php files>
composer test
DDL parse/review
enum(DDL) == enum(Model) == enum(Freeze)
git diff --check
```

### S01-P04 · 2B-2 合同补齐

固定范围：

```text
ApprovalRequest
ParameterRelease
ParameterSnapshot
Notice
NotificationDelivery
AuthSession
MfaEnrollment
KycCase
RiskCase
Ticket
TicketMessage
TicketAttachment
SettlementMethod
```

固定步骤：

1. 复用 05 已有 Approval、ParameterRelease、AuthSession、KYC、Ticket 状态。
2. 只为 NotificationDelivery、MfaEnrollment、RiskCase 等缺失状态生成 Owner Decision 表；不自行发明。
3. 明确 `PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR`。
4. 明确 `RISK_ANALYST != RISK_APPROVER`，申请人不得审批本人申请。
5. 明确 Notice 与业务事务解耦，NotificationDelivery 失败不回滚业务。
6. 明确 Parameter approved 不等于 active，历史对象使用 snapshot。
7. 独立审核通过后置 2B-2 FROZEN。

### S01-P05 · 2B-2 DDL 与骨架

目标目录：

```text
library/{model,dao,service}/approval/
library/{model,dao,service}/parameter/
library/{model,dao,service}/notice/
library/{model,dao,service}/auth/
library/{model,dao,service}/kyc/
library/{model,dao,service}/risk/
library/{model,dao,service}/support/
library/{model,dao,service}/settlement/
```

逐项顺序：

1. ApprovalRequest。
2. ParameterRelease、ParameterSnapshot。
3. AuthSession、MfaEnrollment、KycCase。
4. RiskCase。
5. Ticket、TicketMessage、TicketAttachment。
6. Notice、NotificationDelivery。
7. SettlementMethod。

每项都必须完成 DDL → Model → DAO → Service → syntax/class-load test，再进入下一项。禁止一次生成全部文件后统一修错。

### S01-P06 · 非持久投影服务

禁止建表的对象：

```text
FeatureEntitlement
OtcEligibility
OtcCapacity
PowerImpactPreview
SecurityProfile
SessionDevice
LoginAudit
```

只允许创建 DTO/Response/Service：

- DTO 字段严格复制 05。
- 资格/allowed_actions 由服务端解析，默认 deny。
- TBC 参数为 null/closed，不使用旧值或 mock fallback。
- 所有投影返回 data_status、as_of、updated_at、next_refresh_at、snapshot_id、source_status。

### S01-P07 · Affiliate/Agent P1 合同与骨架

05 未定义前不得建表。先完成对象合同、字段、状态、权限、参数、API 和失败关闭规则的 Owner Signoff/IR。

最小对象仅限：

```text
Agent
Referral
AgentEarning
```

P0 期间默认关闭正式奖励，禁止使用用户本金、退款或 Prediction 结算资金支付增长奖励。

### S01-P08 · AI Operations P1 与内部套利引擎边界

先冻结内部对象合同，再建骨架：

```text
AISignal
AIRecommendation
SimulationRun
```

必须保留：

- BetBurger/API-Football 仅作为内部数据输入。
- C 端不得出现 arbitrage signal、profit、position。
- 对外允许的是 AI 数据解释、Robot、Prediction；Reward Budget 受参数/预算/审计约束。

### S01-P09 · STAGE-01 全量收口

检查：

- 01/02/05/06 每个持久对象都有明确 DDL 或明确 `NOT_PERSISTED`。
- 每张新表有且只有一个 Authoritative Writer。
- enum 的 Freeze/DDL/Model 三方一致。
- 所有未冻结业务转移仍 fail-closed。
- 旧 8 实体没有被重做。
- `php -l`、class-load、`composer test`、DDL 检查通过。
- 全 Stage 独立审核通过后进入 STAGE-02。

---

## 7. STAGE-02 · OpenAPI、环境合同与后端 P0 业务闭环

### S02-P01 · OpenAPI 3.1、Environment 与通用内核

先产出：

```text
openapi/gainode-v2.yaml
openapi/schemas/*.yaml
openapi/paths/*.yaml
.env.example
tests/Contract/
tests/Integration/
tests/Feature/
```

顺序：

1. 冻结统一响应、错误码、六个请求头、cursor pagination、decimal string。
2. 冻结 Auth、Robot、Prediction、APT/OTC、Policy/Parameter/Admin 路径。
3. 校验 YAML、local refs、operationId 唯一、write idempotency、If-Match/object_version。
4. 配置只写变量名与安全默认；不写 Secret 或正式生产参数。
5. 测试依赖只使用 manifest 已批准项；未批准的新包先走 Dependency Decision。

### S02-P02 · Auth / KYC / User / Eligibility

实现顺序固定：

1. 注册、登录、OTP、找回、密码重置。
2. MFA enrollment/verify。
3. Session refresh/logout/list/revoke。
4. KYC submit/needs_info/approve/reject/review。
5. FeatureEntitlement + allowed_actions + policy fail-closed。
6. LoginAudit、安全原因映射、频控。

每个写操作必须有 idempotency；登录失败不得泄露账号是否存在；KYC Reviewer 不得接触资产。

### S02-P03 · Ledger / AptAccount / Power 基础

按事务顺序实现：

1. 锁定 `apt_accounts.object_version`。
2. 读取账户与 aggregate hold。
3. 校验 idempotency、余额、冻结和合同 guard。
4. 追加 LedgerEntry；禁止更新经济字段。
5. CAS 更新 AptAccount 投影。
6. 追加 AuditEvent。
7. 写 Outbox；通知失败不回滚。
8. 提交事务后返回统一响应。

必须测试双击、超时后成功、并发、reversal、dispute、shortfall、负余额禁止和重复 Outbox。

### S02-P04 · Robot / Reward / Upgrade

实现顺序：

1. 56 级规则读取与 snapshot。
2. Robot start/stop + PowerImpactPreview。
3. Upgrade quote → confirm → processing/result。
4. Reward candidate → held/pending_claim → claiming → claimed。
5. 过期退预算、review、reversal。

禁止前端或 Service 写死正式 capacity、coefficient、Power Cap、升级成本；无 Active Release 时真实写操作 closed。

### S02-P05 · Prediction P0

严格按对象顺序：

1. Market create/open/closing/lock。
2. Disclosure + ConsentReceipt。
3. PredictionOrder submit/same-selection addition。
4. Result provisional/official/disputed/corrected。
5. SettlementBatch/Settlement。
6. RefundCase。
7. CorrectionCase（旧 posting reversal + 新 posting）。

只允许 Football Pre-match 1X2，90 分钟+伤停补时；不可取消、减少或换方向。Result official 不等于 Settlement paid。

### S02-P06 · OTC / Power

顺序：

1. OtcEligibility/OtcCapacity。
2. Quote + PowerImpactPreview。
3. Create order。
4. Review/matching。
5. Partial fill/OtcTrade。
6. Completed。
7. Cancel/Expire remaining。
8. Dispute resolution。

Sell 提交冻结 Power；成交部分消耗；未成交继续冻结；取消/到期仅释放 remaining。Buy 不套用 Sell Power 规则。

### S02-P07 · Approval / Parameter / Risk / Support / Notice / Audit

顺序：

1. Approval 的发起、分派、决定、执行。
2. Parameter Candidate → Approval → Release → Schedule/Active/Pause/Rollback。
3. RiskCase 与 restricted allowed_actions。
4. Ticket/Message/Attachment。
5. Notice/Delivery/Outbox retry/dedupe。
6. Audit 多维查询与敏感字段最小化。

高风险操作必须检查 actor-level SoD；超级管理员不能绕账本、审批或审计。

### S02-P08 · 内部 AI 经济引擎

只实现：

```text
confirmed_profit
reference_profit
mapped_apt_budget
daily_ai_budget
```

所有 smoothing、mapping multiplier、budget cap 来自 Active Parameter。confirmed_profit <= 0 时 reference_profit = 0。不得向 C 端返回内部套利来源、利润明细或仓位。

### S02-P09 · 后端 Gate

必须通过：

- OpenAPI parse/ref/operationId/required/auth/idempotency。
- 所有 PHP syntax、unit/integration/feature tests。
- 15 个极端场景的后端部分。
- RBAC/ABAC、SoD、Secret、依赖和 SQL 审核。
- Ledger/Power 守恒、reversal、Outbox replay。

---

## 8. STAGE-03 · H5 与 Admin 增量升级

### S03-P00 · 前端开发目录冻结

`_existing_prod/gainode_h5` 与 `_existing_prod/gainode_admin` 当前是生产基线镜像，不得直接当成新 V2 交付目录。开始前必须在 manifest 冻结：

```text
H5_TARGET_ROOT =
ADMIN_TARGET_ROOT =
SOURCE_BASELINE_COMMIT =
MIGRATION_MODE = INCREMENTAL
```

字段缺失时只生成路径决策请求，不复制、不移动、不重写镜像。

### S03-P01 · H5 基础设施

按顺序：

1. 保留 Vue 3 + TypeScript，接入 Pinia persistedstate、vue-i18n、Vant 4。
2. 建立 `src/api` 统一 Axios、六请求头、错误恢复和 RESULT_UNKNOWN 查询。
3. 建立 `src/tokens` 映射 Gainode2.0 Figma tokens。
4. 建立 7 语言资源；用户可见文案禁止硬编码。
5. Secret 移出代码；S3 改后端 presigned URL。
6. 配置 Vitest/Playwright 和三尺寸视觉回归。

### S03-P02 · H5 页面实现顺序

按流程而不是随机页面：

1. Auth/KYC/Notice：`M-AUTH-001..005`、`M-KYC-001..003`、`M-NOTICE-001`。
2. Home：`M-HOME-001`。
3. Robot：`M-ROBOT-001..007`。
4. Prediction：`M-PREDICT-001..006`。
5. Assets/Power/OTC：`M-ASSET-001..003`、`M-POWER-001`、`M-OTC-001..006`。
6. Me/Security/Support/Settings：`M-ME-001`、`M-SEC-001..002`、`M-SUPPORT-001..003`、`M-SETTINGS-001`。
7. P1 页面在 P0 Gate 后实施；Migration 保持 CLOSED。

每页必须记录 Figma Node ID、组件/token、Default/Loading/Empty/Error/Restricted、写操作状态、375/390/430 截图。

### S03-P03 · Admin 基础设施与页面

保留 Schema 驱动、Pinia、多 Tab、面包屑和 RBAC；组件库迁移到 Element Plus 不得改变业务合同。

按 8 个一级导航和 04 Page ID 顺序实施。资产调整、结果、结算、冲正、参数发布、紧急操作必须展示影响预览、理由、审批、执行结果和 Audit ID；Quality 按 Page ID 逐批提审。

### S03-P04 · 前端 Gate

- H5/Admin build、typecheck、unit test、E2E 通过。
- 7 语言 key 集一致，敏感文案保持人工签核状态。
- 所有 P0 页面状态完整；前端不推导资格、金额、Power 或 Reward。
- Figma 三尺寸无未批准偏差。

---

## 9. STAGE-04 · Flutter App

### S04-P00 · Flutter 工程决策冻结

当前无 Flutter 工程。开始前必须冻结：

```text
FLUTTER_TARGET_ROOT
APPLICATION_ID_IOS
APPLICATION_ID_ANDROID
MINIMUM_OS
STATE_MANAGEMENT = Riverpod or Bloc
ROUTING
SECURE_STORAGE
EXACT_FLUTTER_VERSION
```

未冻结时 Agent 只生成 Decision Request，不自选。

### S04-P01 · 工程基础设施

冻结后按以下顺序创建，不得同时铺页面：

1. 创建 app、dev/test/sandbox flavor；production flavor 不配置真实值。
2. 建立 design tokens、dark theme、字体、图标和正式 Logo 资产。
3. 建立 7 语言 ARB，key 与 `ui-copy-manifest.json` 对齐。
4. 建立 Dio client，统一注入六个请求头、认证 refresh、RESULT_UNKNOWN 原请求查询。
5. 建立 decimal value object；资产、Reward、Power、Settlement 禁止 double。
6. 建立 router、session guard、restricted guard、deep link 安全降级。
7. 建立 widget/unit/integration/golden test 目录和 CI 命令。

### S04-P02 · Auth / KYC / Notice

实现 `M-AUTH-001..005`、`M-KYC-001..003`、`M-NOTICE-001`。先 API/DTO/store，再页面，再 golden/integration test。覆盖 OTP resend、MFA challenge、session expired、needs_info、restricted、通知对象无权限安全正文。

### S04-P03 · Home / Robot

实现 `M-HOME-001`、`M-ROBOT-001..007`。Robot 的 level、capacity、Power Cap、Reward 和 allowed_actions 全部服务端下发；Upgrade 和 Start 必须先显示 quote/PowerImpactPreview。

### S04-P04 · Prediction

实现 `M-PREDICT-001..006`。依次联调 market list/detail → disclosure/consent → confirm → order → settlement → refund/correction。Result official 不得直接显示成 Settlement paid。

### S04-P05 · APT / Power / OTC

实现 `M-ASSET-001..003`、`M-POWER-001`、`M-OTC-001..006`。OTC partial、cancelled、expired、disputed 必须分别渲染；金额只显示服务端 decimal string。

### S04-P06 · Me / Security / Support / Settings

实现 `M-ME-001`、`M-SEC-001..002`、`M-SUPPORT-001..003`、`M-SETTINGS-001`。Session revoke 不得撤销当前请求错误对象；附件上传走后端 presigned URL。

### S04-P07 · Flutter Gate

金额使用 String + decimal 包，禁止 double；平台差异仅限 safe area、键盘、系统返回和设备能力。

必须通过：

- analyze、format check、unit/widget/integration/golden。
- iOS/Android Sandbox build。
- 所有 P0 Page ID 和状态覆盖。
- 与 H5 使用同一 API、状态语义、7 语言 key 和 Figma 基线。

---

## 10. STAGE-05 · Sandbox E2E 与迁移演练

### S05-P01 · Sandbox 环境与确定性 Fixture

1. 固定后端、H5、Admin、App 的 Sandbox revision。
2. 创建与 production config 分离的 fixture/seed；禁止真实个人数据和 Secret。
3. 建立可重置的测试账户、13 角色、KYC 状态、Robot 等级、Market、OTC、Parameter Release。
4. 建立队列、定时任务、数据源 stub 和故障注入开关。
5. 记录环境 manifest、镜像/依赖版本、初始化和清理命令。

### S05-P02 · 五条主流程 E2E

按顺序执行并保存 API、UI、ledger、audit 证据：

1. 新用户：注册 → OTP → MFA → KYC → entitlement。
2. Robot：start → upgrade quote/confirm → reward → claim。
3. Prediction：market → consent → order → result → settlement。
4. OTC：quote → sell → partial → complete/cancel/expire。
5. Support：异常 → notice → ticket → admin handle → audit。

### S05-P03 · 15 个故障与边界场景

固定场景：

1. 双击 Upgrade/Claim/Prediction/OTC。
2. 客户端超时但服务端成功。
3. Parameter Release 在确认期间变化。
4. Market 在确认期间 Locked。
5. quote 后余额变化。
6. KYC/地区资格提交瞬间受限。
7. OTC 部分成交后取消 remaining。
8. Result 主备源冲突。
9. Settlement 成功、通知失败。
10. Refund 某 batch 失败。
11. Correction 重复执行。
12. Audit/Outbox 重放。
13. Policy 超时。
14. 无 Active Release。
15. 受限用户查看历史、退款和工单。

每个场景必须记录：初态、注入点、请求 ID、Idempotency-Key、对象终态、Ledger/Power delta、Audit ID、通知结果、二次执行结果。

### S05-P04 · 迁移 dry-run 与回滚

固定步骤：

1. 只读盘点 V1 表、行数、主键、异常值和 PII 分类。
2. 冻结 V1→V2 字段映射、默认处理、拒绝条件和 reconciliation query。
3. 在全新 Sandbox 数据库执行 Big Bang dry-run。
4. 比对行数、金额、APT 数量、订单状态、审计关联和不可迁移清单。
5. 演练失败回滚到迁移前快照，再运行应用健康检查。
6. 不修改 `sql/database.sql` 历史；迁移脚本 forward-only。

### S05-P05 · Sandbox Gate

- 账本数量守恒、Power freeze/consume/release 守恒。
- H5/Admin/App 跨端状态一致。
- 断网重连、队列重试、进程重启、陈旧数据恢复通过。
- 15 场景 P0/P1 Finding = 0。

只允许 Sandbox 数据。不得触碰生产数据库。

---

## 11. STAGE-06 · 发布就绪，不等于生产部署

### S06-P01 · 安全与依赖

- Secret/PII 扫描、依赖漏洞和许可证清单。
- Auth、MFA、Session、RBAC/ABAC、SoD、rate limit、上传、日志脱敏。
- 13 角色正向/反向权限矩阵和紧急操作补审。

### S06-P02 · 性能与可观测性

- API 延迟/错误率、数据库索引/慢查询、Redis/队列堆积、定时任务漂移。
- Ledger/Settlement/Refund/Correction/Outbox 的业务指标和告警。
- 外部数据源超时、熔断、陈旧状态和 fail-closed。

### S06-P03 · 运维与恢复材料

- OpenAPI、Environment、Deployment、Migration、Rollback、Incident Runbook。
- 数据库备份恢复、队列重放、参数回滚、服务降级和对账。
- 正式地区、年龄、费用、Robot/Reward、OTC/Prediction 参数 Active Release 检查。

### S06-P04 · 最终 Release Readiness Gate

Quality 只对“是否具备发布准备条件”给结论，不执行部署。Owner 另行决定生产范围、时间、参数和风险接受。

输出只能是：

```text
DEVELOPMENT = GO
SANDBOX = GO
RELEASE_READINESS = PASS | FAIL
PRODUCTION_REAL_VALUE = NO-GO_UNTIL_SEPARATE_APPROVAL
```

---

## 12. P0 功能验收矩阵

| 模块 | 必须通过 |
|---|---|
| Auth | 注册、OTP、登录、MFA、找回、refresh/revoke、频控和安全失败状态 |
| KYC | not_started→pending→needs_info/approved/rejected；准入与 KYC 分开 |
| Home | 任一卡片失败不拖死整页，当前状态/原因/下一步明确 |
| Robot | 1–56、启动/停止、升级 quote/snapshot、Power preview、状态恢复 |
| Reward | 公式、系数 0、Claim 幂等、过期、review、reversal |
| APT | available/frozen/pending 分开；append-only；历史不覆盖 |
| Power | cap 服务端下发；freeze/consume/release/recover 可解释 |
| OTC | submitted≠completed；partial/cancel/expire/dispute 和 Power 联动 |
| Prediction | Football 1X2、Consent、锁定、Result/Settlement 分离、Refund/Correction |
| Parameter | Candidate/Approval/Release/Snapshot；TBC=null；Active immutable |
| Support/Notice | 工单闭环；通知失败不回滚业务；深链安全 |
| Admin | 8 导航、13 canonical roles、审批/账本/参数/审计闭环 |
| Audit | request/object/user/approval/case 全链路可追踪 |

---

## 13. 通用 Definition of Done

### 后端

- OpenAPI 与实现一致；所有写操作幂等。
- 并发写有 object_version/lock，经济写走 AptAccount 统一 CAS 锁域。
- Service 为唯一写入者；Controller 不直接写 DAO/Model。
- 账本 append-only，修正用 reversal；历史通过 snapshot 回算。
- Policy/Parameter/allowed_actions 服务端解析，TBC fail-closed。
- Outbox 重试不重复资金效果。
- RBAC + ABAC + MFA + SoD（适用时）有测试。

### H5/Admin/Flutter

- Page ID、Route、Figma Node 可追溯。
- Default/Loading/Empty/Error/Restricted 和写操作状态完整。
- 金额不用 float/double 做业务计算。
- 刷新/重连后查服务端终态。
- 7 语言 key 一致，无用户文案硬编码。
- H5 375/390/430 与 Figma 对照通过。

### Quality

- 审核绑定准确 commit/range/hash。
- 每条 Finding 有文件、行/函数、证据、可达场景、最小修复、验收和回归。
- Development Agent 已对 Finding adjudicate。
- P0=0、P1=0、blocking P2=0 后才允许当前包合并。

---

## 14. 执行 Agent 固定首轮提示词

把以下内容连同本文件交给 Development Agent：

```text
你是 Gainode 唯一 Development Agent。工作区只能是 E:\github\sports。

先完整读取：
1. Gainode_Development_Ready_V6.1_Latest/01-08 当前基线；
2. Gainode_Development_Ready_V6.1_Latest/design-system/12_FIGMA_FRONTEND_DEVELOPMENT_BASELINE_V1.0.md；
3. .project-ai/bootstrap.md、context.md、architecture.md、manifest.yaml、rules/coding.md、rules/review.md；
4. 当前 package 指定的 task/freeze/review；
5. 通过agent开发前规则/EXECUTOR_AGENT_PROTOCOL.md。

严格执行 07 §4 的 12 步。只执行 CURRENT_DEVELOPER_PACKAGE，不自行创造 Stage、业务规则、状态、API、DDL、依赖或正式参数。
当前包 = S01-P01-MC2-REVIEW-LOCK。
当前复审绑定 = 2795e38abd9bfff0383992f98ce01193e7fe1a5f。

已完成的 MC1 8 实体和 Ledger append-only 防护禁止重做。Quality 锁定快照后，如果下一包路径不重叠且不消费未冻结合同，继续下一个已定义包；不得把“继续开发”写成“上一包审核通过/允许合并”。

每包结束必须输出完整交接字段、审核提示词、精确文件范围、验证证据和 NEXT_PACKAGE_OVERLAP。
涉及规则性修改时只生成 Change Request/Decision Matrix，等待 Owner；不得猜测。
允许为当前 Package 创建一个范围纯净的本地 `origin:developer` 提交；禁止 push、merge、deploy，除非派发单另有明确授权。
```

---

## 15. Quality Agent 固定审核提示词

```text
你是 Gainode Independent Quality Agent，默认只读。工作区只能是 E:\github\sports。

唯一审核计划基线是本文件 V3.1，Freeze ID=GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.1-20260816，状态必须为 FROZEN_FOR_EXECUTION。先读取 DEVELOPMENT_EXECUTION_PLAN_FREEZE_V3.1.md，并按凭证规定的 `UTF8_LF_NO_BOM` 规范化方式核对本文件 SHA-256；不一致时输出 EXECUTION_PLAN_FREEZE_MISMATCH 并停止使用该快照，不得自行选择旧版计划。

必须按本文件定义的 Package 顺序逐包锁定 Snapshot 和审核，并在每个 Formal Stage 的全部 Package 完成后单独执行 Stage Gate。不得新增、删除、合并、拆分、跳过或重排 Package；计划变更必须先取得 Owner 明确批准并生成新版本和新 Freeze ID。

先验证 PROJECT/STAGE/PACKAGE/BASE_COMMIT/SNAPSHOT_COMMIT/REVIEW_RANGE/PACKAGE_SHA256/SNAPSHOT_PATHS。
只审核锁定快照，不把 Developer 后续提交混进本轮，不修改产品代码。

依据 01-08、当前 Freeze、.project-ai/rules/review.md 和 INDEPENDENT_REVIEW_AGENT_PROTOCOL.md 审核。
每条 Finding 必须给：严重度、精确文件、行/函数、当前行为、期望行为、证据、根因、触发条件、可达场景、影响、最小修复步骤、禁止扩展、验收、回归、Gate 影响。

区分：
- 当前包代码是否可合并；
- Development Agent 是否可继续路径不重叠的下一包；
- Formal Stage 是否关闭；
- Production 是否授权。

禁止把它们合并成一个结论。报告末尾必须输出：
SNAPSHOT_LOCKED
CODE_MERGE_RECOMMENDATION
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW
FORMAL_STAGE_GATE
PRODUCTION_APPROVAL = NO
```

---

## 16. 文档变更与后续维护

- 需求变化优先修改 01–08 对应文件，不新增“差不多一样”的说明书。
- 本文件是唯一开发路线和 Agent 派发基线；`.project-ai/bootstrap.md` 只保存当前状态和指针，不复制整份计划。
- 每完成一个工作包，只更新 §2 进度、当前包、对应验收状态和证据链接。
- 任何规则性修改必须有 Owner 决策记录；执行 Agent 不得把自己的推理当成 Owner Signoff。
- 文档修改完成后应同步项目上下文发布工具；若工具不可用，记录 `CONTEXT_PUBLISH = NOT_RUN_TOOL_UNAVAILABLE`，不得伪造发布成功。
