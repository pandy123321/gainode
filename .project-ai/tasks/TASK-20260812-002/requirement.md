# Requirement: STAGE-01 Backend Domain Objects — 10 模块 Model/DAO/Service 骨架搭建

## 背景

STAGE-00 已完成并通过 Independent Review（GAINODE-STAGE00-IR-20260812-002，VERDICT=CONDITIONAL_APPROVAL）。
15 项 Finding 全部闭合，0 个阻塞项。现推进至 STAGE-01。

项目已确认关键决策：
- 开发策略：Backend-first，所有模块领域对象建好 → 前端按模块逐个接入
- arbitrage 引擎：改造为内部 AI 经济引擎（方案 B），不对 C 端暴露
- 代码基础：在现有 `0.5代码/gainode后端/gainode` 上升级
- **Machine Contract 第一批（STAGE-01 前必须）**：DB DDL（8 核心实体）+ Canonical State Freeze

## 核心任务

按 bootstrap.md §16 推荐顺序，完成所有 V6.1 模块的后端领域对象骨架（Model/DAO/Service），建立状态机、API 契约。

### 开发顺序

1. **Auth/KYC** — MFA/OTP、多级 KYC 状态机、登录日志增强
2. **User/Eligibility** — FeatureEntitlement、Global P、地区准入、User360
3. **Robot/Reward** — Robot 56 级模型、Reward 计算引擎、升级/Power Cap
4. **APT Ledger 改造** — append-only 四账分离、reversal 机制
5. **Prediction** — 足球赛前 1X2、Market/Lock/Settlement/Refund
6. **OTC/Power** — OTC 撮合、Power 账户、买卖单
7. **Affiliate/Agent** — Agent 总览/列表/详情/推荐关系；7 个 Agent Portal
8. **AI Operations** — 运营驾驶舱/建议/市场分析/策略模拟/竞猜助手/客服风险助手
9. **Approval/Parameter** — 审批工作流、版本化参数管理
10. **Support/Audit** — 工单系统、审计追踪增强、通知 Outbox

### Machine Contract 第一批（STAGE-01 前必须）

- DB DDL（8 核心实体）
- Canonical State Freeze

## 强制要求

1. 所有新模块遵守现有分层约定：`library/model/{module}/`、`library/dao/{module}/`、`library/service/{module}/`
2. Model 必须 `extends support\extend\Model`，Service 必须 `extends support\extend\Service`
3. 业务状态常量定义在 Model 类中
4. 每张新表创建对应 `sql/YYYYMMDD_description.sql` DDL 文件（Migration 阶段一）
5. API 路由通过 `sys_route` 表插入
6. 所有写操作含 idempotency_key
7. 账本表 append-only，reversal 用追加替代覆盖
8. 金额精度 string decimal
