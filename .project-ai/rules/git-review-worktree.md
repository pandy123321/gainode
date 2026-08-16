# Gainode 独立 Review Worktree 执行方式

> 本文件源自创世DOG `tools/git-review-worktree/README.md` 通用框架，已适配 Gainode。
> 核心价值：把「某个 Stage 的提交」固定到独立 git worktree 里审核，彻底不碰 Developer 的
> HEAD 和工作区，同时固化角色分工与全部执行细节。
> 角色定义见 [`roles.md`](./roles.md)，执行细节见 [`workflow.md`](./workflow.md)。

---

## 一、它解决什么问题

当 Developer Agent 在同一分支上持续提交、HEAD 快速前移时，审核工具如果只认
`HEAD`，就无法稳定地审「某个历史 Stage」的固定提交。

这套方法把审核对象从「Developer 的 HEAD」解耦成「一个固定 SHA 的独立分支」：

- Developer 继续在主工作区一路提交，HEAD 随便前进。
- Quality/Reviewer 在独立 worktree 里审**固定的提交链**，HEAD 永远钉死在该点。
- 两个工作区物理隔离（不同目录），互不干扰。

> Gainode 现状：Developer 在 `feature/gainode-v3-serial-development` 串行提交，
> Quality 用 `gainode/review/<stage-id>` 建立独立审核分支，外审只读固定 SHA。

---

## 二、手工命令（PowerShell，项目无关，等价于 review-worktree.ps1）

```powershell
$BASE = "master"
$STAGE = "s02-p03"                       # 替换为实际 Stage ID
$REVIEW_BRANCH = "gainode/review/$STAGE"
$WORKTREE = "E:\github\sports-review-$STAGE"
$COMMITS = @("<sha1>", "<sha2>")         # 按应用顺序

# 1. 校验
git branch --list $REVIEW_BRANCH        # 已存在则拒绝
if (Test-Path $WORKTREE) { throw "worktree exists" }

# 2. 建独立 worktree + review 分支
git worktree add -b $REVIEW_BRANCH $WORKTREE $BASE
Set-Location $WORKTREE

# 3. cherry-pick 本 Stage 的提交链
git cherry-pick $COMMITS

# 4. 校验
git status --short                       # 期望为空
git rev-parse HEAD                       # 固定审核 SHA
```

内置安全校验（缺一即抛错、不产生副作用）：

- base / 每个 commit 必须能解析为 commit；
- review 分支已存在 → 拒绝；
- worktree 路径已存在 → 拒绝；
- 全程不 push、不 `reset --hard`、不 force-checkout、不碰原仓库 HEAD/index；
- cherry-pick 失败 → 保留现场并打印 `--abort` 清理指令。

---

## 三、执行后必查清单（Quality 视角）

1. `git status --short` 为空（干净）。
2. review 分支提交链线性、base = `master`。
3. 每个提交只碰**本 Stage 允许的文件**（无跨 Stage 混入）。
4. review 分支内容与 Developer 侧对应提交内容**一致**（用 `git diff` 核对）。
5. 全程未 `push`，未改动主工作区 HEAD。
6. 提交 trailer 来源归属正确（`Code-Origin: Developer` / `Code-Origin: Quality` 分离）。

---

## 四、边界与回滚

- **不 push**：分支和 worktree 都是本地产物，合并/推送需 Owner 单独授权。
- **失败回滚**：
  - cherry-pick 冲突 → `git -C <worktree> cherry-pick --abort`；
  - 撤销整个 worktree → `git worktree remove <worktree> --force` + `git branch -D <review-branch>`。
- **不要**在主工作区做任何 reset / checkout / 强制操作来「配合」这套流程。

---

## 五、接入 Gainode 的参数（已从 roles.md 参数表落地）

| 参数 | 值 |
| --- | --- |
| `-Base` | `master` |
| `-ReviewBranch` | `gainode/review/<stage-id>` |
| `-Commits` | 本 Stage 的提交 SHA，按应用顺序 |
| `-WorktreePath` | 建议 `E:\github\sports-review-<stage-id>`（仓库外或仓库内子目录均可，物理隔离即可） |
| `-Repo` | `E:\github\sports` |

---

## 六、与 Gainode 既有流程的关系

- **不替代** Developer Snapshot / Quality Review 产物；worktree 是这些产物背后的
  物理隔离手段，保证「审固定 SHA、不碰主 HEAD」。
- 若 Owner 未要求物理隔离（例如单机单人快速迭代），可退化为「直接审 Developer 提交 SHA」；
  但正式独立外审必须绑定固定 SHA（见 `workflow.md` §9/§10）。

---

## 信息来源

- 创世DOG `tools/git-review-worktree/README.md`（通用框架来源）
- `.project-ai/rules/roles.md`（Gainode 参数表）
- `.project-ai/rules/workflow.md`（worktree 拓扑与衔接）
