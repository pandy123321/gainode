# FRONTEND_TARGET_ROOT_DECISION.md

> S03-P00 · 前端目标目录决策（Owner 已签批）
> 状态：**FROZEN（OWNER_DIRECTIVE）** · 方案 A 已选定
> 决策日期：2026-08-16 · 批准方式：Owner 指令「按照你的推荐执行」

## 0. 决策结果

```text
DECISION_ID      = S03-P00-TARGET-ROOTS
STATUS           = FROZEN
SCHEME           = A（新 V2 目录增量复制，source 保持只读）
OWNER_DIRECTIVE  = 2026-08-16「按照你的推荐执行」
APPROVED_BY      = OWNER（非 Agent 自签）
```

## 1. 冻结字段（全字段齐备）

```text
H5_SOURCE_ROOT    = E:\github\sports\_existing_prod\gainode_h5
ADMIN_SOURCE_ROOT = E:\github\sports\_existing_prod\gainode_admin
H5_TARGET_ROOT    = E:\github\sports\gainode_h5_v2
ADMIN_TARGET_ROOT = E:\github\sports\gainode_admin_v2
SOURCE_BASELINE_COMMIT = H5=5e4b88b0016d747938c65b8fc4a9bfc65d3e42ae / ADMIN=e0d2224a1ef4210f1276f906dfe34af22befb172
SOURCE_BASELINE_TREE   = H5=7a6c63581b58a77757b557e6c16143b3e75fe359 / ADMIN=caa4800e13b113e9331f55c9089aa89706525c2d
MIGRATION_MODE    = INCREMENTAL
PACKAGE_MANAGER_H5   = npm（package-lock 存在；node engine 需对齐，见 §3）
PACKAGE_MANAGER_ADMIN= pnpm@8.14.0（packageManager 字段锁定）
NODE_VERSION      = 22.17.1（当前环境实测）
TARGET_ROOT_WRITE_POLICY = DEVELOPMENT_ONLY
```

## 2. 方案选择记录

- 方案 A（推荐，已选）：新 V2 目录 `gainode_h5_v2` / `gainode_admin_v2` 增量复制，`_existing_prod/**` 全程只读。
- 方案 B（未选）：现有正式目录原位升级。

## 3. baseline 工具链约束（迁移前基线记录）

```text
node    = 22.17.1（H5 engines 要求 ^22.18.0 || >=24.12.0 → 触发 engine 警告，S03-P01 需锁定兼容版本或调整 engines）
npm     = 10.9.2
pnpm    = 9.15.9（Admin packageManager 声明 pnpm@8.14.0 → S03-P01 需对齐或批准替换）
```

## 4. containment 校验（步骤 4，Owner 选择后由 Developer 执行）

```text
target ∉ _existing_prod          = PASS
target ∉ 其他仓库（非嵌套 git）    = PASS（新建于主仓库根，非其他仓库内）
target 非 source 子目录（防循环）   = PASS
```

## 5. 来源清单

`FRONTEND_BASELINE_INVENTORY.md`（KEEP/REPLACE/REMOVE_LATER/DO_NOT_COPY 分类 + baseline HEAD/tree）。
复制来源：仅 source 最小构建集（排除 node_modules/dist/.env/secret/mock production data）。
