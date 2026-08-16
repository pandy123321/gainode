# S02-P02 · Auth / KYC / User / Eligibility — 设计

## 1. REUSE_MATRIX

| 资产 | 判定 | 说明 |
|---|---|---|
| `library/service/auth/MemberAuth.php` / `AuthAbstract.php` | KEEP | V1.x 认证核心：`verifyLogin`、`createLoginAuth`、`loginFailure` 频控、`codeLogin`、`register` |
| `library/service/member/UserService.php` / `UserModel` / `UserAuthService` / `UserAuthModel` | KEEP | 会员账号/密码哈希/JWT token 存储（V1.x） |
| `support/utils/JwtToken.php` | KEEP | `getToken/getTokenJwtData/deleteToken` |
| `library/service/member/UserKycService.php` / `UserKycModel` | KEEP | V1.x KYC 字段存档（review_status/reject_reason/is_verify） |
| `verifyCodeMsg` / `sendCodeMsg` 全局助手 | KEEP | OTP 校验/发送（供应商接口适配器，TBC 前 fail-closed） |
| `library/service/auth/AuthSessionService.php` | EXTEND | 增加 issue/refresh/revoke 状态转移 + token_hash 生命周期 |
| `library/service/auth/MfaEnrollmentService.php` | EXTEND | 增加 setup/confirm/challenge/disable 状态转移 |
| `library/service/kyc/KycCaseService.php` | EXTEND | 增加 submit/needs_info/resubmit/approve/reject 状态转移 |
| `library/service/entitlement/FeatureEntitlementProjectionService.php` | EXTEND | 增加 global_p/AI/Prediction 三分支聚合 |
| `library/service/auth/LoginAuditProjectionService.php` | EXTEND | 由「source 未裁决 UNAVAILABLE」升级为可写 LoginAudit 记录（写入仍走 audit 侧，投影读仍 fail-closed） |
| `app/api/controller/LoginController.php` | EXTEND | 合并 V2 端点，复用 V1.x 逻辑 |
| `app/api/controller/{Auth,Kyc,User}Controller.php` | NEW | V2 控制器 |
| `library/service/auth/*ApplicationService.php` 等 | NEW | Application Service 编排层 |
| `library/dict/SecurityReasonMap.php` | NEW | 安全 reason mapping |
| MC1/2B-1/2B-2 已冻结 DDL/Model/DAO/Service | FORBIDDEN_TO_TOUCH | 不得重写或重构 |

## 2. 状态机（fail-closed 边界）

转移矩阵属 2B-2 CANDIDATE。本包按 07 §S02-P02 已列转移实现，未列转移 `FAIL_CLOSED`。

### AuthSession（05 §2.2：active / mfa_required / restricted / expired / revoked）

```
issue(登录成功, MFA 未验) → active | mfa_required
refresh(access 过期, refresh 有效) → 轮换 refresh，旧 refresh 立即失效（rotation）
revoke_one(本人/安全吊销) → revoked
logout_all(登出) → 全部 active/mfa_required/restricted → revoked
expire(时间到) → expired（惰性判定：读取时 expires_at < now 视为 expired，不写回）
```
- `token_hash`：只存哈希（`hash('sha256', $token)`），不存明文 token。
- refresh 重放检测：已轮换的 refresh_token_hash 命中旧记录 → fail-closed（`AUTH_UNAUTHENTICATED`）。

### MfaEnrollment（05 §4：pending / active / revoked）

```
setup(method=TOTP) → pending（生成 secret，仅返回一次性 secret/QR，不落明文日志）
confirm(校验 code) → active（enrolled_at=now，last_verified_at=now）
challenge(active 会话, 校验 code) → 保持 active，更新 last_verified_at
disable/recovery → revoked
```
- `method_type` 本包仅 `totp`（email/sms 属供应商适配器，TBC fail-closed）。
- secret 加密存储（`APP_KEY`），日志/响应不回显。

### KycCase（05 §4：not_started / pending / needs_info / approved / rejected / review）

```
submit(not_started|needs_info|rejected) → pending
admin_review(pending) → review
needs_info(review) → needs_info
resubmit(needs_info) → pending
approve(review) → approved
reject(review) → rejected
```
- `reviewed_by` 必须为 KYC_REVIEWER；KYC_REVIEWER 不得触碰资产（ABAC）。
- 附件引用走后端签发对象引用，不存用户上传直链明文。

## 3. FeatureEntitlement 三分支聚合（07 步骤 6）

```
global_p       : 读 User.global_p_level + 06 Feature 参数（TBC）→ 未冻结 default deny
ai_eligibility : 读 User.ai_reward_eligibility + 06 AI 参数（TBC）→ 未冻结 default deny
prediction     : 读 User.prediction_eligibility + 06 Prediction 参数（TBC）→ 未冻结 default deny
```
- 三者独立计算，互不推导；policy/source timeout → default deny（`FEATURE_RULE_UNAVAILABLE`）。
- `allowed_actions` 字段 05 §3 缺失（Contract Gap G2）→ 空数组，不自行推断。

## 4. 安全 reason mapping（05 §4）

`SecurityReasonMap::resolve(string $internalCode): string` 返回安全 I18N key：

```text
USER_NOT_FOUND          → auth.account_or_password_incorrect
PASSWORD_INCORRECT      → auth.account_or_password_incorrect
ACCOUNT_LOCKED          → auth.account_locked
ACCOUNT_DELETED         → auth.account_unavailable
OTP_INVALID             → auth.otp_invalid
OTP_RATE_LIMITED        → auth.otp_rate_limited
MFA_REQUIRED            → auth.mfa_required
MFA_INVALID             → auth.mfa_invalid
SESSION_REVOKED         → auth.session_revoked
KYC_REJECTED            → kyc.rejected
KYC_NEEDS_INFO          → kyc.needs_info
FEATURE_RULE_UNAVAILABLE→ entitlement.feature_rule_unavailable
DEFAULT                 → auth.generic_error
```
- 越权/不存在统一返回 `auth.account_or_password_incorrect`，不泄露对象存在性。
- 未知 code 回落 `auth.generic_error`。

## 5. 分层与文件

- **Controller**：`support\controller\Api`，`getPost` 校验 → Application Service → `Envelope`。
- **Validator**：`library\validator\*Validation.php`（复用 V1.x `LoginValidation` 风格）。
- **Application Service**：编排 V1.x 认证 + V2 实体 + 幂等 + 审计；不直接写 SQL。
- **Domain Service**：`AuthSessionService`/`MfaEnrollmentService`/`KycCaseService` 状态转移 + 乐观锁。
- **Transaction**：`TransactionBoundary`（S02-P01 已落地）+ `object_version` CAS。
- **Outbox/Audit**：写路径经 `OutboxStore`（Null → fail-closed）+ `AuditEventService` 追加。

## 6. 幂等与并发

- 写操作强制 `Idempotency-Key`（RequestContext 已落地）；按 key 查原结果，命中返回原响应。
- `AuthSession`/`MfaEnrollment`/`KycCase` 写操作走 `object_version` CAS，`affected rows ≠ 1` → `OBJECT_VERSION_CONFLICT(409)`。

## 7. 测试策略

- Contract：Envelope 结构、错误码映射、OpenAPI parse。
- Integration：Session rotation 重放、MFA challenge、KYC 状态流转、越权（跨用户）不泄露存在性。
- Feature/负向：账户枚举、OTP 爆破、refresh 重放、MFA 并发、KYC 越权、默认 deny。
