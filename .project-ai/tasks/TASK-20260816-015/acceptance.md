# Acceptance: S02-P08 · 内部 AI 经济引擎

## 机械验收

| 项 | 方法 | 预期 |
|---|---|---|
| PHP lint | `php -l` 全部新建 PHP 文件 | 无语法错误 |
| Contract 测试 | `php tests/Contract/S02P08AiOpsContractTest.php` | 全部 PASS，0 fail |
| Integration 测试 | `php tests/Integration/S02P08AiOpsEngineTest.php` | 全部 PASS，0 fail |
| OpenAPI 解析 | 校验 `gainode-v2.yaml` 可解析 + aiops schema 注册 | 通过 |

## 业务验收（07 §S02-P08 验收）

| 验收项 | 落地 | 状态 |
|---|---|---|
| 四字段可从 snapshot 完整重放 | source_hash/rule_version/snapshot_id/parameter_release_id 进决策元数据 | ✅ |
| daily budget 不超过五类上限最小值 | `DailyAIBudgetService::computeDaily` 取 min | ✅（纯函数） |
| 不直接修改用户 APT | 不触碰 apt_accounts 余额，只产出预算字段 | ✅ |
| 内部边界有自动负向测试 | `BudgetDecision::forExternal` 负向扫描测试 | ✅ |

## 停止条件核对

- smoothing、price source、mapping multiplier、任何 cap、rounding 未由 Active Release 定义 → 引擎保持 closed，未写默认值。
- 预算持久对象未冻结 → `AiBudgetEngine::persist` FAIL_CLOSED（DEPENDENCY_UNAVAILABLE 503），未建 DDL。

## 合同缺口（登记，不阻塞）

- **S02-P08-BUDGET-PERSISTENCE**：预算决定持久对象（Budget Decision 表）未冻结 → 步骤 7 持久化/Audit/Outbox fail-closed，需 Owner/合同冻结后建表（`NEEDS_OWNER_DECISION`）。
- **S02-P08-PARAMS**：smoothing 规则 / APT reference price source / mapping_multiplier / 四 cap / rounding 参数键在 06 参数字典未定义（全 TBC）→ 需 Parameter Release 冻结后方可解除 closed。

## 结论

S02-P08 交付纯计算内核（ConfirmedProfitAdapter 去重/短路/min/bcmath 精度完整实现 + 确定性 `reference_profit=0` 短路）与 fail-closed 边界（positive smoothing / price / multiplier / cap / 持久化）。测试覆盖全部 07 验证项中可离线验证的部分，其余（价格源、汇率、真实 smoothing、持久化）因依赖未冻结而 fail-closed，符合停止条件。
