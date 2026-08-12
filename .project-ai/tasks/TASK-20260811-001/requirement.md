# Requirement: 后端 V6.1 全模块领域对象骨架搭建

## 背景

项目已确认以下关键决策：
- 前端框架：Vue 3
- 开发顺序：后端领域对象全量建好 → 前端按模块逐个接入
- arbitrage 引擎：改造为内部 AI 经济引擎（方案 B），不对 C 端暴露
- 代码基础：在现有 `0.5代码/gainode后端/gainode` 上升级
- **Contract Gap 已全部解除（2026-08-11）**：16/18 缺口确认，57/58 页 Admin 可开发。Affiliate 入范围、API-Football/BetBurger 合同签署、AI 策略/建议管线入范围、资产调整权限策略确认

当前后端代码有 Auth/用户/Admin 基础设施和 arbitrage 套利引擎，但缺 V6.1 新增模块的领域对象。

## 核心任务

按 07 号文档推荐顺序，完成所有 V6.1 模块的后端领域对象骨架（Model/DAO/Service），建立状态机、API 契约。

### 开发顺序（Stage 3 Backend Core）

1. **Auth/KYC 补全** — MFA/OTP、多级 KYC 状态机、登录日志增强
2. **User/Eligibility** — FeatureEntitlement、Global P、地区准入、User360
3. **Robot/Reward** — Robot 56 级模型、Reward 计算引擎、升级/Power Cap
4. **APT Ledger 改造** — append-only 四账分离、reversal 机制
5. **Prediction** — 足球赛前 1X2、Market/Lock/Settlement/Refund
6. **OTC/Power** — OTC 撮合、Power 账户、买卖单
7. **Affiliate/Agent** — Agent 总览/列表/详情/推荐关系；7 个 Agent Portal
8. **AI 运营** — 运营驾驶舱/建议/市场分析/策略模拟/竞猜助手/客服风险助手
9. **Approval/Parameter** — 审批工作流、版本化参数管理
10. **Support/Audit** — 工单系统、审计追踪增强、通知 Outbox

### arbitrage 改造要点

- `confirmed_profit` 写入内部经济指标（`reference_profit → mapped_apt_budget`）
- 拆除 BetBurger 信号对 C 端的任何直接暴露
- 保留 `ArbitrageTask` 进程和 10 张 arb 表
- 作为 Reward 预算的后台输入源

## 强制要求

1. 所有新模块遵守现有分层约定：`library/model/{module}/`、`library/dao/{module}/`、`library/service/{module}/`
2. Model 必须 `extends support\extend\Model`，Service 必须 `extends support\extend\Service`
3. 业务状态常量定义在 Model 类中
4. 每张新表创建对应 `sql/YYYYMMDD_description.sql` DDL 文件（Migration 阶段一）
5. API 路由通过 `sys_route` 表插入
6. 所有写操作含 idempotency_key
7. 账本表 append-only，reversal 用追加替代覆盖
8. 金额精度 string decimal
