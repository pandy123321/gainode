# STAGE-02 后端覆盖矩阵（Developer 侧草稿，S02-P09 Gate）

> 起草：DEVELOPMENT-01
> 日期：2026-08-20
> 分支：feature/gainode-v3-serial-development
> 基线：bec04b46dcb5b0764bb14db01c9a0f5311dfdafa
> 说明：本矩阵为 Developer 侧终态证据草稿，供 Owner 复核 + 转外审。逐 operationId 标注
>       Controller 绑定 / 路由注册 / Service 存在 / fail-closed 状态。未运行项如实标 NOT_RUN。

## 图例

| 状态 | 含义 |
|---|---|
| WIRED | 控制器方法存在 + sys_route 已注册 + Service 已绑定 |
| CONTROLLER_ONLY | 控制器方法存在，路由未注册（或未绑定 service） |
| NOT_WIRED | 无控制器 / 无路由 |
| FAIL_CLOSED | 服务层对 TBC 参数抛 DEPENDENCY_UNAVAILABLE（安全关闭） |
| N/A | 契约骨架，无对应实现（Contract Gap） |

## C 端只读（已接线）

| operationId | Path | Controller::method | 路由 | Service | 状态 |
|---|---|---|---|---|---|
| me_asset_balance | /api/v1/me/asset | LedgerController::asset | ✅ | AptAccountService | WIRED |
| me_ledger_entries | /api/v1/me/ledger-entries | LedgerController::ledgerEntries | ✅ | LedgerService | WIRED |
| me_power_position | /api/v1/me/power | LedgerController::power | ✅ | PowerPositionService | WIRED |
| robot_list | /api/v1/ai/robots | RobotController::list | ✅ | RobotService::summary | WIRED |
| robot_detail | /api/v1/ai/robots/{robot_id} | RobotController::detail | ✅ | RobotService::detail | WIRED |
| robot_action | /api/v1/ai/robots/{robot_id}/actions | RobotController::actions | ✅ | RobotService::allowedActions | WIRED(只读投影) |
| robot_user_summary | /api/v1/ai/users/{id}/summary | RobotController::userSummary | ✅ | RobotService::summary | WIRED |
| robot_upgrade_orders(detail) | /api/v1/ai/users/{id}/upgrade-orders | RobotController::upgradeOrders | ✅ | RobotUpgradeOrderService::getByRobot | WIRED(只读) |
| robot_rewards | /api/v1/ai/users/{id}/rewards | RobotController::rewards | ✅ | RobotRewardService::listByUser | WIRED(只读) |
| parameter_snapshot_detail | /api/v1/parameters/snapshots/{id} | ParameterController::snapshot | ✅ | ParameterSnapshotService::detail | WIRED |
| parameter_definitions(active) | /api/v1/parameter-releases | ParameterController::activeRelease | ✅ | ParameterReleaseService::getActive+detail | WIRED(只读) |
| prediction_markets | /api/v1/markets | PredictionController::markets | ✅ | PredictionMarketService::listByEvent | WIRED(只读) |
| prediction_market_detail | /api/v1/markets/{id} | PredictionController::marketDetail | ✅ | PredictionMarketService::detail | WIRED |
| prediction_order_receipt(me) | /api/v1/orders | PredictionController::myOrders | ✅ | PredictionOrderService::listByUser | WIRED(只读) |
| prediction_order_receipt | /api/v1/orders/{id}/receipt | PredictionController::orderReceipt | ✅ | PredictionOrderService::detail | WIRED |
| prediction_consent_receipt(me) | /api/v1/consent-receipts | PredictionController::myConsentReceipts | ✅ | ConsentReceiptService::getByUser | WIRED(只读) |
| otc_order_book(me) | /api/v1/otc/order-book | OtcController::orderBook | ✅ | OtcOrderService::listByUser | WIRED(只读, me 语义) |
| otc_order_detail | /api/v1/otc/orders/{id} | OtcController::orderDetail | ✅ | OtcOrderService::detail | WIRED |
| otc_user_orders | /api/v1/otc/users/{id}/orders | OtcController::userOrders | ✅ | OtcOrderService::listByUser | WIRED(只读) |
| otc_trades | /api/v1/otc/trades | OtcController::trades | ✅ | OtcTradeService::getByBuyer/Seller | WIRED(只读) |
| otc_eligibility | /api/v1/otc/eligibility | OtcController::eligibility | ✅ | OtcEligibilityProjectionService | WIRED(只读投影) |

## C 端 Auth/KYC/User（S02-P02 已接线，路由本轮已注册）

