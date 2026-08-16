# 06 · Gainode 参数字典

> 版本：V2.2 · OTC Reserve / Expiry / Notice / Refresh / Treasury Closure
> 定位：开发参考 + Admin Parameter Center 基线
> 关键原则：**结构规则可以锁定，生产参数没批准就必须是 null/closed；不能为了开发方便偷偷写默认生产值。**

> 本文件只定义可版本化参数。参数值如未获生产批准，一律保持 TBC/null，不得自行填入默认值或从旧文档推测。

## 1. 参数生命周期

```text
Definition
→ Candidate
→ Simulation
→ Approval
→ Immutable Release
→ Scheduled / Active
→ Pause / Rollback
```

- 保存 Candidate 不生效。
- Approved 也不代表 Active。
- Active Release 才能供生产解析。
- 历史订单使用自己的 snapshot，不用当前参数回算。

## 2. 参数类型

| 类型 | 意义 | 例子 |
|---|---|---|
| LOCKED | 业务语义，参数中心不能改变 | 1X2 三方向；订单确认后不可换向 |
| STATIC | 很少变，改动要更严格 | APT max supply、Market Template 结构 |
| DYNAMIC | 可版本化调整 | fee、limits、lock offset |
| TBC | 生产未定 | 正式地区年龄、AI coefficient source |

## 3. 参数公共字段

```text
parameter_key
type
value_type
unit
scope_type
scope_id
candidate_value
production_value
status
release_id
effective_at
expires_at
min/max or validation_schema
dependency_keys[]
failure_behavior
owner
approval_id
```

## 4. AI / Robot

| key | 状态 | 生产处理 | 人话备注 |
|---|---|---|---|
| `AI.standard_capacity_rule_version` | TBC/Release | 无有效 Release 则真实 Reward/Upgrade 关闭 | 56级能力表来自版本，不写死前端 |
| `AI.daily_yield_coefficient_source` | TBC | Claim/Reward 关闭或只读 | 当日系数从哪里来 |
| `AI.daily_yield_coefficient_precision` | TBC | 不自行猜精度 | 0 是合法值 |
| `AI.ai_reward_budget_cap` | TBC | Reward posting closed | 当期最大 Reward 预算 |
| `AI.ai_reward_period_cap` | TBC | Reward posting closed | 周期上限 |
| `AI.ai_reward_hold_period` | TBC | HELD | 观察期 |
| `AI.ai_reward_expiry_period` | TBC | 不开放过期自动动作 | 待领取多久过期 |
| `AI.ai_reward_claim_enabled` | TBC/boolean | false | Claim 总开关 |
| `AI.ai_reward_clawback_window` | TBC | 无自动 clawback | 可纠错窗口 |
| `AI.ai_lifecycle_stage` | TBC | closed by stage | AI 当前阶段 |
| `AI.upgrade_apt_requirement` | TBC | Upgrade closed | 各等级升级成本/要求 |
| `AI.upgrade_allocation_profile` | TBC | 只允许 sandbox | 升级 APT 去向结构 |
| `AI.computing_power_requirement` | TBC | OTC sell closed if needed | Power 要求 |
| `AI.computing_power_consumption_rule` | TBC | 不执行消费 | 成交怎么消耗 Power |
| `AI.computing_power_release_rule` | TBC | 不执行自动释放 | 取消/过期怎么释放 |
| `AI.power_cap_by_robot_level` | TBC/Release | 无有效 Release 时不允许前端推导 | Robot Level → Power Cap 映射 |
| `AI.power_restore_rule` | TBC/Release | 不显示正式恢复量/周期 | Power 如何恢复 |
| `AI.power_withdrawal_consumption_rule` | TBC/Release | Withdrawal 如依赖 Power 则 fail closed | 提现需要多少 Power、何时消耗 |
| `AI.power_robot_start_consumption_rule` | TBC/Release | Robot Start 如依赖 Power 则 fail closed | 启动 Robot / 自动执行能力需要多少 Power |
| `AI.power_action_consumption_profile` | TBC/Release | Action Preview unavailable / closed | 不同高价值动作的统一 Power Profile |

## 5. OTC

| key | 状态 | 失败关闭 |
|---|---|---|
| `OTC.order_min_amount` | TBC | Create order closed |
| `OTC.order_max_amount` | TBC | Create order closed |
| `OTC.inventory_limit` | TBC | Sell closed |
| `OTC.real_buy_volume_requirement` | TBC | Platform/market action closed |
| `OTC.partial_fill_enabled` | TBC | 按安全默认关闭部分成交新逻辑 |
| `OTC.fee_rate` | TBC | 不允许前端填默认手续费 |
| `OTC.market_pressure_limit` | TBC | 相关高风险动作 closed |
| `otc_inventory_min_coverage_days` | TBC | treasury/platform sale closed |
| `zero_buy_pressure_limit` | TBC | sale closed |
| `related_account_buy_ratio_limit` | TBC | review/deny |
| `platform_sale_share_limit` | TBC | sale closed |

