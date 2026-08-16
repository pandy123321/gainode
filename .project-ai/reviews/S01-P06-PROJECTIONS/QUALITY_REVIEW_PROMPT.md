# S01-P06 独立审核提示词（Quality Agent）

你是 Gainode 项目的独立质量审核 agent。请对 S01-P06（非持久投影服务）进行 **Evidence First** 审核：每条结论必须有文件/行号/命令输出支撑，禁止臆测。

## 审核对象

- 实现 commit：`0e5c0ae`（分支 `feature/gainode-v3-serial-development`）
- 复审包目录：`.project-ai/reviews/S01-P06-PROJECTIONS/`
- 范围：7 个非持久投影（FeatureEntitlement/OtcEligibility/OtcCapacity/PowerImpactPreview/SecurityProfile/SessionDevice/LoginAudit）

## 必查清单（逐项给出 PASS/FAIL + 证据）

1. **NOT_PERSISTED**：确认本包无任何生产 DDL（`_bootstrap.php` 的 SQLite in-memory 建表是测试引导，不算生产 DDL）。
2. **字段对齐 05 §3**：7 个 Response 的字段是否严格对齐 `05_DATA_STATE_PERMISSION_API_CONTRACT.md` §3；除 `allowed_actions`（G2，已登记）外不得自创字段。
3. **默认 deny**：未冻结依赖（OTC/Power/Feature/安全参数）是否统一返回 `data_status=UNAVAILABLE` + `allowed=false`（资格类）或字段 null（profile 类）。
4. **无 mock fallback**：TBC 字段是否 null/空，绝不回退旧值、不填占位 mock、不前端推导。
5. **decimal string**：capacity/power 数值是否为 string（`PowerImpactPreviewResponse.available_before` 等），无 float。
6. **越权不泄露存在性**：SecurityProfile/SessionDevice/LoginAudit 跨用户访问是否统一返回 UNAVAILABLE，且存在/不存在不可区分。
7. **reason_code 枚举**：OtcEligibility 仅使用 05 §3 冻结七选一，不覆盖 OtcOrder.status。
8. **元数据 8 字段**：每个 Response 是否含 05 §10 的 8 字段。
9. **测试覆盖**：REALTIME/UNAVAILABLE/越权/无 mock 是否有单元测试证据。

## 交接声明核验

- `CONSUMED_UNFROZEN_CONTRACT = YES`（OTC/Power/Feature/安全 参数 TBC）
- `OPEN_OWNER_DECISION = YES`（G1/G2/G3）
- 按 CR-20260816-003，07 §S01-P06 的「前置/停止条件/Stage Gate」降级为验证项，请逐项登记为 Finding（不阻塞 Dev），符合 07 §15 DEV_GATE_VIOLATIONS。

## 输出格式

```text
VERDICT = APPROVED | CHANGES_REQUIRED
P0（阻塞，必须修）:
P1（重要）:
P2（建议）:
P3（非阻塞备注）:
NEXT_PACKAGE_RECOMMENDATION = <下一包建议>
```
