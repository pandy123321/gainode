# PAGE_IMPLEMENTATION_RECORD — H5-07 OTC（M-OTC-001..006）

> 批次：H5-07 OTC ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-OTC-001..006 ｜ OpenAPI otc.yaml#OtcOrder / OtcTrade（S02-P06）+ apt_otc.yaml 路径
> 治理核心：OTC = Controlled Matching。三个写操作（`otc_quote` / `otc_order_create` / `otc_order_cancel`）后端 fail-closed
> （fee/limit/库存/Power 规则 TBC → 503 DEPENDENCY_UNAVAILABLE），前端**不提供写方法**，相关按钮一律 disabled 或 Restricted 占位。
> 只读端点（order-book / order-detail / trades / user-orders）保持绑定；资格（eligibility）/容量（capacity）无 C 端路径 → 不展示结论，不伪造。

## 页面清单

| Page ID | Route | 读/写 | DTO/API | 实现形态 |
|---|---|---|---|---|
| M-OTC-001 | `/otc` | 只读 | OtcOrder[]（otc_order_book）+ PowerPosition（me_power_position） | 完整（挂买/挂卖 disabled + Power 摘要 + 订单簿只读 + 流动性/参考价文案；eligibility/capacity 无端点不展示） |
| M-OTC-002 | `/otc/new` | — | 无（otc_quote 503） | Restricted 占位 |
| M-OTC-003 | `/otc/confirm` | — | 无（otc_order_create 503） | Restricted 占位 |
| M-OTC-004 | `/otc/result/:id` | — | 无（提交未开放） | Restricted 占位 |
| M-OTC-005 | `/otc/my` | 只读 | OtcOrder[]（otc_user_orders，`me` 占位） | 完整（订单列表：方向/状态/数量/成交进度） |
| M-OTC-006 | `/otc/:id` | 只读 | OtcOrder（otc_order_detail）+ OtcTrade[]（otc_trades） | 完整（订单事实 + Sell Power 影响 + 成交记录；Power Flow Timeline 空态；取消 disabled） |

## M-OTC-001 OTC 市场（只读实现，部分受限）

| 字段 | 值 |
|---|---|
| Page ID | M-OTC-001 |
| Route | `/otc`（meta.pageId=M-OTC-001, auth=true） |
| DTO/API | `GET /api/v1/otc/order-book`（OtcOrder[]）+ `GET /api/v1/me/power`（PowerPosition 摘要） |
| Store | otc（orderBook / fetchOrderBook）+ power（position / fetch） |
| 五态 | Loading / Error / Empty（订单簿）/ Default |
| 写状态 | 挂买/挂卖 disabled（fail-closed，不开放真实挂单） |
| 权限 | auth=true；OTC = Controlled Matching，禁止 K-Line / Order Book Trading Terminal / 红绿博彩感 |
| I18N | page.m_otc_001.* + otc.side.* + otc.status.* |
| Tests | tests/unit/otc.spec.ts + otc-view.spec.ts |
| Known Deviation | S03-P02-OTC-ELIGIBILITY / -CAPACITY（不展示资格/容量结论）；S03-P02-OTC-QUOTE / -CREATE（挂单禁用） |

关键语义：参考价必须带来源/时点且**不是官方兑付价**；本页只展示订单簿与 Power 摘要，不把订单簿当「官方价格」。

## M-OTC-002/003/004 挂单输入/确认/结果（Restricted）

| 字段 | 值 |
|---|---|
| Page ID | M-OTC-002 / M-OTC-003 / M-OTC-004 |
| Route | `/otc/new` / `/otc/confirm` / `/otc/result/:id`（auth=true） |
| DTO/API | 无（`POST /otc/quotes`、`POST /otc/orders` 均 503 fail-closed） |
| 形态 | FiveStateContainer state=restricted + 返回按钮 |
| Known Deviation | S03-P02-OTC-QUOTE / S03-P02-OTC-CREATE（写操作未冻结 → 不开放真实提交） |

M-OTC-004（提交结果）依赖一次真实 order_create；因 create fail-closed，无真实提交可展示，故 Restricted。绝不把 Submitted 显示成 Completed。

## M-OTC-005 我的 OTC 订单（只读实现，me 占位）

| 字段 | 值 |
|---|---|
| Page ID | M-OTC-005 |
| Route | `/otc/my`（meta.pageId=M-OTC-005, auth=true） |
| DTO/API | `GET /api/v1/otc/users/me/orders`（OtcOrder[]，`me` 占位） |
| Store | otc（orders / fetchMyOrders） |
| 五态 | Loading / Error / Empty / Default |
| 写状态 | 无；点击行 → M-OTC-006 详情；Partial 显示 filled/remaining |
| 权限 | auth=true；本人只读；Expired ≠ Cancelled；Partial+Expired 只释放 remaining |
| I18N | page.m_otc_005.* + otc.side.* + otc.status.* |
| Known Deviation | S03-P02-OTC-ME-ORDERS（`/otc/users/{id}/orders` 无 `me` 变体，`me` 占位，冻结后无需改调用层） |

