# 05 · Gainode 数据、状态、权限与 API 契约

> 版本：V2.3 · 2B-1 canonical enum supplement（Owner 裁决 2026-08-16）
> 目标：让前后端不再靠页面截图猜字段；所有 P0 核心对象均有最低字段定义或明确对象类型标注

## 1. API 全局规则

```text
API_STYLE = REST/JSON
OPENAPI = 3.1
JSON_SCHEMA = 2020-12
TIME = UTC timestamp, client localizes
DECIMAL = string, never float for assets
PAGINATION = cursor
WRITE_IDEMPOTENCY = required
CONCURRENCY = If-Match / object_version where applicable
```

所有写操作最少返回：

```json
{
  "request_id": "...",
  "idempotency_key": "...",
  "object_type": "...",
  "object_id": "...",
  "status": "...",
  "result_code": "...",
  "result_message": "...",
  "next_action": "...",
  "rule_version": "...",
  "parameter_release_id": "...",
  "policy_version": "...",
  "snapshot_id": "...",
  "approval_id": null,
  "audit_event_id": "..."
}
```

## 2. Auth / Session 契约

### 2.1 核心接口

| API | 方法 | 用途 |
|---|---|---|
| `/api/v1/auth/register` | POST | 注册 |
| `/api/v1/auth/login` | POST | 登录 |
| `/api/v1/auth/otp/verify` | POST | OTP 验证 |
| `/api/v1/auth/otp/resend` | POST | OTP 重发 |
| `/api/v1/auth/mfa/verify` | POST | MFA challenge |
| `/api/v1/auth/refresh` | POST | 刷新 session |
| `/api/v1/auth/logout` | POST | 当前 session 退出 |
| `/api/v1/auth/recovery` | POST | 发起找回 |
| `/api/v1/auth/password/reset` | POST | 重置密码 |
| `/api/v1/me/sessions` | GET | 已登录会话/设备 |
| `/api/v1/me/sessions/{id}/revoke` | POST | 撤销其他 session |

### 2.2 Session 状态

```text
active
mfa_required
restricted
expired
revoked
```

## 3. 核心对象最低字段

### User

```text
user_id
status
display_name
locale
timezone
global_p_level
ai_reward_eligibility
prediction_eligibility
created_at
updated_at
```

### FeatureEntitlement

```text
feature_key
allowed
reason_code
reason_text
next_action
policy_version
rule_version
expires_at
```

### Robot

```text
robot_id
user_id
level
status
standard_capacity
capabilities[]
allowed_actions[]
rule_version
parameter_release_id
snapshot_id
updated_at
```

### AIReward

```text
reward_id
user_id
robot_id
period
standard_capacity
daily_reward_coefficient
quantity_apt
state
eligibility_snapshot_id
budget_snapshot_id
claim_id
ledger_entry_id
expires_at
```

### AptLedgerEntry

```text
ledger_entry_id
account_id
asset = APT-I
quantity
entry_direction
entry_type
state
source_object_type
source_object_id
journal_batch_id
reversal_of
rule_version
snapshot_id
created_at
```

### PowerPosition

```text
user_id
available
frozen
consumed_period
released_period
recovering
limit
power_cap_source_robot_level
last_restore_at
next_restore_at
rule_version
parameter_release_id
```

### PowerImpactPreview

所有会使用 Power 的高价值写操作，在用户确认前必须由服务端返回统一 Preview：

```text
action_type              # OTC_SELL / WITHDRAWAL / ROBOT_START
required_power
freeze_power              # action-specific
consume_power             # action-specific preview
release_power             # action-specific preview
available_before
available_after_preview
frozen_before
frozen_after_preview
robot_level
power_cap
rule_version
parameter_release_id
snapshot_id
expires_at
allowed
reason_code
```

规则：

