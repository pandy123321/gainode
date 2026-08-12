# 领域术语

## 已确认信息

### 核心产品术语

| 术语 | 含义 | 来源 |
|------|------|------|
| **Robot** | 用户的自动 AI 代理：等级、能力、启动/停止、升级、Reward/Claim | 01 §2, 02 |
| **Prediction** | 多市场预测系统：P0 足球赛前 1X2；P1 免费 YES/NO/XP | 01 §3 |
| **APT** | 平台加密货币（APT-I = Internal；APT-C = Convertible，Future） | 01 §3, 02 |
| **Power** | 可消耗、可恢复操作资源：OTC Sell / Withdrawal / Robot Start 均消耗 | README, 02 |
| **OTC** | Over-The-Counter 点对点资产交易市场 | 01 §3, 05 |
| **Fixture** | 预测赛事实例：每场至少 12–24 场同时激活 | README #5, 05 |

### 产品模块

| 缩写 | 全称 | 说明 |
|------|------|------|
| Auth | Authentication | 注册/登录/OTP/MFA/Session |
| KYC | Know Your Customer | 身份验证与准入 |
| OTP | One-Time Password | 一次性验证码 |
| MFA | Multi-Factor Authentication | 多因素认证 |
| APT-I | APT Internal | 内部 APT 资产系统 |
| APT-C | APT Convertible | 可兑换 APT（Future） |
| OTC | Over-The-Counter | 场外交易市场 |

### 用户角色

| 角色 | 说明 |
|------|------|
| 游客 | 看公开产品说明和允许公开的赛事 |
| 已登录未 KYC | 看账户和公开内容、开始 KYC |
| 已准入用户 | 按服务端资格使用 Robot/Prediction/APT/OTC |
| 受限用户 | 看历史、合法退款、工单和允许的资产取回 |
| 客服（SUPPORT_AGENT） | 处理工单、补件、解释状态 |
| 运营（OPS_OPERATOR） | 管对象、市场、任务、普通配置草案 |
| 风控/审核（RISK_ANALYST/RISK_APPROVER） | 复核风险与案件 |
| 财务/账本（LEDGER_OPERATOR/FINANCE_REVIEWER） | 对账、收入证据、账本异常 |
| 参数角色（PARAM_EDITOR/PARAM_APPROVER） | 编辑、审批、发布或回滚参数 |
| 审计（AUDITOR） | 查日志、证据、审批链 |

### API 契约术语

| 术语 | 含义 |
|------|------|
| Idempotency-Key | 所有写操作的唯一幂等键 |
| Cursor-based pagination | 基于游标的分页（非 offset/limit） |
| If-Match | HTTP 并发控制头，配合 object_version |
| object_version | 对象版本号，用于乐观锁 |
| RESULT_UNKNOWN (202) | 使用原 Idempotency-Key 查询结果，不重试创建 |
| Snapshot ID | 快照标识，用于审计追溯 |
| Rule Version | 当前生效规则版本号 |
| Parameter Release ID | 参数发布版本 |

### 主要业务实体

| 实体 | 类型 | 说明 |
|---|---|---|
| **User** | 持久领域实体 | 用户身份 `active / restricted / suspended / closed` |
| **AuthSession** | 持久领域实体 | 登录会话 `active / expired / revoked` |
| **KycCase** | 工作流对象 | KYC 审核 `pending / under_review / approved / rejected / expired` |
| **FeatureEntitlement** | 投影/聚合 | 功能是否允许，服务端统一返回 `allowed / reason_code / next_action` |
| **Robot** | 持久领域实体 | Robot 等级与状态 `idle / running / upgrading / paused / disabled` |
| **RobotUpgradeOrder** | 工作流对象 | 升级订单，含 current/target level、apt_cost、power_cap_after |
| **AIReward** | 领域实体 | Reward `candidate / held / pending_claim / claimed / expired_returned / reversed` |
| **AptAccount** | 持久领域实体 | APT-I 数量账（available/frozen/pending） |
| **AptLedgerEntry** | 持久领域实体 | 每笔 APT 变化 `pending / posted / reversed / disputed` |
| **PowerPosition** | 聚合/投影 | Power 余额/冻结/消耗/释放/Cap |
| **Market** | 领域实体 | 预测市场 `scheduled / open / locked / in_settlement / settled / cancelled` |
| **PredictionOrder** | 领域实体 | 用户预测订单 `pending / confirmed / settled / refunded / corrected` |
| **Result** | 工作流对象 | 赛果 `provisional / official / disputed / corrected` |
| **Settlement** | 工作流对象 | 单笔结算 `queued / calculating / review / payable / paid / failed` |
| **OtcOrder** | 领域实体 | OTC 挂单 `open / partial_filled / filled / cancelled / expired / disputed` |
| **OtcTrade** | 持久领域实体 | OTC 成交记录 |
| **ApprovalRequest** | 工作流对象 | 高风险审批 `draft / pending_review / approved / rejected / executed / rolled_back` |
| **ParameterRelease** | 工作流对象 | 参数发布版本 `draft / pending_approval / published / rolled_back` |
| **ParameterSnapshot** | 只读投影 | 参数快照，历史订单用于回算 |
| **RiskCase** | 工作流对象 | 风控案件 |
| **Ticket** | 工作流对象 | 工单 `submitted / in_progress / waiting_user / under_review / resolved / closed` |
| **AuditLog** | 只读投影 | 审计日志 |
| **Notice** | 只读投影 | 用户通知（read_state、priority、关联对象深链） |
| **NotificationDelivery** | 工作流对象 | 通知投递记录（channel、delivery_status、retry） |
| **ConsentReceipt** | 持久领域实体 | 用户确认回执 |
| **MfaEnrollment** | 持久领域实体 | MFA 注册 |
| **AssetAdjustmentProposal** | 工作流对象 | 资产调整提案（仅 ADMIN_SECURITY 可执行） |
| **Agent** | 持久领域实体 | 代理 `pending / active / suspended / terminated` |
| **Referral** | 持久领域实体 | 推荐关系 `pending / active / settled / revoked` |
| **AgentEarning** | 持久领域实体 | 代理收益 `pending / calculated / payable / claimed` |
| **AISignal** | 领域实体 | AI 信号 `generated / validated / published / rejected` |
| **AIRecommendation** | 领域实体 | AI 运营建议 `draft / reviewed / published / dismissed` |
| **SimulationRun** | 工作流对象 | AI 策略模拟 `queued / running / completed / failed / cancelled` |

