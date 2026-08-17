# S03-P03-P01 Admin 基础设施 实施记录

## 目标
建立 Element Plus 基础设施：Schema adapter + Table/Form/Drawer 公共组件 + AdminFiveState + 高危操作组件，并输出复用矩阵。

## 交付物

### 1. Schema 类型契约
- `src/types/schema.ts`：`SchemaField` / `SchemaFieldProps` / `SchemaOption` / `SchemaMap` / `SchemaColumn` / `ApiEnvelope<T>` / `AdminStateName`。
- 目的：以强类型替换历史代码散落的 `any` / `@ts-ignore`，后端契约不变。

### 2. Element Plus 公共组件（src/components/ep/）
| 组件 | 说明 |
| --- | --- |
| `SchemaForm.vue` | schema 表单 adapter，替换 `FormSchema.vue`（props: code/loading/row；emits: listenerEvent/formEvent） |
| `SchemaSearch.vue` | schema 搜索 adapter，替换 `TableSearchSchema.vue`（emits: searchEvent） |
| `SchemaTable.vue` | schema 表格 adapter（columns/data/pagination/selection，列级 scoped slot） |
| `AdminState.vue` | AdminFiveState：default/loading/empty/error/noPermission/dependencyUnavailable |
| `EpDrawer.vue` | 抽屉基线（默认 480px，可 640px） |
| `ImpactPreview.vue` | 高危操作影响预览（rows/columns/原因必填） |
| `ApprovalBar.vue` | 高危操作审批动作条 |
| `AuditLink.vue` | 审计日志链接 |
| `index.ts` | 桶导出 |

### 3. 依赖与注册
- 新增 `element-plus@2.4.4` + `@element-plus/icons-vue@2.3.2`。
  - 说明：`element-plus@2.14.x` peer 依赖 `vue@^3.3.7` / `@vueuse/core@14`（需 Vue≥3.5），与本基线 Vue 3.3.4 冲突，故固定 2.4.4（Vue 3.2+ 兼容）。
- `main.ts` 全量注册 Element Plus（过渡期）。

### 4. 复用矩阵
- `.project-ai/decisions/S03-P03_REUSE_MATRIX.md`。

## 验证
- `pnpm exec vue-tsc --noEmit`：0 错误。
- `pnpm run build:check`：通过（构建成功）。

## 已知待优化（非阻塞）
- Element Plus 全量引入导致 chunk 过大（`index-ea9d0ecc.js` 2.8MB / CSS 1.9MB）。
  - 后续 P1 优化项：`unplugin-vue-components` + `ElementPlusResolver` 按需引入 + `manualChunks` 拆包。
- 其余 peer 告警（`vue-router@4.6.4` 需 vue≥3.5）为基线遗留，非本次引入。
