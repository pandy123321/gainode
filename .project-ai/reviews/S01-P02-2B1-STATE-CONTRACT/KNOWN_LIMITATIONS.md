# KNOWN_LIMITATIONS — S01-P02 · 2B-1 状态合同补齐

## 工具限制（已记录，不影响本包完整性）

### L1：AI Code Review Assistant 的 diff 硬截断

```text
LIMITATION = 工具的 get_latest_commit / start_latest_reviews 将 diff 硬截断在 25000 字符
ROOT_CAUSE = 工具内部硬编码上限（settings.json 的 max_diff_chars 键对其无效，已实验验证）
IMPACT = 依赖该工具提交的 ChatGPT Web 审核只能看到截断 diff
MITIGATION = Development Agent 已直接通过 git diff 生成完整未截断 DIFF.txt（42282 字符）
            复审 Agent 应以本包 DIFF.txt / files_at_impl/*.txt 为权威输入，而非工具截断输出
```

### L2：工具只能复审「latest commit」

```text
LIMITATION = start_latest_reviews 只针对项目当前 HEAD，无法指定历史 commit a32918c
IMPACT = 无法通过该工具直接对 a32918c 发起复审
MITIGATION = 以本隔离快照包（绑定 a32918c + SHA256）作为权威复审对象
```

### L3：ChatGPT Web 审核桥接失效

```text
LIMITATION = complete_project_context_update 的 ChatGPT 同步绑定 stale/failed（无法写入一次性审核契约）
IMPACT = 通过工具的 review_latest_commit / start_latest_reviews 无法真正驱动 ChatGPT 审核
MITIGATION = 本隔离快照包 + QUALITY_REVIEW_PROMPT.md 已就绪；待共享浏览器 ChatGPT 会话恢复后提交
```

## 合同未定义维度（非本包范围）

```text
6 实体 enum 已 Owner 裁决并补入 05 §4（V2.3），无残留 CONTRACT_GAP。
转移矩阵（Result/Settlement/6 实体）仍候选，待 Independent Review（State Machine gate）。
```

## 状态声明

```text
2B-1 enum = 已确定（复制 05 §4 + Owner 裁决）
2B-1 转移矩阵 = CANDIDATE（未 FROZEN）
本包不声明任何对象 FROZEN
```
