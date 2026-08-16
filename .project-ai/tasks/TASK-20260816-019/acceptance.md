# S03-P02 · H5-01 Auth 批次 — 验收

## 1. 机械验收

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 6 files / 36 tests pass（新增 auth.spec.ts 9 + auth-views.spec.ts 4） |
| `npm run build` | ✅ vite 140 modules，含 5 个 auth 页 chunk |
| i18n key parity（7 语言） | ✅ 同构（tests/unit/i18n.spec.ts key parity 断言覆盖） |
| secret 扫描 | ✅ 0 匹配（无硬编码密钥） |

## 2. 业务验收（07 §S03-P02 H5-01）

| 验收点 | 结果 |
|---|---|
| M-AUTH-001..005 均有 route + pageId meta | ✅ `/auth/{login,register,otp,recovery,mfa}` |
| 绑定 OpenAPI DTO，不手写第二套字段 | ✅ `src/api/auth.ts` 透传 auth.yaml |
| 五态 / 写操作状态 | ✅ submitting + 错误横幅 + Restricted 文案 + UnknownResult（http.ts） |
| 登录中禁止重复点 | ✅ `submitting` 守卫 |
| 失败保留账号清密码 | ✅ 仅清 errorMessage，不动账号 |
| 成功由服务端给 next_step（mfa_required） | ✅ mfa_required → /auth/mfa |
| 条款不可默认勾选 | ✅ 默认 false + 未勾选拦截 |
| 不泄露账号是否存在（防枚举） | ✅ 统一「账号或密码错误」/「若账号存在已发送」 |
| 倒计时 | ✅ 60s 兜底（服务端 retry_after 未冻结，见缺口） |
| 无生产 secret | ✅ |

## 3. 合同缺口

`S03-P02-AUTH-REFRESH-TOKEN` / `-CONSENT-VERSION` / `-RECOVERY-VERIFY` / `-MFA-METHODS` / `-LOGIN-POLICY` / `-OTP-CHALLENGE`（详见 design.md §4），登记 NEEDS_OWNER_DECISION，不阻塞后续无依赖批次。

## 4. 结论

H5-01 Auth 批次（M-AUTH-001..005）机械/业务验收全 PASS。三尺寸视觉截图（375/390/430）与逐页 E2E 属 S03-P02 后续补，本批次不产出（继承 S03-P01-VISUAL-BASELINE 缺口）。下一批次 H5-02 KYC/Notice。
