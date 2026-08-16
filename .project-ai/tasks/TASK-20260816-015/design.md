# Design: S02-P08 · 内部 AI 经济引擎（计算流水线 + fail-closed）

## Part A — 计算流水线（07 §S02-P08 步骤 1-8）

```text
输入（内部可审计执行结果）
  │  ConfirmedProfitAdapter.normalize()          —— 确认/可追溯/去重/归一化（纯逻辑）
  ▼
confirmed_profit（decimal string）
  │  ReferenceProfitService.computeReference()   —— <=0 短路=0；>0 需 smoothing（fail-closed）
  ▼
reference_profit
  │  AptBudgetMappingService.mapToApt()          —— /price*multiplier（bcmath；price/multiplier fail-closed）
  ▼
mapped_apt_budget
  │  DailyAIBudgetService.computeDaily()         —— min(mapped, 4 caps)（caps fail-closed）
  ▼
daily_ai_budget
  │  AiBudgetEngine.persist()                    —— 持久化/Audit/Outbox（预算对象未冻结 → fail-closed）
  ▼
BudgetDecision（四字段 + 版本/审计元数据）
```

## Part B — 各组件实现策略

### B1. ConfirmedProfitAdapter（纯逻辑，完整实现）

- 常量：`SOURCE_STATUS_CONFIRMED/UNCONFIRMED`、`DEFAULT_CURRENCY=USDT`。
- `normalize(array $raw): array` 纯函数：
  - `confirmed` 非 true → `VALIDATION_ERROR`（400）未确认拒绝。
  - `source_object_type` / `source_object_id` 非空（可追溯）→ 否则 `VALIDATION_ERROR`。
  - `currency` 非空；`confirmed_at` > 0（有效 Unix 秒）；否则 `VALIDATION_ERROR`。
  - `profit_amount` 为合法 decimal string 且 >= 0；否则 `VALIDATION_ERROR`。
  - 计算 `source_hash`（sha256(source_object_type|source_object_id|raw_payload_hash)），未提供 `raw_payload_hash` 则用 source_object_id 兜底。
  - 计算 `dedupe_key`（sha256 前 64 位）。
  - 返回归一化数组（`confirmed_profit`/`source_object_type`/`source_object_id`/`source_hash`/`currency`/`confirmed_at`/`dedupe_key`）。
- `assertNotDuplicate(array $confirmed, IdempotencyStore $store): void`：store 不可用 → `DEPENDENCY_UNAVAILABLE`（fail-closed，无法保证去重）；`store.find(dedupe_key)` 非空 → `IDEMPOTENCY_CONFLICT`（409）。

### B2. ReferenceProfitService（<=0 短路完整实现；>0 smoothing fail-closed）

- 常量：`ALGORITHM_ZERO_SHORTCUT`、`ALGORITHM_APPROVED_SMOOTHING`。
- `computeReference(string $confirmedProfit, ?array $smoothingContext): array` 纯逻辑：
  - `bccomp(confirmedProfit, '0', 18) <= 0` → 确定性返回 `reference_profit = '0'`（`algorithm=ZERO_SHORTCUT`，不调用 smoothing）。
  - `> 0` 且 `smoothingContext` 为空（Active Release approved smoothing 规则 + historical_reference snapshot 缺失）→ `DEPENDENCY_UNAVAILABLE`（503）。
  - `> 0` 且有上下文 → 记录 `algorithm=APPROVED_SMOOTHING` + `rule_version` + `input_hash`，数值由外部 approved rule 提供（本包不实现具体算法，仅透传 + 校验非负）。

### B3. AptBudgetMappingService（price/multiplier fail-closed；bcmath 精度完整实现）

- `mapToApt(string $referenceProfitUsdt, ?string $aptReferencePrice, ?string $mappingMultiplier): array`：
  - `reference_profit <= 0` → 确定性 `mapped_apt_budget = '0'`（短路，不需 price/multiplier）。
  - `aptReferencePrice` 为空/<=0 → `DEPENDENCY_UNAVAILABLE`（缺失/过期/<=0，不除零、不回退 mock）。
  - `mappingMultiplier` 为空/<=0 → `DEPENDENCY_UNAVAILABLE`。
  - 否则 `mapped = bcdiv(reference, price, 18)` 再 `bcmul(mapped, multiplier, 18)`，全程 bcmath。
- 纯计算函数 `computeMapped(...)` 可单独测试除零/精度。

### B4. DailyAIBudgetService（min 纯函数完整实现；cap 读取 fail-closed）

- `computeDaily(array $candidates): string` 纯函数：`min(mapped_apt_budget, stage_expected_budget, stage_hard_cap, cash_support_cap, human_approved_cap)`（bcmath `bccomp` 取最小）。
- `resolveCaps(?array $caps): array`：四 cap 任一缺失 → `DEPENDENCY_UNAVAILABLE`。

### B5. AiBudgetParameterReader（Parameter/Snapshot adapter，仿 RobotRuleReader）

