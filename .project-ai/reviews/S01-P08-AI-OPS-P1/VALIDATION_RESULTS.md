# S01-P08 验证结果（Validation Results）

## 机械断言

```text
OBJECT_COUNT = 3（AISignal / AIRecommendation / SimulationRun）               PASS
DDL_TABLE_COUNT_DELTA = 0                                                     PASS
OWNER_DECISION_COUNT = 9（D1~D9）                                             PASS
LOCKED_COUNT = 1（D10 C 端边界）                                               PASS
C_ENDPOINT_INTERNAL_LEAK = FORBIDDEN                                          PASS
NO_LEGACY_MINER_INHERITANCE = YES                                             PASS
V1X_ARBITRAGE_UNTOUCHED = YES                                                 PASS
AI_PREDICTION_BUDGET_ISOLATION = YES                                          PASS
CONTRACT_GAP = YES（三对象不建表）                                             PASS
NO_SELF_INVENTED_STATE = YES（enum 全部候选）                                  PASS
NO_SELF_INVENTED_ROLE = YES（复用 05 §8）                                     PASS
DIFF_UNTRUNCATED = YES（37744 bytes）                                         PASS
SECRET_SCAN = PASS                                                            PASS
```

## V1.x 盘点核对（10 表）

| V1.x 表 | 结论 | 状态 |
|---|---|---|
| arbitrage_signal | ADAPT | ✅ |
| arbitrage_signal_raw | ADAPT | ✅ |
| arbitrage_fixture | KEEP_INTERNAL | ✅ |
| arbitrage_attempt | KEEP_INTERNAL | ✅ |
| arbitrage_day_plan | KEEP_INTERNAL | ✅ |
| arbitrage_position | KEEP_INTERNAL（FORBIDDEN_TO_EXPOSE） | ✅ |
| arbitrage_project | RETIRE | ✅ |
| arbitrage_project_order | RETIRE | ✅ |
| arbitrage_project_order_day | RETIRE | ✅ |
| arbitrage_project_order_logs | RETIRE | ✅ |

## 字段候选表核对

| 对象 | 字段数 | SOURCE_CONFIRMED | OWNER_DECISION_REQUIRED |
|---|---|---|---|
| AISignal | 12 | 8 | 4（source_status, retention_until, pii_secret_classification, status） |
| AIRecommendation | 11 | 9 | 2（model_version, status） |
| SimulationRun | 12 | 11 | 1（status） |

## Decision Matrix 核对（7 维度 + 1 LOCKED）

```text
状态（D1/D2/D3）       = 覆盖 ✅
retention（D4）        = 覆盖 ✅
供应商许可（D5）       = 覆盖 ✅
writer（D6）           = 覆盖 ✅
重试/幂等（D7）        = 覆盖 ✅
预算连接（D8）         = 覆盖 ✅
模型版本（D9）         = 覆盖 ✅
C 端边界（D10）        = LOCKED ✅
```

## 非目标验证

```text
DDL/Model/DAO/Service/command 生成 = NOT_RUN（合同未 FROZEN）✅
负向测试                          = NOT_RUN（快照 2）✅
AI 采集/推荐/模拟写流程            = NOT_RUN（STAGE-02）✅
```

## 一致性核对

- decision_request.md D1~D9 与 design.md Decision Matrix 逐项一致；D10 标记 LOCKED 且声明不可豁免。
- Freeze 文档 §4 状态合同候选 enum 与 decision_request RECOMMENDED_OPTION 一致。
- Freeze 文档显式声明 CANDIDATE（未 FROZEN）+ FAIL_CLOSED。
