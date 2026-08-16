# Design: S03-P01 · H5 基础设施

## 迁移策略（strangler fig）

新基建替换 `src/api/http.ts`、`src/i18n/index.ts`、`src/router/index.ts`；旧模块迁至 legacy shim，
保持 22 个旧 view（`src/views/**`）在 `type-check` 下可编译，待 S03-P02 逐页迁移到新基建后删除 legacy。

## 依赖核准（步骤 2）

| 依赖 | 用途 | 决策 |
|---|---|---|
| pinia + pinia-plugin-persistedstate | 状态管理 + 安全持久化 | APPROVED（08/05 技术栈既定） |
| vue-i18n | 7 语言 | APPROVED |
| vant | H5 组件库 | APPROVED |
| axios | HTTP 客户端 | APPROVED |
| vitest + @vue/test-utils + jsdom | 单元/组件测试 | APPROVED |
| @playwright/test | E2E + visual | APPROVED |
| @solana/web3.js / ethers / tronweb | 链上 | **REMOVE**（引用清单=0，仅 ConnectWallet.vue 引用，单独提交） |

## 六请求头（05/OpenAPI `components/headers/request.yaml`）

```text
Authorization     Bearer JWT
Idempotency-Key   写操作幂等（≤64）
If-Match          object_version 乐观锁
Accept-Language   7 语言（默认 zh-CN）
X-Request-Id      请求追踪（缺失服务端生成）
X-Timestamp       客户端时钟（Unix 秒）
```

## 文件清单

```text
package.json / vite.config.ts / vitest.config.ts / playwright.config.ts
src/api/{http.ts,client.ts,types.ts,legacy.ts}          # http=V2 六请求头；legacy=旧 MD5（S03-P02 删）
src/api/services.ts                                      # 改 import ./http → ./legacy
src/stores/{session,entitlement,robot,prediction,asset,notice}.ts   # Pinia
src/stores/{user,project}.ts                             # 旧 store 保留（legacy）
src/router/{index.ts,meta.ts}                            # meta: pageId/auth/restricted/feature
src/i18n/index.ts                                        # vue-i18n 7 语言 + legacy t/setLanguage 兼容导出
src/i18n/locales/{zh-CN,en-US,ja-JP,ko-KR,th-TH,de-DE,fr-FR}.json
src/i18n/{ui-copy-manifest,terminology-lock,sensitive-copy-review}.json
src/tokens/{color,spacing,typography,radius,elevation}.ts + index.ts + tokens.css
src/utils/request-id.ts
src/components/{ApiErrorBoundary,FiveStateContainer,RestrictedState,UnknownResult}.vue
tests/unit/{http,tokens,i18n,components}.spec.ts
tests/e2e/smoke.spec.ts
```

## 关键实现要点

- **http.ts**：axios instance + 请求/响应拦截器注入六请求头；`refreshToken` single-flight（并发 401 只刷新一次）；
  写请求携带 Idempotency-Key（无则生成并保存）；If-Match 来自 object_version；超时写请求 → `RESULT_UNKNOWN`
  并进入 `unknown-query`（按 request_id/idempotency_key 查询原对象），不自动重 POST。
- **stores**：persistedstate 白名单仅存 `session.token_ref`/`session.locale`/安全偏好；不持久化 secret 或权威余额。
- **router meta**：每条 route 带 `meta.pageId/auth/restricted/feature`；`beforeEach` 深链无权限安全降级到受限页。
- **tokens**：颜色/间距/字号/圆角/阴影完全取 08 §2-6；`tokens.css` 暴露 CSS 变量，业务页禁止硬编码品牌色。
- **i18n**：7 locale 均含 `common.*` 基础 key；`t` 兼容旧 view 调用；敏感文案（KYC/Consent/OTC 风险）标
  `PENDING_HUMAN_REVIEW`，不伪造人工 PASS。
- **legacy shim**：`legacy.ts`（旧 MD5 http）+ 旧 i18n 导出（t/setLanguage/getCurrentLanguage/getSupportedLanguages/
  getCurrentLocale）+ 旧 stores/user|project + 旧 utils/toast|s3Upload 保留，仅服务旧 view 编译。

## 验证命令

```text
npm install（锁定 lockfile）
npm run type-check
npm run build
npm run test:unit
npm run test:e2e（smoke，可选有浏览器环境）
i18n parity / hardcoded secret/copy scan
```
