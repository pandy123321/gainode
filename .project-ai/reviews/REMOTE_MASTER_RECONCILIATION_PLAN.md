# REMOTE MASTER RECONCILIATION PLAN（DR-08 / P1-GIT-001）

> 生成：2026-08-25 · 中控调度 Agent
> 目的：明确 `origin/master → local master → feature` 的合并链来源、审核范围与顺序，
>       消除 P1-GIT-001「正式合并基线不一致」。
> 纪律：本计划仅描述现状与建议，**不执行任何 merge/push**（README §6：合并需 Owner 单独授权）。

## 一、实测 Git 关系（2026-08-25 只读核查）

```text
origin/master              = cf03f0d
local master               = fd7968b（origin..master = +25，全为 .project-ai + 0.5代码 文档/任务/契约）
feature/gainode-v3-serial  = 5ce1901
```

| 关系 | 实测 | 结论 |
|---|---|---|
| `origin/master` 是 feature 的祖先 | ✅ (`merge-base --is-ancestor` = 0) | **feature 干净基于 origin/master** |
| `local master` 是 feature 的祖先 | ✅ (= 0) | **local master 25 提交已并入 feature** |
| feature 相对 origin/master 提交数 | **242** | 全部业务开发 |
| feature 是否有 merge 提交 | **无**（线性历史） | 无分叉 |
| `rev-list feature..master` | **0** | local master 无 feature 缺失提交 |

### 关键更正（相对控制台 README 描述）

控制台描述"local master 比 origin/master 前进 25、feature 前进 201、需 reconciliation"——
**实测更简单**：`origin/master` 与 `local master` **都是 feature 的严格祖先**，feature 是
二者的干净超集（242 个线性提交，无 merge）。即 **特征分支无需 rebase/重写**，可**直接快进**。

## 二、建议合并方案（按安全优先级）

### 方案 A（推荐）：Fast-Forward origin/master → feature
- 条件：origin/master 是 feature 祖先（✅ 已满足），无分叉。
- 操作：`git push origin feature/gainode-v3-serial-development:master`（仅更新 master 指针）。
- 优点：**不改任何历史 SHA**、零 rebase、零 merge 冲突、保留完整评审链。
- 风险：接近零（feature 已含 origin/master 全部提交 + 242 个新提交）。
- 前置：Quality 对 feature 完成独立 Gate 复跑（STAGE-02/03）后执行。

### 方案 B（备选）：three-way merge（不推荐）
- 需 merge commit 或 rebase；因 feature 已含全部祖先，纯属冗余步骤，徒增历史。

## 三、合并前必须满足的 Gate（Quality 独立复跑，非 Developer 自评）

1. 后端全量 `php tests/run_all.php`（现 30 套件）全绿。
2. STAGE-02 / STAGE-03 Gate 由 Quality 复核并落正式结论（非 Developer 草稿）。
3. 前端 Admin/H5 `vue-tsc --noEmit` + `vite build`（CI 环境复跑，本机工具链过慢）。
4. 无未提交工作树改动（PRODUCT_OVERVIEW.md 纳入 Git 或排除）。

## 四、不执行合并的现状影响

- 可继续在 feature 上推进后续 Package（STAGE-04/05/06），不受影响。
- `origin/master` 保持冻结于 cf03f0d，不产生线上发布（PRODUCTION_APPROVAL=NO）。

## 五、待 Owner 授权

- **合并动作**：方案 A（fast-forward master→feature），在 Quality Gate 通过后执行。
- **PRODUCT_OVERVIEW.md**：是否纳入 Git（纳入则作为执行基线；不纳入则持续 untracked）。

## 六、验证方式（合并后）

- `git rev-parse origin/master` == `git rev-parse feature`（应为同一 SHA）。
- 全量测试再复跑一次确认无回归。
