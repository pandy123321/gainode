# PAGE_IMPLEMENTATION_RECORD — H5-05 Prediction（M-PREDICT-001..006）

> 批次：H5-05 Prediction ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-PREDICT-001..006 ｜ OpenAPI prediction.yaml#PredictionMarket / PredictionOrder / PredictionResult / PredictionSettlement / RefundCase / CorrectionCase / ConsentReceipt
> 治理核心：写操作（order_create / order_addition / appeal_create）依赖锁盘参数/资格/stake 或 RiskCase 契约未冻结 → 后端 fail-closed（503 DEPENDENCY_UNAVAILABLE）。前端遵循 fail-closed，**不开放任何真实写提交**；列表/详情/订单回执为只读投影，契约缺口页面用 Restricted/文案占位，不伪造数据。

## 页面清单

| Page ID | Route | 读/写 | DTO/API | 实现形态 |
|---|---|---|---|---|
| M-PREDICT-001 | `/prediction` | 只读 | PredictionMarket[]（markets） | 完整（热门 open + 即将截止 closing 分区赛事卡列表） |
| M-PREDICT-002 | `/prediction/:id` | 只读 | PredictionMarket（market_detail） | 完整（Match Hero + 三方向同级 + 规则/AI 参考占位） |
| M-PREDICT-003 | `/prediction/confirm/:id` | 写 fail-closed | order_create（503） | Restricted 占位 |
| M-PREDICT-004 | `/prediction/my` | 只读 | `/me/prediction-orders`（无路径） | Restricted 占位 |
| M-PREDICT-005 | `/prediction/order/:id` | 只读 | PredictionOrder（order_receipt） | 完整（订单状态 Hero + 选择/数量/订单号；赛果/结算占位） |
| M-PREDICT-006 | `/prediction/exception/:id` | 只读 | RefundCase/CorrectionCase（无 corrections 路径） | Restricted 占位 |

## M-PREDICT-001 竞猜广场（只读完整实现）

| 字段 | 值 |
|---|---|
| Page ID | M-PREDICT-001 |
| Route | `/prediction`（meta.pageId=M-PREDICT-001, auth=true） |
| Figma node | 03 原型「竞猜广场 / 赛事竞猜」；Gainode2.0 node 待对齐 |
| DTO/API | `GET /api/v1/markets`（PredictionMarket[]） |
| Store | prediction（markets / openMarkets / closingMarkets） |
| Components/tokens | 分区标题 + 赛事卡（最小 128px）+ 状态徽标 + BottomNav + FiveStateContainer；无财富/博彩/盘口红绿视觉 |
| 五态 | Loading / Error / Empty / Default |
| 写状态 | 无写操作；点击卡片仅进入详情，不提交 |
| 权限 | auth=true；未准入也可浏览公开信息，提交在 M-PREDICT-003 fail-closed |
| I18N | page.m_predict_001.* + prediction.market_status.* + prediction.selection.* |
| Tests | tests/unit/prediction.spec.ts + prediction-view.spec.ts |
| Known Deviation | S03-P02-PREDICT-DISCLOSURE / -ME-ORDERS（广场不依赖两者） |

## M-PREDICT-002 竞猜详情（只读完整实现）

| 字段 | 值 |
|---|---|
| Page ID | M-PREDICT-002 |
| Route | `/prediction/:id` |
| DTO/API | `GET /api/v1/markets/{id}`（PredictionMarket）；`GET /markets/{id}/disclosure` 有路径无 DTO → 规则/AI 参考占位 |
| Store | prediction（marketDetail） |
| 五态 | Loading / Error / Empty（未找到）/ Default |
| 写状态 | 无写操作；「参与竞猜」仅跳转 M-PREDICT-003（该页 Restricted） |
| 权限 | auth=true；Home/Draw/Away 三方向同级无推荐光效，权重一致 |
| I18N | page.m_predict_002.* + prediction.market_status.* + prediction.selection.* |

## M-PREDICT-005 订单详情（只读完整实现）

