# S01-P06-PROJECTIONS-QUALITY-REVIEW-V3

> QUALITY-01 独立审核报告。绑定 Git 快照，不审核浮动工作树。

## 0. 审核绑定

```text
REVIEW_ID = GAINODE-S01P06-PROJECTIONS-IR-20260816-001
PROJECT = Gainode
WORKSPACE = E:\github\sports
FORMAL_STAGE = STAGE-01
PACKAGE_ID = S01-P06-PROJECTIONS
BASE_COMMIT = 7e1d3c0
SNAPSHOT_COMMIT = 0e5c0ae
REVIEW_RANGE = 7e1d3c0..0e5c0ae（实现 27 文件）
REVIEW_PACKAGE_COMMIT = 593775f（复审包 12 文件）
QUALITY_MODE = INDEPENDENT_READ_ONLY_SNAPSHOT_REVIEW
PRODUCT_CODE_CHANGED_BY_QUALITY = NO
```

## 1. 材料完整性矩阵

| 材料 | 路径 | 状态 |
|---|---|---|
| 开发交接 | `S01-P06-PROJECTIONS/REVIEW_REQUEST.md` | COMPLETE |
| 审查范围 | `REVIEW_RANGE.txt`（BASE/IMPLEMENTATION/BRANCH） | COMPLETE |
| 变更清单 | `PAYLOAD_MANIFEST.csv`（27 文件 path+change_type） | COMPLETE（无逐文件 sha256，见 P3） |
| 验证结果 | `VALIDATION_RESULTS.md` | COMPLETE |
| 已知限制 | `KNOWN_LIMITATIONS.md`（3 Contract Gap） | COMPLETE |
| 自审报告 | `SELF_REVIEW.md` | COMPLETE |
| 全量 Diff | `DIFF.txt`（88179 字节，2065 行） | COMPLETE（LF-norm sha256 匹配） |
| 密钥扫描 | `SECRET_SCAN.txt`（0 命中） | COMPLETE |

## 2. 变更概览

- 实现 27 文件 / 1903 插入：3 task 文档 + 2 公共基类（ProjectionResponse/ProjectionService）+ 7 Response DTO + 7 ProjectionService + 7 投影测试 + 1 测试引导。
- 7 个非持久投影（NOT_PERSISTED，禁止建表）只读聚合服务。
- 全部 default-deny：依赖参数 TBC → allowed=false + UNAVAILABLE，字段 null，不回退旧值，不填 mock。

## 3. 审核结论

```text
VERDICT = APPROVED
P0_OPEN = 0
P1_OPEN = 0
BLOCKING_P2_OPEN = 0
NON_BLOCKING_P2_OPEN = 0
P3_OPEN = 1
REVIEW_COMPLETENESS = COMPLETE
```

## 4. 需求与验收覆盖矩阵（11 项）

| # | 验收项 | 结果 |
|---|---|---|
| 1 | 7 对象 NOT_PERSISTED，无 DDL | ✅ PASS（DDL_TABLE_COUNT_DELTA=0） |
| 2 | 7 Response + 7 Service + 2 基类 | ✅ PASS |
| 3 | 8 元数据字段（05 §10） | ✅ PASS（ProjectionResponse 全 8 字段） |
| 4 | 未冻结依赖 default-deny | ✅ PASS（allowed=false + UNAVAILABLE） |
| 5 | 无 mock fallback、无前端推导 | ✅ PASS（TBC 字段 null/空数组） |
| 6 | decimal string / 禁 float | ✅ PASS（`(string)$position->available`） |
| 7 | 跨用户访问 FORBIDDEN | ✅ PASS（viewer≠target → access_denied） |
| 8 | 投影测试 | ✅ PASS（105 断言 / 0 失败，独立运行） |
| 9 | php -l 全部 PASS | ✅ PASS |
| 10 | git diff --check 无空白错误 | ✅ PASS |
| 11 | 无新 DDL | ✅ PASS（_bootstrap SQLite 为测试引导） |

## 5. Freeze / Machine Contract 一致性

- reason_code 严格对齐 05 §3 L407 七选一（KYC_REQUIRED/SECURITY_VERIFICATION_REQUIRED/OTC_CAPACITY_INSUFFICIENT/INSUFFICIENT_POWER/UNDER_REVIEW/REGION_UNAVAILABLE/MAINTENANCE），未覆盖 OtcOrder.status。
- FeatureEntitlement 字段对齐 05 §3（8 字段）；`allowed_actions` 为 07 步骤 4 要求但 05 §3 缺失，已交接 Contract Gap G2，未裁决前返回空数组，不自行推断。
- 3 个 Contract Gap（G1 LoginAudit source / G2 allowed_actions / G3 capacity 结构）已交接 Owner 决策，不阻塞实现。
- SessionDevice.revocable 撤销规则未冻结 → 默认 false（fail-closed）。
- 未修改 MC1/MC2/2B-1/2B-2 冻结文件。

## 6-9. Findings

### P3-001（非阻断）— 完整性指纹格式弱于 S01-P05

