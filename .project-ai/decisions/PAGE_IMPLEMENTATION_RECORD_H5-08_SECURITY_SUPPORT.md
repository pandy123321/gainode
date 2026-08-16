# PAGE_IMPLEMENTATION_RECORD — H5-08 Me/Security/Settings（M-ME-001 / M-SEC-001..002 / M-SETTINGS-001）

> 批次：H5-08 Me/Security/Settings ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-ME-001 / M-SEC-001..002 / M-SETTINGS-001 ｜ OpenAPI user.yaml / auth.yaml / eligibility.yaml
> （S02-P02 APPROVED：User / SecurityProfile / SessionDevice / MfaEnrollment / LoginAudit）
> 治理核心：**写操作全 fail-closed**。MFA 注册（setup/confirm/disable）、会话撤销（revoke）、偏好（GET/PUT /me/preferences）
> 均无 Active Release（后端 503 DEPENDENCY_UNAVAILABLE），前端**不提供写方法**，对应按钮一律 disabled 或 Restricted 占位。
> 只读端点（`GET /me`、`GET /me/security-profile`、`GET /me/sessions`）保持绑定；登录审计 source 未裁决 → UNAVAILABLE，受限展示。
> M-SUPPORT-001..003（帮助中心/工单）属 H5-09，本批次在 M-ME-001 标「coming soon」，不伪造入口。

## 页面清单

| Page ID | Route | 读/写 | DTO/API | 实现形态 |
|---|---|---|---|---|
| M-ME-001 | `/me` | 只读 | User（user_me）+ KycCase（kyc_me）+ SecurityProfile（security_profile） | 完整（用户摘要 + KYC/MFA 状态 + 7 功能入口；Support 标 coming soon） |
| M-SEC-001 | `/security` | 只读 | SecurityProfile + SessionDevice[]（sessions） | 完整（安全总览 + MFA/设备/密码分组入口；登录记录 Restricted） |
| M-SEC-002 | `/security/sessions` | 只读 | SecurityProfile + SessionDevice[]（sessions） | 完整（MFA enrolled 只读 + 当前/其余会话脱敏列表；绑定/撤销 disabled） |
| M-SETTINGS-001 | `/settings` | 只读 + 本地语言 | 无（偏好未冻结）；语言本地 setLanguage | 完整（语言切换本地生效 + 偏好 Restricted + 退出登录 best-effort） |

## M-ME-001 我的（入口页，只读摘要）

| 字段 | 值 |
|---|---|
| Page ID | M-ME-001 |
| Route | `/me`（meta.pageId=M-ME-001, auth=true） |
| DTO/API | `GET /api/v1/me`（User）+ `GET /api/v1/me/kyc`（KycCase）+ `GET /api/v1/me/security-profile`（SecurityProfile） |
| Store | profile（user / displayName / status / globalPLevel）+ kyc（status）+ security（mfaEnrolled） |
| 五态 | Loading / Error / Default（用户摘要）；KYC/MFA 状态以 common.unknown 兜底 |
| 写状态 | 无；纯入口页，不做资产/Power 权威推导 |
| 权限 | auth=true；脱敏展示，不把资产/数字做成第一视觉焦点 |
| I18N | page.m_me_001.* + user.status.* + user.global_p_level + common.unknown + common.comingSoon |
| Tests | security-view.spec.ts（me-profile / me-entry-*） |
| Known Deviation | S03-P02-SEC-LOGIN-AUDIT（不在此页展开审计）；Support 入口 H5-09 coming soon |

关键语义：Me 是导航枢纽，KYC/MFA 状态只读摘要；任何「下一步能做什么」以入口跳转表达，不在此页聚合复杂业务结论。

## M-SEC-001 安全中心（只读总览）

| 字段 | 值 |
|---|---|
| Page ID | M-SEC-001 |
| Route | `/security`（meta.pageId=M-SEC-001, auth=true） |
| DTO/API | `GET /api/v1/me/security-profile`（SecurityProfile）+ `GET /api/v1/me/sessions`（SessionDevice[]） |
| Store | security（mfaEnrolled / suspiciousCount / sessions） |
| 五态 | Loading / Error / Default |
| 写状态 | 登录记录 Restricted（source 未裁决）；MFA/设备/密码仅入口跳转 |
| 权限 | auth=true；克制展示，不靠大面积红色制造恐慌 |
| I18N | page.m_sec_001.* |
| Known Deviation | S03-P02-SEC-LOGIN-AUDIT（登录记录 restricted） |

## M-SEC-002 MFA / 设备 / 会话（只读管理，写 fail-closed）

