# Acceptance: S01-P06 非持久投影服务

## 状态

- **执行授权**：CR-20260816-003 OPTION_A
- **Independent Review：未开始**
- **冻结状态**：投影依赖参数多 TBC，默认 deny

## 验收清单

| # | 验收项 | 状态 |
|---|---|---|
| 1 | 7 对象全部 NOT_PERSISTED，无任何 DDL | 待独立审核 |
| 2 | 7 Response DTO + 7 ProjectionService + 2 公共基类 | 待独立审核 |
| 3 | 每个响应含 05 §10 的 8 元数据字段 | 待独立审核 |
| 4 | 未冻结依赖默认 deny（allowed=false + UNAVAILABLE，字段 null 不回退旧值） | 待独立审核 |
| 5 | 无 mock fallback、无前端资格推导 | 待独立审核 |
| 6 | decimal string / 禁 float（capacity/power） | 待独立审核 |
| 7 | 跨用户访问 FORBIDDEN（SecurityProfile/SessionDevice/LoginAudit） | 待独立审核 |
| 8 | 投影单元测试（REALTIME/UNAVAILABLE/越权/无 mock） | 待执行 |
| 9 | php -l 全部 PASS | 待执行 |
| 10 | git diff --check 无空白错误 | 待执行 |
| 11 | 无新 DDL 检查（diff 中无 CREATE TABLE） | 待执行 |

## 机械一致性断言

```text
NOT_PERSISTED = YES
DDL_TABLE_COUNT_DELTA = 0（本包不新增任何表）
PROJECTION_COUNT = 7
RESPONSE_COUNT = 7
BASE_CLASS_COUNT = 2（ProjectionResponse + ProjectionService）
METADATA_FIELDS = 8（data_status/as_of/updated_at/next_refresh_at/refresh_hint/stale_after/snapshot_id/source_status）
NO_MOCK_FALLBACK = YES
DEFAULT_DENY = YES
DECIMAL_STRING = YES
CROSS_USER_ACCESS_FORBIDDEN = YES
CONTRACT_GAP_COUNT = 3（G1 LoginAudit source / G2 allowed_actions / G3 capacity 结构）
```

## 非目标验证（NOT_RUN，属后续包）

```text
真实 OTC/Power/Feature 资格计算 = NOT_RUN（参数未冻结）
业务写流程 = NOT_RUN（STAGE-02）
Controller/OpenAPI 路由 = NOT_RUN
参数冻结 = NOT_RUN（06 / ParameterRelease）
```

## 交付物

- `.project-ai/tasks/TASK-20260816-005/{requirement,design,acceptance}.md`
- `support/extend/ProjectionResponse.php`、`support/extend/ProjectionService.php`
- 7 `library/response/*/<Object>Response.php`
- 7 `library/service/*/<Object>ProjectionService.php`
- 7 `tests/projection/<Object>ProjectionTest.php`