| operationId | Controller | 路由 | 状态 |
|---|---|---|---|
| auth_register/login/otp_*/mfa_verify/refresh/logout/recovery/password_reset | AuthController | ✅ | WIRED |
| auth_me_sessions / me_session_revoke | AuthController::sessions/sessionRevoke | ✅ | WIRED |
| user_me / security_profile / login_audit / eligibility_me | UserController | ✅ | WIRED |
| user_mfa_enrollment_setup/confirm/disable | UserController | ✅ | WIRED |
| kyc_me / kyc_submit | KycController | ✅ | WIRED |

## 写路径（全部 fail-closed / 未在 C 端控制器暴露）

| operationId | Service 现状 | C 端 Controller | 路由 | 状态 |
|---|---|---|---|---|
| robot_upgrade_order_create | submit 抛 DEPENDENCY_UNAVAILABLE | 未暴露 | 未注册 | FAIL_CLOSED |
| robot_reward_claim | completeClaim 抛 DEPENDENCY_UNAVAILABLE | 未暴露 | 未注册 | FAIL_CLOSED |
| prediction_order_create/addition | submit 抛 DEPENDENCY_UNAVAILABLE | 未暴露 | 未注册 | FAIL_CLOSED |
| prediction_appeal_create | 无 Appeal 服务 | 未暴露 | 未注册 | N/A(CONTACT_GAP) |
| otc_quote/order_create/order_cancel | quote/createOrder/cancel 抛 DEPENDENCY_UNAVAILABLE | 未暴露 | 未注册 | FAIL_CLOSED |
| policy_evaluation_create | 无对应服务 | 未暴露 | 未注册 | N/A |
| parameter_candidate_create/release_create/release_activate | 需 admin 角色守卫 | 未暴露 | 未注册 | FAIL_CLOSED(admin) |
| admin_*（12 项 market/publish/result/settlement/refund/correction/case/approval/async/export） | 部分服务存在(Result/Refund/Correction/Approval/Audit)，AsyncJob/Export 无服务 | 未暴露 | 未注册 | FAIL_CLOSED/N/A；admin 认证架构已裁决 OPTION_A |

## Admin V2 只读接口（本轮批量落地，OPTION_A admin 应用组 /api/v1/admin）

| 接口 | 前端 Page | Controller 方法 | 路由 | Service | 状态 |
|---|---|---|---|---|---|
| GET /api/v1/admin/admission/users | A-USER-001 | users | ✅ | AdminUserDtoService | WIRED |
| GET /api/v1/admin/otc/orders | A-OTC-001 | otcOrders | ✅ | AdminOtcDtoService | WIRED |
| GET /api/v1/admin/otc/orders/{id} | A-OTC-002 | otcOrderDetail | ✅ | OtcOrderService::detail | WIRED |
| GET /api/v1/admin/robot/list | A-ROBOT-001 | robots | ✅ | AdminRobotDtoService | WIRED |
| GET /api/v1/admin/robot/{id} | A-ROBOT-002 | robotDetail | ✅ | RobotService::detail | WIRED |
| GET /api/v1/admin/robot/rewards | A-ROBOT-003 | rewardOps | ✅ | AdminRewardDtoService | WIRED |
| GET /api/v1/admin/support/tickets | A-SUPPORT-001 | tickets | ✅ | AdminTicketDtoService | WIRED |
| GET /api/v1/admin/support/tickets/{id} | A-SUPPORT-002 | ticketDetail | ✅ | TicketService::detail | WIRED |
| GET /api/v1/admin/ledger/overview | A-LEDGER-001 | ledgerOverview | ✅ | AdminLedgerOverviewDtoService | WIRED |
| GET /api/v1/admin/ledger/accounts | A-LEDGER-002 | ledgerAccounts | ✅ | AdminLedgerDtoService | WIRED |
| GET /api/v1/admin/ledger/entries | A-LEDGER-002明细 | ledgerEntries | ✅ | AdminLedgerEntriesDtoService | WIRED |
| GET /api/v1/admin/risk/cases | A-RISK-001 | riskCases | ✅ | AdminRiskDtoService | WIRED |
| GET /api/v1/admin/approval/tasks | A-APPROVAL-001 | approvalTasks | ✅ | AdminApprovalDtoService | WIRED |
| GET /api/v1/admin/parameter/definitions | A-CONFIG-001 | parameterDefinitions | ✅ | AdminConfigDtoService | WIRED |
| GET /api/v1/admin/prediction/markets | A-PREDICT-001 | predictionMarkets | ✅ | AdminPredictionDtoService | WIRED |
| GET /api/v1/admin/power/accounts | A-POWER-001 | powerAccounts | ✅ | AdminPowerDtoService | WIRED |
| GET /api/v1/admin/audit-log | A-AUDIT-001 | auditLog | ✅ | AuditEventService::listAdmin | WIRED |
| GET /api/v1/admin/audit-log/{id} | A-AUDIT-001 | auditLogDetail | ✅ | AuditEventService::detail | WIRED |
| GET /api/v1/admin/workbench/overview | A-WORK-001 | workbenchOverview | ✅ | AdminWorkbenchDtoService | WIRED |
| GET /api/v1/admin/workbench/todo | A-WORK-002 | workbenchTodo | ✅ | AdminTodoDtoService | WIRED |
| GET /api/v1/admin/admission/users/{id} | A-USER-002 | userDetail | ✅ | AdminUserDetailDtoService | WIRED |
| GET /api/v1/admin/admission/kyc | A-KYC-001 | kycQueue | ✅ | AdminKycDtoService | WIRED |
| GET /api/v1/admin/prediction/results | A-PREDICT-003 | predictionResults | ✅ | AdminPredictionResultDtoService | WIRED |
| GET /api/v1/admin/prediction/refunds | A-PREDICT-004 | refunds | ✅ | AdminRefundDtoService | WIRED |
| GET /api/v1/admin/prediction/corrections | A-PREDICT-004 | corrections | ✅ | AdminCorrectionDtoService | WIRED |
| GET /api/v1/admin/prediction/refunds/{id} | A-PREDICT-004 | refundDetail | ✅ | RefundCaseService::detail | WIRED |
| GET /api/v1/admin/prediction/corrections/{id} | A-PREDICT-004 | correctionDetail | ✅ | CorrectionCaseService::detail | WIRED |
| GET /api/v1/admin/approval/tasks/{id} | A-APPROVAL-001 | approvalDetail | ✅ | ApprovalRequestService::detail | WIRED |
| GET /api/v1/admin/parameter/releases/{id} | A-CONFIG-002 | parameterReleaseDetail | ✅ | ParameterReleaseService::detail | WIRED |
| GET /api/v1/admin/risk/cases/{id} | A-RISK-001 | riskDetail | ✅ | RiskCaseService::detail | WIRED |
| GET /api/v1/admin/otc/users/{id}/orders | A-OTC-001 | otcUserOrders | ✅ | OtcOrderService::listByUser | WIRED |

