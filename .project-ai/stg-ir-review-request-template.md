# STAGE-0X Independent Review 请求（通用可复用模板）

```text
# STAGE-0X Independent Review 请求

REVIEW_ID = GAINODE-STAGE0X-IR-YYYYMMDD-NNN
PROJECT = Gainode
PROJECT_ID = GAINODE-2.0
STAGE = STAGE-0X
STAGE_DOCUMENT_ROOT = .project-ai/
STAGE_TASK_DIR = .project-ai/tasks/TASK-YYYYMMDD-NNN/

## 审核范围
全部 .project-ai/ 文件 + STAGE-0X 新增/修改的任务文件

## 基线
- BASELINE_COMMIT = <上一个 Stage 结束时的 commit SHA>
- CURRENT_COMMIT = <当前工作区未提交的变更>
- contextVersion = <manifest.yaml 中的当前值>

## 审核要点（请在报告中逐一回答）

### P0 — 阻断项
1. 是否存在硬编码密钥/凭证？（搜索：AKIA\|sk-\|private_key\|password\s*=\s*['"][^'"]{8,}）
2. 是否存在资金/签名相关的不安全边界？
3. 是否存在可导致数据丢失的 Migration 缺陷？

### P1 — 应在本 Stage 修复
4. 所有 TBC 字段是否已确认或记录为待确认且 fail-closed？
5. 状态机是否与 05 canonical enum 一致？（交叉比对 glossary.md 的 05 行号）
6. 禁止事项是否在本 Stage 触及的范围内无违反？
7. 各文档间的关键数字是否一致（contextVersion / 页面数 / 模块数 / 角色数）？

### P2 — 建议修复
8. 是否存在陈旧计数/废弃引用？
9. 是否存在跨文档术语不一致？
10. 待确认清单是否完整且不过时？

## 要求
- 逐项出具 VERIFIED / FAILED / PARTIAL 判定
- 每个 FAILED/PARTIAL 输出具体文件、行号和修复建议
- 输出最终 VERDICT：APPROVED / CONDITIONAL_APPROVAL / CHANGES_REQUIRED
```
