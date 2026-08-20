# S03-P03 Admin 基础设施 + 逐页 — 前端交接单

> 交接对象：前端同事（Admin 后续逐页联调）
> 交接范围：STAGE-03 Admin 的「基础设施 + 33 页骨架」已完成，后续由前端同事接手逐页接接口。
> 权威依据：`Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md` §8（S03-P03）

---

## 0. 一句话现状

Admin 前端已经跑通「登录 → 11 根侧边栏 → 40 个路由 → 通用列表/看板骨架渲染」，
但**表格数据是空壳，未接任何后端接口**。这是刻意为之：后端 HTTP 层尚未实现（STAGE-02 只完成了域对象/状态机/OpenAPI），
本阶段交付的是**可运行的 UI 骨架 + 权威 Page ID 契约 + 页面级操作策略**，供同事在接口就绪后逐页填充。

---

## 1. 权威 Page ID 源（唯一真源，不可另起炉灶）

**`07_DEVELOPMENT_AND_ACCEPTANCE.md` §8「显式 Page ID 注册表（范围写法不得替代本清单）」**

```text
ADMIN_P0(29) + ADMIN_P1_CONDITIONAL(A-USER-004) + ADMIN_P1(A-REPORT-001,A-GROWTH-001)
+ ADMIN_FUTURE_CLOSED(A-MIGRATION-001) = 33 个权威 Page ID
```

- 验收边界 = **33 个权威 Page ID** 全部进 registry；另有 7 个 `DEFERRED` 页（`A-AI-*`、`A-DATA-*`）
  仅占位不 404，**不计入验收**。
- **Page ID 与标题解耦**：`pageId` 是契约（不可改），`title` 是展示层人话（可改）。
  本次已把 title 统一为人话（与 SQL `name`、mock `title`、route `meta.title` 四处一致）。

---

## 2. 四层文件与职责（已对齐，均为「骨架层」）

| 文件 | 职责 | 关键点 |
|---|---|---|
| `src/views/common/pageRegistry.ts` | **Page ID 权威注册表**（33 权威 + 7 DEFERRED），含 `nav`/`route`/`title`/`priority`/`actions` | `AUTHORITATIVE_PAGE_IDS` = 33；`isActionAllowed()` 决定按钮显隐 |
| `src/views/common/pageSchema.ts` | 每页 `type`(dashboard/list)、`stats`、`filters`、`columns` | `key = route.path`；数字列 `align:'right'` |
| `src/views/common/ListPage.vue` | 通用渲染器：统计卡 + 搜索/筛选 + 表格 + 分页 + 五态 + 差异化按钮 | 当前 `rows=[]`，`onSearch` 不接后端 |
| `src/router/module/base-routes.ts` | 40 个 2.0 路由，每个带 `meta.pageId` | `meta.pageId` 链接到 `pageRegistry` |

### 2.1 后端数据源（SQL / mock，供同事对齐，不参与前端运行）

- **SQL 种子**：`0.5代码/gainode后端/gainode/sql/20260817_admin_20_menu_seed.sql`（V3 版）
  - `descr` 已从 V2.4.1 旧命名**机械对齐到 07 §8 权威 Page ID**（幽灵 ID 已清）。
  - `route_url` = 前端路由 path（与 mock/route 一致）。
  - `route_key` **留空**：后端 HTTP 接口未实现，接接口时回填。
  - 隐藏菜单（`is_show=0`）：`紧急操作 /system/emergency`、`APT 迁移 /system/migration`。
- **mock 菜单**：`src/mockjs/user.ts`（登录后侧边栏用，title 已对齐人话）。

---

## 3. 渲染路径切换点（重要，勿反向收敛）

当前存在两套渲染层，**定位不同，禁止现在合并**：

| 路径 | 定位 | 数据来源 | 状态 |
|---|---|---|---|
| `views/common/ListPage.vue` | **本地骨架**，立即渲染 UI 看板/列表 | 静态 `pageSchema.ts` | ✅ 可运行 |
| `components/ep/*`（SchemaTable/SchemaForm/SchemaSearch/ImpactPreview/ApprovalBar/AuditLink/EpDrawer/AdminState） | **强类型组件**（ImpactPreview/ApprovalBar/AuditLink 为业务组件，未来可直接复用） | 见下方「schema 配置策略」 | ⚠️ 待后端 DTO 元数据下发 |

- **`AdminState.vue`（五态）已被 `ListPage.vue` 复用**，这是当前两套路径的**唯一交集**，是正确的复用。
- **`ImpactPreview`/`ApprovalBar`/`AuditLink` 是高价值业务组件**：高风险页（资产调整/账本更正/赛果/结算/冲正）强制要求「来源对象 + before/after impact + reason + evidence + approval actor + Audit ID」，这三个组件就是为此设计，逐页联调时应直接复用，不要另写一套。
- ⚠️ **`SchemaTable`/`SchemaForm`/`SchemaSearch` 目前绑定 V1.x 的 `/admin/schemaForm/*` 老接口，不要误用**：它们的字段来源是 `sys_table_field` 表字段直映射，见 §3.1，2.0 不走这条老路。

### 3.1 schema 配置策略（Owner 已定：走 DTO 元数据新路，不碰 `sys_table_field` 老路）