### 状态定义

| 域 | 状态 |
|------|------|
| KYC | pending / under_review / approved / rejected / expired |
| Robot | idle / running / upgrading / paused / disabled |
| Prediction Fixture | scheduled / open / locked / in_settlement / settled / cancelled |
| Prediction Order | pending / confirmed / settled / refunded / corrected |
| OTC Order | open / partial_filled / filled / cancelled / expired / disputed |
| Power | available / frozen / consumed |
| Auth Session | active / expired / revoked |
| Approval | draft / pending_review / approved / rejected / executed / rolled_back |
| Parameter Release | draft / pending_approval / published / rolled_back |
| Agent | pending / active / suspended / terminated |
| Referral | pending / active / settled / revoked |
| AgentEarning | pending / calculated / payable / claimed |
| AISignal | generated / validated / published / rejected |
| SimulationRun | queued / running / completed / failed / cancelled |

### 经济术语

| 术语 | 含义 |
|---|---|
| **standard_capacity** | Robot 等级对应的分配权重 |
| **daily_reward_coefficient** | 当天服务端系数，允许等于 0 |
| **pending_apt** | standard_capacity × daily_reward_coefficient，待领取数量 |
| **APR / APY** | 禁止在用户端出现，Reward 不得表达为固定收益率 |
| **confirmed_profit** | 内部 AI 引擎的可审计执行结果 |
| **reference_profit** | 经平滑处理的参考利润 |
| **mapped_apt_budget** | reference_profit 转换的 APT 预算 |
| **daily_ai_budget** | 当日 AI Reward 预算上限（取多项 cap 的最小值） |
| **APT_MAX_SUPPLY** | 100,000,000,000 APT（1000 亿） |

### 技术缩写

| 缩写 | 全称 |
|------|------|
| REST | Representational State Transfer |
| JSON | JavaScript Object Notation |
| OpenAPI 3.1 | API 规范标准 |
| RBAC | Role-Based Access Control |
| ABAC | Attribute-Based Access Control |
| MFA | Multi-Factor Authentication |
| OTP | One-Time Password |
| KYC | Know Your Customer |
| OTC | Over-The-Counter |
| APT | 平台代币代号 |
| UTC | Coordinated Universal Time |
| SoD | Separation of Duties（职责分离） |
| P0 / P1 / P1_CONDITIONAL / Future | 优先级分级：P0 本批次完成（57 页），FUTURE 默认关闭（1 页 A-MIGRATION-001） |
| DoD | Definition of Done（完成标准） |
| TBC | To Be Confirmed（待确认） |
| BLOCKING_GAP | 阻断性 Contract Gap：05 核心契约缺失，页面写能力 DISABLED、FAIL_CLOSED |
| NON_BLOCKING_GAP | 非阻断性 Contract Gap：BASE_CONTRACT=FROZEN，仅子能力未冻结 |
| Actor-level SoD | Actor Separation Invariant：SoD 基于 Actor ID 而非当前激活 Role；同一 Workflow Object 冲突阶段必须由不同 Actor ID 执行 |
| CONTRACT_GAP | 合同状态：05 契约未冻结，页面仅 Preview / Read-Only，执行按钮不可用 |
| FROZEN | 合同状态：05 契约已冻结，可进入 HIFI 实现 |

### 视觉关键词

| 关键词 | 说明 |
|--------|------|
| Western | 西部风格 |
| Premium | 高端品质感 |
| Sports-Tech | 体育科技感 |
| Operational | 运营可用（非纯展示） |

## 基于代码的推断
- 遗留 PHP 后端的 60 张 MySQL 表定义了完整的数据域
- 遗留代码集成了 Tron/BSC/Ethereum 三条链
- ORM 层使用 illuminate/database（Laravel Eloquent），非 ThinkPHP（来源：`composer.json` 依赖清单）

## 待确认事项
- 正式开发的技术栈选型（现有 PHP/Webman + illuminate/database + MySQL；详见 TASK-20260812-001 #1-#3）
- Power 的精确消耗规则（由 Active Rule/Parameter 决定）
- AI Robot 引擎的技术实现方案（现有 arbitrage 引擎；详见 TASK-20260812-001 #5）
- 60 张遗留表的迁移/重构策略（现有代码基础上升级 vs 完全重写；详见 TASK-20260812-001 #6）

## 信息来源
- `01_PRODUCT_FUNCTIONAL_BASELINE.md` §2 — 用户角色
- `05_DATA_STATE_PERMISSION_API_CONTRACT.md` §1-8 — API 契约、状态机、RBAC
- `README.md` — 关键决策与术语
- `0.5代码/gainode后端/gainode/sql/database.sql` — 遗留数据库
