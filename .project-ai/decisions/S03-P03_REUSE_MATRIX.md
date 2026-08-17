# S03-P03 Admin 基础设施 复用矩阵（旧 → 新）

> 范围：S03-P03-P01 Element Plus 基础设施迁移。
> 原则：后端 schema 契约不变，仅替换渲染层；layui 与 Element Plus 过渡期共存，
> 迁移完成后删除零引用旧依赖。

## 1. 组件映射

| 旧（layui） | 位置 | 处置 | 新（Element Plus） |
| --- | --- | --- | --- |
| `FormSchema.vue` | src/components | 重写 | `ep/SchemaForm.vue` |
| `TableSearchSchema.vue` | src/components | 重写 | `ep/SchemaSearch.vue` |
| `TableActionSchema.vue` | src/components | 收敛为行内 slot | `EpSchemaTable` 列 slot |
| `TableToolsSchema.vue` | src/components | 收敛为工具栏 slot | `EpSchemaTable` 工具栏 slot |
| `ImportSchema.vue` | src/components | 改写 | `el-upload` |
| `ImageUpload.vue` | src/components | 改写 | `el-upload` |
| `lay-table` | 全局 | 替换 | `el-table` |
| `lay-layer` | 全局 | 替换 | `el-drawer` / `el-dialog` |
| `lay-form` / `lay-form-item` | 全局 | 替换 | `el-form` / `el-form-item` |
| `lay-input/select/date-picker/switch/radio/checkbox` | 全局 | 替换 | `el-*` |
| `lay-json-schema-form` | 全局 | 弃用 | `EpSchemaForm`（自渲染 schema） |
| — | — | 新增 | `ep/AdminState.vue`（五态） |
| — | — | 新增 | `ep/EpDrawer.vue`（480/640 抽屉基线） |
| — | — | 新增 | `ep/ApprovalBar.vue` / `ep/ImpactPreview.vue` / `ep/AuditLink.vue`（高危操作统一验收） |

## 2. API 模块

| 旧 | 位置 | 处置 | 说明 |
| --- | --- | --- | --- |
| `api/http.ts` | src/api | 保留（过渡） | V1.x 信封 + md5 签名；P02 迁移统一 client + request context + `object_version` 冲突提示 |
| `api/module/common.ts` | src/api/module | 保留 | schema 接口契约不变（search/list/create/update） |
| `api/module/*.ts` | src/api/module | 保留 | 业务接口；P02 收口类型 |
| `types/result.ts` | src/types | 保留 | `Result` 信封；新增 `ApiEnvelope<T>` 对齐 |

## 3. Store

| 旧 | 位置 | 处置 | 说明 |
| --- | --- | --- | --- |
| `store/app.ts` | src/store | 保留 | locale/theme/themeVariable；P03 追加 Page meta |
| `store/user.ts` | src/store | 保留 | token/permissions/menus；P03 追加 AdminFiveState |
| `store/index.ts` | src/store | 保留 | pinia 实例 |

## 4. Route

| 旧 | 位置 | 处置 | 说明 |
| --- | --- | --- | --- |
| `router/index.ts` | src/router | 保留 | 根路由；P03 建 8 导航 route registry |
| `router/module/base-routes.ts` | src/router/module | 保留 | 基础路由；P03 追加 Page ID meta |

## 5. 类型契约（新增）

| 文件 | 说明 |
| --- | --- |
| `src/types/schema.ts` | `SchemaField` / `SchemaMap` / `SchemaColumn` / `ApiEnvelope` / `AdminStateName` |

## 6. 依赖

| 依赖 | 版本 | 处置 |
| --- | --- | --- |
| `element-plus` | 2.4.4 | 新增（兼容 Vue 3.3.4；2.14.x 需 Vue≥3.3.7/3.5，故降级） |
| `@element-plus/icons-vue` | 2.3.2 | 新增 |
| `@layui/layui-vue` | 2.23.3 | 保留（过渡共存，迁移后删除） |
| `@layui/json-schema-form` | 1.0.16 | 保留（过渡，弃用后删除） |