## Admin V2 写路径（fail-closed 占位，A5403BD 起）

| 接口 | Page | Controller 方法 | 路由 | 状态 |
|---|---|---|---|---|
| POST /api/v1/admin/admission/kyc/{id}/decision | A-KYC-001 | kycDecision | ✅ | FAIL_CLOSED(POLICY_DENIED) |
| POST /api/v1/admin/otc/orders/{id}/review | A-OTC-002 | otcOrderReview | ✅ | FAIL_CLOSED(POLICY_DENIED) |
| POST /api/v1/admin/approval/tasks/{id}/decision | A-APPROVAL-001 | approvalDecision | ✅ | FAIL_CLOSED(POLICY_DENIED) |
| POST /api/v1/admin/prediction/refunds | A-PREDICT-004 | refundCreate | ✅ | FAIL_CLOSED(POLICY_DENIED) |
| POST /api/v1/admin/ledger/corrections | A-LEDGER-004 | correctionCreate | ✅ | FAIL_CLOSED(POLICY_DENIED) |
| POST /api/v1/admin/async-jobs/{id} | A-OPS-001 | asyncJob | ✅ | FAIL_CLOSED(DEPENDENCY_UNAVAILABLE) |
| POST /api/v1/admin/export-tasks | A-OPS-001 | exportTask | ✅ | FAIL_CLOSED(DEPENDENCY_UNAVAILABLE) |

> 写路径占位统一 fail-closed：admin 角色映射（sys_admin.role_id→05 13 角色）未冻结，不开放有经济/审批副作用的写路径。待映射冻结后再绑定真实 Service。

## 汇总

```text
operationId 总数 = 81（Admin 域）+ api_v2 50 条（C 端 read + Auth/KYC/User）
已接线(WIRED)     = api_v2 50 + admin_v2 54（48 只读 + 5 写占位 + async/export 占位）≈ 104 路由已注册
写路径 FAIL_CLOSED = Admin V2 5 写操作 + async/export 占位已注册路由；服务层未绑定（角色映射冻结后接线）
前端对接         = admin-v2.ts + pageData.ts 19 权威页真实数据（vite build 通过）
池子对账/策略     = 无冻结 service，保持 fail-closed（不猜测建表）
```

## 未运行项（如实声明）

- 真实 HTTP 请求/E2E：NOT_RUN（无 runnable 服务实例；sys_route seed 需 DB 应用）
- Admin V2 写路径集成测试：NOT_RUN（控制器 fail-closed 占位已建；真实写需 admin 角色映射冻结）
- OpenAPI lint 工具（parse/ref/operationId 唯一性）：NOT_RUN（本环境无独立 lint 脚本）
- 前端 33 权威 Admin 页 DTO 对接：PARTIAL（19 页已接，其余待后端接口/前端同事）
