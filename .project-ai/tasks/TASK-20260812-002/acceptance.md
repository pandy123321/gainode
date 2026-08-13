# Acceptance: STAGE-01 Backend Domain Objects — 10 模块 Model/DAO/Service 骨架搭建

> STAGE-01 状态：**BLOCKED**（BLOCKED_BY = GAINODE-MC1-IR）。前置「Machine Contract 第一批（MC1）」仍为 CANDIDATE（待 Independent Review），未正式 FROZEN，故本 STAGE 尚未启动；MC1 通过并置 FROZEN 后方可开始逐模块开发。

## 验收方法

- 代码审查（Code Review）：逐模块检查分层约定、状态机完整性
- DDL 审查：检查 Migration 阶段一纪律（日期命名、顶部注释）
- 静态分析：`php -l` 语法检查通过

## Machine Contract 第一批 — 前置验收

- [x] DB DDL（8 核心实体）已创建（`0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql`，forward-only，无 DROP）— 独立审核进行中（GAINODE-MC1-IR）
- [ ] Canonical State Freeze 正式 FROZEN（当前为 CANDIDATE；8 实体状态枚举与 05 canonical 一致，见 `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`；待 Independent Review 通过后由治理流程置 FROZEN）
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
- [ ] RobotModel 已创建（56 级数据模型）
- [ ] RobotRewardModel 已创建（canonical: candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed）
- [ ] RobotUpgradeOrderModel 已创建
- [ ] Power Cap 联动逻辑已实现
- [ ] 状态机完整（所有合法转换路径定义）
- [ ] `sql/` 对应 DDL 文件已创建
- [ ] API 路由已插入 `sys_route` 表

### APT Ledger 改造
- [ ] append-only 机制已实现（reversal_of 字段，追加不覆盖）
- [ ] 四账分离模型（05 AptAccount）: 1.APT数量账(balance_apt_i/c + frozen_apt_i/c) 2.参考估值账 3.功能货币收入账 4.Reward/预算账
- [ ] 现有 wallet 表的迁移计划已制定（不直接破坏现有数据）
- [ ] `sql/` 对应 DDL 文件已创建

### Prediction（全新模块）
- [ ] Market/Fixture 模型已创建
- [ ] PredictionOrder 模型已创建（canonical: submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected）
- [ ] Settlement 引擎已实现（赛果确认 → 结算）
- [ ] Refund/Correction 机制已实现
- [ ] `sql/` 对应 DDL 文件已创建
- [ ] API 路由已插入 `sys_route` 表

### OTC/Power（全新模块）
- [ ] OtcOrder 模型已创建（draft → review → matching → partial → completed | cancelled | expired | rejected | disputed）
- [ ] OtcTrade 模型已创建（撮合记录）
- [ ] PowerAccount 模型已创建（append-only）
- [ ] PowerTransaction 模型已创建（consume/recover/convert）
- [ ] `sql/` 对应 DDL 文件已创建
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
- [ ] 无跨层调用（Controller → DAO/Model 直接操作）
- [ ] DDL 文件遵循日期命名约定（`YYYYMMDD_description.sql`）
- [ ] DDL 文件顶部有变更原因和影响范围注释
- [ ] 错误码统一在 `library/dict/ErrorDict.php` 中定义
