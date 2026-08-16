# S02-P01 · OpenAPI 3.1、Environment 与通用内核

## 目的

STAGE-02 首个实现包。冻结合同后的第一份「可执行内核」：OpenAPI 3.1 规范、环境变量契约（`.env.example`）、统一 Response/Error/RequestContext 中间件、通用内核服务（idempotency / outbox / audit / transaction）、测试入口。本包不实现任何 P0 业务写流程（属 S02-P02~P08）。

## 范围（对齐 07 §S02-P01 固定步骤 1-7）

```text
1. 从 05 §1/§6/§7 冻结 base URL、六请求头、认证、统一 success/error envelope、cursor pagination、decimal string、日期/时区和错误分类。
2. 按 Auth→Robot→Prediction→APT/OTC→Policy/Parameter→Admin 顺序拆 paths；schema 只引用 05 已冻结对象。
3. 每个 operation 补唯一 operationId、security、required、closed schema、错误响应、idempotency、If-Match/object_version、敏感字段可见性和示例（仅 fixture）。
4. 实现统一 Response/Error/RequestContext middleware，解析六请求头并生成 request_id；旧签名密钥移到 env，缺失 fail-closed。
5. 实现 IdempotencyRecord、Outbox、AuditEvent 的通用接口和 transaction boundary。
6. `.env.example` 只写变量名、安全关闭值和说明，禁止 secret / 生产 URL / 正式参数。
7. 建立 Contract/Integration/Feature 测试入口。
```

## 目标文件

```text
openapi/gainode-v2.yaml
openapi/{schemas,paths,components}/**/*.yaml
.env.example
library/dict/ErrorDict.php（对齐 05 §7 错误分类）
support/middleware/RequestContext.php（六请求头 + request_id）
library/response/（统一 envelope 基类）
library/service/{idempotency,outbox,audit,transaction}/
tests/{Contract,Integration,Feature}/
```

## 非目标

- 不实现 P0 业务写流程（Auth/KYC/Ledger/Robot/Prediction/OTC/Approval 属 S02-P02~P08）。
- 不新增第二套路由框架（复用 Webman `config/route/{api,admin}.php` + 数据库驱动 `sys_route`）。
- 不写生产 secret、真实签名密钥、正式参数或生产 URL。
- 不改动 V1.x `arbitrage_*` 代码（只读盘点）。
- 不为未冻结字段补默认值；未冻结合同写路径 fail-closed。

## 依赖与冻结状态

```text
AuditEvent（audit_events）          = FROZEN（MC2 DDL 已冻结，可建 Service）
IdempotencyRecord（通用内核表）     = 未冻结（CONSUMED_UNFROZEN_CONTRACT，best-effort 接口 + fail-closed 存储）
Outbox（通用内核表）                = 未冻结（CONSUMED_UNFROZEN_CONTRACT，best-effort 接口 + fail-closed 存储）
Transaction boundary                = 复用 MC2 Economic Mutation Lock 语义（apt_accounts.object_version CAS）
六请求头                            = 候选（本包提案，Environment Freeze 待 Owner 签）
```

## 验收摘要

- OpenAPI 可解析、无 dangling `$ref`、operationId 唯一。
- `.env.example` 无 secret，缺失环境变量安全关闭。
- 统一 envelope + RequestContext 有正反向测试。
- 错误分类与 05 §7 逐项对齐（16 错误码 + HTTP 状态）。