## M-OTC-006 OTC 订单详情（只读实现，部分受限）

| 字段 | 值 |
|---|---|
| Page ID | M-OTC-006 |
| Route | `/otc/:id`（meta.pageId=M-OTC-006, auth=true） |
| DTO/API | `GET /api/v1/otc/orders/{id}`（OtcOrder）+ `GET /api/v1/otc/trades`（OtcTrade[]，按 order 过滤） |
| Store | otc（order / fetchOrderDetail；trades / fetchTrades / tradesByOrder） |
| 五态 | Loading / Error / Empty（未找到）/ Default |
| 写状态 | 取消剩余订单 disabled（cancel fail-closed）；Power Flow Timeline 空态（无端点） |
| 权限 | auth=true；Sell Power 冻结/消耗来自服务端下发，不自算；不得直接编辑终态 |
| I18N | page.m_otc_006.* + otc.side.* + otc.status.* |
| Known Deviation | S03-P02-OTC-CANCEL（取消禁用）；S03-P02-POWER-LEDGER（Power Flow Timeline 空态，关联 H5-06） |

关键语义：Sell 的 Power 先冻结，只有 filled 部分消耗；未成交按取消/到期规则释放。前端只展示服务端下发的 `power_required / power_frozen / power_consumed`，不自算。

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-OTC-QUOTE | `POST /otc/quotes` 依赖 fee/limit/库存（TBC）→ 503 fail-closed。 | M-OTC-002 Restricted；不提供报价写方法 |
| S03-P02-OTC-CREATE | `POST /otc/orders` 依赖 min/max/fee/inventory + Power freeze（TBC）→ 503 fail-closed。 | M-OTC-003/004 Restricted；不提供挂单写方法 |
| S03-P02-OTC-CANCEL | `POST /otc/orders/{id}/cancel` 依赖 Power release 规则（TBC）→ 503 fail-closed。 | M-OTC-006 取消按钮 disabled |
| S03-P02-OTC-ELIGIBILITY | OtcEligibility schema 存在但无 C 端暴露路径（无 /me/otc-eligibility）。 | M-OTC-001 不展示资格结论（不默认 deny 也不默认 allow） |
| S03-P02-OTC-CAPACITY | OtcCapacity schema 存在但无 /me/otc-capacity 路径，capacity/储备参数 TBC → null。 | M-OTC-001 容量区域文案受限，不伪造数值 |
| S03-P02-OTC-ME-ORDERS | `/otc/users/{id}/orders` 取用户 id，C 端自取无 `me` 变体。 | `/otc/users/me/orders` 占位（与 S03-P02-HOME-ROBOT-ME-PATH 同口径） |
| S03-P02-OTC-FLOW | Power Flow Timeline（提交冻结→逐笔 fill 消耗→取消/到期释放）无冻结端点。 | M-OTC-006 Power Flow 空态，不伪造时间线 |

## 状态映射（05 canonical 展示映射，不新增领域状态）

| canonical | I18N key |
|---|---|
| BUY / SELL（OtcOrder.side） | otc.side.BUY / SELL |
| draft / review / matching / partial / completed / cancelled / expired / rejected / disputed（OtcOrder.status） | otc.status.* |
| completed（OtcTrade.status，单态） | otc.status.completed |

## 合规约束

- OTC 白底 + Navy/Blue；不用涨跌红绿作为主要视觉语言；不呈现交易终端大屏 / K-Line / 订单簿深度交易视图。
- `OTC`、`APT`、`Power` 锁定不翻译；动作词按 locale 本地化。
- 挂单/取消/报价等写能力 fail-closed：不伪造数据、不本地推导 Power 影响、不把 Submitted 显示成 Completed。
- 敏感文案 `otc.risk_disclosure.body` 仍 `PENDING_HUMAN_REVIEW`（Owner 签核后方可声明最终生产文案）；本批次页面未直接呈现该文案，避免未签核内容上线。

## 验证

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 19 files / 124 tests pass（新增 otc.spec.ts 10 + otc-view.spec.ts 13，合计 +21） |
| `npm run build` | ✅ vite 244 modules，含 m-otc-001/002/003/004/005/006 chunk |
| i18n key parity（7 语言） | ✅ 同构（otc.side.* / otc.status.* / page.m_otc_00[1-6].*，含既有 otc.risk_disclosure.body） |
| secret 扫描 | ✅ 0 匹配 |
| 三尺寸截图（375/390/430） | 待补（缺口，S03-P02-VISUAL-BASELINE 逐页固定文件） |
