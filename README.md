# Gainode 项目控制台（唯一入口）

> 状态：`CURRENT_PROJECT_ENTRY`
> 核验日期：2026-08-22（Asia/Shanghai）
> 工作区锁：`E:\github\sports`

本文件是项目的唯一启动入口，用于回答：Git 是否同步、代码实际做到哪里、应读哪些文档、下一步按什么顺序开发。它不是新的产品/经济/API 合同；业务规则仍以 `Gainode_Development_Ready_V6.1_Latest/01–08` 为准。

## 当前结论

```text
PROJECT = Gainode AI 体育分析与足球赛前 1X2 竞猜
BACKEND = PHP 8.2 + Webman
CURRENT_BRANCH = feature/gainode-v3-serial-development
CURRENT_HEAD = 04b8e404f33c665643a76c62e3dc95342b4c578e
REMOTE_FEATURE_HEAD = 04b8e404f33c665643a76c62e3dc95342b4c578e
FEATURE_LOCAL_REMOTE_SYNC = YES（2026-08-22 只读远端查询实测）
WORKTREE = 仅 PRODUCT_OVERVIEW.md 未跟踪；业务代码无未提交修改
PRODUCTION_APPROVAL = NO
RELEASE_READY = NO
```

| Stage | 实际状态 | 已验证 | 未完成/不得宣称 |
|---|---|---|---|
| STAGE-00 | 条件通过 | 规划与 01–08 基线存在 | 不是发布批准 |
| STAGE-01 | 本地条件通过 | 9/9 包有实现/证据；MC1/MC2 冻结 | 外部正式 Gate 与状态文档未完全收口 |
| STAGE-02 | 核心域已实现，Gate 未关闭 | S02-P01~P08 有代码和测试；26/26 后端测试通过 | S02-P09 仍是 Developer 草稿；真实 HTTP/E2E、OpenAPI lint、部分写路径未完成 |
| STAGE-03 | 大量页面已落地，Gate 未批准 | H5 23 文件/147 测试通过；Admin 类型检查/构建通过 | Admin 无单测；部分页面仍是骨架；E2E/视觉/a11y/i18n parity 未完成 |
| STAGE-04 | P00 决策登记；P01 仅脚手架 | Flutter 工程和依赖存在；analyze 通过 | `lib/main.dart` 仍是默认 Counter Demo；P01 基础设施和 P02-P07 未完成 |
| STAGE-05 | 未开始 | 无 | Sandbox E2E、故障矩阵、迁移排练未执行 |
| STAGE-06 | 未开始 | 无 | 安全、性能、运维、Release Gate 未执行 |

## 文档读取顺序

1. 本文件：唯一启动入口与实时进度。
2. `Gainode_Development_Ready_V6.1_Latest/01–08`：唯一业务/经济/页面/数据/API/参数/验收/视觉合同。
3. `.project-ai/reviews/STAGE-02-QUALITY-GATE.md`、`STAGE-03-QUALITY-GATE.md`：当前未关闭 Gate。
4. 当前 Package 对应的 `.project-ai/tasks/**`、`.project-ai/reviews/**`：按需读取，禁止全量扫历史快照。
5. `历史文档/**`、V3.1/V3.2/V3.3 Freeze、`_existing_prod/**`：仅追溯/参考，禁止执行或反推需求。

`07_DEVELOPMENT_AND_ACCEPTANCE.md` V3.4 的 40 个 Package 结构仍有效，但 §2 的进度停在 2026-08-16，不能判断当前包。`PRODUCT_OVERVIEW.md` 当前未跟踪且部分进度/协作模型过时，在复核并纳入 Git 前不得作为执行基线。

## Git 状态

- feature 本地与 GitHub 都是 `04b8e40`，当前同步。
- feature 相对本地 `master` 前进 201 个提交。
- 本地 `master=fd7968b`，远端 `origin/master=cf03f0d`；本地 master 比远端前进 25 个提交。
- 审核 worktree `E:\github\sports-review-s02-p07` 干净，但比当前 feature 落后 143 个提交，只是历史固定 SHA 现场。
- 在合并前必须由 Quality 形成 `REMOTE_MASTER_RECONCILIATION_PLAN`；禁止把“feature 已同步”理解为“master 可直接合并”。
- 本次盘点未执行 commit、push、merge、rebase、deploy。

## 实际复跑证据（2026-08-22）

