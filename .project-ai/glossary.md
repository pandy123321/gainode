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
| **User** | 持久领域实体 | 用户身份状态 `active / restricted / suspended / closed`（05 §4） |
| **AuthSession** | 持久领域实体 | 登录会话 `active / expired / revoked`（来源待确认：非 05 §4 统一状态机） |
| **KycCase** | 工作流对象 | KYC 审核 `not_started / pending / needs_info / approved / rejected / review`（05 §4） |
| **FeatureEntitlement** | 投影/聚合 | 功能是否允许，服务端统一返回 `allowed / reason_code / next_action` |
| **Robot** | 持久领域实体 | Robot 等级与状态 `inactive / active / cooling / review / restricted / paused`（05 §4，已 FROZEN） |
| **RobotUpgradeOrder** | 工作流对象 | 升级订单，含 current/target level、apt_cost、power_cap_after |
| **AIReward** | 领域实体 | Reward `candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed`（05 §4，已 FROZEN） |
| **AptAccount** | 持久领域实体 | APT-I 数量账（balance_apt_i / balance_apt_c / frozen_apt_i / frozen_apt_c / total_earned_apt / total_spent_apt，MC1 冻结，无状态机） |
| **AptLedgerEntry** | 持久领域实体 | 每笔 APT 变化 `pending / posted / reversed / disputed`（05 §4，已 FROZEN） |
| **PowerPosition** | 聚合/投影 | Power 余额/冻结/消耗/释放/Cap |
| **Market** | 领域实体 | 预测市场 `draft / open / closing / locked / awaiting_result / settlement / settled / void / exception`（05 §4，已 FROZEN） |
| **PredictionOrder** | 领域实体 | 用户预测订单 `submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected`（05 §4，已 FROZEN） |
| **Result** | 工作流对象 | 赛果 `provisional / official / disputed / corrected` |
| **Settlement** | 工作流对象 | 单笔结算 `queued / calculating / review / payable / paid / failed` |
| **OtcOrder** | 领域实体 | OTC 挂单 `draft / review / matching / partial / completed / cancelled / expired / rejected / disputed`（05 §4，已 FROZEN） |
| **OtcTrade** | 持久领域实体 | OTC 成交记录 |
| **ApprovalRequest** | 工作流对象 | 高风险审批 `draft / pending / changes_requested / approved / rejected / executing / executed / failed`（05 §4） |
| **ParameterRelease** | 工作流对象 | 参数发布版本 `draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived`（05:854-865） |
| **ParameterSnapshot** | 只读投影 | 参数快照，历史订单用于回算 |
| **RiskCase** | 工作流对象 | 风控案件 |
| **Ticket** | 工作流对象 | 工单 `submitted / in_progress / waiting_user / under_review / resolved / closed`（05 §4） |
| **AuditLog** | 只读投影 | 审计日志 |
| **Notice** | 只读投影 | 用户通知（read_state、priority、关联对象深链） |
| **NotificationDelivery** | 工作流对象 | 通知投递记录（channel、delivery_status、retry） |
| **ConsentReceipt** | 持久领域实体 | 用户确认回执 |
| **MfaEnrollment** | 持久领域实体 | MFA 注册 |
| **AssetAdjustmentProposal** | 工作流对象 | 资产调整提案（仅 ADMIN_SECURITY 可执行） |
| **Agent** | 持久领域实体 | 代理。05: NOT DEFINED — 状态枚举 TBC，等待 Contract Freeze |
| **Referral** | 持久领域实体 | 推荐关系。05: NOT DEFINED — 状态枚举 TBC |
| **AgentEarning** | 持久领域实体 | 代理收益。05: NOT DEFINED — 状态枚举 TBC |
| **AISignal** | 领域实体 | AI 信号。05: NOT DEFINED — 状态枚举 TBC |
| **AIRecommendation** | 领域实体 | AI 运营建议。05: NOT DEFINED — 状态枚举 TBC |
| **SimulationRun** | 工作流对象 | AI 策略模拟。05: NOT DEFINED — 状态枚举 TBC |

### 状态定义（所有 Domain State 依法来自 05 §4 统一状态机）

