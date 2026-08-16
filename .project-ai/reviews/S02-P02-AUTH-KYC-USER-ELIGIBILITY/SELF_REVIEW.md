# S02-P02 自审报告（Self Review）

## 结论

**COMPLETE**（STAGE-02 首个业务子流程包，35 文件 / 2851 insertions）。Auth / KYC / User / Eligibility 六条子流程（register/login/otp、MFA、session、KYC、FeatureEntitlement、LoginAudit）领域逻辑落地，V2 契约 + V1.x 桥接 + fail-closed 边界全部就位。机械校验全过（PHP 语法 21/21、OpenAPI 17/17 解析 + $ref 0 missing、测试 69 断言、secret scan PASS、DDL delta 0）。

## 交付核对

| 交付物 | 状态 |
|---|---|
| `ApiV2` 控制器基类 + `DomainException`（05 §7） | ✅ |
| `SecurityReasonMap` 防枚举映射 | ✅ |
| AuthController/KycController/UserController 三控制器 | ✅ |
| AuthApplicationService / SessionApplicationService / MfaApplicationService | ✅ |
| AuthSessionService / MfaEnrollmentService 状态机 | ✅ |
| KycApplicationService / KycCaseService 状态机 + 自审守卫 | ✅ |
| EligibilityApplicationService / FeatureEntitlementProjectionService 三分支 | ✅ |
| UserApplicationService 投影（me/securityProfile/loginAudit） | ✅ |
| AuthValidation / KycValidation / EligibilityValidation 校验 | ✅ |
| OpenAPI schemas（auth/user/kyc/eligibility）+ paths（user/kyc/eligibility/auth/gainode-v2） | ✅ |
| tests（SecurityReasonMapContractTest + S02P02StateMachineTest） | ✅ |
| TASK-20260816-009 任务文档 + manifest/context 指针 | ✅ |

## 关键设计决策

1. **fail-closed 优先**：MFA TOTP secret 未冻结（DDL 无 secret 列）→ `confirm/challenge` 抛 `DEPENDENCY_UNAVAILABLE`、`setup` 返回 `secret=null`；06 资格参数未冻结 → 三分支默认 `deny`、`allowed_actions=[]`；User 未冻结字段（locale/timezone/global_p_level 等）→ `null`。
2. **账户枚举防护**：`SecurityReasonMap` 将 `USER_NOT_FOUND` / `PASSWORD_INCORRECT` 统一映射 `auth.account_or_password_incorrect`，登录/找回不暴露账号存在性。
3. **V2 权威、V1.x 存档**：`AuthSession` / `MfaEnrollment` / `KycCase` 走 V2 表（2B-2 DDL）；`MemberAuth` / `UserAuthService` / `JwtToken` 仅作凭证生成与桥接存档，V2 状态为权威。
4. **DomainException 替代 VerifyException**：V2 API 统一抛 `DomainException`（字符串 resultCode + httpStatus），由 `ApiV2::envelopeError` 映射为 envelope，不与 V1.x failJson 混用。
5. **KYC 自审守卫**：`KycCaseService::assertNotSelf` 拒绝申请人审批本人；`startReview`/`approve`/`reject` 校验 `reviewed_by`。
6. **会话轮换正确性**：`SessionApplicationService::refresh` 先按旧 access_token 定位会话再 `rotateAccessToken`；`AuthApplicationService` 用 `fetchLoginAuth` 取完整 `UserAuthModel`（`MemberAuth::login()` 返回 toM() 摘要，不含 refresh_token）。

## 已执行校验

- `php -l` 21 个 PHP 文件全过（无语法错误）。
- OpenAPI 17 个 YAML 均 `yaml.safe_load` 通过；`$ref` 文件目标 17/17 存在（0 missing）。
- SecurityReasonMapContractTest 23 断言 / S02P02StateMachineTest 46 断言 = 69 全过（ALL PASS）。
- `SECRET_SCAN` PASS（本包 35 文件 0 明文密钥）。
- 修复 `auth.yaml` `MfaEnrollment` $ref 从 `mfa.yaml`（不存在）→ `./user.yaml`（`c29f42a`）。
- `git diff --check` 通过；DIFF 未截断（282668 bytes）；PACKAGE_SHA256 已计算（DIFF.txt）。

## 已知权衡

- MFA secret 存储未冻结 → confirm/challenge 不可用（预期内，见交接声明）。
- 06 资格参数未冻结 → 资格三分支默认 deny（预期内）。
- idempotency/outbox 沿用 S02-P01 Null fail-closed，持久化待冻结合同。
- V1.x 桥接仅读不写 V1.x 语义，V2 表为权威；V1.x 遗留硬编码密钥不在本包范围。

## 提交绑定

```text
COMMITS = 1c37f6b / 84177e5 / 2af1ea9 / c29f42a
BRANCH  = feature/gainode-v3-serial-development
PUSH    = NO（按分工，Dev 不 push，由 Quality agent push）
```
