# Acceptance: S03-P01 · H5 基础设施

## 机械验收

| 项 | 方法 | 预期 | 实际 |
|---|---|---|---|
| 依赖锁定 | package-lock.json 含 pinia/vue-i18n/vant/axios/vitest/playwright | 全部在锁 | PASS（6 依赖全部在 lockfile；链上三依赖已移除） |
| 六请求头 | http.ts 拦截器注入 6 header | 6/6 | PASS（tests/unit/http.spec.ts 3 用例覆盖） |
| 无生产 secret | secret scan（AES/S3/私钥/助记词） | 0 匹配 | PASS（AKIA/SECRET_KEY/PRIVATE_KEY/MNEMONIC 扫描 0） |
| 链上依赖 | package.json 无 solana/ethers/tronweb | 0（单独提交） | PASS（package.json + lockfile 0；ConnectWallet.vue 删除） |
| type-check / build / test:unit | npm run | PASS | PASS（type-check 0 error；build vite 122 modules；test:unit 19/19） |

## 业务验收（07 §S03-P01）

| 验收项 | 落地 |
|---|---|
| 六请求头/RESULT_UNKNOWN 有自动测试 | tests/unit/http.spec.ts（6 头注入 + 202 RESULT_UNKNOWN + 非 2xx 错误信封 + 写超时 UnknownResultError） |
| 7 语言 key parity | tests/unit/i18n.spec.ts（7 locale key 集一致 + common.* + 敏感文案存在） |
| tokens 有测试 | tests/unit/tokens.spec.ts（color/spacing/typography/radius/elevation 结构 + 取值） |
| 五态组件有测试 | tests/unit/components.spec.ts（ApiErrorBoundary/FiveStateContainer/RestrictedState/UnknownResult 6 用例） |
| 无生产 secret | secret scan 0（s3Upload.ts 硬编码 AK/SK 已移除，改后端 presigned） |

## 合同缺口 / Owner 待决（登记，不阻塞）

- **S03-P01-UPLOAD-PRESIGNED-URL**：上传 presigned URL 端点未冻结。`p0_001_s3_remediated` 提及 `POST /api/upload/presigned-url`，03 原型引用 `POST /api/v1/uploads`，OpenAPI 尚无 uploads 路径。`src/utils/s3Upload.ts` 临时采用 `POST /api/v1/uploads`（请求 `{key, content_type}`，响应 `{upload_url, object_url}`），上传契约冻结后需对齐。
- **S03-P01-VISUAL-BASELINE**：375/390/430 三尺寸视觉基线已配置于 `playwright.config.ts`（mobile-375/390/430 项目），逐页三尺寸截图归 S03-P02 每页固定文件，S03-P01 不产出。
- 敏感文案（`otc.risk_disclosure.body`）`PENDING_HUMAN_REVIEW`，需 Owner 签核后方可声明最终生产文案。
- 旧 22 视图使用 V1.x `/v1/api/*` 路径与旧 i18n key（经 `legacy.ts` / legacy `t` 兼容），S03-P02 逐页迁移后删除 legacy shim。

## 结论

S03-P01 建立 H5 基础设施（六请求头 http、Pinia stores、router meta、tokens、7 语言、五态组件、测试），
legacy shim 保持旧 22 view 可编译；链上依赖与硬编码密钥移除（引用清单归零）；机械/业务验收全 PASS。
上传 presigned URL 契约与逐页视觉截图为已知合同缺口，登记不阻塞 S03-P02。
