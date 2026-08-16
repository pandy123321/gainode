# PAGE_IMPLEMENTATION_RECORD — H5-04 Robot（M-ROBOT-001..007）

> 批次：H5-04 Robot ｜ 状态：IMPLEMENTED（待 Quality 审）
> 基线：03 §M-ROBOT-001..007 ｜ OpenAPI robot.yaml#RobotSummary / RobotDetail / RobotRuleSnapshot / AIReward / RobotUpgradeOrder
> 治理核心：S02-P04 56 级规则/预算/系数/Power/升级成本未 Active → 三个写操作（启停 actions / 升级 upgrade-orders / 领取 reward-claims）后端 fail-closed（503 DEPENDENCY_UNAVAILABLE）。前端遵循 fail-closed，**不开放任何真实写提交**，按钮由 `allowed_actions` + `source_status` 驱动，不由本地状态推导。

## 页面清单

| Page ID | Route | 读/写 | DTO/API | 实现形态 |
|---|---|---|---|---|
| M-ROBOT-001 | `/robot` | 只读 | RobotSummary + RobotDetail（allowed_actions/capabilities/source_status） | 完整（状态 Hero + 能力 + 导航入口） |
| M-ROBOT-002 | `/robot/start` | 写 fail-closed | robot_action（无 action quote DTO） | Restricted 占位 |
| M-ROBOT-003 | `/robot/upgrade` | 写 fail-closed | upgrade_eligibility（无 DTO）/ upgrade_orders_create（503） | Restricted 占位 |
| M-ROBOT-004 | `/robot/upgrade/result/:id` | 只读 | RobotUpgradeOrder（upgrade_order_detail） | 完整（结果 Hero + Before/After + 版本） |
| M-ROBOT-005 | `/robot/levels` | 只读 | RobotRuleSnapshot（有 schema 无路径） | Restricted 占位 |
| M-ROBOT-006 | `/robot/rewards` | 读 + claim 写 fail-closed | AIReward[]（rewards）+ reward_claim（503） | 完整只读列表 + Claim disabled |
| M-ROBOT-007 | `/robot/activity` | 只读 | RobotEvent[]（无 schema/路径） | Restricted 占位 |

## M-ROBOT-001 控制中心（只读完整实现）

| 字段 | 值 |
|---|---|
| Page ID | M-ROBOT-001 |
| Route | `/robot`（meta.pageId=M-ROBOT-001, auth=true） |
| Figma node | 03 原型「Robot 控制中心」；Gainode2.0 node 待对齐 |
| DTO/API | `GET /ai/users/me/summary`（RobotSummary，ME 占位）→ 取 robot_id → `GET /ai/robots/{robot_id}`（RobotDetail） |
| Store | robot（summary/detail/allowedActions/sourceUnavailable） |
| Components/tokens | StatusHero + 能力 chip + 导航入口 + BottomNav + FiveStateContainer；var(--brand-blue-600)，无财富/博彩大屏 |
| 五态 | Loading / Error / Empty（无 Robot）/ Restricted（source_status=UNAVAILABLE）/ Default |
| 写状态 | 无写操作；所有子页写提交均 fail-closed |
| 权限 | auth=true；按钮不本地推导，detail.allowed_actions 为空即无动作入口 |
| I18N | page.m_robot_001.* + robot.status.* |
| Tests | tests/unit/robot.spec.ts + robot-view.spec.ts |
| Known Deviation | S03-P02-ROBOT-WRITE-FAILCLOSED / -ME-PATH |

## M-ROBOT-004 升级结果（只读完整实现）

| 字段 | 值 |
|---|---|
| Page ID | M-ROBOT-004 |
| Route | `/robot/upgrade/result/:id` |
| DTO/API | `GET /ai/users/me/upgrade-orders/{upgrade_order_id}`（RobotUpgradeOrder，ME 占位） |
| Store | robot（upgradeOrder） |
| 五态 | Loading / Error / Default（成功/处理中/失败等由 upgrade_status.* 映射） |
| 写状态 | 无写操作；「已提交 ≠ 已升级」；未知态只查询不重复提交 |
| 权限 | auth=true；历史只读 |
| I18N | page.m_robot_004.* + robot.upgrade_status.* |

## M-ROBOT-006 收益与领取（只读列表 + Claim fail-closed）