## 6. Prediction P0

### Locked 结构

```text
template = FOOTBALL_PREMATCH_1X2
selections = HOME,DRAW,AWAY
result_scope = 90_MINUTES_PLUS_STOPPAGE
extra_time = EXCLUDED
penalties = EXCLUDED
post_submit_cancel = FORBIDDEN
post_submit_reduce = FORBIDDEN
post_submit_change_selection = FORBIDDEN
pre_lock_same_selection_addition = ALLOWED_IF_SERVER_ALLOWS
```

### Dynamic keys

| key | 作用 | 无有效值时 |
|---|---|---|
| `prediction_1x2_enabled` | P0 真值玩法开关 | closed |
| `market_creation_enabled` | 创建市场 | closed |
| `market_lock_offset` | 提前多久锁定 | 不发布市场 |
| `prediction_total_min_users` | 最低有效用户 | lock fail/refund path |
| `prediction_total_min_pool_apt` | 最低总池 | lock fail/refund path |
| `market_total_pool_cap` | 单场池上限 | deny new order |
| `service_fee_rate` | 服务费 | 不接真实订单 |
| `result_source_primary` | 主结果源 | settlement held |
| `result_source_secondary` | 备结果源 | conflict/review |
| `result_abnormal_wait_period` | 异常等待 | safe review state |
| `prediction_order_limit_profile` | 用户订单限额 | deny real order |
| `prediction_reward_settlement_coefficient` | Reward/settlement 参数 | settlement closed |

## 7. Policy / KYC / 保护

| key | 说明 | 默认 |
|---|---|---|
| `policy_default_deny` | 没配置时拒绝 | `true` |
| `policy_fail_closed` | 策略服务失败时关闭 | `true` |
| `policy_timeout` | 策略超时 | TBC |
| `policy_cache_ttl` | 安全缓存有效期 | TBC |
| `policy_evidence_max_age` | 地区/KYC证据有效期 | TBC |
| `age_minimum_by_region` | 地区年龄门槛 | `null` |
| `kyc_level_by_action` | 每动作 KYC 要求 | `null` |
| `cooling_off_period_options` | 冷静期 | `null` |
| `self_exclusion_min_period` | 自我排除最短期 | `null` |
| `responsible_single_limit` | 单次限额 | `null` |
| `responsible_daily_limit` | 日限额 | `null` |
| `responsible_weekly_limit` | 周限额 | `null` |
| `responsible_monthly_limit` | 月限额 | `null` |

## 8. APT / Migration

结构常量：

```text
APT_MAX_SUPPLY = 100,000,000,000 APT
APT_I_TO_APT_C_QUANTITY_MAPPING = 1:1
```

Migration keys：

| key | P0 |
|---|---|
| `AI.apt_migration_enabled` | false / closed |
| `AI.apt_migration_window` | null |
| `AI.apt_migration_finality_requirement` | null |

Prediction Reserve：

```text
prediction_reserve_allocated_quantity = 100,000,000 APT   # 受控预留参考
prediction_reserve_spendable_quantity = 0                 # 未通过 Gate 前
```

**人话备注：** "allocated" 只是预留位置，不是已经能花、能发、能卖。

## 9. Growth / Team（P1）

```text
prediction_first_gen_rate
prediction_second_gen_rate
prediction_team_pool_enabled
prediction_team_pool_rate
prediction_team_p3_share
prediction_team_p4_share
prediction_team_p5_share
prediction_team_p6_share
kol_reward_enabled
prediction_to_global_p_posting_enabled
prediction_contribution_coefficient
prediction_contribution_user_cap
prediction_contribution_team_cap
prediction_contribution_period_cap
```

P0 可以建 Definition，但不开正式功能。

Owner 签核（2026-08-16，S01-P07 D4/D8 OPTION_A）：
- 层级深度 ≤ 2 代（直推 + 二代）；team_pool 更深层级（p3~p6）留 STAGE-02 单独决策。
- 增长奖励资金来源 = 独立 growth treasury budget（走 ParameterRelease，禁用户本金/退款/Prediction 结算）。
- 佣金确认时点 = 平台收入确认后；回滚 = append-only reversal（复用 2B-1 CorrectionCase/RefundCase）。

## 10. Treasury / Finance

