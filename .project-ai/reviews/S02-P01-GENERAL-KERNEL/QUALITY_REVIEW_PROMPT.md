# S02-P01 独立审核提示词（Independent Review Agent）

你是 Gainode 项目的独立审核 Agent。请以 **Evidence First（证据优先）** 原则，对本包 S02-P01 通用内核进行只读审核，逐项验证并输出 PASS / CHANGES_REQUIRED 结论。

## 审核对象

```text
PACKAGE_ID = S02-P01-GENERAL-KERNEL
COMMIT     = 097b5ce（30 文件，2365 insertions / 2 deletions）
BASE       = 678b61a
BRANCH     = feature/gainode-v3-serial-development
```

## 审核要点（逐项验证）

1. **环境变量契约**：`.env.example` 是否无明文 secret（SIGN_PRIVATE_KEY/PWD_SECRET_KEY/JWT_SECRET/APP_KEY/MYSQL_PASS/REDIS_PASS/MAIL_PASSWORD/OSS_ACCESS_KEY_ID/OSS_ACCESS_KEY_SECRET 均为空）；注释是否声明缺失 fail-closed。
2. **错误分类**：`ErrorDict` 是否与 05 §7 的 16 项错误码完全一致；`httpStatus()` 映射是否准确（VALIDATION_ERROR→400、AUTH_*→401/403、*_CONFLICT→409、INSUFFICIENT_*→422、DEPENDENCY_UNAVAILABLE→503、RESULT_UNKNOWN→202、INTERNAL_ERROR→500）。
3. **统一信封**：`Envelope` success 是否含 8 数据新鲜度元数据；error 是否含 result_code/result_message/http_status/details；`extra` 是否无法覆盖固定字段（request_id/data）。
4. **请求上下文**：`RequestContext` 是否解析六请求头；写操作（POST/PUT/PATCH/DELETE）是否强制 Idempotency-Key（1~64 字符），缺失/超长 fail-closed；`config/middleware.php` 是否 admin/api/common 三组均接入且未破坏原有中间件。
5. **内核 fail-closed**：`NullIdempotencyStore`/`NullOutboxStore` 是否 `isAvailable()=false` 且操作 no-op；`TransactionBoundary` 是否引用 `support\extend\Db`（非 `support\Db`）。
6. **OpenAPI 3.1**：`gainode-v2.yaml` 是否 version=3.1.0、bearerAuth 定义、6 域 path $ref 是否全部可解析；10 个 YAML 是否语法合法；path 是否只含 operationId/security/统一响应（无虚构业务 schema）。
7. **测试**：Contract/Integration 测试是否可独立运行，41 断言是否真实通过；Feature README 是否说明无特性测试原因。
8. **治理一致性**：`context.md`/`manifest.yaml` 进度指针是否与交付一致；`stage02_p01_general_kernel` 是否 COMPLETE 且交付清单准确。

## 证据要求

- 每项结论引用具体文件行/字段作为证据。
- 发现缺陷标注严重级（BLOCKING / P2 / P3）+ 缺陷 ID。
- 不修改任何文件，仅输出审核报告。

## 参考权威契约

- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（S02-P01 固定步骤 1-6）
- `05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§1 全局规则 / §7 错误分类 / §10 数据新鲜度）
- `06_PARAMETER_DICTIONARY.md`（请求头 / 参数契约）
- `.project-ai/tasks/TASK-20260816-008/{requirement,design,acceptance}.md`
