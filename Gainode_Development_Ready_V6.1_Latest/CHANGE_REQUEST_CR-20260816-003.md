# Change Request — 开发进度门禁移交 Quality（CR-20260816-003）

```text
CHANGE_REQUEST_ID = CR-20260816-003
PROJECT = Gainode
SUBMITTED_BY = OWNER
SUBMITTED_AT = 2026-08-16T11:53+08:00
AFFECTED_FREEZE = DEVELOPMENT_EXECUTION_PLAN（Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md）
AFFECTED_FREEZE_STATUS = FROZEN_FOR_EXECUTION
CHANGE_CONTROL = OWNER_APPROVAL_REQUIRED
SUPERSEDES = CR-20260816-002（V3.2 全量重构批准）
```

## 一、变更对象与现状

`07_DEVELOPMENT_AND_ACCEPTANCE.md` V3.2 定义了「Development Agent 必须等待的情况」（§3.2 五条）、每个 Package 的「前置」字段（如 S01-P03 前置 S01-P02 FROZEN）、逐包「停止条件」、以及六个 Formal Stage Gate，这些共同构成 Development Agent 的**开发进度门禁**——开发 Agent 在推进下一包前必须先满足合同冻结、审核返回或 Owner 决策等条件。

OWNER 决定改变这一模型：**Development Agent 一开到底，不再被任何进度门禁阻塞**；这些门禁改为 Quality Agent 在审核/提审时验证。

## 二、变更性质

### A. 移除的 Development Agent 进度门禁（移交 Quality）

| 原门禁 | 位置 | 新归属 |
|---|---|---|
| 「Development Agent 必须等待的情况」5 条 | §3.2 | Quality 审核时的验证项（§15） |
| 逐包「前置」字段（合同 FROZEN / 目录冻结 / Decision Gate） | S01-P03/P05、S03-P01、S04-P00 等 | Quality 审核时验证，不再阻塞 Dev |
| 逐包「停止条件」 | 全部 40 包 | Quality 审核时的验证点/风险登记 |
| Stage Gate 作为 Dev 阻塞点 | S01-P09 等 6 处 | Quality 单独提交；Dev 不等待 |

### B. 保留的硬停止（非进度门禁，永不解除）

- §0.1 永久禁止项：修改其他仓库、改语言、改链上、执行生产 DDL/数据/部署/密钥/链上广播/真实价值、删除已完成冻结代码。
- 不得自行新增/删除/合并/拆分/跳过/重排 Package（范围边界，非进度门禁）。

### C. best-effort 继续 + 显式声明（新增）

Development Agent 遇到未冻结合同、未决 Owner 决策时不停下，按 05/Freeze 已知内容 best-effort 实现，受影响写路径保持 fail-closed，并在交接文件声明：

```text
CONSUMED_UNFROZEN_CONTRACT = <合同名>
OPEN_OWNER_DECISION = <决策 ID>
OVERLAPS_LOCKED_SNAPSHOT = <path>
```

## 三、Owner 决策记录

```text
OWNER_DECISION = OPTION_A（全量批准：Dev 一开到底，门禁移交 Quality）
OWNER_SIGNED_AT = 2026-08-16T11:53+08:00
OWNER_SCOPE_CONFIRMATION_1 = fail_closed_scope = relax_fail_closed
  放开：Dev 按 best-effort 直接建表/写骨架，交接 + KNOWN_LIMITATIONS 声明消费了未冻结合同，
  Quality 审核时记为 Finding/风险；合同未冻结的受影响写路径仍保持 fail-closed。
OWNER_SCOPE_CONFIRMATION_2 = owner_decision_gate = continue_after_decision_req
  Dev 生成 Decision Request 后继续做无依赖部分，不原地等 Owner；Owner/Quality 后续追认。
RESOLUTION = APPROVED
```

## 四、影响

```text
NEW_FREEZE_VERSION = V3.3
NEW_FREEZE_ID = GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.3-20260816
AFFECTED_FILES =
  Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md
  Gainode_Development_Ready_V6.1_Latest/DEVELOPMENT_EXECUTION_PLAN_FREEZE_V3.3.md
  .project-ai/manifest.yaml
  .project-ai/bootstrap.md
  .project-ai/context.md
INDEPENDENT_REVIEW_REQUIRED = NO（治理模型变更，OWNER 直接签署；Quality 后续按 V3.3 执行审核）
TRACEABILITY_NOTE = 已完成的 S01-P01/P02/P03/P04 不追溯；V3.3 门禁模型自 S01-P05 起对 Dev 生效，
  Quality 对所有后续包按新模型审核。
```