| 域 | Canonical State | 05 行号 |
|------|------|------|
| User | active / restricted / suspended / closed | 05:735 |
| KYC | not_started / pending / needs_info / approved / rejected / review | 05:738 |
| Robot | inactive / active / cooling / review / restricted / paused | 05:741 |
| AI Reward | candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed | 05:744 |
| Ledger Entry | pending / posted / reversed / disputed | 05:747 |
| Market | draft / open / closing / locked / awaiting_result / settlement / settled / void / exception | 05:750 |
| Result | provisional / official / disputed / corrected | 05:753 |
| Settlement | queued / calculating / review / payable / paid / failed | 05:756 |
| Prediction Order | submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected | 05:759 |
| OTC Order | draft / review / matching / partial / completed / cancelled / expired / rejected / disputed | 05:762 |
| Ticket | submitted / in_progress / waiting_user / under_review / resolved / closed | 05:773 |
| Approval | draft / pending / changes_requested / approved / rejected / executing / executed / failed | 05:776 |
| Parameter Release | draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived | 05:779 |
| Power | 无 canonical status enum — 05 使用 scalar fields: available, frozen, consumed_period | 05:151 |
| Auth Session | active / expired / revoked（**待确认**：非 05 §4 统一状态机，来源需确认） | — |
| Affiliate/Agent | **05: NOT DEFINED** — 状态枚举 TBC，等待 Contract Freeze | — |
| AI Signal/Rec./Sim. | **05: NOT DEFINED** — 状态枚举 TBC，等待 Contract Freeze | — |

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

### 工程术语（STAGE-01 新增）

| 术语 | 含义 |
|---|---|
| **Snowflake ID** | 分布式唯一 ID（`godruoyi/php-snowflake`），V2.0 主键 `bigint unsigned`，应用层生成，禁用 AUTO_INCREMENT；Model 层 `$incrementing=false` + `$keyType='string'` |
| **Authoritative Writer** | 每个数据实体有且仅有一个 Service 作为唯一写入方，Service 声明 `@authoritative_writer <table>` 注解 |
| **FAIL_CLOSED** | 状态转移矩阵未冻结时，Service 拒绝任何未授权的 state transition（默认拒绝写入），不自行发明转移规则 |
| **append-only 账本** | `apt_ledger_entries` 经济字段一经写入永不覆盖/删除，更正通过 `reversal_of` 追加反向分录；无 `updated_time` 列（Model `$timestamps=false` + `UPDATED_AT=null`） |
| **MC1 Freeze** | Machine Contract 第一批冻结：8 核心实体 DDL + Canonical State Freeze（2026-08-13 FROZEN，Owner Signoff） |

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
- 遗留代码曾集成 Tron/BSC/Ethereum 三条链（V2.0 已移除。OWNER_DIRECTIVE 2026-08-12）
- ORM 层使用 illuminate/database（Laravel Eloquent），非 ThinkPHP（来源：`composer.json` 依赖清单）

## 待确认事项
- Power 的精确消耗/恢复规则（由 Active Rule/Parameter 决定，生产参数未批准）
- AI Robot 引擎的技术实现方案（现有 arbitrage 引擎改造为内部 AI 经济引擎，具体算法/模型 TBC）
- Auth Session 状态枚举来源（`active / expired / revoked` 非 05 §4 统一状态机，需确认定义位置）
- Affiliate/Agent、AI Signal/Rec./Sim. 状态枚举（05: NOT DEFINED，待 Contract Freeze）
- manifest `contextVersion` 与 AI Code Review Assistant 已发布上下文版本号（当前 v17）的映射关系

## 信息来源
- `01_PRODUCT_FUNCTIONAL_BASELINE.md` §2 — 用户角色
- `05_DATA_STATE_PERMISSION_API_CONTRACT.md` §1-8 — API 契约、状态机、RBAC
- `README.md` — 关键决策与术语
- `0.5代码/gainode后端/gainode/sql/database.sql` — 遗留数据库
- `0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql` — MC1 8 核心实体 DDL
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md` — MC1 状态冻结
