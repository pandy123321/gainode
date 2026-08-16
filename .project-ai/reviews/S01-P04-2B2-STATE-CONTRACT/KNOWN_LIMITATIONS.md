# KNOWN_LIMITATIONS — S01-P04 · 2B-2 状态合同补齐

## 前置状态（非阻塞，已明确）

```text
S01-P04 enum = 已 Owner 裁决（2B2-ENUM-01..03 = OPTION_A，2026-08-16）并补入 05 §4 V2.4
S01-P04 转移矩阵 = CANDIDATE（未 FROZEN，待质量 agent State Machine gate）
本包 = 仅合同文档，不建 DDL、不写代码、不消费未冻结转移矩阵
```

## 工具限制（沿用，不影响本包完整性）

### L1：AI Code Review Assistant 的 diff 硬截断

```text
LIMITATION = 工具将 diff 硬截断在 25000 字符（settings.json 的 max_diff_chars 无效）
MITIGATION = 本包 DIFF.txt 由 git diff 直接生成，完整未截断（39707 字符），为权威输入
```

### L2：ChatGPT Web 审核桥接

```text
LIMITATION = complete_project_context_update 的 ChatGPT 同步绑定可能 stale/failed
MITIGATION = 提审由质量 agent 负责；本隔离快照包 + QUALITY_REVIEW_PROMPT.md 已就绪
```

## 未定义维度（非本包范围）

```text
转移矩阵（5 复用对象 + 3 缺 enum 对象）= CANDIDATE，本包未实现任何转移逻辑
生产参数（大额阈值、通知重试参数等）= TBC，本包未使用
```

## 状态声明

```text
2B-2 enum = 5 复用（复制 05）+ 3 裁决（补 05 §4 V2.4）
2B-2 转移矩阵 = 候选，未 FROZEN
2B-2 DDL / Model / DAO / Service = NOT_STARTED（属 S01-P05）
本包不声明任何对象 FROZEN；不声明任何转移矩阵 FROZEN
```
