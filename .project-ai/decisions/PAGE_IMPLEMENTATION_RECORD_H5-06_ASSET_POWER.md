# PAGE_IMPLEMENTATION_RECORD — H5-06 Asset/Power（M-ASSET-001..003 / M-POWER-001）

> 批次：H5-06 Asset/Power ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-ASSET-001..003 + §M-POWER-001 ｜ OpenAPI ledger.yaml#AssetBalance / LedgerEntry / PowerPosition（S02-P03）
> 治理核心：经济写路径（post/cancel/reverse/dispute）由内部 Authoritative Writer 触发，**不对外暴露**；前端仅绑定 3 个只读端点（`/me/asset`、`/me/ledger-entries`、`/me/power`）。资产可见与交易权限分离；可用/冻结/待确认三态分离；参考估值、Power 7 日趋势、PowerImpactPreview、关联操作等无冻结端点 → 一律不伪造，用文案/空态/disabled 表达。OTC（挂买/挂卖）属 H5-07，本批次 fail-closed 不开放真实挂单。

## 页面清单

| Page ID | Route | 读/写 | DTO/API | 实现形态 |
|---|---|---|---|---|
| M-ASSET-001 | `/asset` | 只读 | AssetBalance（me_asset_balance）+ LedgerEntry[]（me_ledger_entries） | 完整（余额 Hero + 可用/冻结/待确认拆分 + 最近流水预览 + Power 入口 + OTC 挂买挂卖 disabled） |
| M-ASSET-002 | `/asset/ledger` | 只读 | LedgerEntry[]（me_ledger_entries） | 完整（流水列表：方向/数量/状态/来源/时间） |
| M-ASSET-003 | `/asset/ledger/:id` | 只读 | LedgerEntry（无详情端点 → 从列表取） | 完整（详情：方向/状态/来源/冲正/规则/快照；深链无数据 Empty） |
| M-POWER-001 | `/power` | 只读 | PowerPosition（me_power_position） | 完整（Battery + 可用/冻结/消耗/释放/恢复/上限拆分 + 恢复时间；7 日趋势/关联操作空态） |

## M-ASSET-001 APT 资产（只读完整实现）

| 字段 | 值 |
|---|---|
| Page ID | M-ASSET-001 |
| Route | `/asset`（meta.pageId=M-ASSET-001, auth=true） |
| DTO/API | `GET /api/v1/me/asset`（AssetBalance）+ `GET /api/v1/me/ledger-entries`（LedgerEntry[] 预览前 5） |
| Store | asset（balance / ledger / recentLedger） |
| 五态 | Loading / Error / Default |
| 写状态 | 无写操作；OTC 挂买/挂卖 disabled（H5-07 未实现，fail-closed） |
| 权限 | auth=true；资产可见与交易权限分离；APT 数字均有单位与状态 |
| I18N | page.m_asset_001.* + asset.direction.* + asset.ledger_state.* |
| Tests | tests/unit/asset.spec.ts + asset-view.spec.ts |
| Known Deviation | S03-P02-ASSET-VALUATION（参考估值不展示）；S03-P02-OTC-NOT-YET（挂买/挂卖 disabled） |

关键语义：Hero 与拆分「可用」均取 `effective_available`（= stored_available − aggregate_dispute_hold）；「冻结」取 `frozen_apt_i`；「待确认冻结」取 `aggregate_dispute_hold`。三者与四账分离一致，不把估值当收入/兑付价。

## M-ASSET-002 APT 流水列表（只读完整实现）

| 字段 | 值 |
|---|---|
| Page ID | M-ASSET-002 |
| Route | `/asset/ledger`（meta.pageId=M-ASSET-002, auth=true） |
| DTO/API | `GET /api/v1/me/ledger-entries`（LedgerEntry[]，append-only，按时间倒序） |
| Store | asset（ledger / fetchLedger） |
| 五态 | Loading / Error / Empty / Default |
| 写状态 | 无；点击行 → M-ASSET-003 详情（不发起详情请求） |
| 权限 | auth=true；本人只读；不隐藏负向/冲正记录，不用纯颜色表示正负 |
| I18N | page.m_asset_002.* + asset.direction.* + asset.ledger_state.* |

## M-ASSET-003 APT 流水详情（只读完整实现，无详情端点）

