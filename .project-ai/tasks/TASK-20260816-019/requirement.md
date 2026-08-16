# S03-P02 · H5 页面实现顺序 — 任务需求

> 阶段：STAGE-03 · H5 与 Admin 增量升级
> 前置：S03-P01 H5 基础设施 COMPLETE；OpenAPI 2.0（S02-P01/P02 APPROVED）已冻结
> 权威：`07_DEVELOPMENT_AND_ACCEPTANCE.md` §S03-P02

## 1. 背景

S03-P01 已建立 H5 基础设施（V2 http 六请求头、Pinia stores、router meta、design tokens、7 语言 i18n、五态组件、测试骨架）。S03-P02 按 07 §S03-P02 的 11 个批次逐批把 40 个 P0 Page ID 迁移到 V2 契约，采用 strangler fig：新页面绑定 OpenAPI DTO + tokens + 7 语言 + 五态，旧 view 在迁移完成后删除 legacy shim。

## 2. 批次清单（07 §S03-P02 冻结）

| 批次 | Page ID | 本包覆盖 |
|---|---|---|
| H5-01 Auth | M-AUTH-001..005 | ✅ 本包 |
| H5-02 KYC/Notice | M-KYC-001..003, M-NOTICE-001 | 后续 |
| H5-03 Home | M-HOME-001 | 后续 |
| H5-04 Robot | M-ROBOT-001..007 | 后续 |
| H5-05 Prediction | M-PREDICT-001..006 | 后续 |
| H5-06 Asset/Power | M-ASSET-001..003, M-POWER-001 | 后续 |
| H5-07 OTC | M-OTC-001..006 | 后续 |
| H5-08 Me/Security | M-ME-001, M-SEC-001..002, M-SETTINGS-001 | 后续 |
| H5-09 Support | M-SUPPORT-001..003 | 后续 |
| H5-10 P1 | M-AI-001, M-GROWTH-001, M-PREDICT-FREE-001 | 后续（合同 Gate 后） |
| H5-11 Future | M-MIGRATION-001 | 后续（CLOSED） |

**本包（首增量）范围 = H5-01 Auth 批次**：M-AUTH-001..005 五页。

## 3. 每页固定产物（07 §S03-P02）

`src/views/auth/<page-id-lower>/index.vue` + route meta（pageId/auth）+ domain API/DTO + Pinia selector/action + i18n keys（7 语言）+ `tests/unit/*.spec.ts`。三尺寸（375/390/430）视觉截图归 `tests/visual/`（本包暂不产出，属已知缺口）。

每页固定步骤：①抄录 03 目标/数据/动作/状态/限制；②绑定 OpenAPI DTO；③五态（Default/Loading/Empty/Error/Restricted）；④写操作状态（Submitting/Success/Failed/Unknown）；⑤ allowed_actions（按钮不由本地金额/等级推导）；⑥7 语言/tokens；⑦unit 测试；⑧PAGE_IMPLEMENTATION_RECORD。

## 4. 非目标 / 停止条件

- 不重写旧 22 view（旧 login/Register 等保留 legacy，待全量迁移后删除）。
- 不产出三尺寸视觉截图（S03-P01-VISUAL-BASELINE 已登记）。
- 某页 API/DTO/Feature Gate 未冻结 → 该页 Contract Gap / Closed，不阻塞无依赖批次（一开到底）。

## 5. 验收（H5-01）

5 个 P0 Page ID 均有 route、五态、API/DTO、7 语言、unit 测试；写操作（login/register/otp/mfa/recovery/reset）可经 request_id 查询终态；无生产 secret；type-check/build/test 全绿。
