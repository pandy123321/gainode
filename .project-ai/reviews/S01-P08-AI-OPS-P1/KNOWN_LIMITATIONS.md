# S01-P08 已知限制（Known Limitations）

## 1. Owner 未签（核心限制）

三对象（AISignal/AIRecommendation/SimulationRun）status enum、retention、供应商许可、writer、预算连接、模型版本等 9 项（D1~D9）均 **OWNER_DECISION_REQUIRED**。未签前：

```text
CONTRACT_GAP = YES（不建表不建 Service）
STATUS_MACHINE = FAIL_CLOSED（无合法转移）
MODEL_VERSION = NULL（禁推理）
BUDGET_LINK = CLOSED（禁预算映射）
```

## 2. C 端泄露边界（LOCKED，非限制而是硬约束）

07 §S01-P08 固定边界：C 端不得返回 arbitrage signal、profit、position 或供应商原始 payload。此约束锁定（D10），违反即 Scope Finding，不可申请豁免。所有快照 2 serializer 必须 deny 内部字段。

## 3. 源文档待定参数

- **retention 天数**（D4）：06 未定义供应商信号/raw payload 保留期限，具体天数待 Owner 填写。
- **供应商许可**（D5）：BetBurger/API-Football 再分发条款未明确，默认最保守「仅内部使用」。

## 4. V1.x 语义边界

- V1.x `arbitrage_project*`（矿机项目/订单/分销）属 V1.x 矿机套利模式，V6.1 废弃，**不迁移**。
- V1.x `arbitrage_signal`/`arbitrage_signal_raw` 仅作 AISignal 字段候选来源，不继承矿机语义。
- V1.x 硬编码 BetBurger/API-Football secret **不沿用**，迁移到 `.env`，缺失 fail-closed。

## 5. 依赖合同未冻结

- 预算连接（D8）依赖 06 §4 AI 参数（TBC）+ 02 §5.4 生产连接（未签）。
- model_version（D9）依赖 06 参数生命周期（Definition→Release）。
- 本包不消费这些未冻结合同，仅登记为决策依赖。

## 6. 工程边界

- 本包无代码、无 DDL、无测试（合同盘点包）。
- `.gitignore` 忽略 `0.5代码/gainode后端/`，freeze 文档经 `git add -f` 强制跟踪（与既有文件同机制）。
- V1.x `_existing_prod/` 为只读盘点对象，未在本包改动。
