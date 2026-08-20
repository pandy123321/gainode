# Admin 2.0 后端 DTO 接口契约清单（S03-P03 逐页对接依据）

> 起草：DEVELOPMENT-01
> 日期：2026-08-21
> 依据：07 §8 S03-P03、04 §12 权限公式、05 §6、前端 `admin-registry.ts`（33 权威 Page ID）
> 用途：供前端同事按 OpenAPI DTO 口径逐页对接；后端按此清单补接口。
> 原则：字段以后端 OpenAPI DTO 为准；金额 string decimal；权限只读 `allowed_actions`；
>       写操作走审批/SoD；`route_key` 待接口落地后回填。

## 约定

- **base**：Admin 前端经 `http-v2.ts` 调用 `VITE_API_BASE_URL ?? ''` + `/api/v1/admin/...`（OPTION_A，admin 应用组）。
- **只读列表**：GET `/api/v1/admin/<domain>/<resource>`，支持 page/size/keyword/filter；返回分页 data。
- **详情**：GET `/api/v1/admin/<domain>/<resource>/{id}`。
- **写操作**：POST/PUT，带 Idempotency-Key + If-Match(object_version)；高风险页走审批（SoD）。
- **状态**：READY（后端接口已存在）/ PARTIAL（部分）/ NOT_BUILT（后端待建）/ CONTRACT_GAP（契约未冻结）。

## 33 权威页 × 接口契约

### 01 工作台（ADM-01）

| Page ID | 前端 route | 所需接口 | 后端 readiness |
|---|---|---|---|
| A-WORK-001 运营总览 | /workbench/overview | GET /api/v1/admin/workbench/overview（聚合指标：用户/资产/Robot/市场/待办） | NOT_BUILT（需 DTO 聚合 service） |
| A-WORK-002 今日待办 | /workbench/todo | GET /api/v1/admin/workbench/todo（待审批/待复核列表） | NOT_BUILT |

### 02 用户与准入（ADM-02）

| Page ID | 前端 route | 所需接口 | 后端 readiness |
|---|---|---|---|
| A-USER-001 用户列表 | /admission/users | GET /api/v1/admin/admission/users（分页：UID/手机/邮箱/Robot状态/KYC/APT/Power） | NOT_BUILT（需聚合 User+RBT+KYC+APT） |
| A-USER-002 用户360 | /admission/user-360 | GET /api/v1/admin/admission/users/{id}（9 Tab 聚合） | NOT_BUILT |
| A-USER-004 资产调整 | /admission/asset-adjust | POST /api/v1/admin/ledger/corrections（审批） | CONTRACT_GAP（GAP-015） |
| A-KYC-001 KYC队列 | /admission/kyc | GET /api/v1/admin/admission/kyc + POST /…/kyc/{id}/decision | PARTIAL（KycApplicationService 有 submit/approve；列表待 DTO） |

### 03 资产与账本（ADM-03）

| Page ID | 前端 route | 所需接口 | 后端 readiness |
|---|---|---|---|
| A-LEDGER-001 资产总览 | /ledger/overview | GET /api/v1/admin/ledger/overview（四账聚合） | NOT_BUILT |
| A-LEDGER-002 APT账户流水 | /ledger/accounts | GET /api/v1/admin/ledger/accounts + /{id}/entries | PARTIAL（AptAccountService/LedgerService 有只读；列表 DTO 待建） |
| A-LEDGER-003 池子对账 | /ledger/pools | GET /api/v1/admin/ledger/pools + 对账动作 | NOT_BUILT（Pool/对账无 service） |
| A-LEDGER-004 更正冲正 | /ledger/corrections | POST /api/v1/admin/ledger/corrections（冲正→Approval） | PARTIAL（LedgerService::reverse 有；Controller 待建） |

### 04 机器人与权益（ADM-04）

| Page ID | 前端 route | 所需接口 | 后端 readiness |
|---|---|---|---|
| A-ROBOT-001 Robot列表 | /robot/list | GET /api/v1/admin/robot/list | PARTIAL（RobotService::getByUser；列表 DTO 待建） |
| A-ROBOT-002 Robot详情 | /robot/detail | GET /api/v1/admin/robot/{id} | PARTIAL（RobotService::detail 有） |
| A-ROBOT-003 Reward运营 | /robot/rewards | GET /api/v1/admin/robot/rewards | PARTIAL（RobotRewardService::listByUser 有） |

### 05 OTC 与 Power（ADM-05）

| Page ID | 前端 route | 所需接口 | 后端 readiness |
|---|---|---|---|
| A-OTC-001 OTC订单列表 | /otc/orders | GET /api/v1/admin/otc/orders | PARTIAL（OtcOrderService::listByUser） |
| A-OTC-002 OTC详情/审核 | /otc/order-detail | GET /api/v1/admin/otc/orders/{id} + POST review/approve | PARTIAL（OtcOrderService 有 guard；写路径 fail-closed） |
| A-POWER-001 Power账户 | /power/accounts | GET /api/v1/admin/power/accounts | PARTIAL（PowerPositionService::getByUser） |

### 06 赛事预测（ADM-06）