- `OTC_SELL` 的 freeze / consume / release 生命周期按 OTC 正式规则执行。
- `WITHDRAWAL` 与 `ROBOT_START` 的具体扣减时点和数量由 Active Parameter + 服务端动作合同决定；客户端不得推断。
- Robot Start 对用户可见仍称“启动 Robot / 启动运行”，不得重新暴露 Crypto Arbitrage 叙事。
- Prediction P0 不自动套用 Power 规则。


### Market

```text
market_id
event_id
template_id = FOOTBALL_PREMATCH_1X2
market_status
lock_at
selections[HOME,DRAW,AWAY]
liquidity_summary
result_status
rule_version
parameter_release_id
policy_version
snapshot_id
```

### PredictionOrder

```text
order_id
user_id
market_id
selection
amount_apt
order_status
asset_status
risk_status
consent_receipt_id
submit_snapshot_id
parameter_release_id
policy_version
created_at
```

### OtcOrder

```text
otc_order_id
user_id
side
price
quantity_apt
filled_quantity_apt
remaining_quantity_apt
fee_apt
power_required
power_consumed
power_frozen
status
review_required
quote_id
snapshot_id
created_at
```

### AuthSession [持久领域实体]

```text
session_id
user_id
token_hash
status
device_info
ip_address
mfa_verified
expires_at
created_at
updated_at
```

### KycCase [工作流对象]

```text
case_id
user_id
kyc_level
status
submitted_at
reviewed_at
reviewed_by
reason_code
reason_text_key
next_action
policy_version
rule_version
created_at
updated_at
```

### AptAccount [持久领域实体]

```text
account_id
user_id
balance_apt_i
balance_apt_c
frozen_apt_i
frozen_apt_c
total_earned_apt
total_spent_apt
last_ledger_entry_id
rule_version
snapshot_id
updated_at
```

### Result [工作流对象]

```text
result_id
market_id
event_id
scores
outcome
status
confirmed_by
confirmed_at
evidence_ids[]
dispute_reason_code
correction_version
rule_version
snapshot_id
created_at
updated_at
```

### RefundCase [工作流对象]

```text
refund_id
market_id
batch_size
principal_total_apt
service_fee_total_apt
status
approved_by
executed_at
ledger_batch_ids[]
reason_code
case_id
approval_id
rule_version
snapshot_id
created_at
```

### CorrectionCase [工作流对象]

```text
correction_id
market_id
result_id_old
result_id_new
settlement_ids_old[]
settlement_ids_new[]
status
approved_by
executed_at
ledger_reversal_ids[]
ledger_new_ids[]
case_id
approval_id
evidence_ids[]
rule_version
snapshot_id
created_at
```

### OtcTrade [持久领域实体]

```text
trade_id
otc_order_id
buyer_user_id
seller_user_id
quantity_apt
price_apt
fee_apt
power_consumed
status
ledger_entry_ids[]
ledger_batch_id
created_at
```

### OtcEligibility [只读聚合/Projection — 每个请求/用户评估结果，非持久实体]

```text
allowed
buy_allowed
sell_allowed
reason_code
reason_text_key
next_action
policy_version
rule_version
capacity
power_impact
expires_at
as_of
```

约束：

- 这不是持久领域实体，是每次请求/上下文计算的资格投影。
- **不得定义成 `OtcEligibility.status = 七选一` 状态机**。
- `reason_code` 至少能表达：`KYC_REQUIRED` / `SECURITY_VERIFICATION_REQUIRED` / `OTC_CAPACITY_INSUFFICIENT` / `INSUFFICIENT_POWER` / `UNDER_REVIEW` / `REGION_UNAVAILABLE` / `MAINTENANCE`。
- `reason_code` 不得覆盖 `OtcOrder.status`。

### OtcCapacity [只读聚合/Projection]

```text
direction
user_remaining_capacity
global_remaining_capacity
reserve_ratio
as_of
next_refresh_at
rule_version
parameter_release_id
```

### ApprovalRequest [工作流对象]

