# S01-P06 复审请求（供 Quality Agent）

## 提审绑定

```text
PACKAGE_ID          = S01-P06-PROJECTIONS
TASK_ID             = TASK-20260816-005
IMPLEMENTATION_COMMIT = 0e5c0ae
BRANCH              = feature/gainode-v3-serial-development
PACKAGE_SHA256      = （见 PACKAGE_SHA256.txt）
DIFF_UNTUNCATED     = YES
REVIEW_PACKAGE_TRUNCATED = NO
SECRET_SCAN         = PASS（见 SECRET_SCAN.txt）
PHP_LINT            = PASS（16 生产文件 + 7 测试 + 1 引导，全部 No syntax errors）
PROJECTION_TESTS    = PASS（105 断言 / 0 失败）
GIT_DIFF_CHECK      = PASS
DDL_TABLE_COUNT_DELTA = 0（本包不新增任何生产表）
```

## 范围

实现 7 个**非持久投影**（NOT_PERSISTED，禁止建表）的服务端只读聚合服务 + Response DTO + 单元测试。

```text
FeatureEntitlement / OtcEligibility / OtcCapacity / PowerImpactPreview /
SecurityProfile / SessionDevice / LoginAudit
```

交付物（27 文件，1903 insertions）：

```text
support/extend/ProjectionResponse.php        公共 Response 基类（05 §10 8 元数据字段）
support/extend/ProjectionService.php         公共投影基类（default-deny + 元数据辅助）
library/response/{entitlement,otc,power,auth}/<7 对象>Response.php
library/service/{entitlement,otc,power,auth}/<7 对象>ProjectionService.php
tests/projection/_bootstrap.php              公共测试引导（SQLite in-memory）
tests/projection/<7 对象>ProjectionTest.php
.project-ai/tasks/TASK-20260816-005/{requirement,design,acceptance}.md
```

## 非目标

- 不实现任何业务写流程（OTC 下单/Robot 启动/Withdrawal 属 STAGE-02）。
- 不冻结参数（属 06 / ParameterRelease）。
- 不建表、不实现 Controller/OpenAPI 路由。

## 关键不变量（请逐项验证）

```text
NOT_PERSISTED = YES（7 对象无 DDL）
NO_MOCK_FALLBACK = YES（TBC 字段 null/空，不回退旧值，不填 mock）
DEFAULT_DENY = YES（未冻结依赖 → UNAVAILABLE + allowed=false）
DECIMAL_STRING = YES（capacity/power 数值 string，禁 float）
METADATA_8_FIELDS = YES（每响应含 data_status/as_of/updated_at/next_refresh_at/refresh_hint/stale_after/snapshot_id/source_status）
CROSS_USER_ACCESS = FORBIDDEN（SecurityProfile/SessionDevice/LoginAudit 越权不泄露存在性）
CONSUMED_UNFROZEN_CONTRACT = YES（OTC/Power/Feature/安全 规则参数 TBC）
OPEN_OWNER_DECISION = YES（G1 LoginAudit source / G2 allowed_actions / G3 capacity 结构）
```

## 交接声明（Dev → Quality）

按 CR-20260816-003 OPTION_A（开发 agent 一开到底），本包在质量门禁未关闭、依赖参数未冻结的情况下按 best-effort 实现：
未冻结参数一律 default-deny（UNAVAILABLE + allowed=false + 字段 null），写路径无（投影只读）。
Quality 审核时请将 07 §S01-P06 的「前置/停止条件/Stage Gate」作为验证项登记，不阻塞 Dev。

## 审核重点

1. 7 个 Response 字段是否严格对齐 05 §3（无自创字段；`allowed_actions` 例外，见 G2）。
2. ProjectionService 的 source 读取顺序 + 默认 deny 是否符合 design.md。
3. 越权是否统一返回 UNAVAILABLE 且不泄露存在性（不抛会写 DB 的 AuthorizeException）。
4. decimal string 是否无 float（available_before 等保持 string）。
5. 测试是否覆盖 REALTIME/UNAVAILABLE/越权/无 mock。
6. 无新增生产 DDL（_bootstrap.php 的 SQLite 建表是测试引导，非生产 DDL）。
