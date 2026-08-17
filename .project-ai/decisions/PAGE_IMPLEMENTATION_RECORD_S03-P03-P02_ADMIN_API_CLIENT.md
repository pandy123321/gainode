# S03-P03-P02 Admin API client + request context + object_version 冲突提示 实施记录

## 目标
统一 Admin API client、request context、RESULT_UNKNOWN、导出任务轮询、字段/数据范围权限和 object_version 冲突提示（07 §9 S03-P03 基础设施步骤 2）。

## 交付物

### 1. V2 契约类型（`src/api/types.ts`）
与 `gainode_h5_v2/src/api/types.ts` 同构，对齐 05 §1/§7/§10：
- `ResultCode` 16 项错误码（含 `RESULT_UNKNOWN` / `OBJECT_VERSION_CONFLICT`）。
- `Envelope<T>`（request_id + data + FreshnessMeta + WriteMeta）。
- `ApiError`（含 `isObjectVersionConflict` / `isAuthError` / `isForbidden` 语义 getter）。
- `UnknownResultError`（写请求结果未知，需用原 Idempotency-Key 查询）。
- Admin 特有：`AsyncJob` / `AsyncJobStatus` / `ExportTaskResult`（导出/异步任务）。

### 2. request context 工具（`src/utils/request-id.ts`）
`generateRequestId` / `generateIdempotencyKey`（UUID v4 无连字符 ≤64）/ `nowUnixSeconds`。与 H5 同构。

### 3. 统一 V2 客户端（`src/api/http-v2.ts`）
- 六请求头：`Authorization: Bearer` / `Accept-Language` / `X-Request-Id` / `X-Timestamp` / `Idempotency-Key`（写）/ `If-Match`（乐观锁）。
- auth refresh single-flight（`/api/v1/auth/refresh`，401 `AUTH_UNAUTHENTICATED` 刷新后重试一次）。
- 写请求超时/无响应 → `UnknownResultError`，**不自动重试创建**（05 §7）。
- 令牌/语言持有器模块级注入（`setAccessToken/setRefreshToken/setLanguageGetter`），避免与 Pinia store 循环依赖。
- 与 V1.x `api/http.ts`（MD5 签名 + code/msg/data）并存；新页走 V2，Layui 旧页保持旧 client 直至迁移。

### 4. 导出任务轮询（`src/api/export-task.ts`）
- `createExportTask`（POST `/api/v1/export-tasks`）、`getAsyncJob`（GET `/api/v1/async-jobs/{id}`）。
- `pollAsyncJob`：终态/超次数/外部取消三态终止，超时抛错进入错误态，不无限挂起。

### 5. object_version 冲突处理（`src/utils/object-version.ts`）
- `isObjectVersionConflict` / `showObjectVersionConflict` / `handleObjectVersionConflict`。
- 冲突时 `ElMessageBox.alert` 提示「数据已被其他操作员修改，请刷新后重试」，**不提供强制覆盖**，确认后触发刷新回调。

### 6. 字段/数据范围权限辅助（`src/utils/data-scope.ts`）
- `hasAction` / `hasAnyAction`（只读服务端 `allowed_actions`，禁止本地推导可操作性）。
- `DataScopeContext` 存取、`isFieldMasked` 脱敏辅助。
- SoD 为后端强制，前端仅展示禁用态，不作为安全边界。

## 契约依据
- 05 §1：`WRITE_IDEMPOTENCY = required`、`CONCURRENCY = If-Match / object_version`。
- 05 §2.1：auth 统一 `/api/v1/auth/refresh`。
- 05 §7：16 项错误码 + `RESULT_UNKNOWN` 用原 Idempotency-Key 查询，不提示重试创建。
- 04 §12 / 05 §11：授权公式 = canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD；页面权限 ≠ 字段权限 ≠ 数据范围权限。

## 验证
- `pnpm exec vue-tsc --noEmit`：0 错误。
- `pnpm run build:check`：通过（3601 modules）。

## 未冻结 / 留待逐页批次（非阻塞，不臆造契约）
- Admin 登录页（`/api/v1/auth/login`）接入 access/refresh token 持有器 → 属 P04/P05 逐页迁移。
- `data_scope` 的具体传输载体（header vs query）未在 OpenAPI 冻结；当前不臆造请求参数，仅提供前端上下文存取与 allowed_actions 渲染辅助，真实授权由后端强制。
- 字段脱敏的具体返回结构（`masked` 标记 / 脱敏值）以 OpenAPI 冻结为准；`isFieldMasked` 已按通用约定兜底。
