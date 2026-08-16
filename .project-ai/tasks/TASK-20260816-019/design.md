# S03-P02 · H5-01 Auth 批次 — 设计

## 1. 绑定 OpenAPI（S02-P02 APPROVED，`openapi/paths/auth.yaml`）

| Page ID | Route | 接口（operationId） | DTO |
|---|---|---|---|
| M-AUTH-001 登录 | `/auth/login` | `POST /api/v1/auth/login` | LoginRequest{account,password} → AuthTokenResponse{token_type,access_token,expires_in,session_id,mfa_required,mfa_enrollment?} |
| M-AUTH-002 注册 | `/auth/register` | `POST /api/v1/auth/register` | RegisterRequest{account,account_type,consent_version,password?,vcode?,invite_code?,nickname?,locale?,timezone?} → {user_id,account} |
| M-AUTH-003 OTP | `/auth/otp` | `POST /api/v1/auth/otp/verify` / `otp/resend` | OtpVerifyRequest{account,vcode,type?,source?} / OtpResendRequest{account,type?,source?} |
| M-AUTH-004 找回 | `/auth/recovery` | `POST /api/v1/auth/recovery` + `otp/verify`(source=forget) + `password/reset` | RecoveryRequest{account} / PasswordResetRequest{account,vcode,password} |
| M-AUTH-005 MFA | `/auth/mfa` | `POST /api/v1/auth/mfa/verify` | MfaVerifyRequest{code,session_id?} → AuthTokenResponse |

## 2. 结构

```
src/api/auth.ts            # 领域客户端 + DTO（透传 OpenAPI 字段，不手写第二套）
src/stores/auth.ts         # 认证流程上下文（account/source/purpose/session_id，不持久化）
src/utils/mask.ts          # 账号脱敏
src/views/auth/AuthShell.vue  # 浅色「表单页」共享框架 + .auth-* 样式（品牌蓝，禁金色）
src/views/auth/authError.ts   # 错误码 → 本地化文案（禁 raw enum，防枚举）
src/views/auth/m-auth-001..005/index.vue
```

## 3. 关键实现点

- **写操作状态**：submitting（禁用 + spinner）→ Success（导航）/ Failed（错误横幅）/ Unknown（写请求超时经 http.ts 抛 UnknownResultError，不自动重 POST）。
- **登录 mfa_required**：`mfa_required=true` → 保存 session_id 到 authFlow → `/auth/mfa` 续验；否则 → `/`。
- **防枚举**：登录/找回失败统一「账号或密码错误」/「若账号存在已发送」，不区分账号是否存在。
- **条款不可默认勾选**：注册 consent 默认 `false`。
- **五态**：表单页以 inline 错误横幅 + Restricted 文案覆盖 Loading/Error/Restricted；Empty 不适用于表单，按默认态处理。
- **tokens**：全部取 `var(--*)`（品牌蓝/灰阶/状态色），无硬编码品牌色；`color-scheme: light`。
- **不持久化**：authFlow 不落 localStorage（OTP challenge / MFA session 属敏感上下文）。

## 4. 合同缺口（登记，不阻塞）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-AUTH-REFRESH-TOKEN | AuthTokenResponse 未含 refresh_token，`auth/refresh` 依赖它 | 防御性读取，缺省则 token 过期需重登 |
| S03-P02-AUTH-CONSENT-VERSION | 无 consent 版本来源端点 | 常量 `2026-08-01` 待后端对齐 |
| S03-P02-AUTH-RECOVERY-VERIFY | 03 引 `POST /auth/recovery/verify`，OpenAPI 无 | 用 `otp/verify`(source=forget) |
| S03-P02-AUTH-MFA-METHODS | 03 引 allowed_methods/expires_at，OpenAPI 无 | 仅 totp code + session_id |
| S03-P02-AUTH-LOGIN-POLICY | 03 引 `GET /auth/login-policy`，OpenAPI 无 | 不预取，登录响应 mfa_required 驱动流程 |
| S03-P02-AUTH-OTP-CHALLENGE | 03 引 challenge_id/retry_after，OpenAPI 用 account+vcode | 60s 客户端倒计时兜底 |

## 5. 验证

`npm run type-check` / `npm run test:unit`（新增 auth.spec.ts + auth-views.spec.ts）/ `npm run build`；i18n key parity（7 语言同构）；secret 扫描。