| 范围 | 结果 |
|---|---|
| 后端 | 逐个执行 26 个现有 PHP 测试，26/26 PASS |
| H5 | Vitest 23 files / 147 tests PASS |
| Admin | `vue-tsc --noEmit` PASS；`vite build` PASS；存在 CSS nesting、eval、超大 chunk 警告 |
| Flutter | `flutter analyze` PASS |
| Flutter test | 本机 loopback WebSocket 被拒绝，环境阻塞；不得写 PASS |
| E2E/视觉 | 未运行；STAGE-03 Gate 保持 NOT_APPROVED |

开发阶段通过不等于发布批准；未执行真实数据库、真实 HTTP、迁移、部署和生产数据操作。

## 已确认的真实性缺口

### P1-DOC-001 · 状态文档冲突

- 07 §2 仍指向 S01-P04；`bootstrap.md` 仍写 STAGE-02/03/04 未开始；`context.md` 停在较早进度。
- `PRODUCT_OVERVIEW.md` 的单人自审/忽略 master 表述与 `rules/roles.md`、`workflow.md` 的五角色/Quality 合并纪律冲突。
- Owner 确认协作模型前，不改规则语义；确认后一次性更新 bootstrap/context/manifest/roles/workflow/07 进度指针并重建冻结凭证，禁止零散修改。

### P1-GIT-001 · 正式合并基线不一致

远端 master 不是 feature 的最新直接基线。进入 merge/release 前必须明确 `origin/master → local master 25 commits → feature 201 commits` 的来源、审核范围和合并顺序。

### P1-S02-001 · S02-P09 尚未关闭

- `S02P09ControllerWiringContractTest.php` 主要验证类/方法存在，文件自身声明为静态映射与弱校验；没有验证真实 HTTP、路由、中间件、鉴权、事务或数据库。
- `AdminGovernanceRoleService::ROLES` 只有 11 个角色，缺 `LEDGER_OPERATOR`、`AUDITOR`；普通 `ROLE_MAP` 为空，真实写路径只对超管可能开放。
- 退款/冲正控制器在角色不匹配时回退 `OPS_OPERATOR`；当前虽被下游 fail-closed 挡住，未来开放领域方法前必须移除。
- KYC、退款、冲正、异步、导出和多项 C 端写操作仍 fail-closed。

### P1-S04-001 · S04-P01 快照高估

07 §S04-P01 要求 flavors、API、secure storage、DecimalValue、router、i18n/theme/widgets 和多层测试；当前只有 `flutter create`、依赖和默认 Counter Demo。下一次 Flutter 开发必须先补完 P01，不直接做 P02 页面。

### P2-QUALITY-001 · 测试入口不完整

- 后端 `composer test` 只执行一个账本脚本，不能代表全部 26 个测试。
- Admin 没有 unit/e2e 测试脚本。
- STAGE-03 视觉、a11y、7 语言 parity 和真实联调未完成。

## 后续开发任务（单 Agent 串行，不重做完成代码）

### NEXT-01 · 收口 S02-P09

1. 读取 05、07 §S02-P09、OpenAPI、STAGE-02 Gate/coverage 与 `04b8e40`，生成 operationId→route→middleware→controller→validator→service→writer→test 矩阵。
2. 补齐 canonical 13 角色枚举。`sys_admin.role_id → canonical roles` 属治理规则，只生成 Owner Decision Request，不自行填映射。
3. 移除退款/冲正角色 fallback；未匹配角色必须 `POLICY_DENIED`，未冻结合同继续 `DEPENDENCY_UNAVAILABLE`。
4. 为 OTC review、Approval decision 建真实控制器/服务测试：未认证、无角色、SoD、非法状态、重复请求、object_version 冲突、成功与零写回滚。
5. 用临时数据库/fixture 建 HTTP 集成入口，覆盖路由加载、六请求头、认证、Envelope、错误码、request_id 和零泄漏。
6. 增加 OpenAPI parse/ref/operationId/security/required/idempotency lint。
7. 统一后端测试入口，使其执行全部测试。
8. Developer 只更新覆盖证据；Quality 独立复跑后才可关闭 Gate。

停止：需要改变业务状态、金额规则、角色映射、正式参数、生产数据或外部服务商时，登记 `OWNER_DECISION_REQUIRED` 并继续无依赖部分。

验收：P0/P1=0；OpenAPI lint PASS；HTTP/权限/事务目标测试 PASS；fail-closed 项完整列出；Gate 不再是 Developer 草稿。

### NEXT-02 · 收口 STAGE-03

