# KNOWN_LIMITATIONS — MC2 复审包

## 工具限制（已记录，不影响本包完整性）

### L1：AI Code Review Assistant 的 diff 硬截断

```text
LIMITATION = 工具的 get_latest_commit / start_latest_reviews 将 diff 硬截断在 25000 字符
ROOT_CAUSE = 工具内部硬编码上限（settings.json 的 max_diff_chars 键对其无效，已实验验证）
IMPACT = 依赖该工具提交的 ChatGPT Web 审核只能看到截断 diff
MITIGATION = Development Agent 已直接通过 git diff 生成完整未截断 DIFF.txt（41930 字符）
            复审 Agent 应以本包 DIFF.txt / files_at_impl/*.txt 为权威输入，而非工具截断输出
```

### L2：工具只能复审「latest commit」

```text
LIMITATION = start_latest_reviews 只针对项目当前 HEAD（现为 fd7968b），无法指定 2795e38
IMPACT = 无法通过该工具直接对 2795e38 发起复审
MITIGATION = 以本隔离快照包（绑定 2795e38 + SHA256）作为权威复审对象
```

## 合同未定义维度（非本包范围）

```text
shortfall 后是否生成 RiskCase / 账户是否 restricted / OTC·Withdrawal·Robot 是否禁启 / 是否需 ApprovalRequest
= deferred 至 2B-2，冻结前不执行（SHORTFALL_UNDECIDED_EXECUTION = 0）
```

## 状态声明

```text
MC2 状态 = IMPLEMENTED / RE_REVIEW_PENDING（未 FROZEN）
本包不声明 REVIEW_PACKAGE_TRUNCATED = NO 之外的任何闭环
```