| 字段 | 值 |
|---|---|
| Page ID | M-ROBOT-006 |
| Route | `/robot/rewards` |
| DTO/API | `GET /ai/users/me/rewards`（AIReward[]，ME 占位）；`POST reward-claims` fail-closed（不提供写方法） |
| Store | robot（rewards/claimableRewards） |
| 五态 | Loading / Error / Empty / Default |
| 写状态 | Claim 按钮 disabled（fail-closed）；只读展示 held/pending_claim 数量 + 历史列表 + reward_state 映射 |
| 权限 | auth=true；只有 claim_allowed=true 才可提交（当前后端未下发，故不提交） |
| I18N | page.m_robot_006.* + robot.reward_state.* |
| 合规 | 动态 Reward 不表述为固定利息；`claim_restricted` 文案保留「动态系数可能为 0」；禁止 APR/APY/回本 |

## M-ROBOT-002/003/005/007 Restricted 占位

四个页面因对应端点/写操作未冻结，统一渲染 `FiveStateContainer state=restricted`，仅展示标题 + 受限说明 + 返回，不伪造数据、不开放提交：

| Page ID | Restricted 原因 | 对应缺口 |
|---|---|---|
| M-ROBOT-002 | 启停 action POST fail-closed，且无 action quote DTO | S03-P02-ROBOT-WRITE-FAILCLOSED / -ACTION-DTO |
| M-ROBOT-003 | 升级 create POST fail-closed，upgrade-eligibility 无 DTO | S03-P02-ROBOT-WRITE-FAILCLOSED / -UPGRADE-ELIGIBILITY-DTO |
| M-ROBOT-005 | 56 级规则 RobotRuleSnapshot 有 schema 无路径 | S03-P02-ROBOT-RULES |
| M-ROBOT-007 | 活动 RobotEvent[] 无 schema 无路径 | S03-P02-ROBOT-EVENT |

## 契约缺口登记（本批次）

| ID | 说明 | 处置 |
|---|---|---|
| S03-P02-ROBOT-WRITE-FAILCLOSED | 三写操作（robot_action / upgrade_orders_create / reward_claim）后端 503 fail-closed（S02-P04 未 Active）。 | 前端不提供写 API 方法、不开放真实提交；子页 Restricted/disabled，等后端 Active + 参数版本冻结后开放 |
| S03-P02-ROBOT-ME-PATH | `/ai/users/{id}/*` 取用户 id，C 端自取无 `me` 变体。 | 以 `/ai/users/me/*` 占位，后端冻结 `me` 语义后无需改调用层（沿用 H5-03） |
| S03-P02-ROBOT-EVENT | 03 引 `GET /ai/users/{id}/activity` + RobotEvent[]，OpenAPI 无路径无 schema。 | M-ROBOT-007 保持 Restricted/Closed |
| S03-P02-ROBOT-RULES | RobotRuleSnapshot（56 级）有 schema 但无暴露路径。 | M-ROBOT-005 保持 Restricted/Closed |
| S03-P02-ROBOT-ACTION-DTO | robot_action / robot_action_detail 无 action 请求/响应 schema。 | M-ROBOT-002 保持 Restricted |
| S03-P02-ROBOT-UPGRADE-ELIGIBILITY-DTO | upgrade_eligibility GET 存在但无 DTO schema。 | M-ROBOT-003 保持 Restricted |
| S03-P02-ROBOT-CAPACITY | user_capacity / computing_power 路径存在但无 DTO schema（Power preview 未冻结）。 | M-ROBOT-001 不展示 Power Battery（原型要求的 PowerMeter 降级，不伪造） |
| S03-P02-ROBOT-SCHEMA-REF | gainode-v2.yaml 引用 robot.yaml#/Robot、#/RobotReward 但 schema 未定义（broken $ref）。 | 后端契约缺陷登记，前端不使用该两 schema |

## 状态映射（05 canonical 展示映射，不新增领域状态）

| canonical | I18N key |
|---|---|
| inactive/active/cooling/review/restricted/paused | robot.status.* |
| pending/processing/completed/failed/cancelled（升级订单） | robot.upgrade_status.* |
| candidate/held/pending_claim/claiming/claimed/expired_returned/review/reversed（Reward） | robot.reward_state.* |

Runtime Stage（PREPARING/STARTING/RUNNING/...）为 UI 呈现层，本批次不新增前端推断；等后端下发可展示 stage 后再接入。

## 验证

| 项 | 结果 |
|---|---|
| `npm run type-check` | ✅ 0 error |
| `npm run test:unit` | ✅ 13 files / 79 tests pass（新增 robot.spec.ts 7 + robot-view.spec.ts 5） |
| `npm run build` | ✅ vite 192 modules，含 m-robot-001..007 chunk |
| i18n key parity（7 语言） | ✅ 同构（robot.upgrade_status.* / robot.reward_state.* / page.m_robot_00[1-7].*） |
| secret 扫描 | ✅ 0 匹配 |
| 三尺寸截图（375/390/430） | 待补（缺口，S03-P02-VISUAL-BASELINE 逐页固定文件） |
