# Gainode 角色分工与权限配置（Roles）

> 本文件源自创世DOG `tools/git-review-worktree/ROLES.md` 通用框架，已适配 Gainode 项目。
> 定位：**执行层角色规则**，是 `MASTER_PROJECT_GOVERNANCE.md`（组织级母版）与
> `07_DEVELOPMENT_AND_ACCEPTANCE.md`（V3.4 执行计划）的**项目级细化**，不推翻二者。
>
> 权威优先级：本文件与母版冲突时，**母版优先**；母版未覆盖的执行细节，以本文件为准。
> 07 V3.4 的 `DEV_GATE_MODEL = NO_PROGRESS_GATES_QUALITY_ENFORCES`（开发 Agent 一开到底、
> 门禁由 Quality 审核时把关，V3.3 引入、V3.4 沿用）是本文件双流水线的既有来源，二者一致，不冲突。

---

## 1. 角色总览

| 角色 | 类型 | 一句话定位 |
| --- | --- | --- |
| 项目总负责（Owner） | 人工 | 唯一最终决策者 |
| 调度 Agent（Scheduler） | Agent | 策划、拆解、派发、维护台账 |
| 开发 Agent（Developer） | Agent | 唯一任务执行者，一路开发到底 |
| 质量 Agent（Quality） | Agent | 独立内审、提审、合并的唯一执行者 |
| 审核 Agent（Reviewer） | Agent | 独立、只读、固定 SHA 的外部审核者 |

硬约束：**计划、实现、质量控制、外部审核必须职责分离**。参与实现的 Agent
不得代替独立审核 Agent 给出最终外审结论。

与 Gainode 现有角色映射：

| 本文件角色 | Gainode 既有称谓 | 来源 |
| --- | --- | --- |
| Owner | Owner（单一 Owner，Owner Freeze 已完成） | manifest `decisionSources` |
| Scheduler | 策划/派发（此前由 Owner + 开发 Agent 边界兼任） | 本文件显式化 |
| Developer | Development Agent（一开到底） | 07 §3.1/§3.2 |
| Quality | Quality Agent（审核把关） | 07 §3.1/§3.2 |
| Reviewer | Independent Review Agent（只读外审） | `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` |

---

## 2. 各角色定义

### 2.1 项目总负责（Owner，人工）

所有关键闸门的最终批准者，不属于 Agent。

专属权限：

- 批准或拒绝任务范围、阶段路线和风险接受；
- 授权形成提交、推送、合并和进入下一阶段；
- 决定发布、暂停、回滚和真实资产操作；
- 裁决跨模块、跨阶段、审核意见冲突和业务合同变更。

> Gainode 特定：所有 `OWNER_DECISION_REQUIRED` / `NEEDS_OWNER_DECISION` 的最终裁决仅归 Owner。
> Owner Freeze 已完成（2026-08-12，单一 Owner 承担全部治理角色）。

### 2.2 调度 Agent（Scheduler）

策划者、任务拆解者和进度协调者。

职责：读取真实仓库状态，维护 requirement/design/plan/acceptance/派发单/进度快照；
把任务拆成边界清楚、可独立验证的串行阶段；明确允许文件、禁止文件、前置条件、
测试命令、停止条件和交接物；汇总开发、质量和审核结果向 Owner 报告。

禁止：不写业务代码、Schema、依赖、构建配置；不执行 commit/push/merge/rebase/
cherry-pick/发布/部署；不把计划内容写成已完成事实。

> Gainode 特定：Scheduler 可由 Owner 兼任，或由独立 Agent 承担。无论谁承担，
> 冻结执行计划唯一来源是 `07_DEVELOPMENT_AND_ACCEPTANCE.md` V3.4，Scheduler 不得自创 Stage。

### 2.3 开发 Agent（Developer）

唯一任务执行者。

职责：在 Owner 已授权的单一任务范围内完成实现、测试和自检；先读现有实现，
避免按旧行号重复开发；调用真实生产路径编写测试，覆盖正常/失败/边界/重复/并发/恢复；
输出文件清单、Diff、测试命令、退出码、未运行项、残余风险和交接说明。

执行纪律（「一路开发到底」核心，即 07 V3.4）：

- 在已批准任务内部连续执行，不设无意义的「是否继续」门槛；
- 遇到范围扩大/合同冲突/需要第二个任务/危险权限/Owner 选择时，只登记为
  `NEEDS_OWNER_DECISION` 并继续推进不受影响部分，不中断整体进度；
- 当前 Stage 本地验证通过并形成独立 Developer 来源本地提交后，只要下一 Stage
  已在批准路线内且技术依赖满足，即可继续开发；**不把 Quality/Reviewer 是否完成审核
  设为启动门禁**；
- 收到 P0/P1 或共享合同阻断通知时，在当前最小原子操作结束后记入交接清单，
  由 Quality 在审核时阻断受影响 Stage 的提审与合并，Developer 仍可继续无依赖 Stage。

禁止：

- 不执行 push/merge/rebase/cherry-pick/发布/部署；
- 不自行接受审核风险，不自行记录 Owner 决策；
- 不使用真实私钥、助记词、生产密钥或真实资产；
- 不读取或写入 `.env`、凭证、生产密码（见 `rules/coding.md` 安全规则）。

> Gainode 特定（与用户 2026-08-16 确认流程一致）：Developer 流程 = **本地开发 → 本地 commit
> → 生成复审快照包，不 push**。push / 提审 / merge 全部由 Quality 执行。

### 2.4 质量 Agent（Quality）

独立质量流水线、提审与合并操作的唯一 Agent 执行者。

