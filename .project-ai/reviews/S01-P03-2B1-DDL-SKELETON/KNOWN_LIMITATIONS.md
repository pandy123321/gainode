# KNOWN_LIMITATIONS — S01-P03 · 2B-1 DDL 与 Model/DAO/Service 骨架

## 前置状态（非阻塞，已明确）

```text
S01-P02 enum = 已 Owner 裁决并补入 05 §4（V2.3）
S01-P02 转移矩阵 = CANDIDATE（未 FROZEN，待质量 agent State Machine gate）
本包 = 仅骨架 + fail-closed guard，不消费未冻结转移矩阵
```

## 工具限制（沿用，不影响本包完整性）

### L1：AI Code Review Assistant 的 diff 硬截断

```text
LIMITATION = 工具将 diff 硬截断在 25000 字符（settings.json 的 max_diff_chars 无效）
MITIGATION = 本包 DIFF.txt 由 git diff 直接生成，完整未截断（99699 字符），为权威输入
```

### L2：ChatGPT Web 审核桥接

```text
LIMITATION = complete_project_context_update 的 ChatGPT 同步绑定可能 stale/failed
MITIGATION = 提审由质量 agent 负责；本隔离快照包 + QUALITY_REVIEW_PROMPT.md 已就绪
```

## 未定义维度（非本包范围）

```text
状态转移矩阵（Result/Settlement/6 实体）= CANDIDATE，本包未实现任何转移逻辑
生产参数（大额阈值、复核触发条件等）= TBC，本包未使用
```

## 状态声明

```text
2B-1 DDL = 已落盘 forward-only 脚本（8 新表 + audit_events 复用）
2B-1 Model/DAO/Service 骨架 = IMPLEMENTED（fail-closed，无业务转移）
本包不声明任何对象 FROZEN；不声明任何转移矩阵 FROZEN
```
