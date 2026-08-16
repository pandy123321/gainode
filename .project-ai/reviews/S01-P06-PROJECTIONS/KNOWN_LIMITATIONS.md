# S01-P06 已知限制

## 前置条件（依赖未冻结，default-deny 兜底）

| 对象 | 依赖 | 状态 |
|---|---|---|
| FeatureEntitlement | 06 Feature 规则参数 | TBC → allowed=false |
| OtcEligibility | OTC 参数（fee/limit/库存）+ Power 阈值 | TBC → MAINTENANCE/默认 deny |
| OtcCapacity | otc.inventory_limit / reserve_ratio | TBC → 字段 null |
| PowerImpactPreview | AI.power_* 消耗/冻结/释放/Cap | TBC → allowed=false，字段 null |
| SecurityProfile | 安全策略（登录历史窗口/可疑标记/改密） | TBC → 字段 null |

## Contract Gap（交接 Owner 决策，不阻塞实现）

| # | 对象 | Gap | 建议 |
|---|---|---|---|
| G1 | LoginAudit | source-of-truth 未明确（V1.x user_logs vs MC2 audit_events） | Owner 裁决复用 MC2 audit_events 或 V1.x user_logs |
| G2 | FeatureEntitlement | allowed_actions 字段 05 §3 缺失（07 步骤 4 要求） | Owner 裁决补 05 §3 或降级为派生字段 |
| G3 | OtcEligibility.capacity | capacity 结构未在 05 明确 | 默认 null，待 06 OTC 参数冻结 |

## 工程边界

1. **越权处理不用 AuthorizeException**：该异常构造器触发 LangKeyService 写 DB，单元测试（SQLite in-memory）会失败。越权统一返回 UNAVAILABLE + access_denied reason。Controller 层接入时需把 `data_status=UNAVAILABLE && refresh_hint=access_denied` 映射为 403/404（不泄露存在性）。

2. **与 V1.x auth 代码的边界**：`library/service/auth/` 下已有 V1.x `AuthService`/`LoginService` 等，与本次投影服务无重叠（投影服务命名 `*ProjectionService`）。本次不触碰 V1.x 代码。

3. **SessionDevice.revocable**：撤销规则（哪些会话可撤销）未冻结 → 默认 false（fail-closed），前端不显示撤销入口，待安全策略冻结后放开。

4. **测试为独立 CLI 脚本**（非 PHPUnit），对齐 S01-P03 的 LedgerAppendOnlyMutationMatrixTest 风格；`_bootstrap.php` 的 SQLite in-memory 建表仅用于测试，非生产 DDL。
