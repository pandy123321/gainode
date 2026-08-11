# 领域术语

## 已确认信息

### 产品/协议术语（不翻译）

| 术语 | 含义 |
|---|---|
| **Gainode** | 产品名，AI 驱动的体育分析与竞猜平台 |
| **APT** | 系统内部数量代币，总量上限 1000 亿 |
| **APT-I** | APT Internal，系统内部数量账形态，P0 使用 |
| **APT-C** | APT Chain，链上形态，Future 阶段开放 |
| **Robot** | 56 级 AI 代理，用户的 AI 数据与分析执行能力载体 |
| **Power** | 可消耗、可恢复操作资源；不是手续费、不是 Reward、不是"收益算力" |
| **OTC** | Over-The-Counter，用户间受控撮合交易，非平台固定回购 |
| **1X2** | Football Pre-match 三种赛果：Home Win / Draw / Away Win |
| **MFA** | Multi-Factor Authentication，多因素认证 |
| **KYC** | Know Your Customer，用户身份验证与准入 |
| **OTP** | One-Time Password，一次性验证码 |
| **AI** | Artificial Intelligence，内部 AI/数据/执行引擎 |

### 主要业务实体

| 实体 | 类型 | 说明 |
|---|---|---|
| **User** | 持久领域实体 | 用户身份 `active / restricted / suspended / closed` |
| **AuthSession** | 持久领域实体 | 登录会话 `active / mfa_required / restricted / expired / revoked` |
| **KycCase** | 工作流对象 | KYC 审核 `not_started / pending / needs_info / approved / rejected / review` |
| **FeatureEntitlement** | 投影/聚合 | 功能是否允许，服务端统一返回 `allowed / reason_code / next_action` |
| **Robot** | 持久领域实体 | Robot 等级与状态 `inactive / active / cooling / review / restricted / paused` |
| **RobotUpgradeOrder** | 工作流对象 | 升级订单，含 current/target level、apt_cost、capability_diff、power_cap_after |
| **AIReward** | 领域实体 | Reward 候选/待领取/已领取 `candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed` |
| **AptAccount** | 持久领域实体 | APT-I 数量账（available/frozen/pending） |
| **AptLedgerEntry** | 持久领域实体 | 每笔 APT 变化 `pending / posted / reversed / disputed` |
| **PowerPosition** | 聚合/投影 | Power 余额/冻结/消耗/释放/恢复/Cap |
| **PowerImpactPreview** | 投影（每次操作前） | 操作对 Power 的影响预览（freeze/consume/release） |
| **Market** | 领域实体 | Prediction 市场 `draft / open / closing / locked / awaiting_result / settlement / settled / void / exception` |
| **PredictionOrder** | 领域实体 | 用户预测订单 `submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected` |
| **Result** | 工作流对象 | 赛果 `provisional / official / disputed / corrected` |
| **Settlement** | 工作流对象 | 单笔结算 `queued / calculating / review / payable / paid / failed` |
| **SettlementBatch** | 工作流对象 | 批次结算 |
| **RefundCase** | 工作流对象 | 赛事作废退款 |
| **CorrectionCase** | 工作流对象 | 赛果更正（保留原 snapshot + 新建 correction） |
| **OtcOrder** | 领域实体 | OTC 挂单 `draft / review / matching / partial / completed / cancelled / expired / rejected / disputed` |
| **OtcTrade** | 持久领域实体 | OTC 成交记录 |
| **OtcEligibility** | 只读投影（非持久实体） | OTC 资格每次评估结果，不是状态机 |
| **OtcCapacity** | 只读投影 | OTC 容量投影（用户/全局剩余容量、储备比例） |
| **ApprovalRequest** | 工作流对象 | 高风险审批 `draft / pending / changes_requested / approved / rejected / executing / executed / failed` |
| **ParameterRelease** | 工作流对象 | 参数发布版本 `draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived` |
| **ParameterSnapshot** | 只读投影 | 参数快照，历史订单用于回算 |
| **RiskCase** | 工作流对象 | 风控案件 |
| **Ticket** | 工作流对象 | 工单 `submitted / in_progress / waiting_user / under_review / resolved / closed` |
| **TicketMessage** | 值对象 | 工单消息 |
| **TicketAttachment** | 值对象 | 工单附件 |
| **AuditLog** | 只读投影 | 审计日志 |
| **Notice** | 只读投影 | 用户通知（read_state、priority、关联对象深链） |
| **NotificationDelivery** | 工作流对象 | 通知投递记录（channel、delivery_status、retry） |
| **ConsentReceipt** | 持久领域实体 | 用户确认回执 |
| **MfaEnrollment** | 持久领域实体 | MFA 注册 |
| **SecurityProfile** | 只读投影 | 安全档案投影 |
| **SessionDevice** | 只读投影 | 已登录设备/会话 |
| **LoginAudit** | 只读投影 | 登录审计 |
| **AssetAdjustmentProposal** | 工作流对象 | 资产调整提案（P1_CONDITIONAL；UI_CANDIDATE_ONLY / NON_AUTHORITATIVE / NOT_API_CONTRACT / CONTRACT_GAP） |