| Page ID | 前端 route | 所需接口 | 后端 readiness |
|---|---|---|---|
| A-PREDICT-001 Market列表 | /prediction/markets | GET /api/v1/admin/prediction/markets | PARTIAL（PredictionMarketService::listByEvent） |
| A-PREDICT-002 Market详情 | /prediction/market-detail | GET /api/v1/admin/prediction/markets/{id} | PARTIAL（::detail） |
| A-PREDICT-003 Result/Settlement | /prediction/results | GET /api/v1/admin/prediction/results + 赛果录入 | PARTIAL（ResultService/SettlementService 有；Controller 待建） |
| A-PREDICT-004 Refund/Correction | /prediction/refunds | POST refund/correction（审批） | PARTIAL（RefundCase/CorrectionCase service 有） |

### 07 风控 / 审批 / 参数 / 策略（ADM-07）

| Page ID | 前端 route | 所需接口 | 后端 readiness |
|---|---|---|---|
| A-RISK-001 Risk Case | /risk/cases | GET /api/v1/admin/risk/cases | PARTIAL（RiskCaseService 有） |
| A-APPROVAL-001 审批中心 | /approval/center | GET /api/v1/admin/approval/tasks + POST decisions | PARTIAL（ApprovalRequestService 有） |
| A-CONFIG-001 Parameter Center | /config/definitions | GET /api/v1/admin/parameter/definitions + candidates | PARTIAL（ParameterRelease/Snapshot service 有） |
| A-CONFIG-002 Release/Snapshot | /config/releases | GET/POST /api/v1/admin/parameter/releases | PARTIAL（ParameterReleaseService 有；activate fail-closed） |
| A-POLICY-001 策略 | /policy/list | GET /api/v1/admin/policy | NOT_BUILT（Policy service 无） |

### 08 客服 / 审计 / 运维（ADM-08）

| Page ID | 前端 route | 所需接口 | 后端 readiness |
|---|---|---|---|
| A-SUPPORT-001 工单队列 | /support/tickets | GET /api/v1/admin/support/tickets | PARTIAL（TicketService 有） |
| A-SUPPORT-002 工单详情 | /support/ticket-detail | GET /api/v1/admin/support/tickets/{id} | PARTIAL（TicketMessage/Attachment service 有） |
| A-AUDIT-001 审计日志 | /audit/logs | GET /api/v1/admin/audit-log | **READY**（AdminV2Controller::auditLog 已绑定 AuditEventService::listAdmin） |
| A-OPS-001 异步/对账 | /ops/async-tasks | GET /api/v1/admin/async-jobs/{id} | PARTIAL（AdminV2Controller::asyncJob FAIL_CLOSED；AsyncJob 服务无） |
| A-REPORT-001 报表 | /report/list | GET /api/v1/admin/report | NOT_BUILT（Report service 无；P1） |
| A-GROWTH-001 Referral | /growth/referral | GET /api/v1/admin/growth/referral | NOT_BUILT（P1） |
| A-MIGRATION-001 APT迁移 | /migration/apt | CLOSED（无执行控件） | N/A（FUTURE/CLOSED） |
| A-EMERGENCY-001 紧急控制 | /emergency/control | POST /api/v1/admin/emergency（MFA+case+evidence） | CONTRACT_GAP（override contract 未签） |

### DEFERRED 数据页（A-DATA-002/003/005 已接后端）

| Page ID | 前端 route | 后端接口 | readiness |
|---|---|---|---|
| A-DATA-002 数据源管理 | /data/source | GET /admin/arbitrage/datasource + POST save/test | **READY**（DataSourceController） |
| A-DATA-003 足球数据 | /data/football | GET /admin/arbitrage/fixture | **READY**（FixtureController） |
| A-DATA-005 信号质量 | /data/signal | GET /admin/arbitrage/signal | **READY**（SignalController） |

## 汇总

```text
33 权威页接口契约已逐页列明
READY（后端接口存在并已注册）= audit-log / 数据页 fixture/signal/datasource / C端只读
PARTIAL（service 有、Controller/DTO 列表待建）= 多数 P0 页（User/Robot/OTC/Ledger/Prediction/Risk/Approval/Config/Support）
NOT_BUILT = 工作台聚合 / 资产总览 / 池子对账 / 策略 / 报表 / Referral（需新 DTO service）
CONTRACT_GAP = 资产调整(A-USER-004) / 紧急操作(A-EMERGENCY-001)
CLOSED = APT 迁移(A-MIGRATION-001)
```

## 后端待建接口优先级（建议顺序）

1. **用户列表 DTO**（A-USER-001）：聚合 User + Robot + KYC + APT 分页 —— 最核心。
2. **工作台聚合**（A-WORK-001/002）。
3. **各域列表 DTO Controller**（Robot/OTC/Ledger/Prediction/Risk/Approval/Config）绑定已有 service。
4. 写路径 Controller（待 admin 角色映射 + SoD 冻结后）。

## 说明

- 本清单不产生前端 UI，仅作接口对接依据。
- 各 Controller/DTO 落地按「每任务一包 + 提审」节奏推进。
- 字段口径以 OpenAPI DTO 为准，落地时不得照搬 V1 `sys_table_field`。
