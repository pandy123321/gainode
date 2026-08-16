# S01-P09 已知限制（Known Limitations）

## 1. 矩阵为自检产物（核心限制）

`STAGE-01-OBJECT-COVERAGE-MATRIX.md` 由 Development Agent 汇总生成，标注 **CANDIDATE**。最终 STAGE-01 Gate 由 Quality Agent 独立核对后输出 `STAGE-01-QUALITY-GATE.md`。矩阵可能因核对偏差需回改。

## 2. 21 个未冻结可写路径

2B-1（8 表）+ 2B-2（13 表）状态转移矩阵均 **CANDIDATE**，未 FROZEN 前所有写路径 FAIL_CLOSED。这些对象不可进入 STAGE-02 实现，直至 Owner 签署 + Independent Review 通过。

## 3. 6 个合同盘点对象 Owner 未签

S01-P07（Agent/Referral/AgentEarning，D1~D11）+ S01-P08（AISignal/AIRecommendation/SimulationRun，D1~D9 + D10 LOCKED）共 6 对象未建表，建 DDL 快照 2 阻塞在 Owner 决策签署。

## 4. 生产参数 TBC

06 参数字典中多项生产参数（AI 推理、OTC capacity、Power、Feature 等）仍 TBC/未批准，保持 null/closed。APT-C/Migration 关闭（`AI.apt_migration_enabled=false`）。

## 5. 工程边界

- 本包无代码、无 DDL、无测试（Stage 收口盘点包）。
- 本矩阵只覆盖 S01-P01~P08 已提交对象；V1.x `_existing_prod/` 只读盘点对象不在 43 对象统计内。
- `0.5代码/gainode后端/` 被 `.gitignore` 忽略，Freeze 文档经 `git add -f` 强制跟踪。