### 经济术语

| 术语 | 含义 |
|---|---|
| **standard_capacity** | Robot 等级对应的分配权重 |
| **daily_reward_coefficient** | 当天服务端系数，允许等于 0 |
| **pending_apt** | standard_capacity × daily_reward_coefficient，待领取数量，不是已到账 |
| **APR / APY** | 禁止在用户端出现，Reward 不得表达为固定收益率 |
| **confirmed_profit** | 内部 AI 引擎的可审计执行结果 |
| **reference_profit** | 经平滑处理的参考利润 |
| **mapped_apt_budget** | reference_profit 转换的 APT 预算 |
| **daily_ai_budget** | 当日 AI Reward 预算上限（取多项 cap 的最小值） |
| **APT_MAX_SUPPLY** | 100,000,000,000 APT（1000 亿） |

### 状态术语速查

| 状态 | 所属对象 | 含义 |
|---|---|---|
| `completed` | OtcOrder | 完整成交完成 |
| `cancelled` | OtcOrder | 用户或系统主动取消 |
| `expired` | OtcOrder | 按订单有效期自然到期，不是主动取消 |
| `partial` | OtcOrder | 部分成交，remaining 可继续撮合或取消释放 |
| `void` | Market | 赛事作废 |
| `disputed` | OtcOrder / Result | 争议中，保持冻结 |
| `restricted` | User | 受限用户，仍可查看历史/退款/工单 |
| `cooling` | Robot | 升级冷却期 |
| `TBC` | Parameter | 生产值未定，保持 null/closed |
| `RESULT_UNKNOWN` | API 202 / Request Resolution | 客户端请求结果不确定（非领域状态），用原 Idempotency-Key 查询结果，不重新提交 |

### 缩写词

| 缩写 | 全称 |
|---|---|
| **APT** | 平台内部数量代币（非加密货币缩写） |
| **OTC** | Over-The-Counter（场外/用户间撮合） |
| **KYC** | Know Your Customer |
| **MFA** | Multi-Factor Authentication |
| **OTP** | One-Time Password |
| **RBAC** | Role-Based Access Control |
| **ABAC** | Attribute-Based Access Control |
| **SoD** | Separation of Duties（职责分离） |
| **P0 / P1 / P1_CONDITIONAL / Future** | 优先级分级：P0 必须本批次完成（35 页），P1 先留结构（8 页），P1_CONDITIONAL 合同未冻结仅 Preview（18 页），Future 默认关闭（1 页） |
| **DoD** | Definition of Done（完成标准） |
| **TBC** | To Be Confirmed（待确认） |
| **BLOCKING_GAP** | 阻断性 Contract Gap：05 核心契约缺失，页面写能力 DISABLED、FAIL_CLOSED |
| **NON_BLOCKING_GAP** | 非阻断性 Contract Gap：BASE_CONTRACT=FROZEN，仅子能力未冻结 |
| **Actor-level SoD** | Actor Separation Invariant：SoD 基于 Actor ID 而非当前激活 Role；同一 Workflow Object 冲突阶段必须由不同 Actor ID 执行，不可通过切换 active role 绕过 |
| **CONTRACT_GAP** | 合同状态：05 契约未冻结，页面仅 Preview / Read-Only，执行按钮不可用 |
| **FROZEN_WITH_NONBLOCKING_GAP** | 合同状态：BASE_CONTRACT=FROZEN + 仅 NON_BLOCKING GAP，页面核心写能力可用但 Gap 子能力 FAIL_CLOSED |

## 基于代码的推断

- 无代码，术语全部来源于 01–08 号规格文档和 `/i18n/terminology-lock.json`。
- `OtcEligibility` 明确标注为"非持久实体、非状态机"，每次请求动态计算——需在实现时确保无副作用。
- `expired` 是新引入的 OtcOrder 状态（V6.1 新增），与 `cancelled` 有明确语义区别，实现的展示映射表已定义在 05 §4。

## 待确认事项

- [ ] 部分术语的最终中文翻译是否已由产品/法务确认（如 Power 的中文显示词）
- [ ] Prediction 的中文显示词「竞猜」是否已获得法务/合规签核

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/01_PRODUCT_FUNCTIONAL_BASELINE.md`（第 6 节核心业务对象）
- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（第 3 节对象最低字段、第 4 节统一状态机）
- `Gainode_Development_Ready_V6.1_Latest/02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`（经济术语）
- `Gainode_Development_Ready_V6.1_Latest/04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md`（Admin 页面 ID、权限、合同状态，V2.4.1 治理基线已合入）
- `Gainode_Development_Ready_V6.1_Latest/i18n/terminology-lock.json`
- `0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/`（V2.4.1 治理术语：P1_CONDITIONAL、BLOCKING Gap、Actor-level SoD）