```text
approval_id
request_type
request_object_type
request_object_id
status
submitted_by
submitter_role
assigned_to
decided_by
decided_at
reason_key
changes_requested_reason
execution_id
case_id
created_at
updated_at
```

### ParameterRelease [工作流对象]

```text
release_id
parameter_keys[]
status
draft_version
approved_by
scheduled_at
activated_at
paused_at
rolled_back_at
archived_at
monitoring_job_id
snapshot_id
case_id
audit_event_ids[]
created_at
updated_at
```

### RiskCase [工作流对象]

```text
case_id
user_id
risk_type
severity
status
detected_at
detected_by
reviewed_by
disposition
disposition_reason_key
restrictions[]
appeal_eligible
created_at
updated_at
```

### Ticket [工作流对象]

```text
ticket_id
user_id
category
status
assigned_to
last_activity_at
resolution_type
resolution_summary_key
appeal_eligible
ticket_message_ids[]
case_id
created_at
updated_at
```

### TicketAttachment [值对象]

```text
attachment_id
ticket_id
ticket_message_id
file_type
file_url
file_hash
uploaded_by
created_at
```

### TicketMessage [值对象]

```text
message_id
ticket_id
sender_role
body_key
attachments[]
created_at
```

### AuditLog [只读聚合/Projection]

```text
audit_event_id
event_code
actor_id
actor_role
target_object_type
target_object_id
before_snapshot_id
after_snapshot_id
outcome
reason_code
request_id
approval_id
case_id
created_at
```

### MfaEnrollment [持久领域实体]

```text
enrollment_id
user_id
method_type
status
enrolled_at
last_verified_at
backup_codes_active
device_info
created_at
updated_at
```

### SecurityProfile [只读聚合/Projection]

```text
user_id
mfa_enrolled_methods[]
mfa_required_actions[]
login_history_window
suspicious_flags
last_password_change
last_security_review
policy_version
as_of
```

### SessionDevice [只读聚合/Projection]

```text
session_id
device_fingerprint
os
browser
ip
location_region
last_active_at
is_current
revocable
```

### LoginAudit [只读聚合/Projection]

```text
audit_id
user_id
event_type
ip_address
device_fingerprint
outcome
failure_reason_code
challenge_type
created_at
```

### Settlement [工作流对象]

```text
settlement_id
market_id
batch_id
status
principal_total_apt
reward_total_apt
service_fee_total_apt
ledger_batch_id
approved_by
executed_at
rule_version
parameter_release_id
snapshot_id
created_at
```

### SettlementBatch [工作流对象]

```text
batch_id
status
market_count
order_count
settlement_ids[]
total_principal_apt
total_reward_apt
total_service_fee_apt
executed_at
rule_version
created_at
```

### SettlementMethod [值对象/只读聚合]

```text
method_id
user_id
currency
method_type
is_default
verification_status
created_at
updated_at
```

### Notice [只读聚合/Projection]

```text
notice_id
user_id
notice_type
title_key
body_key
priority
related_object_type
related_object_id
read_state
content_version
locale
created_at
expires_at
```

### NotificationDelivery [工作流对象]

```text
delivery_id
notice_id
channel
delivery_status
dedupe_key
attempt_count
last_attempt_at
next_retry_at
delivered_at
failure_reason_code
```

### ConsentReceipt [持久领域实体]

```text
receipt_id
user_id
consent_type
consent_version
content_hash
status
agreed_at
expires_at
policy_version
created_at
```

### ParameterSnapshot [只读聚合/Projection]

```text
snapshot_id
release_id
parameter_keys[]
parameter_values
version
created_at
created_by
```

### RobotUpgradeOrder [工作流对象]

```text
upgrade_order_id
robot_id
user_id
from_level
to_level
apt_cost
status
power_cap_after
capacities_after
cooling_end_at
review_case_id
approval_id
ledger_entry_id
rule_version
parameter_release_id
created_at
updated_at
```

