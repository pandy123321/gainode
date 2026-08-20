# STAGE-03 前端覆盖矩阵（Developer 侧草稿，S03-P04 Gate）

> 起草：DEVELOPMENT-01
> 日期：2026-08-21
> 依据：07 §8 S03-P04、04/03 Page ID、前端 route/view/i18n/API。

## H5

```text
H5 views = 69（M-* 页面）
路由    = src/router/index.ts + meta.ts
i18n    = src/i18n/locales/{zh-CN,en-US,ja-JP,ko-KR,th-TH,de-DE,fr-FR}（7 语言齐全）
测试    = 23 文件 147 断言全过（auth/kyc/home/robot/prediction/asset/otc/me/support 等批次）
type-check = vue-tsc --build PASS
build      = vite build PASS
E2E        = playwright 脚本存在，NOT_RUN（需浏览器）
```

## Admin

```text
Admin views = 98
权威 Page ID = 33（admin-registry.ts AUTHORITATIVE_PAGE_IDS）
DEFERRED     = 7（A-AI-* / A-DATA-*）
API 对接      = admin-v2.ts 对接后端 /api/v1/admin/* 24 只读接口 + pageData.ts 19 权威页真实数据 loader
type-check = vue-tsc --noEmit PASS
build      = vite build PASS
unit       = 无测试脚本（NOT_RUN，不得写 PASS）
```

## API coverage

| 前端 | 已对接后端接口 | 未对接 |
|---|---|---|
| H5 | auth/kyc/eligibility/asset/robot/prediction/otc 只读（S03-P02 已接） | 写路径（后端 fail-closed） |
| Admin | /api/v1/admin/* 24 只读接口（用户/KYC/OTC/Robot/Reward/工单/Ledger/Risk/Approval/Config/Prediction Market+Order+Result+Refund+Correction/Power/审计/工作台总览+待办） | 池子对账/策略/报表/Referral（无冻结 service）；写路径（admin 角色映射未冻结） |

## 未运行/待补

- H5/Admin E2E、视觉回归、accessibility、7 语言 key parity 全量比对：NOT_RUN（需浏览器+运行环境）
- Admin 单元测试：无脚本
- 前端本地金额/资格/Power/Reward 推导 + float 扫描：NOT_RUN
