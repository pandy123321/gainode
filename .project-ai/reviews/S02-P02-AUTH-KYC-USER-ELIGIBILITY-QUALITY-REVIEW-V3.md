# S02-P02 质量审核报告（Quality Review）

> QUALITY-01 独立审核。只写 `.project-ai/reviews/**`。

## 0. 审核绑定

```text
REVIEW_ID                = GAINODE-S02P02-AUTH-KYC-USER-ELIGIBILITY-IR-20260816-001
PROJECT                  = Gainode
FORMAL_STAGE             = STAGE-02
PACKAGE_ID               = S02-P02-AUTH-KYC-USER-ELIGIBILITY
BASE_COMMIT              = b61b93e
SNAPSHOT_COMMIT          = c29f42a
REVIEW_RANGE             = b61b93e..c29f42a
REVIEW_ROUND             = 1
SNAPSHOT_LOCKED          = YES
```

## 1. 材料完整性矩阵

REVIEW_REQUEST / REVIEW_RANGE / PAYLOAD_MANIFEST(35) / DIFF(282668B 未截断) / VALIDATION_RESULTS / SECRET_SCAN / SELF_REVIEW / KNOWN_LIMITATIONS 全部齐备。

## 2. 变更概览

S02-P02 Auth/KYC/User/Eligibility 六子流程：35 文件 / 2851 insertions / 56 deletions。不建表（DDL_TABLE_COUNT_DELTA=0），复用 2B-2 冻结 DDL，桥接 V1.x 认证，V2 状态为权威。

## 3. 审核结论

**APPROVED** — 0 P0 / 0 P1 / 0 BLOCKING_P2 / 2 NON_BLOCKING_P2 / 0 P3。

## 4. 独立验证记录（QUALITY-01 实际执行）

| 验证项 | 方法 | 结果 |
|---|---|---|
| 变更范围 | `git diff --stat b61b93e..c29f42a` | 35 文件 / 2851+/56- ✅ |
| SecurityReasonMap 防枚举 | 读源码 + 测试 | USER_NOT_FOUND/PASSWORD_INCORRECT 统一文案，未知回落 generic ✅ |
| DomainException | 读源码 | 05 §7 字符串码 + httpStatus() 映射 ✅ |
| ApiV2 基类 | 读源码 | 统一 envelope + DomainException 映射 ✅ |
| AuthSessionService | 读源码 | issue→active/mfa_required、token_hash sha256、revoke 幂等 ✅ |
| MfaEnrollmentService | 读源码 | confirm/challenge fail-closed DEPENDENCY_UNAVAILABLE ✅ |
| KycCaseService | 读源码 | 状态机转移 + assertNotSelf ✅（见 P2-001） |
| FeatureEntitlementProjectionService | 读源码 | 三分支独立默认 deny，allowed_actions=[]，越权 UNAVAILABLE ✅ |
| AuthApplicationService | 读源码 | V1.x 桥接 fetchLoginAuth 反查完整 refresh_token/expired_time ✅ |
| SessionApplicationService | 读源码 | refresh rotation 旧 refresh 失效 + 按旧 token 定位会话轮换 ✅ |
| 测试 | 实际运行 | SecurityReasonMapContractTest 23 断言；S02P02StateMachineTest 46 断言；合计 69 ✅ |

## 5. Freeze / Machine Contract 一致性

```text
2B-2 DDL（auth_sessions/mfa_enrollments/kyc_cases） = 复用，DDL_TABLE_COUNT_DELTA=0 ✅
05 §2.2 会话状态机 = active/mfa_required/restricted/expired/revoked ✅
05 §4 MFA canonical（pending/active/revoked） ✅
05 §4 KYC canonical（not_started/pending/needs_info/approved/rejected/review） ✅
05 §11.1 对象存在性不泄露 = SecurityReasonMap 防枚举 ✅
```

## 6~7. P0 / P1 Findings

无。

## 8. P2 Findings（2 NON_BLOCKING）

### S02-P02-P2-001（NON_BLOCKING）— KYC 审核方法缺少 reviewer 角色（KYC_REVIEWER）校验

