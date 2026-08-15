# KNOWN_LIMITATIONS — S01-P02 · 2B-1 状态合同补齐

## 工具限制（已记录，不影响本包完整性）

### L1：AI Code Review Assistant 的 diff 硬截断

```text
LIMITATION = 工具的 get_latest_commit / start_latest_reviews 将 diff 硬截断在 25000 字符
ROOT_CAUSE = 工具内部硬编码上限（settings.json 的 max_diff_chars 键对其无效，已实验验证）
IMPACT = 依赖该工具提交的 ChatGPT Web 审核只能看到截断 diff
MITIGATION = Development Agent 已直接通过 git diff 生成完整未截断 DIFF.txt（25522 字符）
            复审 Agent 应以本包 DIFF.txt / files_at_impl/*.txt 为权威输入，而非工具截断输出
```

### L2：工具只能复审「latest commit」

```text
LIMITATION = start_latest_reviews 只针对项目当前 HEAD，无法指定历史 commit c2d57ce
IMPACT = 无法通过该工具直接对 c2d57ce 发起复审
MITIGATION = 以本隔离快照包（绑定 c2d57ce + SHA256）作为权威复审对象
```

## 合同未定义维度（非本包范围，需 Owner 裁决）

```text
SettlementBatch/RefundCase/CorrectionCase/OtcTrade/RobotUpgradeOrder/ConsentReceipt 的 canonical enum
= CONTRACT_GAP / FAIL_CLOSED（Owner 裁决 enum → 补 05 §4 → 冻结后 S01-P03 建 DDL）
```

## 状态声明

```text
Result/Settlement 状态合同 = CANDIDATE（转移矩阵待 State Machine gate）
6 缺 enum 实体 = CONTRACT_GAP / FAIL_CLOSED
本包不声明 REVIEW_PACKAGE_TRUNCATED = NO 之外的任何闭环
本包不声明任何对象 FROZEN
```
