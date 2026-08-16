# S02-P01 复审请求（供 Quality Agent）

## 提审绑定

```text
PACKAGE_ID            = S02-P01-GENERAL-KERNEL
TASK_ID               = TASK-20260816-008
IMPLEMENTATION_COMMIT = 097b5ce
BASE_COMMIT           = 678b61a
BRANCH                = feature/gainode-v3-serial-development
PACKAGE_SHA256        = e56262f0cf8fa46e4fdbaacfca4414c0441d96db36159affe8c31380286a5a2b（DIFF.txt）
DIFF_UNTRUNCATED      = YES（105808 bytes，UTF-8 无 BOM）
REVIEW_PACKAGE_TRUNCATED = NO
SECRET_SCAN           = PASS（见 SECRET_SCAN.txt）
DDL_TABLE_COUNT_DELTA = 0（本包不建表、不写业务）
```

## 范围

S02-P01 可执行通用内核（OpenAPI 3.1 + Environment Contract + General Kernel）。交付 30 文件（2365 insertions / 2 deletions）：

```text
0.5代码/gainode后端/gainode/.env.example                                    环境变量契约（安全关闭值 fail-closed）
0.5代码/gainode后端/gainode/library/dict/ErrorDict.php                      05 §7 16 项错误码 + httpStatus() 映射
0.5代码/gainode后端/gainode/library/response/Envelope.php                   统一 success/error 信封（8 数据新鲜度元数据）
0.5代码/gainode后端/gainode/support/middleware/RequestContext.php           六请求头中间件 + 写操作 Idempotency-Key 强制
0.5代码/gainode后端/gainode/config/middleware.php                          admin/api/common 中间件组接入
0.5代码/gainode后端/gainode/library/service/idempotency/*                   IdempotencyStore 接口 + Null 实现（fail-closed）
0.5代码/gainode后端/gainode/library/service/outbox/*                        OutboxStore 接口 + Null 实现（fail-closed）
0.5代码/gainode后端/gainode/library/service/transaction/TransactionBoundary.php  事务 + object_version CAS 乐观锁
0.5代码/gainode后端/gainode/openapi/gainode-v2.yaml                         OpenAPI 3.1 入口（6 域路径 + common 组件引用）
0.5代码/gainode后端/gainode/openapi/components/{schemas,headers,responses}/* common 组件
0.5代码/gainode后端/gainode/openapi/paths/{auth,robot,prediction,apt_otc,policy_parameter,admin}.yaml  P0 路径骨架
0.5代码/gainode后端/gainode/tests/{Contract,Integration,Feature}/*          测试入口（41 断言全过）
.project-ai/tasks/TASK-20260816-008/*                                        requirement/design/acceptance
.project-ai/context.md / .project-ai/manifest.yaml                           进度指针 + stage02_p01_general_kernel
```

## 非目标

- 不建表、不改 DDL（`DDL_TABLE_COUNT_DELTA = 0`）。
- 不实现任何 P0 业务写路径 / Controller / route（留待 S02-P02~P08）。
- 不虚构未冻结的业务 request/response schema（OpenAPI path 仅冻结 operationId/security/统一响应）。
- 不实现 idempotency/outbox 的持久化存储（当前 Null fail-closed，待持久化合同冻结后替换）。
- 不改动任何 V1.x 代码或 `_existing_prod/`。

## 关键不变量（请逐项验证）

```text
DDL_TABLE_COUNT_DELTA           = 0
ENV_SECRET_PLAINTEXT            = 0（.env.example 全部敏感值置空）
HARDCODED_SECRET                = 0（本包变更文件内）
ERROR_CODE_COUNT                = 16（05 §7 全项）
HTTP_STATUS_MAP                 = 16/16（RESULT_UNKNOWN→202，未知→500）
IDEMPOTENCY_KEY_ENFORCED        = POST/PUT/PATCH/DELETE（API_ENFORCE_IDEMPOTENCY=true，缺失 fail-closed）
NULL_STORE_FAIL_CLOSED          = isAvailable()=false（idempotency + outbox）
OPENAPI_YAML_VALID              = 10/10（safe_load 通过）
OPENAPI_REF_RESOLVED            = 10/10（$ref 文件目标全部存在）
OPENAPI_VERSION                 = 3.1.0
TEST_ASSERTIONS                 = 41（33 Contract + 8 Integration）
PHP_SYNTAX                      = 12/12（php -l 全过）
PRODUCTION                      = NO-GO（未冻结业务契约未实现）
```

## 交接声明（Dev → Quality）

按 CR-20260816-003 OPTION_A（开发 agent 一开到底），本包为 STAGE-02 第一个可执行内核包。**只建契约与内核，不落业务写路径**：

- `IdempotencyStore` / `OutboxStore` 目前只有 `Null*` 实现（`isAvailable()=false`），依赖幂等/事务性出箱保证的写操作必须 fail-closed，直至持久化存储合同冻结（`p1_003_two_phase_freeze` 第二批范围）。
- OpenAPI path 骨架只冻结 `operationId` / `security` / 统一响应 envelope，业务 request/response schema 不在此虚构（05 §1/§7/§10 权威），留待 S02-P02~P08 逐域落地。
- 统一错误分类 `RESULT_UNKNOWN` → 202（客户端凭原 Idempotency-Key 查询结果，不重试创建），已按 05 §7 落实。

## 审核重点

1. `.env.example` 是否无任何明文 secret（所有敏感值置空，注释说明 fail-closed）。
2. `ErrorDict` 16 项错误码是否与 05 §7 完全对齐，`httpStatus()` 映射是否准确（尤其 RESULT_UNKNOWN→202）。
3. `Envelope` success/error 结构是否覆盖 05 §1（写操作最少字段）与 §10（8 数据新鲜度元数据），固定字段是否不可被 `extra` 覆盖。
4. `RequestContext` 六请求头解析 + 写操作 Idempotency-Key 强制是否 fail-closed；`config/middleware.php` 是否三组（admin/api/common）均接入且未破坏原有链路。
5. `NullIdempotencyStore`/`NullOutboxStore` 是否严格 fail-closed（`isAvailable()=false`，操作 no-op）。
6. `TransactionBoundary` 的 `lockForUpdate` CAS 是否引用正确 `Db` 门面（`support\extend\Db`）。
7. OpenAPI：`gainode-v2.yaml` 入口 + 10 个 YAML 是否可解析、`$ref` 目标是否全部存在、version=3.1.0。
8. 测试入口：Contract/Integration 测试是否可独立运行且 41 断言全过；Feature 目录占位是否注明无特性测试原因。
9. `context.md` / `manifest.yaml` 进度指针是否与交付一致。