## 4. 统一状态机

### User
`active / restricted / suspended / closed`

### KYC
`not_started / pending / needs_info / approved / rejected / review`

### Robot
`inactive / active / cooling / review / restricted / paused`

### AI Reward
`candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed`

### Ledger
`pending / posted / reversed / disputed`

### Market
`draft / open / closing / locked / awaiting_result / settlement / settled / void / exception`

### Result
`provisional / official / disputed / corrected`

### Settlement
`queued / calculating / review / payable / paid / failed`

### SettlementBatch
`created / processing / completed / partially_failed / failed`

### RefundCase
`pending / approved / executing / completed / rejected / failed`

### CorrectionCase
`pending / approved / executing / completed / rejected / failed`

### OtcTrade
`completed`

> OtcTrade 是 append-only 成交事实（单态）。争议/冲正不覆盖 Trade，走 RiskCase + ledger reversal；OtcOrder 的争议结果作用在 OtcOrder 而非 OtcTrade。

### RobotUpgradeOrder
`pending / processing / completed / failed / cancelled`

### ConsentReceipt
`active / expired`

> `consent_version` + `content_hash` 表达版本演进；「撤回/取代」不新增状态值，由新版本 receipt 表达。到期为唯一终态。

### Prediction Order
`submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected`

### OTC
`draft / review / matching / partial / completed / cancelled / expired / rejected / disputed`

约束：
- `completed` 才表示完整成交完成。
- `cancelled` 是用户或系统主动取消。
- `expired` 是按订单有效期自然到期；订单未被取消，是有效期结束。
- `partial + cancelled/expired` 只释放 remaining 部分，已成交部分不受影响。
- `disputed` 保持相关冻结，直到明确处置结果。
- 不删除或覆盖历史 Trade、APT Ledger、Power Ledger 记录。

### Ticket
`submitted / in_progress / waiting_user / under_review / resolved / closed`

### Approval
`draft / pending / changes_requested / approved / rejected / executing / executed / failed`

### Parameter Release
`draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived`

### OTC 运营/用户展示映射

以下展示映射不新增领域状态，canonical enum 以 §4 OTC 为准。

| canonical code | 运营/用户显示名 | 运营/用户看见什么 | 可执行操作 | 系统约束 | 下一步 |
|---|---|---|---|---|---|
| `draft` | 草稿 | 订单已创建，尚未提交 | 编辑/提交/删除 | 不可撮合 | 提交或放弃 |
| `review` | 审核中 | 订单需审核 | 查看详情 | 审核通过前不可撮合 | 等待审核 |
| `matching` | 撮合中 | 正在撮合 | 可取消 | 撮合中不可修改 | 等待成交或取消 |
| `partial` | 部分成交 | 已成交 X APT，剩余 Y APT | 可取消剩余部分 | remaining 释放规则见 §4 | 继续撮合或取消剩余 |
| `completed` | 已完成 | 全部成交完成 | 查看 Trade/Ledger | 不可取消 | 查看交易明细 |
| `cancelled` | 已取消 | 订单已被主动取消 | 查看详情 | 未成交部分释放 | 可重新挂单 |
| `expired` | 已到期 | 订单有效期结束，未成交 | 查看详情；重新挂单 | 剩余部分按规则释放；不是用户主动 Cancelled | 如需继续可重新创建 |
| `rejected` | 已驳回 | 审核未通过 | 查看原因 | 不可重新提交同订单；保留历史 | 按原因修正后重挂 |
| `disputed` | 争议中 | 存在争议，冻结中 | 争议处置 | 保持冻结直到明确结果 | 等待处置 |

---

### OTC 固定风险提示