| 字段 | 值 |
|---|---|
| Page ID | M-ASSET-003 |
| Route | `/asset/ledger/:id`（meta.pageId=M-ASSET-003, auth=true） |
| DTO/API | 无单笔详情端点；从 `asset.entryById(id)`（已拉取列表）渲染；深链未加载时拉列表一次 |
| Store | asset（entryById） |
| 五态 | Loading / Empty（未找到）/ Default |
| 写状态 | 无；`reversal_of` 展示冲正引用（原分录在列表中可见） |
| 权限 | auth=true；本人数据 |
| I18N | page.m_asset_003.* + asset.direction.* + asset.ledger_state.* |
| Known Deviation | S03-P02-ASSET-LEDGER-DETAIL（无 `/me/ledger-entries/{id}`；深链冷启动只能显示 Empty，不回源） |

## M-POWER-001 Power（只读完整实现）

| 字段 | 值 |
|---|---|
| Page ID | M-POWER-001 |
| Route | `/power`（meta.pageId=M-POWER-001, auth=true） |
| DTO/API | `GET /api/v1/me/power`（PowerPosition） |
| Store | power（position / ratio） |
| 五态 | Loading / Error / Default |
| 写状态 | 无；OTC 挂买/挂卖 disabled（fail-closed） |
| 权限 | auth=true；`power_cap_source_robot_level` 原样展示，前端不算每级数值 |
| I18N | page.m_power_001.* + power.*（available/frozen/consumed/released/recovering/cap/cap_source_robot_level/next_restore_at/last_restore_at） |
| Known Deviation | S03-P02-POWER-LEDGER / -IMPACT-PREVIEW / -RELATED-ACTIONS |

`ratio`（可用/上限）仅用于 Battery 视觉宽度，**不参与任何业务计算**；`limit` 缺失或非正数时 ratio=null，视觉回退 0 宽。

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-ASSET-LEDGER-DETAIL | 03 引 `GET /users/{id}/asset-ledger/{entry_id}`（单笔流水详情），OpenAPI 无 `/me/ledger-entries/{id}`。 | M-ASSET-003 由列表已拉取 entry 渲染；深链冷启动 Empty，不回源伪造 |
| S03-P02-ASSET-VALUATION | Reference Valuation（参考估值）无冻结来源端点。 | M-ASSET-001 不展示参考估值，避免把估值当收入/兑付价 |
| S03-P02-POWER-LEDGER | Power Ledger（7 日变化 / 分笔消耗-释放）无冻结 DTO；`/ai/users/{id}/computing-power-ledger` 有路径无 schema（关联 S03-P02-ROBOT-CAPACITY）。 | M-POWER-001 不展示 7 日趋势，文案空态，不伪造 |
| S03-P02-POWER-IMPACT-PREVIEW | PowerImpactPreview（Withdrawal / Robot Start / OTC Sell 预计冻结）无端点。 | 前端不得自算 Power 影响；写操作在各自业务页 fail-closed |
| S03-P02-POWER-RELATED-ACTIONS | related_actions / allowed_actions 无下发端点。 | M-POWER-001 冻结/关联操作空态 |
| S03-P02-OTC-NOT-YET | OTC 挂买/挂卖属 H5-07，本批次未实现。 | M-ASSET-001 / M-POWER-001 挂买/挂卖按钮 disabled（fail-closed），不开放真实挂单 |

## 状态映射（05 canonical 展示映射，不新增领域状态）

| canonical | I18N key |
|---|---|
| pending / posted / reversed / disputed（LedgerEntry.state） | asset.ledger_state.* |
| 1=CREDIT / -1=DEBIT（LedgerEntry.entry_direction） | asset.direction.credit / debit |

关键区分：`entry_direction` 表达入账/出账，`quantity` 恒正；`state=reversed` 的冲正分录与 `reversal_of` 引用原分录在列表中分别可见，不隐藏负向/冲正记录。

## 合规约束

- 资产/OTC 白底 + Navy/Blue，不用涨跌红绿作为主要视觉语言；负向/冲正不用纯颜色表示。
- `APT`、`Power` 锁定不翻译。
- 参考估值、Power 7 日趋势、PowerImpactPreview、OTC 挂单等未冻结能力一律不伪造、不本地推导。

## 验证

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 17 files / 103 tests pass（新增 asset.spec.ts 8 + asset-view.spec.ts 8） |
| `npm run build` | ✅ vite 224 modules，含 m-asset-001/002/003 + m-power-001 chunk |
| i18n key parity（7 语言） | ✅ 同构（asset.direction.* / asset.ledger_state.* / power.* / page.m_asset_00[1-3].* / page.m_power_001.*） |
| secret 扫描 | ✅ 0 匹配 |
| 三尺寸截图（375/390/430） | 待补（缺口，S03-P02-VISUAL-BASELINE 逐页固定文件） |
