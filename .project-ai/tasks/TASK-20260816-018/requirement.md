# Requirement: S03-P01 · H5 基础设施

> TASK-20260816-018 · Developer 角色 · Code-Origin: Developer
> 权威规格：07 §S03-P01（固定步骤 1-9）

## 背景

S03-P00 已冻结 `H5_TARGET_ROOT = E:\github\sports\gainode_h5_v2`（方案 A），最小构建集已复制（72 files），
baseline build PASS。本包在无页面业务逻辑前提下建立 H5 基础设施，旧 22 视图留待 S03-P02 逐页重建。

## 前置

- `H5_TARGET_ROOT` 已冻结 ✅（2026-08-16 OWNER_DIRECTIVE）

## 固定步骤（07 §S03-P01 1-9，摘录）

1. baseline build/typecheck（已 PASS）；建 migration checklist；保留 Vue3+TS 与可复用 route/view/component。
2. 核准 Pinia+persistedstate、vue-i18n、Vant 4、Axios、Vitest、Playwright；依赖不满足先交 Dependency Decision。
3. `src/api/http.ts` + domain clients：六请求头、auth refresh single-flight、request_id、Idempotency-Key、If-Match；
   超时写请求 → RESULT_UNKNOWN 并按原对象查询，不自动重 POST。
4. Pinia stores：session/entitlement/robot/prediction/asset-power-otc/notice；只持久化 token 引用/安全偏好。
5. router meta：pageId/auth/restricted/feature；深链无权限安全降级；语言切换保留 route/object/tab/filter/form draft。
6. 08+Figma tokens → `src/tokens/{color,spacing,typography,radius,elevation}`；业务页禁散落硬编码品牌色。
7. 7 语言资源 + ui-copy-manifest；key parity / one-locale / hardcoded-copy / 敏感文案 PENDING_HUMAN_REVIEW。
8. 硬编码 AES/sign/S3 key 移后端/环境；上传改 presigned URL；删除链上依赖仅当引用清单=0 且单独提交。
9. unit/component/E2E/375-390-430 visual 基线；先为 ApiErrorBoundary/FiveStateContainer/RestrictedState/UnknownResult 写测试。

## 非目标（本包不做）

- 不重建 22 个旧 view（S03-P02）。
- 不实现具体页面业务逻辑。
- 不静默升级所有依赖。

## 停止条件（只停止受影响项，生成 Decision/Deviation）

- target 未冻结（已解除）；依赖未经批准；OpenAPI 未 APPROVED（V3.4 降级为 Quality 验证项）；Figma token 冲突。

## 验收

基础设施无页面业务逻辑下可 build/test；六请求头/RESULT_UNKNOWN/7语言/tokens/五态组件有自动测试；无生产 secret。
