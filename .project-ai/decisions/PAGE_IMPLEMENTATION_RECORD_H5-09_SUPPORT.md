# PAGE_IMPLEMENTATION_RECORD — H5-09 Support/工单（M-SUPPORT-001..003）

> 批次：H5-09 Support/工单 ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-SUPPORT-001..003 ｜ OpenAPI governance.yaml#Ticket / TicketMessage / TicketAttachment（S02-P07 APPROVED，6 态冻结）
> 治理核心：**工单全链路 fail-closed**。Ticket 6 态 schema 已在 S02-P07 冻结，但 C 端无任何暴露路径
> （`GET /api/v1/help`、`GET/POST /api/v1/me/tickets`、`GET /api/v1/me/tickets/{id}`、`POST /api/v1/me/tickets/{id}/messages`、
> `POST /api/v1/uploads` 均不在 OpenAPI），前端**不提供任何读写方法**，页面以 Restricted/Empty + disabled CTA 占位。
> 帮助中心壳保留结构（先帮用户自助），但 FAQ/工单列表/创建入口全部 fail-closed，不伪造。

## 页面清单

| Page ID | Route | 读/写 | DTO/API | 实现形态 |
|---|---|---|---|---|
| M-SUPPORT-001 | `/support` | — | 无（`GET /help` + `GET /me/tickets` 均无端点） | 壳（创建 CTA disabled + FAQ Restricted + 工单列表 Restricted） |
| M-SUPPORT-002 | `/support/new` | — | 无（`POST /me/tickets` + `POST /uploads` 无端点） | Restricted 占位 |
| M-SUPPORT-003 | `/support/:id` | — | 无（`GET /me/tickets/{id}` + messages 无端点） | Restricted 占位 |

## M-SUPPORT-001 帮助中心 / 工单列表（壳，全 fail-closed）

| 字段 | 值 |
|---|---|
| Page ID | M-SUPPORT-001 |
| Route | `/support`（meta.pageId=M-SUPPORT-001, auth=true） |
| DTO/API | 无（`GET /api/v1/help` FAQ config、`GET /api/v1/me/tickets` 均不在 OpenAPI） |
| Store | 无（静态） |
| 五态 | FAQ Restricted；工单列表 Restricted |
| 写状态 | 创建工单 CTA disabled（`POST /me/tickets` fail-closed） |
| 权限 | auth=true；不做复杂客服工作台样式 |
| I18N | page.m_support_001.* |
| Known Deviation | S03-P02-SUPPORT-PATH / -FAQ（FAQ + 工单列表无端点） |

关键语义：先帮用户自助，再进入人工工单；因 FAQ 与工单列表均无数据源，本页保留帮助中心骨架并如实标注「暂未开放」，不伪造 FAQ 条目或工单记录。

## M-SUPPORT-002 创建工单 / 申诉（Restricted）

| 字段 | 值 |
|---|---|
| Page ID | M-SUPPORT-002 |
| Route | `/support/new`（meta.pageId=M-SUPPORT-002, auth=true） |
| DTO/API | 无（`POST /api/v1/me/tickets`、`POST /api/v1/uploads` 无端点） |
| 形态 | FiveStateContainer state=restricted + 返回按钮 |
| Known Deviation | S03-P02-SUPPORT-PATH / -UPLOAD-POLICY（写操作未冻结 → 不开放真实提交） |

## M-SUPPORT-003 工单详情（Restricted）

| 字段 | 值 |
|---|---|
| Page ID | M-SUPPORT-003 |
| Route | `/support/:id`（meta.pageId=M-SUPPORT-003, auth=true） |
| DTO/API | 无（`GET /api/v1/me/tickets/{id}`、`POST .../messages` 无端点） |
| 形态 | FiveStateContainer state=restricted + 返回按钮 |
| Known Deviation | S03-P02-SUPPORT-PATH（详情/回复无端点） |

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-SUPPORT-PATH | `GET /api/v1/help`、`GET/POST /api/v1/me/tickets`、`GET /api/v1/me/tickets/{id}`、`POST /api/v1/me/tickets/{id}/messages` 均不在 OpenAPI（Ticket 6 态 schema 冻结但无 C 端路径）。 | M-SUPPORT-001..003 全部 Restricted/disabled，不提供读写方法 |
| S03-P02-SUPPORT-FAQ | FAQ config（`GET /api/v1/help`）无端点。 | M-SUPPORT-001 FAQ Restricted |
| S03-P02-UPLOAD-POLICY | M-SUPPORT-002 引 `POST /api/v1/uploads`（upload policy），OpenAPI 无。 | 不提供附件上传；关联 S03-P02-UPLOAD-REF |

## 状态映射（05 canonical 展示映射，不新增领域状态）

| canonical | I18N key |
|---|---|
| Ticket.status（submitted/in_progress/waiting_user/under_review/resolved/closed） | 本批次未展示（无端点）；冻结后按 05 映射补齐 |

## 合规约束

- 工单绑定具体对象（order/ledger/robot）为最佳实践，本批次无写能力故不实现表单。
- `APT`、`Power`、`OTC`、`KYC`、`MFA` 锁定不翻译；动作词按 locale 本地化。
- 帮助中心不做复杂客服工作台样式；FAQ/工单列表 fail-closed 不伪造数据。

## 验证

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 22 files / 143 tests pass（新增 support-view.spec.ts 3，合计 +3） |
| `npm run build` | ✅ vite 266 modules（+9 vs H5-08），含 m-support-001 / m-support-002 / m-support-003 chunk |
| i18n key parity（7 语言） | ✅ 同构（page.m_support_00[1-3].*） |
| secret 扫描 | ✅ 0 匹配 |
| 三尺寸截图（375/390/430） | 待补（缺口，S03-P02-VISUAL-BASELINE 逐页固定文件） |