- 键常量（06 未定义，标注 TBC）：`AI.reference_profit_smoothing_rule` / `AI.apt_budget_mapping_multiplier` / `AI.stage_expected_budget` / `AI.stage_hard_cap` / `AI.cash_support_cap` / `AI.human_approved_cap` / `AI.ai_budget_rounding_precision`。
- `getBudgetParameterSnapshot(): array`：读 Active Release → Snapshot（复用 `ParameterReleaseDao::getActive()` + `ParameterSnapshotDao::get()`），返回 `source_status`（AVAILABLE/UNAVAILABLE）+ 各参数 decimal string。无 Active Release 或键缺失 → 单项 ''（fail-closed）。
- 零写入，禁 float，禁 mock。

### B6. BudgetDecision（内部 DTO）

- 字段：`confirmed_profit` / `reference_profit` / `mapped_apt_budget` / `daily_ai_budget` / `algorithm` / `rule_version` / `source_hash` / `parameter_release_id` / `snapshot_id` / `business_date` / `idempotency_key`。
- `forInternal(): array` 完整结构（含审计元数据）。
- `forExternal(): array` 脱敏：仅四字段 + `source_status`，不暴露 supplier/signal/profit detail/position/cap 明细/内部模型参数（步骤 8 负向扫描）。

### B7. AiBudgetEngine（编排 + 持久化 fail-closed）

- `generate(array $input, ?IdempotencyStore $idem = null, ?OutboxStore $outbox = null): array`：
  1. ConfirmedProfitAdapter.normalize
  2. ReferenceProfitService.computeReference（<=0 短路可完整跑通；>0 fail-closed）
  3. AptBudgetMappingService.mapToApt
  4. DailyAIBudgetService.computeDaily
  5. `persist()`：预算持久对象未冻结 → `DEPENDENCY_UNAVAILABLE`（不建表、不写默认值）。
- `buildIdempotencyKey(...)`：`sha256(source|parameter_release|snapshot|business_date)` 前 64 位。
- 持久化/AuditEvent/Outbox 全部 fail-closed（预算对象、Outbox 表、audit 关联均未冻结或未启用）。

## Part C — 文件清单

| 文件 | 动作 | 说明 |
|---|---|---|
| `library/service/aiops/ConfirmedProfitAdapter.php` | 新建 | 输入校验/去重/source hash/dedupe_key |
| `library/service/aiops/ReferenceProfitService.php` | 新建 | <=0 短路 + smoothing fail-closed |
| `library/service/aiops/AptBudgetMappingService.php` | 新建 | bcmath mapping + price/multiplier fail-closed |
| `library/service/aiops/DailyAIBudgetService.php` | 新建 | min 纯函数 + caps fail-closed |
| `library/service/aiops/AiBudgetParameterReader.php` | 新建 | Parameter/Snapshot adapter |
| `library/service/aiops/BudgetDecision.php` | 新建 | 内部 DTO + 脱敏 serializer |
| `library/service/aiops/AiBudgetEngine.php` | 新建 | 编排 + 持久化/Outbox/Audit fail-closed |
| `openapi/components/schemas/aiops.yaml` | 新建 | AiBudgetDecision/AiBudgetParameterSnapshot schema |
| `openapi/gainode-v2.yaml` | 更新 | 注册 aiops schema |
| `tests/Contract/S02P08AiOpsContractTest.php` | 新建 | 纯逻辑：短路/精度/min/去重/fail-closed/脱敏负向 |
| `tests/Integration/S02P08AiOpsEngineTest.php` | 新建 | 流水线端到端（<=0 完整跑通，>0 fail-closed） |

## Part D — 验证矩阵（07 §S02-P08 验证项映射）

| 07 验证项 | 本包落地方式 |
|---|---|
| profit<=0 | ReferenceProfitService 短路 reference_profit='0'（确定性） |
| positive smoothing | >0 且无 Active Release → DEPENDENCY_UNAVAILABLE |
| missing/stale price | AptBudgetMappingService price 空/<=0 → DEPENDENCY_UNAVAILABLE |
| zero price | price='0' → DEPENDENCY_UNAVAILABLE（不除零） |
| missing cap | DailyAIBudgetService 任一 cap 缺失 → DEPENDENCY_UNAVAILABLE |
| cap min selection | computeDaily 纯函数取五值最小 |
| precision/rounding | bcmath scale=18 + 除零保护；rounding 参数 TBC fail-closed |
| same input replay | source_hash + dedupe_key 幂等（store 可用时 IDEMPOTENCY_CONFLICT） |
| parameter changed snapshot isolation | parameter_release_id + snapshot_id 进 idempotency key |
| concurrent budget generation | 幂等键去重（持久化对象未冻结 → fail-closed） |
| C端字段泄漏 | BudgetDecision.forExternal 负向测试 |
| audit reproducibility | source_hash/rule_version/snapshot_id 进决策元数据 |

## 信息来源

- `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md` §5.4/§11
- `06_PARAMETER_DICTIONARY.md` §4
- `07_DEVELOPMENT_AND_ACCEPTANCE.md` §S02-P08
- `MACHINE_CONTRACT_AI_OPERATIONS_P1_FREEZE.md` §5 D8
- `.project-ai/tasks/TASK-20260816-014/design.md`（S02-P07 先例）
- `library/service/robot/RobotRuleReader.php`（adapter 先例）
- `library/service/ledger/AptAccountService.php`（bcmath 先例）
