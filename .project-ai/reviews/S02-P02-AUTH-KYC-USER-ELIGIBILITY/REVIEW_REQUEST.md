# S02-P02 复审请求（供 Quality Agent）

## 提审绑定

```text
PACKAGE_ID            = S02-P02-AUTH-KYC-USER-ELIGIBILITY
TASK_ID               = TASK-20260816-009
IMPLEMENTATION_COMMIT = 1c37f6b（六子流程实现，28 文件）
TEST_DOC_COMMIT       = 84177e5（状态机测试 + 进度指针，4 文件）
SUPPLEMENT_COMMIT     = 2af1ea9（补齐基类 ApiV2 + DomainException + UserApplicationService，3 文件）
FIX_COMMIT            = c29f42a（修复 auth.yaml MfaEnrollment $ref 指向，1 文件）
BASE_COMMIT           = b61b93e
BRANCH                = feature/gainode-v3-serial-development
PACKAGE_SHA256        = 3bf80189c092e20104210c3fef611dfdd99100b86968be4ffdf9223d215917cf（DIFF.txt）
DIFF_UNTRUNCATED      = YES（282668 bytes，UTF-8 无 BOM）
REVIEW_PACKAGE_TRUNCATED = NO
SECRET_SCAN           = PASS（见 SECRET_SCAN.txt）
DDL_TABLE_COUNT_DELTA = 0（本包不建表，仅引用 2B-2 冻结 DDL）
```

## 范围

S02-P02 Auth / KYC / User / Eligibility 六子流程（register/login/otp、MFA、session、KYC、FeatureEntitlement、LoginAudit）。交付 35 文件（2851 insertions / 56 deletions）：

```text
0.5代码/gainode后端/gainode/app/api/controller/{AuthController,KycController,UserController}.php  V2 控制器（Auth/Session/KYC/User/MFA/Eligibility/LoginAudit）
0.5代码/gainode后端/gainode/support/controller/ApiV2.php                              V2 控制器基类（Envelope + DomainException 映射）
0.5代码/gainode后端/gainode/support/exception/DomainException.php                     V2 字符串错误码异常（05 §7）
0.5代码/gainode后端/gainode/library/dict/SecurityReasonMap.php                        内部 reason_code → 安全 I18N key（防枚举）
0.5代码/gainode后端/gainode/library/service/auth/AuthApplicationService.php           注册/登录/OTP/找回/重置 + 发会话
0.5代码/gainode后端/gainode/library/service/auth/SessionApplicationService.php        refresh 轮换/会话列表/吊销/登出
0.5代码/gainode后端/gainode/library/service/auth/MfaApplicationService.php            MFA setup/confirm/challenge/disable
0.5代码/gainode后端/gainode/library/service/auth/AuthSessionService.php               AuthSession 状态机（issue/rotate/revoke/isExpired）
0.5代码/gainode后端/gainode/library/service/auth/MfaEnrollmentService.php             MfaEnrollment 状态机（setup/disable；confirm/challenge fail-closed）
0.5代码/gainode后端/gainode/library/service/kyc/KycApplicationService.php             KYC 提交/查询 + V1.x 桥接
0.5代码/gainode后端/gainode/library/service/kyc/KycCaseService.php                    KycCase 状态机（submit/startReview/requestInfo/approve/reject + 自审守卫）
0.5代码/gainode后端/gainode/library/service/entitlement/EligibilityApplicationService.php  资格三分支聚合
0.5代码/gainode后端/gainode/library/service/entitlement/FeatureEntitlementProjectionService.php 三分支默认 deny
0.5代码/gainode后端/gainode/library/service/user/UserApplicationService.php           me/securityProfile/loginAudit 投影
0.5代码/gainode后端/gainode/library/validator/{AuthValidation,KycValidation,EligibilityValidation}.php  场景校验
0.5代码/gainode后端/gainode/openapi/components/schemas/{auth,user,kyc,eligibility}.yaml  领域 schema
0.5代码/gainode后端/gainode/openapi/paths/{user,kyc,eligibility}.yaml + auth.yaml 更新 + gainode-v2.yaml  路径与入口
0.5代码/gainode后端/gainode/tests/{Contract/SecurityReasonMapContractTest,Integration/S02P02StateMachineTest}.php  69 断言
.project-ai/tasks/TASK-20260816-009/{requirement,design,acceptance}.md                任务文档
.project-ai/context.md / .project-ai/manifest.yaml                                   进度指针 + stage02_p02_auth_kyc_user_eligibility
```

## 非目标

- 不建表、不改 DDL（`DDL_TABLE_COUNT_DELTA = 0`），仅读取/操作 2B-2 冻结的表结构（auth_sessions / mfa_enrollments / kyc_cases）。
- 不实现 MFA TOTP secret 的真实存储与校验（DDL 未含 secret 字段 → confirm/challenge fail-closed）。
- 不落地 06 参数（global_p / AI / Prediction 资格参数未冻结 → 三分支默认 deny / allowed_actions=[]）。
- 不实现 idempotency/outbox 持久化（沿用 S02-P01 Null fail-closed）。
- 不暴露 admin 审核端点（approve/reject/needs_info 仅领域服务提供，C 端控制器不暴露）。
- 不改动任何 V1.x 代码语义（仅桥接读取，V2 状态为权威）。