```text
cash_coverage_min_ratio
risk_reserve_min_ratio
treasury_realization_daily_cap
treasury_realization_monthly_cap
treasury_realization_region_enabled
burn_approval_thresholds
```

这些参数不应该出现在 C 端。

## 11. Prototype Mock 参数

可视化原型允许使用 mock，但必须满足：

```text
mock_value != production_value
Fixture Metadata / Developer Panel 明确标记 Demo/Mock/Sandbox
正式用户 UI 不得显示 Demo/Mock/Sandbox/模拟环境字样
前端代码 mock fixture 与 production config 分目录
生产 API 无值时不能 fallback 到 mock
```

不建议在开发基线里固定 OTC 市场价格、Robot 正式成本、正式 fee、正式年龄/KYC 国家值；这些由后续 Active Release 决定。

---

## 12. OTC 储备与风控参数

| 参数 key | 类型 | 默认/TBC | 说明 |
|---|---|---|---|
| `otc.settlement_reserve_ratio` | decimal | TBC | OTC 结算储备覆盖比例；如未批准保持 TBC |
| `otc.risk_reserve_ratio` | decimal | TBC | 风险储备比例；如未批准保持 TBC |
| `otc.order_expiry_seconds` | integer | TBC | OTC 订单默认有效时长（秒）；如未批准保持 TBC |
| `otc.max_order_ttl_seconds` | integer | TBC | OTC 订单最大有效期 |
| `otc.min_order_quantity_apt` | decimal_string | TBC | 最小挂单数量 |
| `otc.max_order_quantity_apt` | decimal_string | TBC | 最大挂单数量 |

---

## 13. Treasury 操作上限参数

| 参数 key | 类型 | 默认/TBC | 说明 |
|---|---|---|---|
| `treasury.daily_operation_limit_apt` | decimal_string | TBC | 日操作上限（APT 数量） |
| `treasury.monthly_operation_limit_apt` | decimal_string | TBC | 月操作上限（APT 数量） |
| `treasury.approval_threshold_apt` | decimal_string | TBC | 需要额外审批的阈值（APT 数量） |

---

## 14. 通知渠道与重试参数

| 参数 key | 类型 | 默认/TBC | 说明 |
|---|---|---|---|
| `notice.channel.push_enabled` | boolean | TBC | 推送渠道开关 |
| `notice.channel.email_enabled` | boolean | TBC | 邮件渠道开关 |
| `notice.channel.sms_enabled` | boolean | TBC | SMS 渠道开关 |
| `notice.retry.max_attempts` | integer | null | 最大投递重试次数；null = 无正式批准值 |
| `notice.retry.initial_delay_seconds` | integer | null | 初始重试延迟（秒） |
| `notice.retry.max_delay_seconds` | integer | null | 最大重试延迟（秒） |
| `notice.retry.backoff_multiplier` | decimal | null | 退避乘数 |
| `notice.ttl_seconds` | integer | TBC | 通知过期时间（秒） |

---

## 15. 数据刷新与陈旧阈值参数

| 参数 key | 类型 | 默认/TBC | 说明 |
|---|---|---|---|
| `data.refresh.apt_balance_seconds` | integer | null | APT 余额刷新周期；null = 无正式批准值 |
| `data.refresh.robot_status_seconds` | integer | null | Robot 状态刷新周期 |
| `data.refresh.market_status_seconds` | integer | null | Market 状态刷新周期 |
| `data.refresh.otc_order_book_seconds` | integer | null | OTC 订单簿刷新周期 |
| `data.stale.apt_balance_seconds` | integer | null | APT 余额陈旧阈值 |
| `data.stale.market_seconds` | integer | null | Market 数据陈旧阈值 |

---

## 16. 紧急操作参数

| 参数 key | 类型 | 默认/TBC | 说明 |
|---|---|---|---|
| `emergency.pre_authorized_roles` | string[] | TBC | 预授权紧急操作角色列表；如未批准不填入默认值 |
| `emergency.dual_authorization_required` | boolean | true | 高风险紧急操作是否默认需要双人授权 |
| `emergency.post_action_review_deadline_hours` | integer | null | 事后复核期限（小时）；null = 无正式批准值 |
| `emergency.escalation_on_review_failure` | boolean | true | 补审失败是否升级 |
| `emergency.bypass_scenarios` | string[] | TBC | 明确列举的"先执行后补审"场景；如未批准不填入默认值 |

---

> **约束**：06 只定义可版本化参数，不定义资金池、对象或业务状态。OTC 结算储备的定义见 02 §4.1；OtcOrder 状态见 05 §4；Notice 状态见 05 §4；Approval/ParameterRelease 状态见 05 §4。未获生产批准值时保持 TBC/null，不得自行填入默认值。
