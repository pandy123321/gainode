# S02-P01 · 设计（Design）

## 1. API 契约冻结项（源自 05 §1/§2/§6/§7）

```text
API_STYLE     = REST/JSON
OPENAPI       = 3.1
JSON_SCHEMA   = 2020-12
BASE_URL      = /api/v1（C 端）；/admin/api（后台，沿用现有 admin 路由前缀）
TIME          = UTC timestamp（Unix 秒，dateFormat='U'），客户端本地化
DECIMAL       = string（资产/数量/系数，禁 float）
PAGINATION    = cursor（next_cursor + limit）
WRITE_IDEMPOTENCY = required（Idempotency-Key）
CONCURRENCY   = If-Match / object_version
AUTH          = Bearer JWT（firebase/php-jwt，05 §2）
```

## 2. 六请求头（提案，Environment Freeze 候选）

```text
1. Authorization       Bearer <JWT>      认证（05 §2）
2. Idempotency-Key     写操作幂等键      写入去重（05 §1）
3. If-Match            object_version    并发乐观锁（05 §1）
4. Accept-Language     zh-CN/en/...      7 语言（05/08）
5. X-Request-Id        trace id          缺失时服务端生成（05 §1 request_id）
6. X-Timestamp         Unix 秒          客户端时钟（防重放/新鲜度，05 §1 TIME）
```

> 六请求头在 05 未逐字冻结（仅抽象引用「六请求头」）。本包提案为 CANDIDATE，待 Environment Freeze Owner 签；签前按本提案 best-effort 实现，middleware 对缺失头 fail-closed。

## 3. 统一 Success Envelope

```json
{
  "request_id": "...",
  "data": { },
  "data_status": "REALTIME",
  "as_of": 0,
  "updated_at": 0,
  "next_refresh_at": null,
  "refresh_hint": null,
  "stale_after": null,
  "snapshot_id": null,
  "source_status": "OK"
}
```

写操作附加 05 §1 最少返回字段（idempotency_key / object_type / object_id / status / result_code / result_message / next_action / rule_version / parameter_release_id / policy_version / snapshot_id / approval_id / audit_event_id）。

## 4. 统一 Error Envelope

```json
{
  "request_id": "...",
  "result_code": "VALIDATION_ERROR",
  "result_message": "...",
  "http_status": 400,
  "details": []
}
```

## 5. 错误分类（05 §7 → HTTP 状态 + code 常量）

| code | http | 语义 |
|---|---|---|
| VALIDATION_ERROR | 400 | 参数校验失败 |
| AUTH_UNAUTHENTICATED | 401 | 未认证 |
| AUTH_FORBIDDEN | 403 | 禁止 |
| KYC_REQUIRED | 403 | 需 KYC |
| POLICY_DENIED | 403 | 策略拒绝 |
| FEATURE_CLOSED | 403 | 能力关闭 |
| CONSENT_VERSION_MISMATCH | 409 | 同意书版本不一致 |
| IDEMPOTENCY_CONFLICT | 409 | 幂等冲突 |
| OBJECT_VERSION_CONFLICT | 409 | 版本冲突（乐观锁） |
| QUOTE_EXPIRED | 409 | 报价过期 |
| INSUFFICIENT_APT | 422 | APT 不足 |
| INSUFFICIENT_POWER | 422 | Power 不足 |
| MARKET_LOCKED | 422 | 盘口锁定 |
| DEPENDENCY_UNAVAILABLE | 503 | 依赖不可用 |
| RESULT_UNKNOWN | 202 | 结果未知（用原 Idempotency-Key 查询） |
| INTERNAL_ERROR | 500 | 内部错误（兜底） |

## 6. 通用内核组件

### 6.1 RequestContext middleware（`support/middleware/RequestContext.php`）

- 解析六请求头 → 存入 `Workerman\Coroutine\Context`。
- 生成/透传 `X-Request-Id`；写操作校验 `Idempotency-Key` 存在性（缺失→400）。
- 旧 MD5 签名密钥（`SIGN_PRIVATE_KEY`）迁移到 env，缺失 fail-closed（不再校验 V1.x Sign）。

### 6.2 IdempotencyRecord（未冻结，best-effort 接口）

- 接口 `library/service/idempotency/IdempotencyStore.php` + `NullIdempotencyStore`（默认 deny/无状态，fail-closed）。
- 语义：按 `(idempotency_key, object_type)` 查原结果；已完成→返回原响应；处理中→返回 RESULT_UNKNOWN；冲突→IDEMPOTENCY_CONFLICT。

### 6.3 Outbox（未冻结，best-effort 接口）

- 接口 `library/service/outbox/OutboxStore.php` + `NullOutboxStore`。
- 语义：业务事务内追加 outbox 记录；投递失败不回滚业务；dedupe key 与业务对象/idempotency key 关联。

### 6.4 AuditEvent（FROZEN，可建 Service）

- `library/service/audit/AuditEventService.php` 对 `audit_events`（MC2 DDL）append-only 写入。
- 复用 `library/model/audit/AuditEventModel.php`（若已存在），无 UPDATE/DELETE。

### 6.5 Transaction boundary

- `library/service/transaction/TransactionBoundary.php` 封装 DB 事务 + 复用 MC2 Economic Mutation Lock 语义（`apt_accounts.object_version` CAS），供 S02-P03+ 复用。

## 7. OpenAPI 结构

```text
openapi/gainode-v2.yaml              入口（info/security/servers/tags + $ref 汇总）
openapi/components/schemas/          error.yaml / envelope.yaml / pagination.yaml
openapi/components/headers/          六请求头 + 响应头
openapi/components/responses/        success / error / 202 / 409 等
openapi/paths/auth.yaml             （S02-P02 占位，仅冻结 P0 path 骨架）
openapi/paths/robot.yaml
openapi/paths/prediction.yaml
openapi/paths/apt_otc.yaml
openapi/paths/policy_parameter.yaml
openapi/paths/admin.yaml
```

> P0 paths 仅建骨架（operationId/security/summary），request/response schema 在对应 S02-P02~P08 落地；本包不虚构未冻结字段。

## 8. 测试策略

- `tests/Contract/`：envelope 结构、错误分类、ErrorDict 常量映射。
- `tests/Integration/`：RequestContext 六请求头解析（含缺失 fail-closed）、IdempotencyStore/OutboxStore null 实现。
- `tests/Feature/`：占位入口（本包无业务写流程，仅骨架 + 空场景标记）。
- 沿用现有 `tests/*/_bootstrap.php` 纯 PHP 引导（SQLite in-memory 不触真实 MySQL）。
