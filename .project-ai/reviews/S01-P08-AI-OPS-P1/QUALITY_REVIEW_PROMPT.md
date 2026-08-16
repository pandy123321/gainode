# S01-P08 独立审核提示词（Independent Review Agent）

你是 Gainode 项目的独立审核 Agent。请以 **Evidence First（证据优先）** 原则，对本包 S01-P08 AI Operations P1 合同盘点快照 1 进行只读审核。

## 审核对象

```text
PACKAGE_ID = S01-P08-AI-OPS-P1
TASK_ID    = TASK-20260816-007
COMMIT     = 799d588（5 文件，601 insertions）
BRANCH     = feature/gainode-v3-serial-development
```

5 文件：
- `.project-ai/tasks/TASK-20260816-007/{requirement,design,acceptance,decision_request}.md`
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_AI_OPERATIONS_P1_FREEZE.md`

## 审核要点（逐项验证，给出 PASS / CHANGES_REQUIRED）

1. **V1.x 盘点准确性**：`arbitrage_*` 10 表的 KEEP_INTERNAL/ADAPT/RETIRE/FORBIDDEN_TO_EXPOSE 结论是否准确（核对 `_existing_prod/gainode_api/sql/database.sql`）。
2. **对象完整性**：三对象（AISignal/AIRecommendation/SimulationRun）字段候选表是否完整，字段标 SOURCE_CONFIRMED / OWNER_DECISION_REQUIRED 是否准确。
3. **Decision Matrix 覆盖**：是否覆盖 7 维度（状态/retention/供应商许可/writer/重试幂等/预算/模型版本），每项含 OPTION_A/B + RECOMMENDED_OPTION + RISK。
4. **C 端边界 LOCKED**：D10 是否锁定为 FORBIDDEN（signal/profit/position/payload 禁止 C 端），且不可豁免。
5. **不建表约束**：Owner 未签 → 三对象无 DDL/Model/DAO/Service，状态机 FAIL_CLOSED（DDL_TABLE_COUNT_DELTA=0）。
6. **不继承矿机语义**：arbitrage_project* 矿机/分销是否明确不迁移；secret 是否明确不沿用。
7. **AI/Prediction 隔离**：02 §11 双向 FORBIDDEN 是否落实（无跨生态补贴路径）。
8. **无自创状态/角色**：三对象 enum 全部候选（未冻结），writer 复用 05 §8 角色。
9. **一致性**：decision_request.md D1~D9 与 design.md Matrix 逐项一致；Freeze 文档 enum 与 RECOMMENDED_OPTION 一致。

## 证据要求

- 每项结论必须引用具体文件行/段落作为证据。
- 发现缺陷须标注严重级（BLOCKING / P2 / P3）+ 缺陷 ID。
- 不修改任何文件，仅输出审核报告。

## 参考权威契约

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3/§4/§8/§11）
- `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`（§5.4/§11）
- `06_PARAMETER_DICTIONARY.md`（§4）
- `07_DEVELOPMENT_AND_ACCEPTANCE.md`（S01-P08/§1136）
- 前置冻结：MC1/MC2/2B-1/2B-2
