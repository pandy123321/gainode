# PAGE_IMPLEMENTATION_RECORD — H5-01 Auth（M-AUTH-001..005）

> 批次：H5-01 Auth ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-AUTH-001..005 ｜ OpenAPI auth.yaml（S02-P02 APPROVED）｜ 08 tokens

## M-AUTH-001 登录

| 字段 | 值 |
|---|---|
| Page ID | M-AUTH-001 |
| Route | `/auth/login`（meta.pageId=M-AUTH-001，auth=false） |
| Figma node | 03 原型「表单页」；Gainode2.0 Figma node 待对齐 |
| DTO/API | `POST /api/v1/auth/login` LoginRequest → AuthTokenResponse |
| Store | session（setTokens）、authFlow（mfa 上下文） |
| Components/tokens | AuthShell + .auth-*；var(--brand-blue-600) 等 |
| 五态 | Default（表单）/ Loading（submitting）/ Error（错误横幅）/ Restricted（账号锁定文案） |
| 写状态 | Submitting / Success（→/ 或 /auth/mfa）/ Failed（错误横幅）/ Unknown（http.ts） |
| 权限 | 游客可访问；不泄露账号是否存在 |
| I18N | page.m_auth_001.* + auth.* |
| 截图 | 375/390/430 待补（缺口） |
| Tests | tests/unit/auth-views.spec.ts |
| Known Deviation | S03-P02-AUTH-REFRESH-TOKEN / -LOGIN-POLICY |

## M-AUTH-002 注册

| 字段 | 值 |
|---|---|
| Page ID | M-AUTH-002 |
| Route | `/auth/register` |
| Figma node | 03 原型「表单页」；待对齐 |
| DTO/API | `POST /api/v1/auth/register` RegisterRequest → {user_id,account} |
| Store | authFlow（OTP 上下文） |
| Components/tokens | AuthShell；账号类型切换（email/mobile） |
| 五态 | Default / Loading / Error / Restricted |
| 写状态 | Submitting / Success（→/auth/otp）/ Failed / Unknown |
| 权限 | 游客可访问；条款不可默认勾选 |
| I18N | page.m_auth_002.* + auth.* |
| 截图 | 375/390/430 待补 |
| Tests | tests/unit/auth-views.spec.ts（consent 拦截） |
| Known Deviation | S03-P02-AUTH-CONSENT-VERSION |

## M-AUTH-003 OTP 验证

| 字段 | 值 |
|---|---|
| Page ID | M-AUTH-003 |
| Route | `/auth/otp` |
| Figma node | 03 原型「验证页」；待对齐 |
| DTO/API | `POST /api/v1/auth/otp/verify` + `otp/resend` |
| Store | authFlow（account/source/purpose，不持久化） |
| Components/tokens | AuthShell；验证码格 48px + 倒计时 |
| 五态 | Default / Loading / Error / Restricted |
| 写状态 | Submitting / Success（register→login）/ Failed / Unknown |
| 权限 | 只能操作当前 challenge（深链无上下文→登录） |
| I18N | page.m_auth_003.* + auth.* |
| 截图 | 待补 |
| Tests | auth.spec.ts（otpVerify 绑定） |
| Known Deviation | S03-P02-AUTH-OTP-CHALLENGE |

## M-AUTH-004 找回/重置密码

| 字段 | 值 |
|---|---|
| Page ID | M-AUTH-004 |
| Route | `/auth/recovery`（内部 4 步：account→otp→reset→done） |
| Figma node | 03 原型「表单页」；待对齐 |
| DTO/API | `POST /auth/recovery` + `otp/verify`(forget) + `password/reset` |
| Store | 本地 state（不外泄） |
| Components/tokens | AuthShell |
| 五态 | Default / Loading / Error / Restricted |
| 写状态 | Submitting / Success（done→login）/ Failed / Unknown |
| 权限 | 不泄露账号是否注册；高风险可转人工（未实现，见缺口） |
| I18N | page.m_auth_004.* + auth.* |
| 截图 | 待补 |
| Tests | auth.spec.ts（recovery/passwordReset 绑定） |
| Known Deviation | S03-P02-AUTH-RECOVERY-VERIFY |

## M-AUTH-005 MFA 二次验证

| 字段 | 值 |
|---|---|
| Page ID | M-AUTH-005 |
| Route | `/auth/mfa` |
| Figma node | 03 原型「验证页」；待对齐 |
| DTO/API | `POST /api/v1/auth/mfa/verify` MfaVerifyRequest → AuthTokenResponse |
| Store | session（setTokens）、authFlow（session_id） |
| Components/tokens | AuthShell；验证码格 |
| 五态 | Default / Loading / Error / Restricted |
| 写状态 | Submitting / Success（→/，保留原 idempotency context）/ Failed / Unknown |
| 权限 | challenge 绑定原登录 session（无 session_id→登录） |
| I18N | page.m_auth_005.* + auth.* |
| 截图 | 待补 |
| Tests | auth.spec.ts（mfaVerify 绑定） |
| Known Deviation | S03-P02-AUTH-MFA-METHODS / -REFRESH-TOKEN |
