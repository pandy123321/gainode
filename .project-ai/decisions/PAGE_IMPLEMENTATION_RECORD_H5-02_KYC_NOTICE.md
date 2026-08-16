# PAGE_IMPLEMENTATION_RECORD — H5-02 KYC/Notice（M-KYC-001..003 / M-NOTICE-001）

> 批次：H5-02 KYC/Notice ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-KYC-001..003 / §M-NOTICE-001 ｜ OpenAPI kyc.yaml / eligibility.yaml / governance.yaml#Notice

## M-KYC-001 KYC 与功能准入概览

| 字段 | 值 |
|---|---|
| Page ID | M-KYC-001 |
| Route | `/kyc`（meta.pageId=M-KYC-001，auth=true） |
| Figma node | 03 原型「状态总览页」；Gainode2.0 node 待对齐 |
| DTO/API | `GET /me/kyc`（KycCase）+ `GET /me/eligibility`（EligibilityResponse） |
| Store | kyc（KycCase）+ entitlement（global_p/ai/prediction） |
| Components/tokens | FiveStateContainer + .app-page/.card；var(--brand-blue-600) |
| 五态 | Default / Loading / Error（空/受限并入业务状态） |
| 写状态 | 无写操作（只读聚合；CTA 仅导航） |
| 权限 | auth 路由守卫；allowed 由服务端下发，前端不推导 |
| I18N | page.m_kyc_001.* + kyc.status.* + kyc.feature.* |
| 截图 | 375/390/430 待补（缺口） |
| Tests | tests/unit/kyc-views.spec.ts |
| Known Deviation | S03-P02-KYC-FORM-META |

## M-KYC-002 KYC 资料提交 / 补件

| 字段 | 值 |
|---|---|
| Page ID | M-KYC-002 |
| Route | `/kyc/form` |
| Figma node | 03 原型「分步流程页」；待对齐 |
| DTO/API | `POST /me/kyc/submit`（KycSubmitRequest）+ uploads presigned |
| Store | 本地 state（attachmentRefs/consent） |
| Components/tokens | 上传卡 96px + 输入 48px + CTA 48px |
| 五态 | Default / Loading（uploading/submitting）/ Error |
| 写状态 | Submitting / Success（→/kyc/status）/ Failed / Unknown（http.ts） |
| 权限 | 仅本人；敏感字段不写日志/埋点 |
| I18N | page.m_kyc_002.*；consent_label 标 sensitive |
| 截图 | 375/390/430 待补 |
| Tests | tests/unit/kyc-views.spec.ts（consent/attachment 拦截） |
| Known Deviation | S03-P02-KYC-FORM-META / S03-P02-UPLOAD-REF |

## M-KYC-003 KYC 状态 / 结果

| 字段 | 值 |
|---|---|
| Page ID | M-KYC-003 |
| Route | `/kyc/status` |
| Figma node | 03 原型「结果/时间线页」；待对齐 |
| DTO/API | `GET /me/kyc`（KycCase，单案件复用，见 CASE-DETAIL 缺口） |
| Store | kyc（KycCase） |
| Components/tokens | 状态卡 48px 图标 + 时间线行 56px+ |
| 五态 | Default / Loading / Error |
| 写状态 | 无写操作 |
| 权限 | needs_info → 补件 CTA；rejected 只显示安全原因 |
| I18N | page.m_kyc_003.* + kyc.status.* |
| 截图 | 375/390/430 待补 |
| Tests | tests/unit/kyc.spec.ts（label 映射） |
| Known Deviation | S03-P02-KYC-CASE-DETAIL（timeline 字段受限，仅 submitted_at/reviewed_at） |

## M-NOTICE-001 消息中心

| 字段 | 值 |
|---|---|
| Page ID | M-NOTICE-001 |
| Route | `/notices` |
| Figma node | 03 原型「消息中心」；待对齐 |
| DTO/API | `GET /me/notices` + `POST /me/notices/{id}/read`（NOTICE-PATH 缺口） |
| Store | notice（items/unreadCount） |
| Components/tokens | SegmentedTabs（未读/全部）+ NoticeRow 64px + UnreadDot |
| 五态 | Default / Loading / Empty / Error |
| 写状态 | markRead（乐观更新，失败不阻断） |
| 权限 | 仅本人通知；正文安全映射（title_key/body_key 经 i18n） |
| I18N | page.m_notice_001.* + notice.type.* |
| 截图 | 375/390/430 待补 |
| Tests | tests/unit/notice.spec.ts + kyc-views.spec.ts |
| Known Deviation | S03-P02-NOTICE-PATH / -DEEPLINK / -CREATED-AT |

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-NOTICE-PATH | 03 引 `/me/notices` + `/me/notices/{id}/read`，OpenAPI 无（仅 governance.yaml#Notice schema 冻结）。 | best-effort 按 03 绑定，路径冻结后无需改调用层 |
| S03-P02-NOTICE-DEEPLINK | object_type → route 深链映射未冻结。 | 点击仅标记已读，不深链 |
| S03-P02-NOTICE-CREATED-AT | Notice schema 无 created_at，列表时间不可展示。 | 暂不展示时间 |
| S03-P02-KYC-FORM-META | kyc_level / consent_version / required_fields / file_rules 无下发端点。 | 占位常量 kyc_level=standard、consent_version=2026-08-01 |
| S03-P02-KYC-CASE-DETAIL | 03 引 `GET /me/kyc/{case_id}`，OpenAPI 仅 `GET /me/kyc`。 | 单案件模型复用 `/me/kyc` |
| S03-P02-UPLOAD-REF | `attachment_refs` 后端签发引用端点未冻结。 | 沿用 S03-P01 uploads presigned（object_url 作 ref，best-effort） |
