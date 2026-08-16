# S02-P02 已知限制（Known Limitations）

## 1. MFA TOTP secret 存储未冻结（核心限制）

`mfa_enrollments` 2B-2 冻结 DDL 不含 `secret` 列。因此 `MfaEnrollmentService::confirm()` / `challenge()` 一律抛 `DEPENDENCY_UNAVAILABLE`，`MfaApplicationService::setup()` 返回 `secret=null` / `otpauth_url=null`。MFA 二次验证全链路不可用，直至 DDL 补充 secret 字段（TOTP 种子安全存储方案）冻结合同。属 `p1_003_two_phase_freeze` 范围，非本包缺陷。

## 2. 06 资格参数未冻结

`FeatureEntitlementProjectionService` 三分支（global_p / AI eligibility / Prediction eligibility）依赖 06 参数（`global_p_level`、`ai_reward_eligibility`、`prediction_eligibility` 的判定规则）均未冻结，故一律默认 `deny`、`allowed_actions=[]`。`UserApplicationService::me()` 相关字段返回 `null`（fail-closed）。

## 3. LoginAudit source 未裁决

`LoginAudit` 投影的 `source`（审计事件来源）尚未在机器合同中裁决，`UserApplicationService::loginAudit()` 返回 `UNAVAILABLE`（fail-closed），不提供可枚举的审计列表，直至审计事件来源合同冻结。

## 4. idempotency/outbox 持久化未冻结

沿用 S02-P01 的 `NullIdempotencyStore` / `NullOutboxStore`（`isAvailable()=false`）。依赖幂等保证或事务性出箱的写操作 fail-closed，持久化存储合同冻结后替换。

## 5. V1.x 桥接为只读存档

`AuthApplicationService` / `SessionApplicationService` / `KycApplicationService` 桥接 V1.x（`MemberAuth` / `UserAuthService` / `JwtToken` / `UserKycService`）仅用于凭证生成与字段存档，不改变 V1.x 业务语义；V2 表（`auth_sessions` / `mfa_enrollments` / `kyc_cases`）为权威。V1.x 遗留硬编码密钥（`support/translate/Openai.php` sk-*、`support/translate/engine/Google.php` 私钥、`support/web3/*`）不在本包范围。

## 6. 测试为领域层（非 HTTP 端到端）

本包 69 断言为领域层契约 + 状态机集成（SecurityReasonMap/DomainException 映射、AuthSession/MfaEnrollment/KycCase 状态机、资格三分支），不覆盖 HTTP 控制器路由（路由尚未在 S02-P02 落地，留待后续包）。HTTP 级端到端测试待路由/内核接线完成后补充。

## 7. DIFF 体积偏大

本包 DIFF ~282668 bytes（35 文件，2851 insertions）。外部审核工具 `max_diff_chars`（当前 100000）可能截断，Quality Agent 提交外部审核时若遇 `diff_truncated: true` 需先确认配置生效。本地 DIFF.txt 为完整未截断版本。