**背景**：V1.x 有一套 DB 驱动的 schema 下发机制（`sys_table_list` + `sys_table_field` + `SchemaFormController`），
前端 `SchemaTable/SchemaForm/SchemaSearch` 消费它动态生成界面。这套机制**不适用于 2.0**，理由如下。

| | V1.x schemaForm 老路 | 2.0 权威契约 |
|---|---|---|
| 字段来源 | 数据库**表字段直映射**（`sys_table_field` 描述 `sys_admin` 每列） | OpenAPI **DTO**（聚合多表投影 + 脱敏 + 字符串 decimal + UTC 时间） |
| 粒度 | 一张表 = 一个界面 | 一个页面 = 多个域对象聚合 |
| 约束 | 无脱敏/SoD 语义 | 字段脱敏、append-only、影响预览、SoD |

`07 §8` 明确要求 **「绑定 OpenAPI DTO，不手写第二套字段」**。若照搬 `sys_table_field`，字段口径会对不上
（如 `apt_accounts.balance` 是字符串 decimal，`sys_table_field` 只能标 `fd_type='varchar'`，丢失金额语义），必然返工。

**结论（三阶段）**：

1. **现在（交接交付）**：`pageSchema.ts` 静态骨架作为 **UI 默认值** 交付，字段口径以后端 OpenAPI DTO 为最终标准，`pageSchema` 仅作占位骨架。
2. **后端 HTTP 层完成时**：把「列/筛选/表单 schema」升级为 **DTO 字段元数据驱动**——由后端按 OpenAPI DTO 下发每页元数据，前端消费。这才是 07 §8「Schema 驱动 + 绑定 DTO」的正解。
3. **后台可配置（长期增强）**：在 DTO 元数据层之上，再提供「哪些字段可见/可筛/必填、排序、宽度」的可配置项；但**必须先解决脱敏、权限、SoD 约束**才能放开配置——高风险页（资产调整、账本更正）的字段**禁止**后台随意改。

**明确不做**：不把 2.0 的 33 张新表登记进 `sys_table_list` / `sys_table_field`（字段语义不匹配，做了返工，且易把「表字段可改」误当成「业务字段可改」，踩 07 §8 脱敏红线）。

---

## 4. 同事接手任务清单（接口就绪后逐页执行）

1. **逐页接 DTO/API**：以 `pageSchema.ts` 的 `columns/filters/stats` 为**占位骨架**，最终字段口径以 OpenAPI DTO 为准（不要手写第二套字段）。
2. **填充 `ListPage.vue` 的 `onSearch/onPageChange`**：替换当前空实现，接入 `pageState`（loading/empty/error/noPermission/dependencyUnavailable）与 `rows/total`。
3. **差异化按钮接真动作**：`pageRegistry.actions`（`allowed`/`forbidden`）已决定按钮显隐，接 `allowed_actions` 时按钮**不可由本地金额/等级推导**。
4. **后端 `route_key` 回填**：每个菜单项的 `route_key` 对接真实接口路由标识符。
5. **7 语言 + 三尺寸**：`07 §8` 要求 1280/1440/1920 visual、7 语言 key parity、i18n parity。
6. **高风险页统一验收**（资产调整/账本更正/赛果/结算/退款/冲正/参数发布/紧急操作）：
   必须展示来源对象、before/after impact、reason、evidence、approval actor、执行终态、request_id、Audit ID；
   状态变化时**不允许用旧表单重复提交**。

---

## 5. 已知边界与遗留

- **后端 HTTP 未实现**：`ListPage` 是纯前端骨架，`onSearch` 为空、`rows` 恒空、按钮点击仅 `ElMessage.info` 提示。
- **`A-USER-004`（用户资产调整）为 P1_CONDITIONAL**：只有 Impact Preview，执行按钮禁用（对应 `ADM-02B`）。
- **`A-MIGRATION-001`（APT 迁移）为 FUTURE/CLOSED**：只显示关闭说明，无执行控件（`actions: closed()`）。
- **7 个 DEFERRED 页**（`/ai/*`、`/data/source|football|market|signal`）：占位不 404，**不计入 S03-P03 验收**。
- **`pageSchema` 字段语义**：基于 V2.4.1 原型字段线索；权威 Page ID 重划后（如 `A-USER-002` 用户360 取代旧 `A-USER-003` 限制），
  逐页联调时需以 OpenAPI DTO 为最终字段口径，`pageSchema` 字段可随之微调。

---

## 6. 交接自检记录

```text
vue-tsc --noEmit        = PASS（exit 0，2026-08-18）
权威 Page ID 数          = 33（pageRegistry.AUTHORITATIVE_PAGE_IDS）
DEFERRED 页数            = 7（不计入验收）
SQL 幽灵 ID 清理         = 已清理（A-USER-003/A-AFF-*/A-ECON-*/A-ROBOT-004/A-OTC-003/A-DATA-001/A-AUDIT-002/A-AI-003/005/006）
四处 title 对齐          = pageRegistry / base-routes / SQL name / mock title 一致
渲染路径边界             = ListPage(骨架,可运行) vs ep(业务组件复用)；schema 走 DTO 元数据新路，不碰 sys_table_field 老路
schema 配置策略          = 三阶段：静态骨架 → DTO 元数据下发 → 后台可配置（先解决脱敏/权限/SoD 再放开）
```
