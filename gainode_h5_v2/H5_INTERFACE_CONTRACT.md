# Gainode H5 2.0 前端接口契约（H5_INTERFACE_CONTRACT）

> 起稿：DEVELOPMENT-01
> 日期：2026-08-21
> 依据：`07 §8 S03-P02`、`05 §1/§6/§7/§10`、`rules/coding.md`、前端 `src/api/*.ts` 各领域 client。
> 用途：供 H5 前端同事按后端 OpenAPI DTO 口径逐页对接；后端按此清单补/改接口。
> 原则：字段以后端 OpenAPI DTO 为准；金额一律 string decimal；资格只读 `allowed_actions` / `FeatureEntitlement`，前端不本地推导；写操作需幂等 / SoD；`me` 占位路径待后端冻结后无需改调用层。

## 约定

- **base**：H5 前端经 `src/api/http.ts` 调用 `VITE_API_BASE_URL ?? ''` + `/api/v1/...`（OPTION_A，C 端应用组）。
- **六请求头**：`Authorization: Bearer <token>`、`Accept-Language`、`X-Request-Id`、`X-Timestamp`、`Idempotency-Key`（写操作自动生成）、`If-Match`（object_version 乐观锁）。
- **统一信封**：`Envelope<T>` = `request_id + data + FreshnessMeta(8 字段) + 写操作 WriteMeta`（见 `src/api/types.ts`）。
- **错误**：16 项统一 `ResultCode`（05 §7）；`RESULT_UNKNOWN(202)` 不得提示用户重试，需用原 `Idempotency-Key` 查询终态。
- **readiness**：`READY`（后端接口已存在）/ `PARTIAL`（只读存在、写路径 fail-closed 503）/ `NOT_BUILT`（后端待建）/ `CONTRACT_GAP`（契约未冻结）。

## 领域接口清单（按 `src/api/*.ts` client 提取）

### Auth / Session（M-AUTH-001..005，`auth.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `authApi.login` | POST `/api/v1/auth/login` | **READY** |
| `authApi.register` | POST `/api/v1/auth/register`（含 consent_version） | **READY** |
| `authApi.otpVerify` | POST `/api/v1/auth/otp/verify` | **READY** |
| `authApi.otpResend` | POST `/api/v1/auth/otp/resend` | **READY** |
| `authApi.mfaVerify` | POST `/api/v1/auth/mfa/verify` | **READY** |
| `authApi.recovery` | POST `/api/v1/auth/recovery` | **READY** |
| `authApi.passwordReset` | POST `/api/v1/auth/password/reset` | **READY** |
| `authApi.logout` | POST `/api/v1/auth/logout` | **READY** |

> refresh 由 `http.ts` single-flight 内部处理（`POST /api/v1/auth/refresh`），不对外暴露 refresh_token。
> 契约缺口：Auth 前端提交时 `consent_version` 需后端下发（`S03-P02-H5-AUTH` 相关 Finding 已登记）。

### User / 资格（M-ME-001 / M-HOME-001，`user.ts` + `kyc.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `userApi.me` | GET `/api/v1/me` | **READY** |
| `eligibilityApi.me` | GET `/api/v1/me/eligibility`（global_p / ai / prediction 三分支） | **READY** |

### KYC（M-KYC-001..003，`kyc.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `kycApi.kycMe` | GET `/api/v1/me/kyc` | **READY** |
| `kycApi.kycSubmit` | POST `/api/v1/me/kyc/submit`（attachment_refs 走后端签发引用） | **PARTIAL**（写路径 fail-closed） |

### 资产 / 账本（M-ASSET-001..003，`asset.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `assetApi.balance` | GET `/api/v1/me/asset`（APT 数量账余额投影） | **READY** |
| `assetApi.ledgerEntries` | GET `/api/v1/me/ledger-entries`（append-only 分录，时间倒序） | **READY** |

> 契约缺口：`/me/ledger-entries/{id}` 单笔详情无路径 → M-ASSET-003 由列表已拉取 entry 渲染；`Reference Valuation` 无冻结来源端点 → M-ASSET-001 不展示估值。

### Power（M-POWER-001，`power.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `powerApi.position` | GET `/api/v1/me/power`（Power 持仓，无状态机） | **READY** |

> 契约缺口：Power Ledger 7 日变化 / PowerImpactPreview 无冻结 DTO/路径 → M-POWER-001 不展示 7 日趋势，前端不自算 Power 影响。

