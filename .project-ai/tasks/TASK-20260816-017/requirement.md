# Requirement: S03-P00 · 前端开发目录冻结

## 背景

STAGE-02 后端开发线（S02-P01..P08）已由 Developer 收口并本地提交。按 V3.4 执行模型
`ONE_DEVELOPMENT_AGENT_SERIAL_PACKAGES` + `NO_PROGRESS_GATES_QUALITY_ENFORCES`，
Developer 一开到底，不因 STAGE-02 Gate 未关闭而停止（07 §0.1 第 37 行）。

STAGE-03 是 H5 与 Admin 增量升级，其**入口包 S03-P00 只做前端开发目录冻结**：
只读盘点 `_existing_prod` 两个前端源码、验证 baseline、固定给 Owner 路径方案。
**本包不复制、不移动、不改动 `_existing_prod/**`，不写任何前端业务代码。**

## 范围（07 §S03-P00）

- 目标文件：`.project-ai/decisions/FRONTEND_TARGET_ROOT_DECISION.md`、
  `FRONTEND_BASELINE_INVENTORY.md`、manifest/context/bootstrap 路径指针。
- 禁止：修改 `_existing_prod/**`。

## 必须冻结字段（Owner 签批后才生效）

```text
H5_SOURCE_ROOT   = E:\github\sports\_existing_prod\gainode_h5
ADMIN_SOURCE_ROOT= E:\github\sports\_existing_prod\gainode_admin
H5_TARGET_ROOT   = <owner-approved path>
ADMIN_TARGET_ROOT= <owner-approved path>
SOURCE_BASELINE_COMMIT = <verified git/tree hash>
MIGRATION_MODE   = INCREMENTAL
PACKAGE_MANAGER_H5   = <locked from target>
PACKAGE_MANAGER_ADMIN= pnpm@8.14.0 or approved replacement
NODE_VERSION     = <locked compatible version>
TARGET_ROOT_WRITE_POLICY = DEVELOPMENT_ONLY
```

## 固定步骤（07 §S03-P00）

1. 对两个 source root 只读盘点 package/scripts、routes、stores、API、i18n、components、
   views、assets、secret 风险和链上依赖，生成 KEEP/REPLACE/REMOVE_LATER/DO_NOT_COPY。
2. 验证 source baseline Git tree/hash；若非 Git 对象则逐文件 SHA-256 manifest。
3. 固定给 Owner 两个路径方案（A/B），默认推荐 A，但 Agent 不自行签字。
4. Owner 选择后写入四路径/版本指针与写入边界。
5. 只复制构建最小集并保留来源清单。
6. 在 target 执行 install/build/typecheck 迁移前基线。

本包在 Owner 未冻结 target roots 前，只执行步骤 1–3，输出 Decision/风险，**不执行步骤 4–6**。

## 非目标（Non-Goals）

- 不复制、不移动、不修改 `_existing_prod/**`。
- 不写任何 H5/Admin 页面业务代码（那是 S03-P01 起的职责）。
- 不把旧前端反推成 V2 业务规则（§0.1 永久禁止）。

## 停止条件（07 §S03-P00）

Owner 未冻结 target roots；target 与 source/其他项目重叠；基线无法追溯。
满足任一即只输出 Decision/风险，不复制或移动文件。
