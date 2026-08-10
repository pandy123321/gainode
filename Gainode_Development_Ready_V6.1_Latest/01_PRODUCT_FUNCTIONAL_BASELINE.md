# 01 · Gainode 产品功能与开发范围基线

> 版本：V2.1 · Core Object & Notice/Delivery/Reserve Closure
> 角色：开发直接执行
> 原则：说人话、少文档、不要从旧原型反推业务

## 1. 产品到底做什么

Gainode 不是一个单纯的“收益看板”。新产品围绕 5 个用户问题组织：

1. **我现在能用什么？** —— 登录、KYC、地区、风险和资格。
2. **我的 Robot 是什么状态？** —— 等级、能力、启动/停止、升级、Reward/Claim。
3. **我能参与什么赛事？** —— P0 足球赛前 1X2，状态、规则、锁定、结算、退款、更正。
4. **我的 APT 到底在哪里？** —— 可用、冻结、待确认、流水、Power、OTC。
5. **出了问题怎么办？** —— 通知、工单、申诉、后台复核和审计。

**人话备注：** 每个页面都应该让用户知道三件事：现在是什么状态、为什么、下一步能做什么。

## 2. 用户角色

| 角色 | 能做什么 | 不能做什么 |
|---|---|---|
| 游客 | 看公开产品说明和允许公开的赛事 | 不能做真实价值写操作 |
| 已登录未 KYC | 看账户和公开内容、开始 KYC | 不能默认获得 Prediction/OTC 权限 |
| 已准入用户 | 按服务端资格使用 Robot、Prediction、APT、OTC | 不能绕过地区/风险/额度 |
| 受限用户 | 看历史、合法退款、工单和允许的资产取回 | 不能继续新受限写操作 |
| 客服 | 处理工单、补件、解释状态 | 不能直接改余额/参数/订单终态 |
| 运营 | 管对象、市场、任务、普通配置草案 | 不能越权放开地区或直接改账 |
| 风控/审核 | 复核风险与案件 | 分权场景下不能自己发起又自己批准 |
| 财务/账本 | 对账、收入证据、账本异常 | 不能直接覆盖历史流水 |
| 参数/发布角色 | 编辑、审批、发布或回滚参数 | 保存草案不等于生效 |
| 审计 | 查日志、证据、审批链 | 默认只读 |

## 3. P0 / P1 / Future

### P0：这批开发必须完整

- Auth：登录、注册、OTP、密码找回、MFA、Session、设备安全。
- KYC/准入：状态、资料、补件、功能可用性、限制原因。
- Home：首页状态分流。
- Robot：概览、启动/停止、等级地图、升级、Reward/Claim、记录。
- Prediction：赛事列表、Football Pre-match 1X2、确认、订单、结算、退款、更正。
- APT-I：资产概览、流水、关联对象。
- Power：可用/冻结/消耗/释放。
- OTC：市场、挂买/挂卖、确认、审核/撮合、部分成交、取消、争议。
- Security：MFA、设备、登录记录、Session revoke。
- Support：帮助、工单、补件、进度、结论。
- Notice：通知中心、通知类型、已读/未读、关联对象深链、投递状态。
- Admin：8 个一级导航下的 P0 对象管理、审批、风控、参数、账本、工单、审计闭环。

### P1：先留结构，不阻塞 P0

- Referral / Team 深度运营。
- Prediction 免费 YES/NO / XP。
- AI Signal 详情页与更多数据解释。
- 运营分析报表、社区扩展、精细通知。

### Future：默认关闭

- APT-I → APT-C Migration 对用户开放。
- 新 Prediction Market Template。
- 跨生态资金划转。
- 任何没有正式地区/参数/安全证据的真实价值能力。

## 4. C 端信息架构

```text
Home
├─ Account / Admission status
├─ Robot summary
├─ Featured Prediction markets
├─ NoticeTicker / Important notice
└─ Optional AI data summary (P1 detail)

Robot
├─ Overview
├─ Start / Stop
├─ Level map
├─ Upgrade
├─ Rewards & Claim
└─ Activity / History

Prediction
├─ Market list
├─ Football Pre-match 1X2 detail
├─ Confirm
├─ My predictions
├─ Order detail
└─ Refund / Correction / Exception

Notice
├─ Notice list (未读/全部)
├─ Notice detail
└─ 关联对象深链跳转

Me
├─ Profile
├─ APT account
├─ APT ledger
├─ Power
├─ OTC
├─ Security
├─ Help & Tickets
├─ Notice Center（同 Notice 入口）
└─ Settings
```

## 5. Admin 信息架构

```text
01 工作台
├─ 运营总览
└─ 今日待办

02 用户与准入
├─ 用户列表
├─ 用户 360
└─ KYC 队列

03 资产与账本
├─ 资产总览
├─ APT 账户与流水
├─ 池子与对账
└─ 更正申请

04 机器人与权益
├─ Robot 列表
├─ Robot 详情
├─ Reward / Claim 记录
└─ 等级/规则只读视图 → 参数中心

05 OTC 与 Power
├─ OTC 订单
├─ OTC 订单详情/审核
├─ Power 账户/流水
└─ OTC 风险摘要

06 赛事预测
├─ Market / Event 列表
├─ Market 详情
├─ Result / Settlement
└─ Refund / Correction

07 风控 / 审批 / 参数 / 策略
├─ Risk Case
├─ Approval Center
├─ Parameter Center
└─ Region / KYC / Protection Policy

08 客服 / 审计 / 运维
├─ Ticket Queue
├─ Ticket Detail
├─ Audit Log
├─ Async / Reconciliation status
└─ System status
```

