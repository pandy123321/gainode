# Design: S01-P06 非持久投影服务

## 状态

- **执行授权**：CR-20260816-003 OPTION_A（一开到底；门禁降级为 Quality 验证项）
- **冻结状态**：投影依赖的规则参数多 TBC，默认 deny；已知内容 best-effort 实现
- **NOT_PERSISTED**：7 对象全部禁止建表

## 公共基类（复用 support/extend，不另造根目录）

```text
support/extend/ProjectionResponse.php — 数据新鲜度元数据（05 §10 8 字段）+ toArray()
support/extend/ProjectionService.php  — data_status/source_status 常量 + 默认 deny 判定
```

### data_status 枚举（05 §10）

```text
REALTIME / NEAR_REALTIME / STALE / UNAVAILABLE
```

### source_status 枚举（本包定义，05 §10 未冻结枚举值）

```text
READY / UNAVAILABLE / PARTIAL
```

## 7 个投影的字段表 + source-of-truth + 读取顺序 + 默认 deny

### 1. FeatureEntitlement（entitlement 域）

DTO 字段（05 §3 + 07 步骤 4 的 allowed_actions）：

```text
feature_key         string  必填（输入）
allowed             bool    deny 时 false
reason_code         string
reason_text_key     string  I18N key
allowed_actions[]   array   07 步骤 4 要求，05 §3 缺失 → Contract Gap（见下）
policy_version      string  未冻结 → null
rule_version        string  未冻结 → null
expires_at          int     未冻结 → null
```

source-of-truth 读取顺序：

```text
1. feature 规则参数（06 Feature 段）→ TBC
2. robots（Robot level → capability 映射）→ MC1 已建，但映射规则依赖 AI.power_cap_by_robot_level（TBC）
```

默认 deny：feature 规则未冻结 → `allowed=false`、`data_status=UNAVAILABLE`、`source_status=UNAVAILABLE`、`reason_code=FEATURE_RULE_UNAVAILABLE`。

### 2. OtcEligibility（otc 域）

DTO 字段（05 §3）：

```text
allowed / buy_allowed / sell_allowed / reason_code / reason_text_key / next_action /
policy_version / rule_version / capacity / power_impact / expires_at / as_of
```

source 读取顺序：

```text
1. kyc_cases（2B-2 已建）→ KYC 状态可读；KYC_REQUIRED 可确定性 deny
2. otc 参数（06：fee/limit/库存）→ TBC
3. power_positions（MC1 已建）→ 可读，但 Power 资格阈值依赖 AI.power_*（TBC）
```

reason_code 枚举（05 §3 冻结）：`KYC_REQUIRED / SECURITY_VERIFICATION_REQUIRED / OTC_CAPACITY_INSUFFICIENT / INSUFFICIENT_POWER / UNDER_REVIEW / REGION_UNAVAILABLE / MAINTENANCE`。

默认 deny 顺序：

```text
1. KYC 未通过（kyc_cases 可读且明确非 approved）→ allowed=false, reason_code=KYC_REQUIRED（确定性，data_status=REALTIME）
2. OTC/Power 参数 TBC → allowed=false, reason_code=MAINTENANCE, data_status=UNAVAILABLE
```

### 3. OtcCapacity（otc 域）

DTO 字段（05 §3）：`direction / user_remaining_capacity / global_remaining_capacity / reserve_ratio / as_of / next_refresh_at / rule_version / parameter_release_id`。

source 读取顺序：

```text
1. otc.settlement_reserve_ratio / otc.risk_reserve_ratio（06）→ TBC
2. otc.inventory_limit（06）→ TBC
3. otc_orders（MC1 已建，但未消费冻结转移矩阵）→ 只读不聚合
```

默认 deny：`reserve_ratio`/`*_remaining_capacity` 均依赖 TBC 参数 → `data_status=UNAVAILABLE`、`source_status=UNAVAILABLE`、字段 null（不回退旧值）。

### 4. PowerImpactPreview（power 域）

DTO 字段（05 §3）：

```text
action_type / required_power / freeze_power / consume_power / release_power /
available_before / available_after_preview / frozen_before / frozen_after_preview /
robot_level / power_cap / rule_version / parameter_release_id / snapshot_id /
expires_at / allowed / reason_code
```

source 读取顺序：

