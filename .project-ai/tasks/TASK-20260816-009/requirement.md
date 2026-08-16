# S02-P02 · Auth / KYC / User / Eligibility — 需求

> 项目：Gainode　工作区：`E:\github\sports`　阶段：STAGE-02　包：`S02-P02`
> 权威执行计划：`Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md` §S02-P02（V3.3 FROZEN_FOR_EXECUTION）
> 权威契约：`05_DATA_STATE_PERMISSION_API_CONTRACT.md` §1/§2/§3/§4/§5/§7/§8/§10/§11

## 1. 目标

实现后端 P0 的 **Auth / KYC / User / Eligibility** 六条子流程，将 V1.x 会员认证代码（`MemberAuth`/`UserService`/`UserAuthService`/`UserKycService`/`JwtToken`）与 V2 冻结契约（`AuthSession`/`MfaEnrollment`/`KycCase`/`FeatureEntitlement`/`LoginAudit`）桥接，落地统一 envelope、六请求头、幂等、频控、安全 reason mapping 与 fail-closed。

实现顺序固定：**Contract → Controller/Validator → Application Service → Domain Service/DAO → Transaction/Outbox/Audit → Tests**（07 §7 STAGE-02 前言）。

## 2. 六条子流程（07 §S02-P02 固定步骤）

1. **注册/登录/OTP/找回/重置**：register → login → otp resend/verify → recovery → password reset。所有失败使用「不存在性安全文案」+ 频控。
2. **MFA**：enrollment setup → confirm → challenge → recovery/disable；secret 只后端安全存储，日志/响应不回显。
3. **Session**：issue → refresh rotation → list devices → revoke one → logout all；refresh 重放与已撤销 token 必须 fail-closed。
4. **KYC**：submit → under_review → needs_info → resubmit → approve/reject；Reviewer 与资产角色隔离，附件走后端签发对象引用。
5. **FeatureEntitlement / allowed_actions 聚合**：global_p、AI eligibility、Prediction eligibility 分开；policy/source timeout 默认 deny。
6. **LoginAudit + 安全 reason mapping**：写 LoginAudit，映射内部 reason_code → 安全 I18N key。

## 3. 权威接口（05 §2.1，11 条）

| API | 方法 | 用途 |
|---|---|---|
| `/api/v1/auth/register` | POST | 注册 |
| `/api/v1/auth/login` | POST | 登录 |
| `/api/v1/auth/otp/verify` | POST | OTP 验证 |
| `/api/v1/auth/otp/resend` | POST | OTP 重发 |
| `/api/v1/auth/mfa/verify` | POST | MFA challenge |
| `/api/v1/auth/refresh` | POST | 刷新 session |
| `/api/v1/auth/logout` | POST | 当前 session 退出 |
| `/api/v1/auth/recovery` | POST | 发起找回 |
| `/api/v1/auth/password/reset` | POST | 重置密码 |
| `/api/v1/me/sessions` | GET | 已登录会话/设备 |
| `/api/v1/me/sessions/{id}/revoke` | POST | 撤销其他 session |

补充只读契约：`GET /api/v1/me`（User）、`GET /api/v1/me/kyc`（KycCase）、`GET /api/v1/me/eligibility`（FeatureEntitlement）、`GET /api/v1/me/security-profile`（SecurityProfile）、`GET /api/v1/me/login-audit`（LoginAudit，source 未裁决 → UNAVAILABLE）。

## 4. 关键约束与不变式

- **枚举冻结**（05 §4 V2.4 canonical，2B-2 已裁决）：`AuthSession`=active/mfa_required/restricted/expired/revoked；`MfaEnrollment`=pending/active/revoked；`KycCase`=not_started/pending/needs_info/approved/rejected/review；`User`=active/restricted/suspended/closed。
- **状态转移矩阵 CANDIDATE（未 FROZEN）**：本包按 07 §S02-P02 已列转移 best-effort 实现；未列转移保持 fail-closed，交接声明 `CONSUMED_UNFROZEN_CONTRACT = 2B-2 state transition matrix`。
- **统一写操作**（05 §1）：`request_id / idempotency_key / object_type / object_id / status / result_code / result_message / next_action / rule_version / parameter_release_id / policy_version / snapshot_id / approval_id / audit_event_id`；写操作强制 `Idempotency-Key`（S02-P01 RequestContext 已落地）。
- **数据新鲜度**（05 §10）：8 元数据字段；投影默认 deny/UNAVAILABLE，不回退旧值、不 mock（05 §9）。
- **错误分类**（05 §7）：统一字符串错误码 + `ErrorDict::httpStatus()`。
- **安全 reason mapping**（05 §4）：通知/响应不暴露 raw reason_code，用 I18N key 映射。
- **账户枚举防护**：register/login/otp/recovery 的「账号不存在/已存在」响应不可泄露存在性（统一文案）。
- **职责分离**（05 §8/§11）：KYC_REVIEWER 不可触碰资产；申请人不得审批本人。

## 5. 非目标（NON_GOALS）

- 不改 MC1/2B-1/2B-2 已冻结 DDL、Model、DAO、Service 骨架（FORBIDDEN_TO_TOUCH）。
- 不实现 OTP 供应商正式接入、正式 KYC 地区/年龄门槛、MFA 恢复正式政策（06 TBC）——接口适配器 + fail-closed，不写生产默认值（07 §S02-P02 停止条件）。
- 不实现 Prediction/OTC/Robot 等 S02-P03~P08 经济写路径。
- 不迁移 V1.x `arbitrage_*`、Web3、充值/提现/红包等无关模块。
- 不 push、不提审（Development Agent 职责边界）。

## 6. 交付物清单

- `openapi/components/schemas/{auth,kyc,user,eligibility}.yaml` + `openapi/paths/{auth,kyc,user,eligibility}.yaml` + `openapi/gainode-v2.yaml` 注册。
- `library/dict/SecurityReasonMap.php`（安全 reason mapping）。
- `library/service/auth/AuthSessionService.php`、`MfaEnrollmentService.php`、`library/service/kyc/KycCaseService.php` 状态转移实现。
- `library/service/auth/AuthApplicationService.php`、`SessionApplicationService.php`、`MfaApplicationService.php`、`library/service/kyc/KycApplicationService.php`、`library/service/entitlement/EligibilityApplicationService.php`。
- `library/service/entitlement/FeatureEntitlementProjectionService.php` 扩展（global_p/AI/Prediction 三分支）。
- `library/validator/{Auth,Kyc,Eligibility}Validation.php`。
- `app/api/controller/{AuthController,KycController,UserController}.php`。
- `tests/{Contract,Integration,Feature}/` 测试 + 负向测试。
