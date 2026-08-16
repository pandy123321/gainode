# FRONTEND_COPY_MANIFEST.md

> S03-P00 · 复制来源清单（最小构建集）
> 日期：2026-08-16 · 方式：robocopy（`/XD .git node_modules dist`，`/XF .env .pnpm-debug.log`）

## 0. 复制映射

```text
SOURCE                          TARGET
_existing_prod/gainode_h5      → gainode_h5_v2       （72 files）
_existing_prod/gainode_admin   → gainode_admin_v2    （539 files）
```

## 1. 排除项（已确认未复制）

```text
.git                 → 不复制（target 属主仓库，禁止嵌套 git）
node_modules         → 不复制（target 需全新 install）
dist                 → 不复制（构建产物）
.env                 → 不复制（敏感）
.pnpm-debug.log      → 不复制（调试日志，Admin）
```

## 2. 复制后校验

```text
gainode_h5_v2/.git      = ABSENT ✅
gainode_admin_v2/.git   = ABSENT ✅
gainode_h5_v2/node_modules = ABSENT ✅
gainode_admin_v2/.env   = ABSENT ✅
```

## 3. 来源 baseline（不可变）

```text
H5    HEAD=5e4b88b0016d747938c65b8fc4a9bfc65d3e42ae  TREE=7a6c63581b58a77757b557e6c16143b3e75fe359
Admin HEAD=e0d2224a1ef4210f1276f906dfe34af22befb172  TREE=caa4800e13b113e9331f55c9089aa89706525c2d
```

## 4. 迁移前基线工具链

```text
node  = 22.17.1
npm   = 10.9.2
pnpm  = 9.15.9
```

## 5. 迁移前 baseline build/typecheck 结果（S03-P00 步骤 6，仅记录现有失败，不在 P00 修页面）

```text
H5 (gainode_h5_v2):
  npm install       = PASS（303 packages，EBADENGINE warn：node 22.17.1 vs engines ^22.18.0；uuid@8.3.2 deprecated）
  npm run build     = PASS（exit 0，built in 19.64s；产物含 HomeView/DepositView/MyView/WithdrawView 等 22 视图 chunk）
Admin (gainode_admin_v2):
  pnpm install      = PASS（16.7s；peer warn：vue-router 4.6.4 要求 vue ^3.5.0，实装 3.3.4）
  pnpm build:check  = FAIL（exit 2；vue-tsc 报多处 TS2322/TS2345：TableColumn 类型不匹配）
    出错文件：src/views/user/grade.vue、src/views/user/index.vue、
              src/views/workSpace/analysis/index.vue、src/views/workSpace/console/index.vue
    根因：layui-vue TableColumn 定义要求 {type,key}，模板表格列对象缺 type 字段 → 既有类型缺陷，非本次迁移引入
```

## 6. 遗留风险（S03-P01 起处理，P00 不修页面）

- H5 `engines.node = ^22.18.0 || >=24.12.0` vs 实测 22.17.1 → npm engine 警告。
- Admin `packageManager = pnpm@8.14.0` vs 实测 9.15.9 → 版本需对齐或 Owner 批准替换。
- Admin `build:check` 既有 TS2322/TS2345（layui-vue TableColumn 类型缺陷）→ S03-P01 基础设施期一并修复或暂缓 build:check。
- Admin 依赖 crypto-js/md5/js-base64/@aws-sdk S3 → V2 改后端签名 + presigned URL。
- Admin `src/mockjs/**` 未在复制时剥离（保留构建保真），按 REMOVE_LATER 分类待 S03-P01+ 移除。
