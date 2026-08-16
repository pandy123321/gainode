# S01-P09 独立审核提示词（Independent Review Agent）

你是 Gainode 项目的独立审核 Agent。请以 **Evidence First（证据优先）** 原则，对本包 S01-P09 STAGE-01 全量收口进行只读审核，并独立输出 `STAGE-01-QUALITY-GATE.md`。

## 审核对象

```text
PACKAGE_ID = S01-P09-STAGE01-CLOSURE
COMMIT     = 5e75ade（3 文件，161 insertions / 1 deletion）
BRANCH     = feature/gainode-v3-serial-development
```

3 文件：
- `.project-ai/reviews/STAGE-01-OBJECT-COVERAGE-MATRIX.md`
- `.project-ai/context.md`
- `.project-ai/manifest.yaml`

## 审核要点（逐项验证，给出 PASS / CHANGES_REQUIRED）

1. **对象总数与分类**：43 = 30 持久 + 7 投影 + 6 盘点是否准确，逐表对齐 S01-P01~P08 的 Machine Contract / 任务文档。
2. **矩阵 A（30 表）映射**：Model/Service/Freeze 是否与实际 DDL/类文件一致；重复 DDL=0；未知 writer=0。
3. **矩阵 B（7 投影）无表**：NOT_PERSISTED_TABLE_LEAK = 0（投影对象禁止建表）。
4. **矩阵 C（6 盘点）无表**：CONTRACT_GAP_TABLE_LEAK = 0（Owner 未签对象禁止建表）。
5. **工程约束机械比对**：Snowflake PK / object_version / idempotency_key（NotificationDelivery 例外 dedupe_key）/ decimal string / append-only（6 对象）。
6. **fail-closed 检查**：21 未冻结写路径、P0 增长奖励、C 端套利泄露（D10 LOCKED）、AI/Prediction 预算隔离、APT-C/Migration、生产参数 TBC 是否均 CLOSED/FAIL_CLOSED。
7. **进度指针一致性**：`context.md` / `manifest.yaml` 是否与矩阵结论一致。
8. **Production 判定**：STAGE-01 未全 FROZEN → Production = NO-GO，是否显式登记。

## 证据要求

- 每项结论必须引用具体文件行/段落作为证据（矩阵行 + 对应 Machine Contract / 任务文档）。
- 发现缺陷须标注严重级（BLOCKING / P2 / P3）+ 缺陷 ID。
- 不修改任何文件，仅输出审核报告 + `STAGE-01-QUALITY-GATE.md`。

## 参考权威契约

- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（S01-P09）
- `01_*`（领域模型）/ `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md` / `05_DATA_STATE_PERMISSION_API_CONTRACT.md` / `06_PARAMETER_DICTIONARY.md`
- 各 Machine Contract Freeze 文档（MC1/MC2/2B-1/2B-2/Affiliate/AI Ops）
