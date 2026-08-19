# S03-P03 逐页实现（ADM-02 .. ADM-08D）实施记录

## 背景与决策
- 用户指令（会话内）：「后面还有几个任务，能把后台做完」。
- 在 ADM-01（8 导航收敛 + 工作台 2 页）完成基础上，按 `07 §9` 逐导航批次表，一次性落地剩余 31 页，达成 **33 个权威 Admin Page ID 全覆盖**。

## 交付物（commit `cb0714f`）

### 公共骨架
- `src/composables/useAdminPage.ts`：`useAdminPage()`（AdminFiveState + `mockLoad` 模拟拉取）+ `useMockPagination()`。接入真实接口时替换 `load()`。

### 逐批页面（MOCK_ONLY，2.0 后端 HTTP 未实现）
| 批次 | 页面文件 | 关键落地点（07 §8 语义） |
|---|---|---|
| ADM-02 用户准入 | `admission/UserList.vue`、`User360.vue`、`KycQueue.vue` | 字段脱敏（手机掩码）、User360 九 Tab、KYC 决定必须 `reason_code` + `decision_version` 不可覆盖 |
| ADM-02B 条件页 | `admission/AssetAdjust.vue` | P1_CONDITIONAL / CONTRACT_GAP；仅 Impact Preview，执行按钮禁用（FAIL_CLOSED），候选字段 NON_AUTHORITATIVE |
| ADM-03 资产账本 | `ledger/LedgerOverview.vue`、`LedgerAccounts.vue`、`LedgerPools.vue`、`LedgerCorrections.vue` | 冻结/待确认/已销毁分别展示；append-only 禁止内联改账；OTC 储备与运营预算隔离；更正只能走 reversal proposal |
| ADM-04 Robot | `robot/RobotList.vue`、`RobotDetail.vue`、`RobotRewards.vue` | 列表不能直接改 level；状态历史不可编辑；Reward 状态与 ledger posting 一致，不手工「补已领取」 |
| ADM-05 OTC/Power | `otc/OtcOrders.vue`、`OtcOrderDetail.vue`、`PowerAccounts.vue` | SUBMITTED/MATCHING/PARTIAL 不显示 Completed；决定必须写 reason；Power 只读不可手改 |
| ADM-06 Prediction | `prediction/MarketList.vue`、`MarketDetail.vue`、`ResultSettlement.vue`、`RefundCorrection.vue` | P0 只允许 Football pre-match 1X2；锁定失败需 reason+refund 路径；未 reconciliation=0 不关闭 batch；更正不覆盖 old snapshot |
| ADM-07 风控治理 | `risk/RiskCase.vue`、`ApprovalCenter.vue`、`ConfigDefinitions.vue`、`ConfigReleases.vue`、`PolicyList.vue` | 用户可见/内部 reason 分离；approved≠executed、SoD 自我审批阻止；保存不生效、TBC=null；Release immutable；无证据不 ALLOW |
| ADM-08 客服审计运维 | `support/TicketQueue.vue`、`TicketDetail.vue`、`AuditLogs.vue`、`OpsConsole.vue` | 内部 note 与用户回复区分；审计不可编辑/删除；资金任务重试需额外确认 |
| ADM-08B P1 | `support/ReportList.vue`、`GrowthReferral.vue` | 报表非账本/收入权威；增长不直接补发 |
| ADM-08C Future | `support/MigrationApt.vue` | CLOSED by default，无执行控件 |
| ADM-08D Emergency | `support/EmergencyControl.vue` | 双人授权 + case_id/reason/scope/recovery；超时补审自动升级 |

### 组件映射
- `src/router/module/admin-page-components.ts`：33 个权威 `PageId` 全部登记对应组件，未登记（deferred 7 页）回退 `ListPage.vue` 骨架。

## 契约依据
- `04 §3` 各页面规格：布局、关键尺寸、Frame 状态、视觉禁止。
- `07 §8` 高风险统一验收：来源对象 / before-after impact / reason / evidence / approval actor / 执行终态 / request_id / Audit ID；状态变化不允许旧表单重复提交。
- `07 §9` S03-P03 逐导航批次（ADM-02 .. ADM-08D）。

## 验证
- `vue-tsc --noEmit`：0 错误。
- `vite build`：通过（3717 modules；各页面独立成 chunk；存在 Element Plus css 嵌套 `button` 预存告警，非本次引入）。
- 33 权威 Page ID 与 `types/page.ts` 联合类型逐一比对，全量覆盖。

## 非阻塞 / 未冻结
- 后端 2.0 全部 Admin 读/写接口尚未实现，所有页面为 MOCK_ONLY UI 骨架；接入时替换各页 `load()/decide()/submit*()`。
- 8 导航 Sidebar/Header 布局、i18n 文案 key、RBAC/字段脱敏服务端强制、Figma token 视觉基线，属后续批次，非本包范围。
- 未执行单元/E2E/视觉回归（对应 07 §9 验证命令），由 Quality 在 Stage Gate（S03-P04）统一把关。