| 字段 | 值 |
|---|---|
| Page ID | M-PREDICT-005 |
| Route | `/prediction/order/:id` |
| DTO/API | `GET /api/v1/orders/{id}/receipt`（PredictionOrder） |
| Store | prediction（orderReceipt） |
| 五态 | Loading / Error / Default |
| 写状态 | 无写操作；赛果与结算双轨因 PredictionOrder 无 result_id/settlement_id 无法链接，用文案占位，不伪造（official ≠ paid） |
| 权限 | auth=true；历史始终可读 |
| I18N | page.m_predict_005.* + prediction.order_status.* + prediction.selection.* |

## M-PREDICT-003/004/006 Restricted 占位

三个页面因对应端点/写操作未冻结，统一渲染 `FiveStateContainer state=restricted`，仅展示标题 + 受限说明 + 返回，不伪造数据、不开放提交：

| Page ID | Restricted 原因 | 对应缺口 |
|---|---|---|
| M-PREDICT-003 | order_create POST fail-closed（锁盘参数/资格/stake TBC） | S03-P02-PREDICT-ORDER-WRITE |
| M-PREDICT-004 | 我的竞猜列表 `/me/prediction-orders` 无路径 | S03-P02-PREDICT-ME-ORDERS |
| M-PREDICT-006 | 更正详情 `corrections/{id}` 无路径；RefundCase/CorrectionCase 无 C 端暴露路径 | S03-P02-PREDICT-CORRECTION |

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-PREDICT-ORDER-WRITE | order_create / order_addition 依赖锁盘参数/资格/stake（06 TBC）→ 503 fail-closed；appeal_create 依赖 RiskCase 未冻结 → 503 fail-closed。 | 前端不提供写 API 方法、不开放真实提交；M-PREDICT-003 Restricted |
| S03-P02-PREDICT-DISCLOSURE | `market_disclosure` 路径存在但无冻结 Disclosure DTO。 | M-PREDICT-002 规则/AI 参考区文案占位，不绑定 |
| S03-P02-PREDICT-ME-ORDERS | 03 引 `GET /me/prediction-orders`（我的竞猜列表），OpenAPI 无此路径。 | M-PREDICT-004 保持 Restricted/Closed |
| S03-P02-PREDICT-CORRECTION | 03 引 `GET /corrections/{id}`，OpenAPI 无 corrections 路径；refund_detail 存在但无 C 端订单/赛果链接。 | M-PREDICT-006 保持 Restricted/Closed |
| S03-P02-PREDICT-ORDER-LINK | PredictionOrder 无 result_id / settlement_id，订单详情无法链到赛果与结算。 | M-PREDICT-005 赛果/结算区文案占位，不伪造；等后端补字段后接入 |

## 状态映射（05 canonical 展示映射，不新增领域状态）

| canonical | I18N key |
|---|---|
| draft/open/closing/locked/awaiting_result/settlement/settled/void/exception（Market） | prediction.market_status.* |
| submitted/locked/awaiting_result/settling/settled/refunding/refunded/correcting/corrected（PredictionOrder） | prediction.order_status.* |
| HOME/DRAW/AWAY（Selection，1X2 canonical 不变，locale 本地化） | prediction.selection.* |

关键区分（05 §M-PREDICT 多状态轴）：Market settled ≠ Order settled ≠ Settlement paid；Result official ≠ Settlement final。本批次只读投影不提前把 official result 画成资金已完成。

## 合规约束

- 中文统一「竞猜」，禁「下注 / 投注 / 押注 / 赔率 / Odds / 盘口」。
- 三方向视觉权重一致，无「推荐方向」光效或高亮。
- AI 数据参考不默认高亮推荐方向、必赢或高胜率暗示（M-PREDICT-002 规则区占位文案不含任何推荐）。

## 验证

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 15 files / 88 tests pass（新增 prediction.spec.ts 5 + prediction-view.spec.ts 4） |
| `npm run build` | ✅ vite 210 modules，含 m-predict-001..006 chunk |
| i18n key parity（7 语言） | ✅ 同构（prediction.market_status.* / order_status.* / selection.* / page.m_predict_00[1-6].*） |
| secret 扫描 | ✅ 0 匹配 |
| 三尺寸截图（375/390/430） | 待补（缺口，S03-P02-VISUAL-BASELINE 逐页固定文件） |
