# S02-P01 已知限制（Known Limitations）

## 1. idempotency/outbox 持久化未冻结（核心限制）

`IdempotencyStore` / `OutboxStore` 目前只有 `Null*` 实现（`isAvailable()=false`）。在持久化存储合同冻结前，依赖幂等保证或事务性出箱的写操作一律 fail-closed 不可用。属 `p1_003_two_phase_freeze` 第二批范围，非本包缺陷。

## 2. OpenAPI path 无业务 request/response schema

6 域 P0 path 骨架仅冻结 `operationId` / `security` / 统一响应 envelope，业务 request/response body 未落地（留待 S02-P02~P08）。刻意不虚构未冻结字段（05 §1/§7/§10 权威契约）。

## 3. DIFF 体积偏大

本包 DIFF ~105808 bytes（30 文件，2365 insertions）。外部审核工具 `max_diff_chars`（当前 100000）可能截断，Quality Agent 提交外部审核时若遇 `diff_truncated: true` 需先确认配置生效。本地 DIFF.txt 为完整未截断版本。

## 4. 生产参数 TBC

`.env.example` 中生产参数（APP_ENV/APP_DEBUG/密钥/OSS/邮件等）均为安全关闭值，真实值待运维注入；缺失即 fail-closed，Production=NO-GO 直至生产参数批准。

## 5. V1.x 遗留密钥不在本包范围

V1.x 硬编码密钥（`support/translate/Openai.php` sk-*、`config/translation.php` private key、`config/web3.php` 合约/地址、`support/web3/*`）属已登记 V1.x 问题（context.md「V1.x 生产代码关键发现」），本包未改动，后续 STAGE 统一迁移至环境变量。

## 6. 测试为逻辑级（非业务级）

本包 41 断言均为 Contract/Integration 逻辑级（Envelope/ErrorDict 映射、Null 内核 fail-closed），不覆盖业务写路径（本包未实现）。Feature 目录占位，待 S02-P02 起随业务落地补充。
