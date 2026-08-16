# S02-P01 · 验收（Acceptance）

## 机械断言

```text
OPENAPI_ENTRY_EXISTS            = openapi/gainode-v2.yaml
ENV_EXAMPLE_EXISTS              = .env.example
ENV_EXAMPLE_NO_SECRET           = YES（无 password/secret/token 值/真实 URL）
ERROR_DICT_ALIGNED_05_S7        = 16 错误码 + HTTP 状态映射
REQUEST_CONTEXT_MIDDLEWARE      = 解析六请求头 + 生成 request_id
SIX_HEADERS_DEFINED             = 6（Authorization/Idempotency-Key/If-Match/Accept-Language/X-Request-Id/X-Timestamp）
IDEMPOTENCY_STORE_IFACE         = 接口 + Null 实现（fail-closed）
OUTBOX_STORE_IFACE              = 接口 + Null 实现（fail-closed）
AUDIT_EVENT_SERVICE             = append-only 写入 audit_events（FROZEN 表）
TRANSACTION_BOUNDARY            = DB 事务 + object_version CAS 语义
TEST_ENTRY_CONTRACT             = tests/Contract/
TEST_ENTRY_INTEGRATION          = tests/Integration/
TEST_ENTRY_FEATURE              = tests/Feature/
NO_P0_BUSINESS_WRITE            = YES（本包不建业务 controller/service）
NO_SECOND_ROUTING               = YES（复用 Webman 路由）
SECRET_SCAN                     = PASS
```

## 非目标验证

```text
P0 业务写流程            = NOT_RUN（S02-P02~P08）
正式参数/生产 URL        = NOT_PRESENT（.env.example 仅变量名 + 安全关闭值）
V1.x arbitrage 改动      = NOT_RUN（只读）
```

## 验证命令

```text
php -l 逐个校验新增 PHP 文件
OpenAPI parse（若本地无 swagger-cli，用 yaml 语法 + $ref 存在性脚本自检）
.git env 扫描（.env.example 无 secret）
git diff --check
```

## 一致性核对

- `ErrorDict` 常量名与 05 §7 错误分类逐项一致（16 项）。
- 六请求头与 design.md §2 提案一致。
- `.env.example` 变量名覆盖 `.env` 现有变量（SIGN_PRIVATE_KEY/PWD_SECRET_KEY/MYSQL_*/REDIS_*/MAIL_*），值全部安全关闭。