- 以下页面/操作必须展示 OTC 风险说明：挂买确认前、挂卖确认前、OTC 资格提示。
- 对应 I18N key 进入 `/i18n`，需同时加入 `sensitive-copy-review.json`，状态保持 `PENDING_HUMAN_REVIEW`，直到 Product/Legal/Compliance Owner 人工签核。
- 每条风险提示带 `content_version`，用户必须主动确认当前版本。
- 本文件不把未签核文案写成"最终合规固定文案"。

---

### Prediction 聚合展示映射

以下映射是 API/UI projection，不是可写状态。Canonical Market、Result、Settlement、PredictionOrder enum 以 §4 为准。

| 用户显示状态 | 权威来源 | 说明 |
|---|---|---|
| 可参与 | `Market=open` | 用户端"可参与"是 Market open 的展示缩写 |
| 即将锁定 | `Market=closing` | lock_at 前促使用户行动 |
| 已锁定 | `Market=locked` | 不可新参与 |
| 等待结果 | `Market=awaiting_result` 且 Result 尚未 official | 不等于结算完成 |
| 结算处理中 | `Market=settlement` 或 Settlement `queued/calculating/payable` | 比赛结果 ≠ 结算完成 |
| 已结算 | Settlement=`paid` | 不能只看 Result official |
| 异常处理中 | Market `exception` / Result `disputed` / Settlement `review/failed` | 可能多个对象同时异常 |
| 已作废/已取消 | Market `void` + `reason_code` | 赛事取消只是 void 原因之一 |

明确：
- Result official 不等于 Settlement 完成。
- Market settled、Order settled、Settlement paid 必须按对象分别解释。
- 03/04 不得用一个"赛事状态"覆盖所有状态轴。

PredictionOrder 只使用当前 9 个 canonical 状态：
`submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected`

不将 `草稿`（属于 PredictionDraft 或本地页面状态）、`已拒绝`（属于提交请求/审核/Appeal）混入 PredictionOrder status。

---

### Approval 运营/后台展示映射

| canonical code | 显示名 | 审批人/操作人看见什么 | 可执行操作 | 系统约束 | 下一步 |
|---|---|---|---|---|---|
| `draft` | 草稿 | 创建人可见，未提交 | 编辑/提交/删除 | 不进入审批队列 | 提交或放弃 |
| `pending` | 待审批 | 已提交，等待审批人 | 可请求修改/批准/驳回/转派 | 审批人不能是自己的申请的审批人 | 等待审批决定 |
| `changes_requested` | 要求修改 | 审批人要求修改 | 申请人修改后重新提交 | 修改不篡改原审批记录 | 修改并重新提交 |
| `approved` | 已批准 | 批准已完成 | 触发执行 | 批准后自动或手动进入执行 | 等待执行 |
| `rejected` | 已驳回 | 审批否绝 | 不可恢复原申请；重新提交需新 Approval | 保留历史 | 重新提交或结束 |
| `executing` | 执行中 | 系统/人工正在执行 | 监控 | 不可修改 | 等待完成 |
| `executed` | 已执行 | 执行完成 | 查看审计 | 不可撤销 | 查看结果 |
| `failed` | 执行失败 | 执行异常 | 查看原因/重试/升级 | 保留失败记录 | 根据原因升级或重试 |

约束：
- Approval 的回滚不通过修改旧 Approval 状态完成，必须形成新的执行/审批对象和审计链。
- `executed` ≠ `executing`：`executing` 是进行中的执行，`executed` 是已完成。

---

### ParameterRelease 运营/后台展示映射

