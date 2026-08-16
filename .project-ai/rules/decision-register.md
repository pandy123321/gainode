# 上下文登记与决策归档纪律

> 权威规则见 `.project-ai/rules/roles.md`、`.project-ai/rules/workflow.md`、
> `.project-ai/rules/git-review-worktree.md`。
> 冲突时以本文件 + `manifest.yaml` + `07_DEVELOPMENT_AND_ACCEPTANCE.md` V3.4 为准。

## 背景

`manifest.yaml` 的 `decisionSources` 随每个包追加 2–5K 决策记录，导致启用上下文
（bootstrap / context / review-context / glossary + manifest）超过
`complete_project_context_update` 的 60,000 字符上限，阻塞上下文发布与外部审核。

2026-08-16 Owner 决策：**归档历史决策 + 本纪律根治**，防止再次超限。

## 硬约束

1. **决策归档（append-only）**：历史决策记录统一追加到
   `.project-ai/decisions/DECISION_SOURCES_ARCHIVE.md`。归档不可变：禁止删除、改写、
   重排已归档条目；新增决策只允许在文件末尾追加。

2. **manifest 瘦身**：`manifest.yaml` 的 `decisionSources` 仅保留「当前活跃关键决策」，
   上限 10 条，且只覆盖重大里程碑（Owner 签核、执行计划升版、治理规则新增、
   Stage 收口）。其余全部移入归档。`manifest.yaml` 顶部保留 `archived` 指针字段，
   指向归档文件。

3. **开发 agent 每完成一个包**：
   - 决策/交付记录 → 追加到 `DECISION_SOURCES_ARCHIVE.md`（或
     `.project-ai/tasks/<task-id>/` 下的 decision 文件），**不得追加到 `manifest.yaml`**。
   - `manifest.yaml` 仅在「重大里程碑」时由 Quality/Owner 维护，不随每个包膨胀。

4. **context.md 进度段落**：只写一行指针
   （package ID + commit SHA + 审核状态 + task 路径）。详细交付清单与审核证据放在
   `.project-ai/tasks/<id>/` 与 `.project-ai/reviews/`，禁止在 `context.md` 内联大段重复。

5. **发布前校验**：Quality 在 `complete_project_context_update` 前，必须校验
   `bootstrap.md + context.md + review-context.md + glossary.md + manifest.yaml`
   合计 ≤ 60,000 字符；超限则先归档/压缩再发布。
