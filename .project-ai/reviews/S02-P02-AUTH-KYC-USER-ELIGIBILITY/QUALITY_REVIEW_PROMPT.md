# S02-P02 独立审核提示词（Independent Review Agent）

你是 Gainode 项目的独立审核 Agent。请以 **Evidence First（证据优先）** 原则，对本包 S02-P02 Auth/KYC/User/Eligibility 进行只读审核，逐项验证并输出 PASS / CHANGES_REQUIRED 结论。

## 审核对象

```text
PACKAGE_ID = S02-P02-AUTH-KYC-USER-ELIGIBILITY
COMMITS    = 1c37f6b / 84177e5 / 2af1ea9 / c29f42a（35 文件，2851 insertions / 56 deletions）
BASE       = b61b93e
BRANCH     = feature/gainode-v3-serial-development
```

## 审核要点（逐项验证）

1. **错误枚举防护**：`SecurityReasonMap` 是否将账号存在性相关 reason_code（USER_NOT_FOUND / PASSWORD_INCORRECT）统一映射 `auth.account_or_password_incorrect`；未知 code 是否回落 `auth.generic_error`（绝不透传 raw code）；`DomainException` 是否携带 05 §7 字符串 resultCode + `ErrorDict::httpStatus()` 映射。
2. **AuthSession 状态机**：`AuthSessionService` issue/rotateAccessToken/revoke/revokeAll/isExpired 是否对齐 05 §2.2；token 是否哈希存储；未过 MFA 是否落 `MFA_REQUIRED`；非法转移是否 fail-closed。
3. **MfaEnrollment fail-closed**：`confirm`/`challenge` 是否因 secret 未冻结抛 `DEPENDENCY_UNAVAILABLE`；`setup` 是否仅 `totp`、`secret=null`。
4. **KycCase 状态机**：submit→startReview→requestInfo→approve/reject 转移是否合法；`assertNotSelf` 是否拒绝自审（AUTH_FORBIDDEN）；`reviewed_by` 是否校验 KYC_REVIEWER 角色。
5. **FeatureEntitlement / Eligibility**：三分支是否独立、互不推导、默认 deny、`allowed_actions=[]`；`getEligibilityBundle` 是否返回 global_p/ai/prediction 三对象。
6. **V1.x 桥接正确性**：`AuthApplicationService` 是否经 `fetchLoginAuth` 取完整 `UserAuthModel`（refresh_token/expired_time），而非 `MemberAuth::login()` 的 toM() 摘要；`SessionApplicationService::refresh` 是否按旧 access_token 定位会话后再轮换，`newRefreshToken` 是否用 `JwtToken::getToken`。
7. **控制器/校验**：`ApiV2` 是否统一 envelope + DomainException 映射（区别于 V1.x failJson）；写操作是否经 `AuthValidation`/`KycValidation` 场景校验；admin 审核端点是否未在 C 端控制器暴露；`UserController` 是否全部 me 作用域只读。
8. **OpenAPI 3.1**：17 个 YAML 是否语法合法；`$ref` 目标全部存在（尤其 auth.yaml `MfaEnrollment` 指向 `./user.yaml`）；schemas 与 paths 是否与控制器/服务实现一致。
9. **测试**：SecurityReasonMapContractTest（23 断言）+ S02P02StateMachineTest（46 断言）是否独立可运行且全过；是否覆盖 fail-closed 负路径（confirm fail-closed、自审守卫、默认 deny）。
10. **治理一致性**：`context.md` / `manifest.yaml` 进度指针是否与交付一致；`stage02_p02_auth_kyc_user_eligibility` 是否 COMPLETE 且交付清单准确。

## 证据要求

- 每项结论引用具体文件行/字段作为证据。
- 发现缺陷标注严重级（BLOCKING / P2 / P3）+ 缺陷 ID。
- 不修改任何文件，仅输出审核报告。

## 参考权威契约

- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（S02-P02 固定步骤）
- `05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§1 全局规则 / §2 认证会话 / §3 用户 / §4 安全 / §7 错误分类 / §10 数据新鲜度）
- `06_PARAMETER_DICTIONARY.md`（请求头 / 参数契约 / 资格参数）
- `.project-ai/tasks/TASK-20260816-009/{requirement,design,acceptance}.md`