| canonical code | 显示名 | 审批人/操作人看见什么 | 可执行操作 | 系统约束 | 下一步 |
|---|---|---|---|---|---|
| `draft` | 草稿 | 参数编辑人可见，未提审 | 编辑/删除 | 不进入发布流程 | 提审 |
| `pending_approval` | 待批准 | 已提审，等待批准 | 可批准/驳回/要求修改 | 批准人与编辑人分离 | 等待批准 |
| `approved` | 已批准 | 已批准，等待调度 | 排期/激活 | 批准后不可修改参数值 | 排期或激活 |
| `scheduled` | 已排期 | 已设置激活时间 | 取消排期/提前激活 | 到期自动激活 | 等待激活或取消 |
| `active` | 已生效 | 当前活跃规则 | 暂停/回滚 | 活跃参数不可直接修改 | 监控中 |
| `paused` | 已暂停 | 临时停用 | 恢复/回滚 | 暂停不删除历史 | 恢复或回滚 |
| `rolled_back` | 已回滚 | 回滚到上一版本 | 不可再次激活 | 保留审计链和新 Snapshot | 新建替代版本 |
| `archived` | 已归档 | 历史版本 | 仅供查看 | 不可激活 | 仅供审计查询 |

约束：
- `approved` ≠ `active`：批准后可以排期延迟生效。
- "监控中"可以是发布后观察阶段或运维任务，但不属于当前 ParameterRelease enum。
- 04 的页面 Variant 不替代 05 的 canonical enum。

---

### Notice 与 NotificationDelivery 基本规则

#### 设计原则

1. **领域业务提交和通知投递不是同一个事务结果**。业务已成功、通知失败时，业务状态不能回滚。
2. 通知通过 Outbox/异步投递重试。
3. 重试不能重复生成多条等价 Notice（去重 key）。
4. 通知必须可以深链到关联对象。
5. 无权限或关联对象失效时，仍可安全查看通知正文，但不能泄露对象数据。
6. 高风险详情不能直接泄露内部风控规则。

#### Notice 字段说明

| 字段 | 说明 |
|---|---|
| `notice_id` | 通知唯一 ID |
| `user_id` | 目标用户 |
| `notice_type` | 通知事件类型（如 ROBOT_UPGRADE, KYC_UPDATE, OTC_ORDER, MARKET_SETTLEMENT, RISK_ACTION） |
| `title_key` | I18N 标题 key |
| `body_key` | I18N 正文 key |
| `priority` | 优先级（INFO/WARNING/CRITICAL） |
| `related_object_type` | 关联对象类型，用于生成深链 |
| `related_object_id` | 关联对象 ID |
| `read_state` | 已读/未读 |
| `content_version` | 文案版本，用于重新确认 |
| `locale` | 生成时的 locale |
| `created_at` | 创建时间 |
| `expires_at` | 过期时间 |

#### NotificationDelivery 字段说明

| 字段 | 说明 |
|---|---|
| `delivery_id` | 投递唯一 ID |
| `notice_id` | 关联 Notice |
| `channel` | 渠道（PUSH/EMAIL/SMS/IN_APP） |
| `delivery_status` | 投递状态 |
| `dedupe_key` | 去重 key |
| `attempt_count` | 尝试次数 |
| `last_attempt_at` | 最后尝试时间 |
| `next_retry_at` | 下次重试时间 |
| `delivered_at` | 投递成功时间 |
| `failure_reason_code` | 失败原因 |

#### 安全 reason mapping

KYC、Robot、OTC、Prediction、风险限制等通知正文中不直接暴露内部原因 code，使用安全 reason mapping：
- 通知可见文案使用 I18N key 映射，不暴露 raw reason_code。
- 通知正文不包含内部风控规则、模型参数或他人数据。

---

## 5. Allowed Actions

前端按钮只读：

```json
{
  "allowed_actions": [
    {"action":"ROBOT_UPGRADE","allowed":false,"reason_code":"COOLING"},
    {"action":"REWARD_CLAIM","allowed":true},
    {"action":"OTC_CANCEL","allowed":false,"reason_code":"PARTIAL_SETTLEMENT_RUNNING"}
  ]
}
```

不要用前端 `if level > 20`、`if balance > x` 自己推导正式资格。

## 6. 关键正式 API（沿用已整理逻辑）

### AI / Robot