| 字段 | 值 |
|---|---|
| Page ID | M-SEC-002 |
| Route | `/security/sessions`（meta.pageId=M-SEC-002, auth=true） |
| DTO/API | `GET /api/v1/me/security-profile`（MFA methods）+ `GET /api/v1/me/sessions`（SessionDevice[]） |
| Store | security（currentSession / otherSessions） |
| 五态 | Loading / Error / Empty / Default |
| 写状态 | MFA 绑定 disabled（fail-closed）；会话撤销 disabled（fail-closed） |
| 权限 | auth=true；设备指纹脱敏（只展示 os·browser·region，不落完整 IP/指纹） |
| I18N | page.m_sec_002.* |
| Known Deviation | S03-P02-SEC-MFA-WRITE（绑定禁用）；S03-P02-SEC-REVOKE（撤销禁用） |

## M-SETTINGS-001 设置（本地语言 + 偏好受限 + 退出）

| 字段 | 值 |
|---|---|
| Page ID | M-SETTINGS-001 |
| Route | `/settings`（meta.pageId=M-SETTINGS-001, auth=true） |
| DTO/API | 无写端点；`POST /api/v1/auth/logout`（auth_logout，best-effort） |
| Store | session（clear）+ i18n（setLanguage / getCurrentLanguage / getSupportedLanguages） |
| 五态 | 偏好区 Restricted；其余 Default |
| 写状态 | 偏好（时区/通知）Restricted（GET/PUT /me/preferences 未冻结）；语言本地切换生效（不改变业务数值/规则语义） |
| 权限 | auth=true；退出 best-effort：服务端注销失败仍本地清 token，不得把用户锁在会话里 |
| I18N | page.m_settings_001.* + common.loading |
| Known Deviation | Preferences API 未冻结（受限）；S03-P02-AUTH-REFRESH-TOKEN（refresh_token 缺失，本次未受影响，auth/refresh 仍待后端） |

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-SEC-LOGIN-AUDIT | LoginAudit source 未裁决 → 后端 UNAVAILABLE。 | M-SEC-001 登录记录 Restricted；loginAudit 方法仅定义不接线 |
| S03-P02-SEC-MFA-WRITE | MFA enrollment setup/confirm/disable 写操作 fail-closed。 | M-SEC-002 绑定按钮 disabled，不提供写方法 |
| S03-P02-SEC-REVOKE | session revoke 写操作 fail-closed。 | M-SEC-002 撤销按钮 disabled，不提供写方法 |
| S03-P02-AUTH-REFRESH-TOKEN | AuthTokenResponse 不含 refresh_token，`auth/refresh` 依赖它。 | 本批次不触发 refresh；登记待后端补 DTO |
| S03-P02-NOTICE-PATH | 03 引 `/me/notices` + `/me/notices/{id}/read`，OpenAPI 无。 | 不新增；沿用 H5-02 Notice 现有口径 |
| Preferences API | `GET/PUT /api/v1/me/preferences` 未冻结。 | M-SETTINGS-001 偏好区 Restricted |

## 状态映射（05 canonical 展示映射，不新增领域状态）

| canonical | I18N key |
|---|---|
| User.status（active/restricted/suspended/closed） | user.status.* |
| KycCase.status（沿用 H5-02） | kyc.status.* |
| MFA enrolled methods（totp/email/sms，string[] 透传） | 原样展示（锁定术语 MFA） |
| SessionDevice.revocable（bool） | page.m_sec_002.revocable / not_revocable |

## 合规约束

- 安全线克制设计：不靠大面积红色制造恐慌；可疑标志用 `--warning-600` 单点提示。
- 设备指纹/完整 IP 一律不落前端展示，只给 os·browser·region 脱敏标签。
- `MFA`、`KYC`、`APT`、`Power`、`OTC` 锁定不翻译；动作词按 locale 本地化。
- 退出登录 best-effort：服务端失败不阻断本地清 token，避免把用户锁死在会话中。

## 验证

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 21 files / 140 tests pass（新增 security.spec.ts 8 + security-view.spec.ts 8，合计 +16） |
| `npm run build` | ✅ vite 257 modules（+13 vs H5-07），含 m-me-001 / m-sec-001 / m-sec-002 / m-settings-001 / security chunk |
| i18n key parity（7 语言） | ✅ 同构（user.status.* / user.global_p_level / page.m_me_001.* / page.m_sec_001.* / page.m_sec_002.* / page.m_settings_001.* / common.unknown） |
| secret 扫描 | ✅ 0 匹配（新文件仅表单状态 `password = ref('')`，无硬编码密钥） |
| 三尺寸截图（375/390/430） | 待补（缺口，S03-P02-VISUAL-BASELINE 逐页固定文件） |
