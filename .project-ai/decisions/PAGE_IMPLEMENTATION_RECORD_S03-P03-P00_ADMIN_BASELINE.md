# S03-P03-P00 Admin V2 基线收口（build:check 类型错误清零）

- 日期：2026-08-17
- 包：`S03-P03-P00-ADMIN-BASELINE-RECLAMATION`
- 角色：Developer（一开到底，本地 commit，不 push）
- 状态：完成，`build:check`（`vue-tsc --noEmit && vite build`）exit 0

## 目标

收口 `gainode_admin_v2`（V1.x Layui 拷贝、尚未提交）基线的 `build:check` 类型错误，
使基线可迭代验证，为后续 Element Plus Schema adapter + 逐页迁移铺路。

## 基线错误分类与处置

| 类别 | 数量 | 根因 | 处置 |
|---|---|---|---|
| node_modules `.d.ts`（aws-sdk/@smithy `node:stream`/`Buffer`/`events`、`@layui/*` 内部 `@layui/component/*` 路径、vue-router `NoInfer`） | ~80 | 第三方 .d.ts 自身无法解析/TS 版本不足 | `tsconfig` 开 `skipLibCheck: true`（不升级 TS/Vue） |
| `crypto-js` 无声明（src 内 TS7016） | 1 | 缺 `@types/crypto-js` | `pnpm add -D @types/crypto-js@4.2.2` |
| `TableColumn`/`fixed`/`type` 严格类型不匹配（`<lay-table :columns>`） | ~55 | `types: ["@layui/layui-vue/types/components"]` 全局注册过严的 `TableColumn`（要求每列 `type` + `fixed` 字面量），V1.x 列定义为宽松字面量 | 从 `types` 移除该全局注册；`<lay-table>` 退化为 `any`，列类型检查解除（迁移 Element Plus 后将彻底删除） |
| src 内 `never[]`/`never` 级联（`ref([])`、`reactive({})`） | ~20 | 空字面量推断为 `never` | `ref([])`→`ref<any[]>([])`（14 业务页）；`reactive({})`→`reactive<any>({})`；store `langList`/`menus`/`permissions` 加 `[] as any[]` |
| src 内局部小错（隐式 any 返回、回调签名、`layer` 调用、缺变量） | ~15 | V1.x 遗留 | 逐个修正（见下） |

## 关键改动

- `tsconfig.json`：新增 `skipLibCheck: true`；`types` 由 `["vite/client", "@layui/layui-vue/types/components"]` → `["vite/client"]`（保留 DOM `setInterval→number`，避免 `node` 类型的 `Timer` 副作用）。
- `package.json`：`devDependencies` 增 `@types/crypto-js@4.2.2`。
- 14 个 V1.x 业务页 `dataSource = ref([])` → `ref<any[]>([])`：Recharge/Withdraw/classification/content/kyc/mining-order/mining-project/redEnvelope/signal-arbitrage/signal/system-admin/system-language/user-grade/user-index。
- `components/TableSearchSchema.vue`：`queryModel`/`queryFormSchema` 改 `reactive<any>`。
- `components/FormOpenSchema.vue`：`defineEmits` 补 `listenerEvent`。
- `components/ImportSchema.vue`：补缺失的 `title` ref（模板 `:title` 引用未声明变量）。
- `components/FormOpenSchema.vue` + `views/permissions/admin/edit.vue`：`reactive({})` → `reactive<any>({})`。
- `store/app.ts`（`langList`）、`store/user.ts`（`permissions`/`menus`）：`[]` → `[] as any[]`。
- `views/form/base.vue`、`views/form/step.vue`：`layer.msg(x, ()=>{})` / `layer.open(string)` → `layer.msg(string)`。
- 回调签名 `(index: Number|number)` → `(id: any)`：`account/profile`、`permissions/menu`、`system/menu/index.bak`。
- `views/system/dictionary/index.vue`：`currentForm` 显式 `: any`（解 `id` 访问）。
- 递归函数显式返回类型 `: any[]`/`: void`：`login/index.vue` `mapUrl`、`system/menu`/`system/role`/`team/relationship` `clean`。

## 验证

- `pnpm exec vue-tsc --noEmit`：exit 0（0 error）。
- `pnpm run build:check`：exit 0；`vite build` 2245 modules transformed，33.30s，仅 chunk>500k / mockjs eval / CSS 嵌套等无害警告。

## 遗留（非阻塞，登记为后续 Admin 待办）

- `vue-router@4.6.4` peer 需 `vue@^3.5.0`，基线为 `vue@3.3.4`（unmet peer 警告）；TS 4.7.4 < 5.4（`NoInfer` 由 `skipLibCheck` 规避）。
- TS/Vue 升级与 Layui→Element Plus 迁移一并收口（见 context.md 待确认事项 / 前端升级清单）。
- `MockJS` 未完全禁用（eval 警告）、硬编码密钥（AES `f080a463654b2279`）待迁移到环境变量（P0，已决策）。

## 提交

- `feat(admin): import V2 baseline (Layui) + S03-P03-P00 build:check 收口`
- trailer：`Code-Origin: Developer` / `Git-Operator: Developer`。