```text
GET  /ai/users/{id}/summary
GET  /ai/users/{id}/eligibility
GET  /ai/users/{id}/capacity
GET  /ai/users/{id}/yield-coefficient
GET  /ai/robots
GET  /ai/robots/{robot_id}
GET  /ai/users/{id}/upgrade-eligibility
POST /ai/users/{id}/upgrade-orders
GET  /ai/users/{id}/upgrade-orders/{upgrade_order_id}
GET  /ai/users/{id}/rewards
POST /ai/users/{id}/reward-claims
GET  /ai/users/{id}/reward-claims/{claim_id}
GET  /ai/users/{id}/computing-power
GET  /ai/users/{id}/computing-power-ledger
```

Robot Start/Stop 新增：

```text
POST /api/v1/ai/robots/{robot_id}/actions
GET  /api/v1/ai/robots/{robot_id}/actions/{action_id}
```

所有需要 Power 的写操作必须遵守统一响应合同：

```text
Preview / Quote response:
  power_impact: PowerImpactPreview

Final / Processing response:
  power_effect:
    consumed
    frozen
    released
    ledger_entry_ids[]
    rule_version
    snapshot_id
```

- Robot Start：现有 Robot Action API 返回 `power_impact`。
- OTC Sell：现有 OTC Quote/Create Order API 返回 `power_impact`。
- Withdrawal：未来/现有 Withdrawal Action API 必须返回同一 `power_impact`；本文件不凭空指定未冻结的 URL 路径。


### Prediction

```text
GET  /markets
GET  /markets/{id}
GET  /markets/{id}/disclosure
POST /consent-receipts
POST /orders
POST /orders/{id}/additions
GET  /orders/{id}/receipt
GET  /settlements/{id}
GET  /refunds/{id}
POST /appeals
GET  /appeals/{id}
```

Admin：

```text
POST /markets
POST /markets/{id}/publish
POST /markets/{id}/lock-evaluations
POST /markets/{id}/results
POST /settlement-batches
POST /refunds
POST /corrections
```

### APT / OTC

```text
GET  /users/{id}/asset-ledger
GET  /otc/order-book
POST /otc/orders
GET  /otc/orders/{id}
POST /otc/orders/{id}/cancel
GET  /otc/trades
GET  /otc/users/{id}/orders
```

统一 quote 推荐新增：

```text
POST /api/v1/otc/quotes
```

### Policy / Parameter / Admin

```text
POST /policy/evaluations
GET  /policy/evaluations/{id}
GET  /parameter-definitions
POST /parameter-candidates
POST /parameter-releases
POST /parameter-releases/{id}/activate
GET  /parameters/snapshots/{id}
POST /admin/cases
POST /approval-tasks/{id}/decisions
GET  /audit-log
GET  /async-jobs/{id}
POST /export-tasks
```

## 7. 错误分类

前端至少能区分：

```text
VALIDATION_ERROR          400
AUTH_UNAUTHENTICATED      401
AUTH_FORBIDDEN            403
KYC_REQUIRED              403
POLICY_DENIED             403
FEATURE_CLOSED            403
CONSENT_VERSION_MISMATCH  409
IDEMPOTENCY_CONFLICT      409
OBJECT_VERSION_CONFLICT   409
QUOTE_EXPIRED             409
INSUFFICIENT_APT          422
INSUFFICIENT_POWER        422
MARKET_LOCKED             422
DEPENDENCY_UNAVAILABLE    503
RESULT_UNKNOWN            202
```

`RESULT_UNKNOWN` 的处理不是提示用户重试创建，而是**用原 Idempotency-Key 查询原请求结果**。

## 8. RBAC / ABAC 最小角色

```text
END_USER
SUPPORT_AGENT
OPS_OPERATOR
KYC_REVIEWER
RISK_ANALYST
RISK_APPROVER
LEDGER_OPERATOR
FINANCE_REVIEWER
PARAM_EDITOR
PARAM_APPROVER
RELEASE_OPERATOR
AUDITOR
ADMIN_SECURITY
```