1. 比对 H5 44 P0 Page ID、Admin 33 权威 Page ID 与 route/view/loader/API，逐页标 `REAL_DATA / READ_ONLY / FAIL_CLOSED / SKELETON / DEFERRED`。
2. 保留 H5 147 个已通过测试，补 API contract、断网、refresh single-flight、RESULT_UNKNOWN、重复提交和 State Changed。
3. Admin 建 Vitest 入口，覆盖 API client、权限、object_version、五态和 33 Page registry；再补关键写页测试。
4. 剩余权威页接已有 DTO；后端未冻结动作保持禁用并显示原因，不生成 mock 成功。
5. 执行 7 语言 parity、禁词、硬编码文案、float/本地资格推导扫描。
6. 按 Figma/08 执行 H5 375/390/430、Admin 1280/1440/1920 视觉回归和 a11y。
7. 运行 H5/Admin E2E，验证每个 P0 页五态及写操作状态链。
8. Quality 输出正式 STAGE-03 Gate；未完成视觉/E2E 时保持 NOT_APPROVED。

### NEXT-03 · 补完 S04-P01

1. 建 `lib/app`、`lib/core`、`lib/features`、`test/unit|widget|golden`、`integration_test`。
2. 建 dev/test/sandbox flavors；production 只有空骨架，无 URL/key/signing value。
3. 实现 Dio 六请求头、request_id、refresh single-flight、Idempotency-Key、If-Match、错误映射；失败 POST 禁止自动重试。
4. 实现 secure storage、DecimalValue（String，禁 double）、Riverpod session、GoRouter guards/deep-link。
5. 从 08/Figma/i18n 建 theme/tokens、7 语言、FiveState、Restricted、UnknownResult、StatusBadge、DecimalText。
6. fake API 只进入测试，和 production client 编译隔离。
7. 在允许 loopback 的环境/CI 修复 Flutter test，补 Android sandbox build；iOS 留 macOS runner。
8. 删除默认 Counter Demo/测试，替换为真实 App shell 与基础设施测试。

验收：07 §S04-P01 目标结构全部存在；format/analyze/test/Android sandbox build PASS；核心基础设施有测试；无生产 secret。

### NEXT-04 · S04-P02 → S04-P07

固定顺序：Auth/KYC/Notice → Home/Robot → Prediction → APT/Power/OTC → Me/Security/Support/Settings → Flutter Gate。统一消费 OpenAPI/Page ID/i18n/Figma；未冻结写路径只显示 Restricted/Unavailable，不伪造成功。

### NEXT-05 · STAGE-05

确定性 fixture → 五条主流程 → 15 个故障场景 → V1→V2 dry-run/回滚至少三轮 → Sandbox Gate。禁止接触生产数据。

### NEXT-06 · STAGE-06

安全/依赖 → 性能/可观测性 → 运维/恢复 → Release Gate。Release Ready 不等于部署授权。

## Agent 纪律

1. 启动先核对 `E:\github\sports`、分支、HEAD、本文件和当前 Package。
2. 已完成代码只读复用；没有绑定当前 SHA 的有效 Finding，不删除、不覆盖、不重做。
3. 完成必须由代码、测试退出码、固定 SHA、Gate 共同证明；文档声明不能单独证明。
4. Developer 与 Quality 的修改/提交来源不得混淆。协作模型冲突未裁决前，不擅自改成单人自审或反向改回。
5. 规则、经济、状态、API、DDL、权限、角色映射、正式参数修改必须人工确认。
6. 禁止 push/merge/rebase/cherry-pick/tag/release/deploy，除非 Owner 单独授权。
7. 每包交付变更文件、实际测试/退出码、未运行项、残余风险和下一技术就绪包。

## 待 Owner 一次性确认（不阻塞无依赖开发）

1. `COLLABORATION_MODEL`：五角色分离，还是 manifest 的 `MERGED_DEV_REVIEW_SINGLE_ACTOR`。建议保留五角色分离；Quality 不阻塞 Developer，但独立负责阶段提审与合并。
2. `REMOTE_MASTER_RECONCILIATION`：远端 master 的 25 个本地前置提交如何纳入正式合并链。
3. `ADMIN_ROLE_MAPPING`：批准 `sys_admin.role_id → 13 canonical roles` 映射。
4. `FLUTTER_CI_ENV`：指定允许 loopback 的测试环境及 macOS iOS runner。

确认前可继续 NEXT-01 的测试框架/OpenAPI lint/无依赖修复和 NEXT-02 的只读页面/测试；不得开放依赖未决规则的真实写路径。
