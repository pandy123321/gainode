# Gainode Admin Prototype — 隔离声明

> 本目录为 **静态交互原型（Wireframe / Mock）**，仅用于 UI 交互走查。

## 状态标记

- **NON_PRODUCTION** — 非生产
- **MOCK_ONLY** — 数据全部来自 `js/mock-data.js`，无真实 API / DB
- **DO_NOT_DEPLOY** — 禁止部署到生产环境

## 禁止事项

- 本目录（`0.5代码/admin-proto/`）**不得**进入任何生产构建 / 部署 Pipeline。
- 登录页（`index.html`）为**无认证**的 Wireframe 登录，`admin` / `admin123` 仅为演示用（已标注 DEMO），**禁止**在生产 Admin 中作为任何真实凭据或默认账号。
- 本目录的 RBAC / Emergency / Approval 逻辑为 **Mock 演示**，不作为生产授权权威；生产后端必须重复校验（`canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD`），不得依赖前端。

## XSS / 渲染边界（重要）

- 本原型大量使用 `innerHTML` + 字符串拼接（`App.openModal` / `tbl` / `_dm` 等）来渲染 Mock 数据。这只在**纯静态、无真实 API 数据**的原型中可接受。
- **正式 Vue Admin（`gainode_admin_v2`，Element Plus）禁止复用此 `innerHTML + raw concat` 模式。**
- 正式 Admin 的所有 API 数据必须使用 **Vue interpolation / 组件渲染**（`{{ }}` / `v-html` 禁用或经白名单净化），不得将后端返回的原始字符串拼接进 `innerHTML`。

## 治理对齐

- RBAC：canonical 13 role IDs（`END_USER … ADMIN_SECURITY`），`display_name` 仅 UI 展示，授权 ID 必须 canonical，无 `Super Admin` 绕过角色。
- Emergency：`ADMIN_SECURITY + MFA + case_id/reason/evidence + 48h Independent Audit`，无强制第二授权人。
- Approval：canonical states（`draft / pending / changes_requested / approved / rejected / executing / executed / failed`），动作消费 `allowed_actions`，`requester_actor_id == current_actor_id` 时禁用批准（Actor-level SoD）。