高风险职责分离：
- 参数编辑 ≠ 参数批准 ≠ 参数激活。
- 风险分析 ≠ 高危处置批准。
- 更正申请 ≠ 更正批准。
- Result 确认 ≠ Settlement 批准。
- 申请人不能审批自己的申请。

## 9. 前端数据规则

- Asset/Power/Reward/Settlement 最终数字全部服务端返回。
- Power Cap、恢复量、Withdrawal/Robot Start Power Impact 全部服务端返回；客户端只展示 Preview 与最终 Ledger 结果。
- Mock 数据绝不作为 production fallback。
- 页面恢复/网络重连后重新查对象终态。
- 不把按钮点击埋点当业务成功。

## 10. 数据新鲜度契约

所有请求的响应中需要包含数据元信息：

| 字段 | 说明 |
|---|---|
| `data_status` | 数据状态：`REALTIME` / `NEAR_REALTIME` / `STALE` / `UNAVAILABLE` |
| `as_of` | 数据反映的截止时间点 |
| `updated_at` | 数据最后更新时间 |
| `next_refresh_at` | 预期下次刷新时间（可 null） |
| `refresh_hint` | 刷新建议文案 I18N key |
| `stale_after` | 超过该时长数据视为陈旧（可 null/TBC） |
| `snapshot_id` | 关联快照 ID |
| `source_status` | 数据源状态 |

规则：
- 实际刷新频率、陈旧阈值如尚未生产批准，进入 06 并保持 TBC/null。
- 不在本文档中虚构生产刷新秒数。
- 页面恢复/网络重连后重新查对象终态，不依靠本地缓存。

## 11. RBAC / ABAC 补充

### 11.1 权限层级

在现有最小角色基础上补充：

- **页面权限不等于字段权限**。某角色能访问 Admin 某页面，不代表能看到该页面所有字段。
- **字段权限不等于数据范围权限**。能看 User 的某字段，不等于能看所有 User。
- **申请人不得审批本人申请**（已确认）。
- **Result confirmer 与 Settlement approver 分离**（已确认）。
- **Parameter editor、approver、release operator 分离**（已确认）。

### 11.2 紧急操作与事后复核

- 紧急操作只能由预授权角色执行（不在本文档预先声称具体角色列表，参数进入 06）。
- 影响资产、账本、资格、参数或结算的紧急操作默认仍需双人授权。
- 每笔紧急操作必须有：`case_id`、理由、影响范围、执行人、时间、审计记录和恢复方案。
- "先执行后补审"的场景必须被明确列举（在已批准的紧急操作矩阵中），不能作为万能绕审批通道。
- 事后补审失败或超时必须升级异常任务（生成 RiskCase / 升级 Ticket）。
- **超级管理员仍不能绕过账本、审批或审计规则**。

### 11.3 全局角色最小保证

```text
END_USER           — 无权访问 Admin 和审批操作
SUPPORT_AGENT      — 可读用户摘要，不可写资产
OPS_OPERATOR       — 可执行已批准操作，不可修改参数
KYC_REVIEWER       — 可审批 KYC case，不可接触资产/Trade
RISK_ANALYST       — 可发起/分析 RiskCase，不可批准高危处置
RISK_APPROVER      — 可批准处置，不可直接执行资产变更
LEDGER_OPERATOR    — 可执行已批准账本操作，不可单独创建新规则
FINANCE_REVIEWER   — 可读全部 Ledger/对账、不可写
PARAM_EDITOR       — 可编辑参数草稿，不可批准或激活
PARAM_APPROVER     — 可批准参数，不可编辑或激活
RELEASE_OPERATOR   — 可排期和激活参数，不可编辑或批准
AUDITOR            — 可读全部审计、不可执行任何变更
ADMIN_SECURITY     — 可管理角色/权限/安全配置，不可接触资产或业务数据
```

所有角色都不具备绕过账本、审批、审计或职责分离的能力。
