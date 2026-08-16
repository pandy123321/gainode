# S01-P07 验证结果（Validation Results）

## 机械断言

```text
OBJECT_COUNT = 3（Agent / Referral / AgentEarning）                        PASS
DDL_TABLE_COUNT_DELTA = 0                                                 PASS
OWNER_DECISION_COUNT = 11（D1~D11）                                       PASS
NO_LEGACY_COMMISSION_INHERITANCE = YES                                    PASS
P0_DEFAULT_CLOSED = YES                                                   PASS
V1X_MEMBER_USER_TEAM_UNTOUCHED = YES                                      PASS
CONTRACT_GAP = YES（三对象不建表）                                         PASS
NO_SELF_INVENTED_STATE = YES（enum 全部候选）                              PASS
NO_SELF_INVENTED_ROLE = YES（复用 05 §8）                                 PASS
DIFF_UNTRUNCATED = YES（49240 bytes）                                     PASS
SECRET_SCAN = PASS                                                        PASS
```

## 字段候选表核对

| 对象 | 字段数 | SOURCE_CONFIRMED | OWNER_DECISION_REQUIRED |
|---|---|---|---|
| Agent | 11 | 9 | 2（status, level） |
| Referral | 9 | 8 | 1（status） |
| AgentEarning | 11 | 9 | 2（status, budget_source） |

> 备注：`parent_path`、`parent_id` 语义（层级深度 D4 / 重复归属 D5 / 解绑 D6）在 Decision Matrix 标注为 OWNER_DECISION_REQUIRED，字段本身标 SOURCE_CONFIRMED（字段名来自 V1.x）。

## Decision Matrix 核对（9 维度）

```text
状态（D1/D2/D3）       = 覆盖 ✅
层级深度（D4）         = 覆盖 ✅
重复归属（D5）         = 覆盖 ✅
解绑/更换（D6）        = 覆盖 ✅
确认时点（D7）         = 覆盖 ✅
预算来源（D8）         = 覆盖 ✅
回滚/reversal（D9）    = 覆盖 ✅
税务/合规（D10）       = 覆盖 ✅
PII（D11）             = 覆盖 ✅
```

## 非目标验证

```text
DDL/Model/DAO/Service 生成 = NOT_RUN（合同未 FROZEN）✅
负向测试                   = NOT_RUN（快照 2）✅
奖励发放写流程             = NOT_RUN（STAGE-02）✅
```

## 一致性核对

- decision_request.md 的 D1~D11 与 design.md 的 Decision Matrix 逐项一致（ID/对象/OPTION_A/OPTION_B/RECOMMENDED_OPTION）。
- Freeze 文档 §3 状态合同候选 enum 与 decision_request RECOMMENDED_OPTION 一致。
- Freeze 文档显式声明 CANDIDATE（未 FROZEN）+ FAIL_CLOSED。
