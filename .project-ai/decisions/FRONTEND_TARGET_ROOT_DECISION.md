# FRONTEND_TARGET_ROOT_DECISION.md

> S03-P00 · 前端目标目录决策（Owner 签批项）
> 状态：**DRAFT_NOT_EXECUTABLE**（Agent 不签字，待 Owner 选择 A/B 方案后冻结）
> 日期：2026-08-16

## 0. 决策请求

```text
DECISION_ID   = S03-P00-TARGET-ROOTS
REQUIRED_BY   = S03-P01（H5 基础设施）前置：H5_TARGET_ROOT 已冻结
STATUS        = NEEDS_OWNER_DECISION
AGENT_ROLE    = Developer（只提方案，不自行签字）
```

## 1. 已固定字段（无需 Owner 决策）

```text
H5_SOURCE_ROOT    = E:\github\sports\_existing_prod\gainode_h5
ADMIN_SOURCE_ROOT = E:\github\sports\_existing_prod\gainode_admin
SOURCE_BASELINE_COMMIT = H5=5e4b88b0016d747938c65b8fc4a9bfc65d3e42ae / ADMIN=e0d2224a1ef4210f1276f906dfe34af22befb172
SOURCE_BASELINE_TREE   = H5=7a6c63581b58a77757b557e6c16143b3e75fe359 / ADMIN=caa4800e13b113e9331f55c9089aa89706525c2d
MIGRATION_MODE    = INCREMENTAL
PACKAGE_MANAGER_ADMIN = pnpm@8.14.0
TARGET_ROOT_WRITE_POLICY = DEVELOPMENT_ONLY
```

## 2. 待 Owner 选择的字段

```text
H5_TARGET_ROOT    = <owner-approved path>
ADMIN_TARGET_ROOT = <owner-approved path>
PACKAGE_MANAGER_H5= <locked from target>
NODE_VERSION      = <locked compatible version>
```

## 3. 两个方案（默认推荐 A）

### 方案 A（推荐）：批准的新 V2 目录增量复制，source 保持只读

```text
H5_TARGET_ROOT    = E:\github\sports\gainode_h5_v2        （示例，待 Owner 批准）
ADMIN_TARGET_ROOT = E:\github\sports\gainode_admin_v2     （示例，待 Owner 批准）
```

- 优点：旧前端 `_existing_prod/**` 全程只读，Git 历史/来源清晰，回滚与对比容易。
- 缺点：多一份目录。

### 方案 B：批准的现有正式目录原位升级

```text
H5_TARGET_ROOT    = <现有正式目录路径>（待 Owner 指定）
ADMIN_TARGET_ROOT = <现有正式目录路径>（待 Owner 指定）
```

- 优点：无目录翻倍。
- 缺点：直接改动现有目录，source 只读保证弱，回滚风险高。

## 4. 写入边界校验（Owner 选择后由 Developer 执行）

无论 A/B，target 必须满足：

1. target 不在 `_existing_prod` 内。
2. target 不在其他仓库内。
3. target 不是 source 的子目录（防循环）。

## 5. 未签前影响

```text
S03-P01 前置「H5_TARGET_ROOT 已冻结」不满足 → S03-P01 起 H5/Admin 实现保持 FAIL_CLOSED。
本 S03-P00 按停止条件只输出本 Decision/风险，不复制、不移动文件。
```
