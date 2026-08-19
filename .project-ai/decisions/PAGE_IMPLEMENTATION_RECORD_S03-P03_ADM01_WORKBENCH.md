# S03-P03 ADM-01 逐页实现（A-WORK-001 运营总览 / A-WORK-002 今日待办）实施记录

## 背景与决策
- 用户决策（会话内明确选择）：「先重构到权威 8 导航，再逐页实现 ADM-01」。
- 前置冲突：前一 agent 遗留的 11 根旧骨架（`pageRegistry.ts` / `ListPage.vue` / `pageSchema.ts` + `base-routes.ts` 40 路由）与权威 `04 §2` 8 导航 IA 冲突。本包先收敛为 8 导航，再落地 ADM-01。

## 交付物

### 1. 8 导航收敛（commit `0c98813`）
- `src/types/page.ts`：`AdminPageMeta` 扩展 `route/type/actions/isDetail/parentPageId`；新增 `AdminAction`、`PageActionPolicy`、`AdminPageType`、`DeferredPageMeta`。
- `src/router/module/admin-registry.ts`：33 权威 + 7 deferred 全量元数据（`route/type/actions/isDetail/parentPageId`）；`getEntryByRoute` / `getEntryByPageId` / `isActionAllowed` / `validateRegistry`。
- `src/router/module/base-routes.ts`：2.0 段改为按 registry 程序化生成（33 + 7 deferred）。
- `src/views/common/pageSchema.ts`：40 个 key 重映射到 8 导航新路由。
- `src/views/common/pageRegistry.ts`：废弃为兼容层，re-export `admin-registry`。
- `src/views/common/ListPage.vue`：指向 canonical `admin-registry`。

### 2. ADM-01 逐页实现（commit `a6f5372`）
- `src/router/module/admin-page-components.ts`：已实现页面组件映射（`Partial<Record<PageId, () => Promise<Component>>>`），未实现的 Page 回退 `ListPage.vue` 骨架。
- `src/views/workbench/Overview.vue`：A-WORK-001 运营总览（环境标识 / KPI 摘要 / 异常 / 待办 / 对账 / 系统状态 / 快捷入口；`EpAdminState` 五态；MOCK_ONLY）。
- `src/views/workbench/Todo.vue`：A-WORK-002 今日待办（筛选 / 工作队列 / 480px Drawer 预览 / 领取走 `object_version` 乐观锁 + 冲突提示；MOCK_ONLY）。

## 契约依据
- `04 §3` A-WORK-001 / A-WORK-002 规格：布局、关键尺寸（摘要卡 min 220×104 / 图表 240 / 区块 gap 24；筛选 56 / 表格行 48 / Drawer 480/640）、状态、视觉禁止（不做黑色收益大屏、优先级不只靠颜色）。
- `05 §1` CONCURRENCY = `If-Match` / `object_version`：并发领取不得静默覆盖，冲突须提示刷新。
- `07 §9` S03-P03 逐导航批次（ADM-01 为工作台首批次）。

## 验证
- `vue-tsc --noEmit`：0 错误。
- `vite build`：通过（3619 modules；`Overview` / `Todo` 各自独立成 chunk）。

## 非阻塞 / 未冻结
- 后端 `GET /admin/dashboard`、`GET/POST /admin/work-items` 尚未实现，页面为 MOCK_ONLY UI 骨架，接入时替换 `load()/claim()/transfer()`。
- Admin 2.0 路由当前为顶层路由（未挂 8 导航 Sidebar 240 / Header 64 布局）；8 导航布局属后续布局批次，非本包范围。
- 领取/转派等写操作前端仅演示 `object_version` 语义；接入真实接口后按 `If-Match` 对接。
