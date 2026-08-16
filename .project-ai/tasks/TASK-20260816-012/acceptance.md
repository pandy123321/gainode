# Acceptance: S02-P05 · Prediction P0（状态机骨架 + fail-closed + 只读投影）

## A. 功能验收

- [ ] A1. PredictionMarket 11 个纯状态转移（M1-M12 除 create）全实现；`create` FAIL_CLOSED。
- [ ] A2. PredictionOrder 4 个纯转移（P1-P4）实现；submit/退款/纠错路径 FAIL_CLOSED。
- [ ] A3. Result 3 个纯转移（RS3/RS4/RS5）实现；RS1/RS2 FAIL_CLOSED；`corrected` 终态不可转移。
- [ ] A4. Settlement 5 个纯转移（ST1/ST3/ST4/ST6/ST7）实现；ST2/ST5 FAIL_CLOSED；`paid` 终态。
- [ ] A5. SettlementBatch 5 个纯转移实现；`create` FAIL_CLOSED。
- [ ] A6. RefundCase/CorrectionCase approve/reject/execute/fail/retry 实现；create/complete FAIL_CLOSED。
- [ ] A7. ConsentReceipt `create`（幂等去重）+ `expire` 实现。
- [ ] A8. 只读投影（list/detail/allowedActions）实现且返回 `source_status`。

## B. 边界/不变量验收

- [ ] B1. 非法状态转移返回 `OBJECT_VERSION_CONFLICT`（409），无部分写入。
- [ ] B2. `object_version` CAS：并发写 `affected_rows=0` → 409，不覆盖。
- [ ] B3. 审计事件 append-only，`target_object_type` 正确，`audit_event_id` 回写。
- [ ] B4. 金额字段全 decimal string，无 float。
- [ ] B5. Result `correction_version` 守卫：官方结果仅可纠错一次。
- [ ] B6. 三状态轴不合并（Market `settled` ≠ Order `settled` ≠ Settlement `paid`）。
- [ ] B7. SoD：Result confirmer ≠ Settlement approver（角色白名单校验）。

## C. 测试验收

- [ ] C1. Contract 测试全绿（状态常量/Event Catalog/fail-closed/HTTP 映射）。
- [ ] C2. Integration 测试全绿（合法+非法转移/CAS/守卫/幂等/投影/fail-closed）。
- [ ] C3. `vendor/bin/phpunit` 整体无回归。

## D. 交付验收

- [ ] D1. OpenAPI `prediction.yaml`（paths+schemas）注册进 `gainode-v2.yaml`。
- [ ] D2. `manifest.yaml` `contextVersion` +1，`decisionSources` 新增 `stage02_p05_prediction_p0`（CANDIDATE）。
- [ ] D3. `context.md` 当前执行包指针更新。
- [ ] D4. 本地 commit（按 `.project-ai/rules/coding.md` 提交规范）。
