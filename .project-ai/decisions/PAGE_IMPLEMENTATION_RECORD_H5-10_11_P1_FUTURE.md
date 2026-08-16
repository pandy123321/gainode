# PAGE_IMPLEMENTATION_RECORD — H5-10 P1 / H5-11 Future（M-AI-001 / M-GROWTH-001 / M-PREDICT-FREE-001 / M-MIGRATION-001）

> 批次：H5-10（P1）+ H5-11（Future） ｜ 状态：IMPLEMENTED（Closed/Restricted 占位，待 Quality 审）
> 基线：03 §M-AI-001（P1）/ M-GROWTH-001（P1）/ M-PREDICT-FREE-001（P1/Sandbox）/ M-MIGRATION-001（Future/CLOSED）
> 治理核心：**P1/Future 一律不越权开放**。四页对应端点均不在 OpenAPI（`GET /ai/signals` 属内部 AI 经济引擎不对 C 端暴露；
> `GET /me/referrals`、`/api/v1/free-predictions/*`、`GET /apt/migration-eligibility` 均无路径），前端**不提供任何读写方法**，
> 页面以 Restricted/Closed 占位，直接 URL 不 404、不伪造数据、不开放执行控件。四页均不挂任何一级/二级导航入口。

## 页面清单

| Page ID | Route | 读/写 | DTO/API | 实现形态 |
|---|---|---|---|---|
| M-AI-001 | `/ai/signals` | — | 无（`GET /ai/signals` 内部引擎，不对 C 端） | Restricted 占位 |
| M-GROWTH-001 | `/growth` | — | 无（`GET /me/referrals` P1 无路径） | Restricted 占位 |
| M-PREDICT-FREE-001 | `/prediction/free` | — | 无（`/free-predictions/*` Sandbox 无路径） | Restricted 占位 |
| M-MIGRATION-001 | `/migration` | — | 无（`GET /apt/migration-eligibility` Future 无路径） | Closed 占位 |

## M-AI-001 AI 数据 / Signal 详情（P1，Restricted）

| 字段 | 值 |
|---|---|
| Page ID | M-AI-001 |
| Route | `/ai/signals`（meta.pageId=M-AI-001, auth=true；无导航入口） |
| DTO/API | 无（Signal/Arbitrage 改造为内部 AI 经济引擎，不对 C 端暴露） |
| 形态 | FiveStateContainer state=restricted + 返回首页 |
| Known Deviation | H5-10 P1 Gate 未开 → Closed/Restricted，不伪造 API |

## M-GROWTH-001 Referral / Team（P1，Restricted）

| 字段 | 值 |
|---|---|
| Page ID | M-GROWTH-001 |
| Route | `/growth`（meta.pageId=M-GROWTH-001, auth=true；无导航入口） |
| DTO/API | 无（`GET /me/referrals` 无 OpenAPI 路径；Affiliate/Agent P1 合同 CONTRACT_GAP） |
| 形态 | FiveStateContainer state=restricted + 返回我的 |
| Known Deviation | H5-10 P1 Gate 未开 → Restricted；不放一级导航 |

## M-PREDICT-FREE-001 免费 YES/NO（P1/Sandbox，Restricted）

| 字段 | 值 |
|---|---|
| Page ID | M-PREDICT-FREE-001 |
| Route | `/prediction/free`（meta.pageId=M-PREDICT-FREE-001, auth=true；无导航入口） |
| DTO/API | 无（`/api/v1/free-predictions/*` 无 OpenAPI 路径） |
| 形态 | FiveStateContainer state=restricted + 返回竞猜 |
| Known Deviation | H5-10 P1/Sandbox 未开 → Restricted；不得与真实 APT/收入混淆 |

## M-MIGRATION-001 APT-I → APT-C Migration（Future/CLOSED）

| 字段 | 值 |
|---|---|
| Page ID | M-MIGRATION-001 |
| Route | `/migration`（meta.pageId=M-MIGRATION-001, auth=true；默认隐藏） |
| DTO/API | 无（`GET /apt/migration-eligibility`、`POST /apt/migration-requests` Future 无路径） |
| 形态 | FiveStateContainer state=restricted（closed 文案）+ 返回资产 |
| Known Deviation | H5-11 Future/CLOSED；生产入口必须关闭，不放出真实入口 |

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-AI-SIGNAL | `GET /api/v1/ai/signals` 属内部 AI 经济引擎，不对 C 端暴露（SignalSummary 无 C 端路径）。 | M-AI-001 Restricted，不提供读方法 |
| S03-P02-GROWTH-REFERRAL | `GET /api/v1/me/referrals` 无 OpenAPI 路径（Affiliate/Agent CONTRACT_GAP GAP-001/002）。 | M-GROWTH-001 Restricted |
| S03-P02-FREE-PREDICTION | `/api/v1/free-predictions/*` 无 OpenAPI 路径（P1/Sandbox）。 | M-PREDICT-FREE-001 Restricted |
| S03-P02-MIGRATION | `GET /apt/migration-eligibility` + `POST /apt/migration-requests` Future 无路径。 | M-MIGRATION-001 Closed |

## 合规约束

- P1/Future 不越权开放：无执行控件、无读写方法、无 mock 开关放出真实入口。
- M-AI-001 不得呈现保证性箭头/暴涨曲线/「必胜」视觉（本批次仅受限占位，无此风险）。
- M-GROWTH-001 不得做层级金字塔/拉人头树形收益图（本批次仅受限占位）。
- `APT`、`APT-I`、`APT-C`、`AI`、`Signal` 锁定不翻译；动作词按 locale 本地化。

## 验证

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 23 files / 147 tests pass（新增 p1-future-view.spec.ts 4，合计 +4） |
| `npm run build` | ✅ vite 278 modules（+12 vs H5-09），含 m-ai-001 / m-growth-001 / m-predict-free-001 / m-migration-001 chunk |
| i18n key parity（7 语言） | ✅ 同构（page.m_ai_001.* / page.m_growth_001.* / page.m_predict_free_001.* / page.m_migration_001.*） |
| secret 扫描 | ✅ 0 匹配 |
| 三尺寸截图（375/390/430） | 待补（缺口，S03-P02-VISUAL-BASELINE 逐页固定文件） |

> 至此 S03-P02 H5 页面实现顺序全部批次（H5-01..H5-11）落地：P0 九批完整实现 + P1/Future 四页 Closed/Restricted 占位。
> 下一阶段：S03-P03 Admin 基础设施与逐页实现（前置 Admin V2 基线 `build:check` 类型错误收口）。