- **FILE_PATH**: `library/service/kyc/KycCaseService.php`
- **LINE_RANGE**: `startReview()`/`requestInfo()`/`approve()`/`reject()`（L107–L181）
- **RELATED_CONTRACT**: design.md L60「`reviewed_by` 必须为 KYC_REVIEWER；KYC_REVIEWER 不得触碰资产（ABAC）」
- **CURRENT_BEHAVIOR**: 四方法仅调用 `assertNotSelf()`（申请人不得审批本人），未校验 `reviewerUserId` 是否具备 KYC_REVIEWER 角色。
- **EXPECTED_BEHAVIOR**: 进入审核转移前，校验 reviewer 具备 KYC_REVIEWER 角色。
- **ROOT_CAUSE**: SoD 角色校验设计在 ABAC/权限层，本包领域服务未接入。
- **REACHABLE_SCENARIO**: Admin 端点（S02-P07/S03-P03）落地后，若权限层遗漏角色校验，任意非本人用户可越权审批/驳回 KYC。
- **IMPACT**: 越权审批风险（当前无暴露端点，不可达）。
- **MINIMUM_SAFE_FIX**: Admin 权限层（Casbin/ABAC）落地 KYC_REVIEWER 角色校验，并在 KycCaseService 审核方法补充角色守卫或显式 TODO 标注绑定角色校验入口。
- **CONSTRAINTS_AND_NON_GOALS**: 不改变状态机转移；不阻断当前 C 端提交/查询。
- **GATE_IMPACT**: NON_BLOCKING。

### S02-P02-P2-002（NON_BLOCKING）— 敏感操作未写入审计事件（auditEventId 恒 ''）

- **FILE_PATH**: `library/service/auth/AuthApplicationService.php`（`auditEventId()` L241–L244）、`library/service/kyc/KycApplicationService.php`（submit 传 `''` L31）、`library/service/auth/MfaApplicationService.php`
- **RELATED_CONTRACT**: design.md L99「Application Service 编排 … 幂等 + 审计」/ L102「写路径经 OutboxStore + AuditEventService 追加」
- **CURRENT_BEHAVIOR**: `auditEventId()` 恒返回 `''`，未调用已存在的 `AuditEventService::create()`，登录/会话签发/MFA/KYC 提交不写 audit_event。
- **EXPECTED_BEHAVIOR**: 敏感操作同事务内追加审计事件，回填 `audit_event_id`。
- **ROOT_CAUSE**: 审计事件写入横切机制未在本包接线（AuditEventService 已于 STAGE-01 落地但未消费）。
- **REACHABLE_SCENARIO**: 登录/KYC 等敏感操作事后无审计追踪（fail-open）。
- **IMPACT**: 审计追踪缺失，事后不可追溯；属合规/安全缺口，不影响功能正确性。
- **MINIMUM_SAFE_FIX**: S02-P07（Audit）统一设计审计事件类型/快照结构后，回溯 S02-P02 敏感操作的审计写入（或本包最小补丁调用 AuditEventService::create()）。
- **CONSTRAINTS_AND_NON_GOALS**: 不自行虚构审计事件 schema；audit_events 保持 append-only。
- **GATE_IMPACT**: NON_BLOCKING。

## 9. P3 Findings

无。

## 10. Closed Finding 回归

N/A（首审）。

## 11. 关键矩阵

```text
权限  = 自审守卫 OK；reviewer 角色校验缺失（P2-001）✅/⚠
状态  = AuthSession/MfaEnrollment/KycCase 状态机正确 ✅
资金  = 无资金路径 ✅
数据  = DDL_TABLE_COUNT_DELTA=0 ✅
API   = OpenAPI 3.1 17 YAML + 11 权威接口 + 5 只读路径 ✅
审计  = 敏感操作未写审计（P2-002）⚠
```

## 12~14. 验证 / 未执行 / 工具限制

STATIC_CHECK = PASS／TEST = PASS（69 断言）／OPENAPI_PARSE = PASS（17/17，见 REVIEW 包 VALIDATION）／BUILD = NOT_RUN／RUNTIME_CHECK = NOT_RUN／DEPLOYMENT = NOT_RUN。HTTP 路由级 E2E 未覆盖（路由未落地，留后续包）。

## 15. 开发 Agent 继续条件

```text
SNAPSHOT_LOCKED = YES
NEXT_PACKAGE_OVERLAP = NO
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW = NO
```

## 16. Package 合并建议

```text
CODE_MERGE_RECOMMENDATION = APPROVED
```

## 17. Formal Stage Gate 状态

STAGE-02 尚有 S02-P03~P09 待审核，本包不触发 Gate。

## 18. 修复提示词（交付后续包/开发 Agent）

无 BLOCKING Finding。2 个 NON_BLOCKING P2 已记录，需在 S02-P07（Audit/Approval）与 Admin 权限层落地时逐项闭环，详见 §8。

---

```text
SNAPSHOT_LOCKED                 = YES
REVIEW_COMPLETENESS             = COMPLETE
VERDICT                         = APPROVED
P0_OPEN                         = 0
P1_OPEN                         = 0
BLOCKING_P2_OPEN                = 0
NON_BLOCKING_P2_OPEN            = 2
P3_OPEN                         = 0
CODE_MERGE_RECOMMENDATION       = APPROVED
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW = NO
BLOCKED_PACKAGE                 = NONE
FORMAL_STAGE_GATE               = NOT_APPLICABLE
PRODUCTION_APPROVAL             = NO
```