```text
1. power_positions（MC1 已建）→ available_before/frozen_before 可读（REALTIME）
2. AI.power_cap_by_robot_level / AI.power_action_consumption_profile（06）→ TBC
   → required_power/freeze/consume/release/power_cap/available_after_preview 全部 UNAVAILABLE
```

默认 deny：Power 动作消耗/上限参数 TBC → `allowed=false`、`reason_code=POWER_RULE_UNAVAILABLE`、`data_status=UNAVAILABLE`（保留 available_before/frozen_before 实时读数，但 `allowed=false`）。

### 5. SecurityProfile（auth 域）

DTO 字段（05 §3）：`user_id / mfa_enrolled_methods[] / mfa_required_actions[] / login_history_window / suspicious_flags / last_password_change / last_security_review / policy_version / as_of`。

source 读取顺序：

```text
1. mfa_enrollments（2B-2 已建）→ mfa_enrolled_methods 可读（REALTIME）
2. auth_sessions（2B-2 已建）→ 会话历史可读
3. user（V1.x member_user 表）→ last_password_change 可读（best-effort）
4. 安全策略参数（06）→ TBC → login_history_window/suspicious_flags/mfa_required_actions UNAVAILABLE
```

脱敏：跨用户访问 → 不泄露存在性，返回安全 reason（`reason_code=FORBIDDEN`），不返回任何字段。

### 6. SessionDevice（auth 域）

DTO 字段（05 §3）：`session_id / device_fingerprint / os / browser / ip / location_region / last_active_at / is_current / revocable`。

source 读取顺序：

```text
1. auth_sessions（2B-2 已建）→ device_info JSON（os/browser/ip 可读）、last_active_at（updated_time）
2. device_fingerprint / location_region → device_info 中缺失 → null（PARTIAL）
```

脱敏：仅会话归属用户或 SUPPORT_AGENT（05 §11.3 可读用户摘要）可读；越权返回 FORBIDDEN。

### 7. LoginAudit（auth 域）

DTO 字段（05 §3）：`audit_id / user_id / event_type / ip_address / device_fingerprint / outcome / failure_reason_code / challenge_type / created_at`。

source-of-truth：**05 未明确**（V1.x `user_logs` 表 vs MC2 `audit_events` 表）→ **Contract Gap**。

默认 deny：source 未裁决 → `data_status=UNAVAILABLE`、`source_status=UNAVAILABLE`，返回空列表 + reason，不读取 V1.x 表（避免依赖未裁决 schema）。

## 关键不变量

```text
NOT_PERSISTED = YES（7 对象，无任何 DDL）
NO_MOCK_FALLBACK = YES（TBC/null 不回退旧值，不填 mock）
DEFAULT_DENY = YES（未冻结依赖 → UNAVAILABLE + allowed=false / 字段 null）
DECIMAL_STRING = YES（capacity/power 数值 string，禁 float）
METADATA_8_FIELDS = YES（每个响应含 05 §10 8 字段）
CROSS_USER_ACCESS = FORBIDDEN（SecurityProfile/SessionDevice/LoginAudit）
CONSUMED_UNFROZEN_CONTRACT = YES
OPEN_OWNER_DECISION = YES（LoginAudit source + FeatureEntitlement allowed_actions）
```

## Contract Gap / Decision Request（交接，不阻塞实现）

| # | 对象 | Gap | 建议 |
|---|---|---|---|
| G1 | LoginAudit | source-of-truth 未明确（user_logs vs audit_events） | Owner 裁决：复用 MC2 audit_events（event_type 扩展）或 V1.x user_logs |
| G2 | FeatureEntitlement | allowed_actions 字段在 05 §3 缺失（07 步骤 4 要求输出） | Owner 裁决：补 05 §3 或降级为派生字段 |
| G3 | OtcEligibility.capacity | capacity 结构未在 05 明确 | 默认 null，待 06 OTC 参数冻结 |

## 测试策略（tests/projection/，独立 CLI 脚本，SQLite in-memory）

复用 S01-P03 的 LedgerAppendOnlyMutationMatrixTest 风格（无需 PHPUnit）：

- REALTIME 路径：source 可读时正确聚合（如 SessionDevice 读 auth_sessions）。
- UNAVAILABLE 路径：依赖 TBC 时默认 deny（如 OtcCapacity、PowerImpactPreview）。
- 跨用户访问：SecurityProfile/SessionDevice/LoginAudit 越权 → FORBIDDEN，不泄露存在性。
- 无 mock：断言 TBC 字段为 null、不回退旧值。
