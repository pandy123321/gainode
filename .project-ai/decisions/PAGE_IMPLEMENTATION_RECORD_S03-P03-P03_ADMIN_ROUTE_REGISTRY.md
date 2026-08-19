# S03-P03-P03 Admin 8 导航 route registry + Page ID meta + AdminFiveState 实施记录

## 目标
建 8 个导航 route registry 和 Page ID meta，并补全写页状态组件（07 §9 S03-P03 基础设施步骤 3、4）。

## 交付物

### 1. 类型契约（`src/types/page.ts`）
- `AdminNavId`：8 个一级导航 ID（workbench/admission/ledger/robot/otc-power/prediction/risk-governance/support-audit-ops），顺序与 04 §2 严格一致。
- `PageId`：33 个权威 Admin Page ID 字面量联合（07 §8 显式注册表）。
- `DeferredPageId`：7 个 DEFERRED 页（`A-AI-*`/`A-DATA-*`，占位不 404，不计入验收）。
- `AnyAdminPageId = PageId | DeferredPageId`（route meta 里可出现的全集）。
- `PagePriority`：`P0 | P1 | P1_CONDITIONAL | FUTURE | DEFERRED`。
- `PageContractStatus`：`OPEN | CONTRACT_GAP | CLOSED | TBC`。
- `WriteStateName`：写页七态（invalid/confirm/submitting/processing/success/failed/stateChanged）。
- `declare module 'vue-router'` 增强 `RouteMeta`：`pageId/navId/priority/contractStatus/requireServerAuth`。

### 2. 8 导航 route registry（`src/router/module/admin-registry.ts`）
- `ADMIN_NAVS`：8 个一级导航（id/title/order）。
- `ADMIN_PAGE_REGISTRY`：33 个权威 Page ID 注册表（pageId/navId/title/priority/contractStatus/requireServerAuth）。
- `pagesByNav` / `pageById` 查询助手。
- `validateRegistry()`：完整性自检（33 个 Page ID 恰好各注册一次，无遗漏、无重复、无未知导航）。
- `ADMIN_PAGE_COUNT = 33`。

### 3. 写页状态组件（`src/components/ep/WriteState.vue`）
- 渲染 7 个写页状态：invalid / confirm / submitting / processing / success / failed / stateChanged。
- 各状态提供具名 slot（`invalid/confirm/success/failed/stateChanged`）以插入差异化按钮。
- `submitting`/`processing` 显示加载态；`stateChanged` 提示「数据状态已变化，请刷新后重试」（对齐 object_version 冲突语义）。

### 4. 组件导出（`src/components/ep/index.ts`）
新增 `EpWriteState` 导出。

### 5. 路由类型收口（`src/router/module/base-routes.ts`）
- `export default [...]` 改为显式 `const routes: RouteRecordRaw[]` 再 `export default routes`，修复 vue-router 4.6 对 `redirect` 路由记录的联合类型推断错误（`redirect: undefined` 不可赋给 `RouteRecordRedirectOption`）。

## 契约依据
- 04 §2/§3：8 Root IA、33 个 Page ID 及优先级标注。
- 07 §8 显式 Page ID 注册表：`ADMIN_P0(29) + A-USER-004 + A-REPORT-001,A-GROWTH-001 + A-MIGRATION-001 = 33`。
- 07 §9 步骤 3：建 8 个导航 route registry 和 Page ID meta；菜单由 RBAC 过滤但直接 URL 仍需服务端授权（`requireServerAuth`）。
- 07 §9 步骤 4：AdminFiveState = Default/Loading/Empty/Error/No Permission/Dependency Unavailable（已由 `AdminState.vue` 承担）；写页再加 Invalid/Confirm/Submitting/Processing/Success/Failed/State Changed（本包 `WriteState.vue`）。

## 验证
- `pnpm exec vue-tsc --noEmit`：0 错误。
- `pnpm exec vite build`：通过（3601 modules）。
- `ADMIN_PAGE_REGISTRY` 条目数 = 33（`validateRegistry()` 返回空错误数组）。

## 与既有工作衔接（非阻塞，留待 Quality 合并审核）
- 工作区存在前序未提交的 Admin 逐页骨架（`src/views/common/pageRegistry.ts` / `ListPage.vue` / `pageSchema.ts`、`base-routes.ts` 中 40 个 2.0 路由、`ADMIN_S03P03_HANDOFF.md`），属 STAGE-03 逐页批次（P04/P05）交付，不在本包范围。本包的 8 导航注册表是「权威 Page ID 全集的强类型化视图」，逐页实现时据此生成 route 并校验完整性，二者最终以 07 §8 33 权威 Page ID 收敛。
- 菜单过滤依赖 `store/user.menus`（RBAC），本包只提供页面全集与 `requireServerAuth` 标记，不做前端授权推导。

## 未冻结 / 留待逐页批次（非阻塞）
- 7 个 DEFERRED 页（`A-AI-*`/`A-DATA-*`）仅占位，契约未冻结，不计入 33 权威验收。
- `A-EMERGENCY-001`（紧急操作控制）合同状态 `TBC`：仅已签 override contract 才开放执行控件。
- 1280/1440/1920 visual、键盘焦点、table density、480/640 Drawer 基线（07 §9 步骤 5）属后续基础设施批次。
