# Requirement: Machine Contract 第二批 2B-2 后续 — S01-P06 非持久投影服务

## 状态

- **Owner Signoff：不适用（无 enum 决策；依赖参数多 TBC，走 CONSUMED_UNFROZEN_CONTRACT）**
- **Independent Review：未开始**
- **执行授权：CR-20260816-003 OPTION_A（开发 agent 一开到底，门禁降级为 Quality 验证项）**

## 背景

S01-P05 已落 2B-2 的 13 张持久表 DDL + Model/DAO/Service 骨架。本包（S01-P06）实现 7 个**非持久投影**（NOT_PERSISTED，禁止建表）的服务端聚合服务，输出只读投影 DTO + 数据新鲜度元数据。

这些投影依赖的规则参数（OTC fee/limit/库存、Power 消耗、Feature 规则、安全策略）在 06 大多为 `TBC`（未获生产批准值）。依据 05 §9「Mock 数据绝不作为 production fallback」、§10「未生产批准保持 TBC/null」与 06 约束，**未冻结依赖一律默认 deny / UNAVAILABLE，绝不 mock、绝不回退旧值、绝不由前端推导**。

## 范围（7 个禁止建表对象）

```text
FeatureEntitlement   — 功能资格（allowed/denied/reason_codes/allowed_actions）
OtcEligibility       — OTC 买卖资格
OtcCapacity          — OTC 容量
PowerImpactPreview   — Power 影响预览（仅服务端计算）
SecurityProfile      — 安全画像
SessionDevice        — 会话设备
LoginAudit           — 登录审计投影
```

## 目标文件

```text
library/response/{entitlement,otc,power,auth}/<Object>Response.php（7）
library/service/{entitlement,otc,power,auth}/<Object>ProjectionService.php（7）
support/extend/ProjectionResponse.php（公共 Response 基类，含 05 §10 元数据）
support/extend/ProjectionService.php（公共 Projection 基类，含 source 解析/default-deny）
tests/projection/<Object>ProjectionTest.php（7）
.project-ai/tasks/TASK-20260816-005/{requirement,design,acceptance}.md
```

## 规则（约束）

1. **禁止建表**：7 对象全部 NOT_PERSISTED，不生成任何 DDL，不写任何表。
2. **只读聚合**：不得把聚合结果写回新表/旧表；投影仅返回内存计算结果。
3. **默认 deny**：聚合依赖（规则参数）未冻结/无 Active Release 时，返回 `data_status=UNAVAILABLE` + `source_status=UNAVAILABLE`，资格类投影 `allowed=false` + 明确 `reason_code`。
4. **无 mock fallback**：TBC/null 不回退旧值、不填充占位 mock、不前端推导。
5. **数据新鲜度元数据**：每个响应含 05 §10 的 8 字段（`data_status/as_of/updated_at/next_refresh_at/refresh_hint/stale_after/snapshot_id/source_status`）。
6. **decimal string / 禁 float**：容量、Power、金额类数值一律 decimal string。
7. **脱敏与存在性**：SecurityProfile/SessionDevice/LoginAudit 越权访问返回安全 reason，不泄露对象存在性（05 §11.1 字段权限≠数据范围权限）。
8. **namespace 复用**：不另造第二套根目录，复用 `library\service`、`library\response`、`support\extend`。

## 非目标（NON_GOALS）

- 不实现任何业务写流程（OTC 下单、Robot 启动、Withdrawal 等属 STAGE-02）。
- 不冻结任何参数（参数冻结属 06 / ParameterRelease 流程）。
- 不建表、不实现 Controller/OpenAPI 路由。
- 不接入真实风控/审计引擎（仅投影骨架 + 单元测试）。

## 交接声明（必填，供 Quality 审核登记）

```text
CONSUMED_UNFROZEN_CONTRACT = YES（OTC/Power/Feature/安全 规则参数 TBC，按默认 deny 实现）
OPEN_OWNER_DECISION = YES（LoginAudit source-of-truth 未在 05 明确；FeatureEntitlement allowed_actions 字段来源待裁决）
```

## 信息来源

- `05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3 字段表 / §9 前端规则 / §10 新鲜度 / §11 RBAC）
- `06_PARAMETER_DICTIONARY.md`（OTC/Power/Feature 参数 TBC 状态）
- `07_DEVELOPMENT_AND_ACCEPTANCE.md`（S01-P06 固定步骤 / 验证 / 验收）
- `.project-ai/manifest.yaml`（CR-20260816-003 一开到底授权）
