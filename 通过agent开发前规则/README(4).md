# AI Project Governance Template V1.0

本目录包含 4 份组织级通用母版：

1. `MASTER_PROJECT_GOVERNANCE.md` — 总治理规则
2. `EXECUTOR_AGENT_PROTOCOL.md` — 开发执行 Agent 固定协议
3. `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` — 独立审核 Agent 固定协议
4. `PROJECT_BOOTSTRAP_TEMPLATE.md` — 新项目初始化模板

同时提供 `PAYLOAD_MANIFEST.csv` 供完整性核对。

使用方式：

```text
复制 PROJECT_BOOTSTRAP_TEMPLATE.md
→ 填写项目专属事实与 Owner
→ 继承 MASTER_PROJECT_GOVERNANCE.md
→ 执行 Agent 加载 EXECUTOR_AGENT_PROTOCOL.md
→ 独立审核 Agent 加载 INDEPENDENT_REVIEW_AGENT_PROTOCOL.md
→ 每 Stage 使用完整提审包 + Manifest + SHA256
```