```text
FINDING_ID = S01-P06-PROJECTIONS-P3-001
SEVERITY = P3
TITLE = PAYLOAD_MANIFEST 无逐文件 sha256，PACKAGE_SHA256 实为 DIFF.txt sha256
FILE_PATH = .project-ai/reviews/S01-P06-PROJECTIONS/PAYLOAD_MANIFEST.csv
CURRENT_BEHAVIOR = PAYLOAD_MANIFEST.csv 仅 path+change_type 两列；PACKAGE_SHA256.txt 内容为「8d090325… DIFF.txt」（DIFF.txt 的 LF-norm sha256），非总包聚合值
EXPECTED_BEHAVIOR = 与 S01-P05 一致：逐文件 sha256 + 总包聚合 sha256 并文档化算法
EVIDENCE = S01-P05 有 46 文件逐文件 sha256；S01-P06 无逐文件 sha256。DIFF.txt LF-norm sha256=8d090325… 匹配 PACKAGE_SHA256.txt
IMPACT = 完整性可验证性弱于 S01-P05；但 git blob 内容寻址（commit 0e5c0ae）+ DIFF.txt sha256 已提供包级完整性兜底，非阻断
MINIMUM_SAFE_FIX = 后续包统一逐文件 sha256 + 总包聚合算法文档化（与 S01-P05-P3-001 合并为跨包格式规范）
GATE_IMPACT = 不阻断本包合并、不阻断 S01-P07
ACCEPTANCE_CRITERIA = 后续包 manifest 含逐文件 sha256 且聚合算法可复现
```

## 10. Closed Finding 回归

无历史 Finding（本包首次审核）。

## 11. 权限/状态/资金/数据/API 关键矩阵

```text
资金操作 = NONE（投影只读）
权限提升 = NONE
状态转移实现 = NONE（只读聚合）
数据不可变 = N/A（不写任何表）
越权泄露 = FORBIDDEN（统一 UNAVAILABLE + access_denied，不泄露存在性）
API 变更 = NONE（属 S02）
```

## 12. 实际执行的验证（QUALITY-01 独立执行）

| 验证 | 方法 | 结果 |
|---|---|---|
| 文件范围 | `git show --stat 0e5c0ae` | 27 文件 / 1903 插入 ✅ |
| 复审包范围 | `git show --stat 593775f` | 12 文件 ✅ |
| 投影测试 | 独立运行 7 个测试脚本 | 105 断言 / 0 失败 ✅ |
| reason_code | 逐值比对 05 §3 L407 | 七选一全对齐 ✅ |
| default-deny | 读 7 Service 源码 | 全部 allowed=false + UNAVAILABLE ✅ |
| 越权不泄露 | 读 OtcEligibility/SessionDevice/Power 源码 | 统一 access_denied ✅ |
| decimal string | 读 PowerImpactPreview 源码 | `(string)` 转换 ✅ |
| DIFF 完整性 | 重算 DIFF.txt LF-norm sha256 | 匹配 8d090325 ✅ |
| 密钥扫描 | 复核 SECRET_SCAN.txt | 0 命中 ✅ |

## 13. 未执行验证（NOT_RUN，如实声明）

```text
真实 OTC/Power/Feature 资格计算 = NOT_RUN（参数未冻结）
业务写流程 = NOT_RUN（STAGE-02）
Controller/OpenAPI 路由 = NOT_RUN
参数冻结 = NOT_RUN（06 / ParameterRelease）
autoload/class-load 全量 = NOT_RUN
```

## 14. 工具限制

- 完整性指纹格式弱于 S01-P05（P3-001），但不影响审核结论（git blob + DIFF sha256 兜底）。

## 15. Development Agent 继续条件

```text
SNAPSHOT_LOCKED = YES
SNAPSHOT_COMMIT_VERIFIED = YES
SNAPSHOT_PATHS_VERIFIED = YES
NEXT_PACKAGE_OVERLAP = NO
NEXT_PACKAGE_DOES_NOT_CONSUME_UNFROZEN_CONTRACT = YES（S01-P07 合同盘点，不建表）
NEXT_PACKAGE_IS_DEFINED_IN_V3_PLAN = YES
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW = NO
```

## 16. Package 合并建议

```text
CODE_MERGE_RECOMMENDATION = APPROVED
```

## 17. Formal Stage Gate 状态

```text
FORMAL_STAGE_GATE = NOT_APPLICABLE
```

## 18. 报告结论（分离）

```text
SNAPSHOT_LOCKED = YES
REVIEW_COMPLETENESS = COMPLETE
VERDICT = APPROVED
P0_OPEN = 0
P1_OPEN = 0
BLOCKING_P2_OPEN = 0
NON_BLOCKING_P2_OPEN = 0
P3_OPEN = 1
CODE_MERGE_RECOMMENDATION = APPROVED
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW = NO
BLOCKED_PACKAGE = NONE
FORMAL_STAGE_GATE = NOT_APPLICABLE
PRODUCTION_APPROVAL = NO
```

## 修复提示词

本包无 P0/P1/P2，仅 1 条非阻断 P3（完整性指纹格式），不影响合并，顺带在后续包修正。无需 Development Agent 单独修复。