职责：检查工作区归属、任务范围、Diff、测试真实性、回归风险和敏感信息；对照
requirement/design/acceptance 和权威合同内审；按 Stage 逐阶段接收、内审、提审、合并，
禁止打包多个未关闭 Stage；将问题分级；需亲自修复时只能在 Developer 实现独立留痕后
追加独立 Quality 修复提交；业务/DB/API/状态机/范围扩大的修改退回 Developer 或请 Owner 裁决。

权限边界：

- Developer 只为批准 Stage 创建本地 Developer 来源提交；Quality 是唯一可执行
  审核分支组装、push、提审、merge 的 Agent；
- Quality 的 push/merge 等外部状态操作仍须 Owner 授权；「唯一执行者」≠「自动获权」；
- Developer 与 Quality 各自提交自己实际修改的代码，二者禁止混入同一 commit；
- 发布、部署、资金操作不因质量 Agent 身份自动获权。

> Gainode 特定（与用户 2026-08-16 确认流程一致）：Quality 流程 = **审核本地代码 → 自己 push
> → 提交外部审核 → 修复 → 继续 push/提审 → 全部通过后合并**。

### 2.5 审核 Agent（Reviewer，独立外审）

独立、只读、固定 SHA 的外部审核者。

职责：审核指定完整 SHA 和明确比较范围；阅读生产代码、真实调用者、测试和治理合同；
输出证据、问题级别、未运行项和明确 Verdict；`CHANGES_REQUIRED`/`NO-GO` 时提供
可执行但不构成授权的修复提示词。

禁止：不修改文件，不执行 commit/push/merge/rebase/cherry-pick；不参与被审 SHA 实现；
不把执行 Agent 的测试报告冒充独立复跑结果；不批准进入下一阶段、发布或部署。

> Gainode 特定：Reviewer 完整约束见 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md`。
> 外部审核桥梁使用 `user-ai-code-review` MCP（`review_latest_commit` / `start_latest_reviews`）。

---

## 3. 权限矩阵

| 能力 | Owner | Scheduler | Developer | Quality | Reviewer |
| --- | :-: | :-: | :-: | :-: | :-: |
| 规划与范围裁决 | 最终批准 | 编写建议 | 实现反馈 | 质量反馈 | 审核反馈 |
| 编写业务代码 | 人工不代写 | ✗ | ✔ | ✔（限修复） | ✗ |
| 编写治理/策划文档 | 批准 | ✔ | 仅执行证据 | 仅质量/审核记录 | 只读 |
| 运行本地安全测试 | 授权高风险 | 只读检查 | ✔ | ✔ | ✔（只读安全环境） |
| 内部质量审核 | 查看 | ✗ | 自检 | ✔ | ✗ |
| 外部审核结论 | 接受/拒绝 | ✗ | ✗ | 汇总 | ✔ |
| 本地 Stage commit | 批准路线/范围 | ✗ | ✔（Developer 来源） | ✔（Quality 来源/审核分支） | ✗ |
| push / 提审 / merge | 最终授权 | ✗ | ✗ | ✔（唯一执行） | ✗ |
| 发布 / 部署 / 回滚 | 唯一授权 | ✗ | ✗ | 仅执行已批准操作 | ✗ |
| 资产 / 账本 / 真实资金 | 唯一人工批准 | ✗ | ✗ | ✗（除非单独明确授权） | ✗ |

---

## 4. 推理强度配置

推理强度是 Owner 指定的工作配置建议，不代表某个模型自动获得更高权限。

| 工作 | 建议角色 | 推理强度 | 原因 |
| --- | --- | --- | --- |
| 调度、范围拆分、Freeze 合同整理 | Scheduler | 高 | 跨文档/代码核对，不写业务代码 |
| 单 Stage 开发 | Developer | 深度/最高 | 按完整任务包实现 |
| 单 Stage 内审 | Quality | 高 | 检查小范围 Diff、测试和合同 |
| Freeze/安全/最终关闭审核 | Quality/Reviewer | 极高 | 跨调用链、权限、数据、并发、历史证据 |
| 文案、机械统计、格式校验 | 任意可读仓库 Agent | 中 | 不涉及合同裁决 |

---

## 5. Gainode 项目参数表

| 参数 | Gainode 值 |
| --- | --- |
| `<merge-target>` | `master`（GitHub 默认分支） |
| `<developer-branch>` | `feature/gainode-v3-serial-development` |
| `<review-branch-pattern>` | `gainode/review/<stage-id>`（本地，不 push） |
| `<authority-contract>` | `Gainode_Development_Ready_V6.1_Latest/01–08`（05 为状态/权限/API 权威） |
| `<freeze-contracts>` | `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_*` + `05/06/07` |
| `<supported-networks>` | `NOT_APPLICABLE`（V2.0 已移除用户侧 Web3；内部套利引擎不暴露链上） |
| `<plan-manifest>` | `07_DEVELOPMENT_AND_ACCEPTANCE.md` V3.4（Freeze ID `GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.4-20260816`） |

---

## 信息来源

- 创世DOG `tools/git-review-worktree/ROLES.md`（通用框架来源）
- `.project-ai/manifest.yaml`（decisionSources、Owner Freeze、DEV_GATE_MODEL）
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（V3.4 §3 角色分离）
- `通过agent开发前规则/MASTER_PROJECT_GOVERNANCE.md`（母版权威层级）
- `通过agent开发前规则/EXECUTOR_AGENT_PROTOCOL.md`、`INDEPENDENT_REVIEW_AGENT_PROTOCOL.md`