## 关键不变量（请逐项验证）

```text
DDL_TABLE_COUNT_DELTA           = 0
HARDCODED_SECRET                = 0（本包 35 文件内）
ERROR_ENUMERATION_SAFE          = 账户存在性不泄露（USER_NOT_FOUND / PASSWORD_INCORRECT → 统一「账号或密码错误」）
MFA_CONFIRM_FAIL_CLOSED         = secret 未冻结 → DEPENDENCY_UNAVAILABLE
ELIGIBILITY_DEFAULT_DENY        = 三分支默认 deny / allowed_actions=[]
KYC_SELF_REVIEW_GUARD           = 申请人不得审批本人（AUTH_FORBIDDEN）
IDEMPOTENCY_KEY_ENFORCED        = 写操作由 RequestContext 强制（S02-P01 内核）
OPENAPI_YAML_VALID              = 17/17（safe_load 通过）
OPENAPI_REF_RESOLVED            = 17/17（$ref 文件目标全部存在，0 missing）
OPENAPI_VERSION                 = 3.1.0
TEST_ASSERTIONS                 = 69（23 Contract + 46 Integration）
PHP_SYNTAX                      = 21/21（php -l 全过）
PRODUCTION                       = NO-GO（MFA secret / 06 参数未冻结 → fail-closed）
```

## 交接声明（Dev → Quality）

按 CR-20260816-003 OPTION_A（开发 agent 一开到底），本包为 STAGE-02 首个业务子流程包，在 STAGE-01 冻结的 2B-2 DDL 上实现六条子流程领域逻辑，并桥接 V1.x（`MemberAuth` / `UserService` / `JwtToken` / `UserAuthService`）。关键 fail-closed 边界：

- **MFA secret 未冻结**：`mfa_enrollments` DDL 无 `secret` 列，`MfaEnrollmentService::confirm/challenge` 一律抛 `DEPENDENCY_UNAVAILABLE`；`setup` 返回 `secret=null` / `otpauth_url=null`。待 DDL 补充 secret 字段后替换。
- **06 资格参数未冻结**：`FeatureEntitlementProjectionService` 三分支（global_p / AI / Prediction）独立计算、互不推导，默认 `deny`、`allowed_actions=[]`。
- **账户枚举防护**：`SecurityReasonMap` 将 `USER_NOT_FOUND` / `PASSWORD_INCORRECT` 统一映射为 `auth.account_or_password_incorrect`，响应不暴露账号存在性。
- **V1.x 桥接**：`AuthApplicationService::login` 通过 `fetchLoginAuth` 取完整 `UserAuthModel`（`MemberAuth::login()` 返回 toM() 摘要，不含 refresh_token/expired_time），V2 会话写入 `auth_sessions`，V2 状态为权威，V1.x 仅存档。

## 审核重点

1. **错误枚举防护**：`SecurityReasonMap` 是否将账号存在性相关 reason_code 统一映射；`DomainException` 是否携带 05 §7 字符串码 + `httpStatus()` 映射（区别于 V1.x `VerifyException`）。
2. **AuthSession 状态机**：issue/rotateAccessToken/revoke/revokeAll/isExpired 是否对齐 05 §2.2；token_hash 是否哈希存储；`mfa_required` 未通过时是否落 `MFA_REQUIRED` 状态。
3. **MfaEnrollment fail-closed**：confirm/challenge 是否因 secret 未冻结而 `DEPENDENCY_UNAVAILABLE`；setup 是否仅 `totp`。
4. **KycCase 状态机**：submit→startReview→requestInfo→approve/reject 转移是否合法；`assertNotSelf` 是否拒绝申请人自审；`reviewed_by` 是否校验 KYC_REVIEWER 角色。
5. **FeatureEntitlement / Eligibility**：三分支是否独立、默认 deny、`allowed_actions=[]`；`getEligibilityBundle` 是否返回 global_p/ai/prediction 三对象。
6. **V1.x 桥接正确性**：`AuthApplicationService` 是否从完整 `UserAuthModel` 取 refresh_token/expired_time（非 toM() 摘要）；`SessionApplicationService::refresh` 是否按旧 access_token 定位会话后再轮换。
7. **控制器/校验**：`ApiV2` 是否统一 envelope + DomainException 映射；写操作是否经 `AuthValidation`/`KycValidation` 场景校验；admin 审核端点是否未在 C 端暴露。
8. **OpenAPI 3.1**：17 个 YAML 是否可解析、`$ref` 目标全部存在（尤其 auth.yaml `MfaEnrollment` 指向 user.yaml）；schemas 与 paths 是否与实现一致。
9. **测试**：SecurityReasonMapContractTest（23 断言）+ S02P02StateMachineTest（46 断言）是否独立可运行且全过。
10. **治理一致性**：`context.md` / `manifest.yaml` 进度指针是否与交付一致；`stage02_p02_auth_kyc_user_eligibility` 是否 COMPLETE 且交付清单准确。