## 6. 核心业务对象

| 对象 | 作用 | 前台入口 | 后台入口 |
|---|---|---|---|
| User | 用户身份 | Me | 用户360 |
| AuthSession | 登录会话 | Security | 安全/审计 |
| KycCase | KYC 与补件 | KYC | KYC 队列 |
| FeatureEntitlement | 功能是否允许 | 全局 | 用户360/策略 |
| Robot | Robot 当前等级与状态 | Robot | Robot 管理 |
| RobotUpgradeOrder | 升级订单 | Robot | Robot 详情 |
| AIReward | Reward 候选/待领取/已领取 | Rewards | Robot/Reward |
| AptAccount | APT-I 数量账 | Assets | Asset |
| AptLedgerEntry | 每次 APT 变化 | Ledger | Ledger |
| PowerPosition | Power 余额/冻结 | Power/OTC | OTC/Power |
| Market | Prediction 市场 | Prediction | Market |
| PredictionOrder | 用户预测订单 | My Prediction | Order/Settlement |
| Result | 赛果 | Order detail | Result |
| RefundCase | 退款 | Exception detail | Settlement |
| CorrectionCase | 更正 | Exception detail | Settlement/Approval |
| OtcOrder | OTC 挂单 | OTC | OTC |
| OtcTrade | OTC 成交记录 | OTC Detail | OTC Detail |
| ApprovalRequest | 高风险审批 | 不直接展示 | Approval Center |
| ParameterRelease | 参数发布版本 | 只显示版本 | Parameter Center |
| RiskCase | 风控案件 | 只显示安全原因 | Risk Center |
| Ticket | 工单 | Support | Support |
| AuditLog | 审计 | 不直接展示 | Audit |
| OtcEligibility | OTC 资格投影（每次评估结果，非持久实体） | OTC 市场 | OTC 详情 |
| OtcCapacity | OTC 容量投影 | OTC 市场 | OTC 详情 |
| Settlement | Prediction 单笔结算 | 订单详情 | Settlement |
| SettlementBatch | Prediction 批次结算 | 不直接展示 | Settlement |
| ConsentReceipt | 用户确认回执 | 确认页 | 用户360 |
| MfaEnrollment | MFA 注册 | Security | 安全/审计 |
| SecurityProfile | 安全档案投影 | Security | 安全/审计 |
| SessionDevice | 已登录设备/会话 | Security | 安全/审计 |
| LoginAudit | 登录审计 | 不直接展示 | Audit |
| TicketAttachment | 工单附件 | Support | Support |
| TicketMessage | 工单消息 | Support | Support |
| ParameterSnapshot | 参数快照 | 只显示版本 | Parameter Center |
| Notice | 用户通知（只读聚合） | Notice Center | 不直接展示 |
| NotificationDelivery | 通知投递 | 不直接展示 | Audit |

> 说明：「前台入口」中标注"不直接展示"或"只显示版本"的对象是后台管理对象或 service-layer 对象，不在移动端页面上直接作为独立页面出现。

## 7. 统一产品状态原则

### 7.1 页面状态

所有页面至少考虑：

```text
Loading
Content
Empty
Error
Restricted
```

所有写操作至少考虑：

```text
Default
Submitting
Accepted / Processing
Success
Failed-No-Effect
Failed-Needs-Review
```

### 7.2 Restricted 不是 Error

- Error = 系统没拿到正确结果。
- Restricted = 系统明确知道你现在不能做。

**人话备注：** “系统坏了”和“你现在没有权限”绝对不能用同一条提示。

## 8. 功能资格统一规则

任何真实价值按钮都不由前端自己判断。

服务端统一返回：

```json
{
  "allowed": false,
  "reason_code": "KYC_REQUIRED",
  "reason_text": "完成身份验证后可以继续。",
  "next_action": "OPEN_KYC",
  "rule_version": "...",
  "policy_version": "..."
}
```

前端只负责显示和执行 `next_action`。

## 9. 产品表达规则

可以说：
- AI 体育数据、分析、Robot 等级能力、动态 Reward、赛事预测、APT 数量、OTC 撮合。

不要说：
- 稳赚、固定收益、保本、固定回本、官方保价、无限流动性、提交订单=一定成交。

Reward 必须表达为动态：系数可能变化，也可能为 0。

## 10. 原型与开发边界

- 新原型全部重新生成。
- 不使用旧大 Figma 作为视觉、页面或交互基线。
- 不从旧 Flutter / Admin 代码反推新业务。
- Mock 数据只能用于视觉填充，必须与正式参数分离。
- 前端不得用 Mock 值作为生产 fallback。

### 10.1 转赠状态说明

APT 转赠（`M-ASSET-005`、`TransferEligibility`、`AptTransfer` 及相关 API、状态和参数）尚未被 V6.1 重新确认进入任何范围（不在 P0、P1 或 Future 中）。如未来恢复转赠，必须同时修改 01、03、05、06、07，不能只添加一个孤立对象。当前文档中不存在 APT 转赠页面、对象、API 或参数孤儿。

### 10.2 新增内容范围

本轮 V2.1 新增：
- Notice / NotificationDelivery 通知体系。
- OTC `expired` 状态和 OTC 资格非状态机定义。
- 资金、储备与运营预算隔离边界。
- 跨端状态与对象一致性验收规则。
- 紧急操作控制页面（A-EMERGENCY-001）。
