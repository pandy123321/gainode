# PAGE_IMPLEMENTATION_RECORD — H5-03 Home（M-HOME-001）

> 批次：H5-03 Home ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-HOME-001 ｜ OpenAPI robot.yaml#RobotSummary / prediction.yaml#PredictionMarket / ledger.yaml#AssetBalance / eligibility.yaml / governance.yaml#Notice

## M-HOME-001 首页

| 字段 | 值 |
|---|---|
| Page ID | M-HOME-001 |
| Route | `/`（meta.pageId=M-HOME-001） |
| Figma node | 03 原型「首页」；Gainode2.0 node 待对齐 |
| DTO/API | 局部聚合：`GET /ai/users/me/summary`（RobotSummary）+ `GET /markets`（PredictionMarket[]）+ `GET /me/asset`（AssetBalance）+ `GET /me/eligibility`（EligibilityResponse）+ `GET /me/notices`（Notice[]） |
| Store | robot / prediction（featuredMarkets）/ asset / entitlement / notice |
| Components/tokens | HomeHeader + Hero + NoticeTicker + 卡片 + BottomNav + FiveStateContainer；var(--brand-blue-600)，无金色/黑色大屏 |
| 五态 | 每卡局部 Loading/Error（单卡失败不拖垮整页）；Hero Admission Restricted |
| 写状态 | 无写操作（首页 CTA 只导航，不在 Home 内完成高风险写操作） |
| 权限 | BottomNav 占位路由 auth=true；leaderboard 无数据不伪造；禁止资产/财富/Reward 金额榜 |
| I18N | page.m_home_001.* + nav.* + robot.status.* + common.comingSoon |
| 截图 | 375/390/430 待补（缺口） |
| Tests | tests/unit/home.spec.ts + home-view.spec.ts |
| Known Deviation | S03-P02-HOME-SUMMARY / -LEADERBOARD / -ROBOT-ME-PATH / -CLAIMABLE / -BANNER |

## 布局与数据映射（03 §M-HOME-001 固定顺序）

| 03 布局 | 本实现 | 数据源 |
|---|---|---|
| Header | HomeHeader（title + 通知铃铛未读角标） | notice.unreadCount |
| Hero/今日状态 | 准入受限 / Robot 状态 + 主 CTA | eligibility + robot |
| Banner | **不渲染**（无 banner 端点，见缺口） | — |
| NoticeTicker | 单行（latest notice title_key / type） | notice |
| DailyClaimCard/Robot 回访 | Robot 卡（level/status/standard_capacity） | robot |
| 热门竞猜 | FeaturedPredictionList（仅 open/closing，截 5 条） | prediction |
| APT/Power/OTC 快捷入口 | 3 张导航卡 → `/me`（占位） | — |
| AI 数据摘要 | APT 可用余额（effective_available，权威只读） | asset |
| UpgradeLeaderboard | Empty/Closed（不伪造数据） | 无端点 |
| BottomNav | Home/Robot/Prediction/Me（后三为占位路由） | — |

## 主 CTA 决策（不在 Home 内写操作）

1. 准入受限（global_p 与 ai 均不允许）→「进入 KYC」→ `/kyc`
2. 有 Robot 且 `inactive` →「启动 Robot」→ `/robot`
3. 其余 →「查看 Robot」→ `/robot`

「今日可领取 Reward」因无冻结端点（RobotSummary 不含 claimable），**不展示可领取、不伪造收益刺激**（见 S03-P02-HOME-CLAIMABLE）。

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-HOME-SUMMARY | 03 引单一聚合 `GET /api/v1/me/home-summary`，OpenAPI 无此路径。 | 前端按「局部 freshness」聚合 5 个已冻结端点；聚合端点冻结后改为一发请求，不新增第二套 DTO |
| S03-P02-HOME-LEADERBOARD | 03 引 UpgradeLeaderboard，OpenAPI 无 leaderboard 路径。 | 榜单卡显示 Empty/Closed，不伪造排名数据 |
| S03-P02-HOME-ROBOT-ME-PATH | `GET /ai/users/{id}/summary` 需用户 id，C 端自取摘要无 `me` 变体。 | 以 `/ai/users/me/summary` 占位，后端冻结 `me` 语义后无需改调用层 |
| S03-P02-HOME-CLAIMABLE | Hero「今日可领取 Reward」无冻结端点（RobotSummary 不含 claimable）。 | 主 CTA 退化到「启动/查看 Robot」，不展示可领取 |
| S03-P02-HOME-BANNER | 03 布局含 Banner，但「读取数据」列表无 banner 源，OpenAPI 无 banner 端点。 | 首页不渲染 Banner（未来营销特性另立合同） |

## 底部导航占位路由（本批次新增）

| Route | Page ID | 处置 |
|---|---|---|
| `/robot` | M-ROBOT-001 | ComingSoonView 占位，H5-04 替换 |
| `/prediction` | M-PREDICT-001 | ComingSoonView 占位，H5-05 替换 |
| `/me` | M-ME-001 | ComingSoonView 占位，H5-08 替换 |

## 验证

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 11 files / 67 tests pass（新增 home.spec.ts 9 + home-view.spec.ts 5） |
| `npm run build` | ✅ vite 171 modules，含 m-home-001 / BottomNav / ComingSoonView chunk |
| i18n key parity（7 语言） | ✅ 同构（i18n.spec.ts key parity 断言覆盖） |
| secret 扫描 | ✅ 0 匹配 |