### Robot / AI Reward（M-ROBOT-001..007，`robot.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `robotApi.summary` | GET `/api/v1/ai/users/me/summary` | **READY** |
| `robotApi.detail` | GET `/api/v1/ai/robots/{robot_id}`（含 allowed_actions） | **READY** |
| `robotApi.rewards` | GET `/api/v1/ai/users/me/rewards` | **READY** |
| `robotApi.upgradeOrder` | GET `/api/v1/ai/users/me/upgrade-orders/{id}` | **READY** |

> 只暴露只读端点。写操作（启停 / 升级 / 领奖）后端 fail-closed（无 Active Release → 503），前端不提供写方法，按钮由 `allowed_actions` 驱动。
> 契约缺口：`/ai/users/me/*` 为 `me` 占位（后端冻结 `me` 语义后无需改调用层）。

### Prediction（M-PREDICT-001..006，`prediction.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `predictionApi.markets` | GET `/api/v1/markets` | **READY** |
| `predictionApi.marketDetail` | GET `/api/v1/markets/{id}` | **READY** |
| `predictionApi.orderReceipt` | GET `/api/v1/orders/{id}/receipt` | **READY** |

> 只暴露只读端点。写操作（order_create / order_addition / appeal_create）后端 fail-closed → 503，前端不提供写方法。
> 契约缺口：`/me/prediction-orders`（我的竞猜）无路径 → M-PREDICT-004 Restricted；`corrections/{id}` 无路径 → M-PREDICT-006 Restricted；`market_disclosure` 无冻结 Disclosure DTO。

### OTC（M-OTC-001..006，`otc.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `otcApi.orderBook` | GET `/api/v1/otc/order-book` | **READY** |
| `otcApi.orderDetail` | GET `/api/v1/otc/orders/{id}` | **READY** |
| `otcApi.trades` | GET `/api/v1/otc/trades` | **READY** |
| `otcApi.myOrders` | GET `/api/v1/otc/users/me/orders`（`me` 占位） | **READY** |

> 只暴露只读端点。写操作（quote / order_create / order_cancel）后端 fail-closed（fee/limit/库存/Power 规则 TBC → 503），前端不提供写方法。
> 契约缺口：`/me/otc-eligibility`、`/me/otc-capacity` 无 C 端暴露路径 → M-OTC-001 不展示资格/容量结论。

### Notice（M-NOTICE-001，`notice.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `noticeApi.list` | GET `/api/v1/me/notices` | **PARTIAL**（C 端路径待冻结，按 03 原型 best-effort 绑定） |
| `noticeApi.read` | POST `/api/v1/me/notices/{id}/read` | **PARTIAL**（写路径 fail-closed） |

### Security / 设备（M-SEC-001..002 / M-SETTINGS-001，`security.ts`）

| 方法 | 前端调用 | 后端 readiness |
|---|---|---|
| `securityApi.securityProfile` | GET `/api/v1/me/security-profile` | **READY** |
| `securityApi.sessions` | GET `/api/v1/me/sessions` | **READY** |
| `securityApi.loginAudit` | GET `/api/v1/me/login-audit`（source 未裁决 → UNAVAILABLE） | **PARTIAL** |

> 只暴露只读端点。写操作（MFA enrollment / session revoke）后端 fail-closed → 503，前端不提供写方法。

## 统一契约（`src/api/types.ts`）

- 16 项 `ResultCode`：VALIDATION_ERROR / AUTH_UNAUTHENTICATED / AUTH_FORBIDDEN / KYC_REQUIRED / POLICY_DENIED / FEATURE_CLOSED / CONSENT_VERSION_MISMATCH / IDEMPOTENCY_CONFLICT / OBJECT_VERSION_CONFLICT / QUOTE_EXPIRED / INSUFFICIENT_APT / INSUFFICIENT_POWER / MARKET_LOCKED / DEPENDENCY_UNAVAILABLE / RESULT_UNKNOWN / INTERNAL_ERROR。
- `FreshnessMeta` 8 字段：data_status / as_of / updated_at / next_refresh_at / refresh_hint / stale_after / snapshot_id / source_status。
- 写操作 `WriteMeta` 最少返回：idempotency_key / object_type / object_id / status / result_code / rule_version / parameter_release_id / policy_version / approval_id / audit_event_id。

## 开发顺序

- 仅当某页对应后端接口 `READY` 时才接入真实数据；`PARTIAL`/`CONTRACT_GAP` 页面保持 Restricted / 空态，不伪造。
- 写操作一律由 `allowed_actions` 驱动，金额 string decimal，Power 只展示后端下发的 Preview 与 Ledger。
