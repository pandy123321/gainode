# STAGE-01 对象覆盖矩阵（Object Coverage Matrix）

> 状态：**CANDIDATE（待 Quality 核对）** — 本文件由 Development Agent 生成，供 Quality Agent 执行 STAGE-01 Gate 时核对。
> 起草日期：2026-08-16
> 权威契约：01/02/05/06/07 + 各 Machine Contract Freeze 文档
> 关联包：S01-P01（MC2）~ S01-P08（AI Ops）

## 1. 概览

```text
TOTAL_OBJECTS            = 43
PERSISTENT（有 DDL）     = 30（MC1 8 + MC2 audit_events 1 + 2B-1 8 + 2B-2 13）
NOT_PERSISTED（无表）    = 7（S01-P06 投影）
CONTRACT_INVENTORY_ONLY  = 6（S01-P07 Affiliate 3 + S01-P08 AI Ops 3，未建表）
```

状态分布：

```text
FROZEN（Owner 签 + IR 通过）    = 9（MC1 8 + audit_events）
CANDIDATE（未 FROZEN）          = 21（2B-1 8 + 2B-2 13）
NOT_PERSISTED（禁止建表）       = 7
CONTRACT_GAP（Owner 未签）      = 6
```

## 2. 矩阵 A — 持久对象（30 表）

### MC1（FROZEN，8 表）

| 对象 | 表 | Model | Service | Freeze | 状态 |
|---|---|---|---|---|---|
| APT Account | `apt_accounts` | AptAccountModel | AptAccountService | MC1 §3 | FROZEN |
| APT Ledger Entry | `apt_ledger_entries` | AptLedgerEntryModel | LedgerService | MC1 §3.6 | FROZEN |
| Robot | `robots` | RobotModel | RobotService | MC1 §3 | FROZEN |
| AI Reward | `robot_rewards` | RobotRewardModel | RobotRewardService | MC1 §3 | FROZEN |
| Prediction Market | `prediction_markets` | PredictionMarketModel | PredictionMarketService | MC1 §3 | FROZEN |
| Prediction Order | `prediction_orders` | PredictionOrderModel | PredictionOrderService | MC1 §3 | FROZEN |
| OTC Order | `otc_orders` | OtcOrderModel | OtcOrderService | MC1 §3 | FROZEN |
| Power Position | `power_positions` | PowerPositionModel | PowerPositionService | MC1 §3.9 | FROZEN |

### MC2 补充（FROZEN，1 表）

| 对象 | 表 | Model | Service | Freeze | 状态 |
|---|---|---|---|---|---|
| Audit Event | `audit_events` | AuditEventModel | AuditEventService | MC2 §6 | FROZEN |

### 2B-1（CANDIDATE，8 表）

| 对象 | 表 | Model | Service | Freeze | 状态 |
|---|---|---|---|---|---|
| Result | `results` | ResultModel | ResultService | 2B-1 Part A | CANDIDATE |
| Settlement | `settlements` | SettlementModel | SettlementService | 2B-1 Part B | CANDIDATE |
| Settlement Batch | `settlement_batches` | SettlementBatchModel | SettlementBatchService | 2B-1 D.1 | CANDIDATE |
| Refund Case | `refund_cases` | RefundCaseModel | RefundCaseService | 2B-1 D.2 | CANDIDATE |
| Correction Case | `correction_cases` | CorrectionCaseModel | CorrectionCaseService | 2B-1 D.3 | CANDIDATE |
| OTC Trade | `otc_trades` | OtcTradeModel | OtcTradeService | 2B-1 D.4 | CANDIDATE |
| Robot Upgrade Order | `robot_upgrade_orders` | RobotUpgradeOrderModel | RobotUpgradeOrderService | 2B-1 D.5 | CANDIDATE |
| Consent Receipt | `consent_receipts` | ConsentReceiptModel | ConsentReceiptService | 2B-1 D.6 | CANDIDATE |

### 2B-2（CANDIDATE，13 表）

| 对象 | 表 | Model | Service | Freeze | 状态 |
|---|---|---|---|---|---|
| Approval Request | `approval_requests` | ApprovalRequestModel | ApprovalRequestService | 2B-2 §3 | CANDIDATE |
| Parameter Release | `parameter_releases` | ParameterReleaseModel | ParameterReleaseService | 2B-2 §4 | CANDIDATE |
| Parameter Snapshot | `parameter_snapshots` | ParameterSnapshotModel | ParameterSnapshotService | 2B-2 §9 | CANDIDATE |
| Notice | `notices` | NoticeModel | NoticeService | 2B-2 §9 | CANDIDATE |
| Notification Delivery | `notification_deliveries` | NotificationDeliveryModel | NotificationDeliveryService | 2B-2 §8.1 | CANDIDATE |
| Auth Session | `auth_sessions` | AuthSessionModel | AuthSessionService | 2B-2 §5 | CANDIDATE |
| MFA Enrollment | `mfa_enrollments` | MfaEnrollmentModel | MfaEnrollmentService | 2B-2 §8.2 | CANDIDATE |
| KYC Case | `kyc_cases` | KycCaseModel | KycCaseService | 2B-2 §6 | CANDIDATE |
| Risk Case | `risk_cases` | RiskCaseModel | RiskCaseService | 2B-2 §8.3 | CANDIDATE |
| Ticket | `tickets` | TicketModel | TicketService | 2B-2 §7 | CANDIDATE |
| Ticket Message | `ticket_messages` | TicketMessageModel | TicketMessageService | 2B-2 §9 | CANDIDATE |
| Ticket Attachment | `ticket_attachments` | TicketAttachmentModel | TicketAttachmentService | 2B-2 §9 | CANDIDATE |
| Settlement Method | `settlement_methods` | SettlementMethodModel | SettlementMethodService | 2B-2 §9 | CANDIDATE |

## 3. 矩阵 B — NOT_PERSISTED 投影（7，无表）

| 对象 | 表 | Response DTO | Projection Service | 依赖 | 状态 |
|---|---|---|---|---|---|
| FeatureEntitlement | 无 | FeatureEntitlementResponse | FeatureEntitlementProjectionService | 06 Feature（TBC） | NOT_PERSISTED |
| OtcEligibility | 无 | OtcEligibilityResponse | OtcEligibilityProjectionService | kyc_cases + 06 OTC（TBC） | NOT_PERSISTED |
| OtcCapacity | 无 | OtcCapacityResponse | OtcCapacityProjectionService | 06 OTC（TBC） | NOT_PERSISTED |
| PowerImpactPreview | 无 | PowerImpactPreviewResponse | PowerImpactPreviewProjectionService | power_positions + 06 Power（TBC） | NOT_PERSISTED |
| SecurityProfile | 无 | SecurityProfileResponse | SecurityProfileProjectionService | mfa_enrollments + 06 安全（TBC） | NOT_PERSISTED |
| SessionDevice | 无 | SessionDeviceResponse | SessionDeviceProjectionService | auth_sessions.device_info | NOT_PERSISTED |
| LoginAudit | 无 | LoginAuditResponse | LoginAuditProjectionService | Contract Gap G1 | NOT_PERSISTED |

## 4. 矩阵 C — 合同盘点未建表（6，CONTRACT_GAP）

| 对象 | 表 | Freeze | Owner 决策 | 状态 |
|---|---|---|---|---|
| Agent | 无 | S01-P07 Freeze（候选） | D1~D11 | CONTRACT_GAP |
| Referral | 无 | S01-P07 Freeze（候选） | D1~D11 | CONTRACT_GAP |
| AgentEarning | 无 | S01-P07 Freeze（候选） | D1~D11 | CONTRACT_GAP |
| AISignal | 无 | S01-P08 Freeze（候选） | D1~D9 + D10 LOCKED | CONTRACT_GAP |
| AIRecommendation | 无 | S01-P08 Freeze（候选） | D1~D9 | CONTRACT_GAP |
| SimulationRun | 无 | S01-P08 Freeze（候选） | D1~D9 | CONTRACT_GAP |

## 5. 机械比对摘要

```text
PERSISTENT_OBJECT_COUNT            = 30
DDL_FILE_COUNT                     = 5（batch1 / batch2_audit / batch2_ledger_ov / batch2b1 / batch2b2）
MODEL_FILE_COUNT                   = 30（+6 AppendOnlyBuilder）
SERVICE_FILE_COUNT（持久）         = 30
FORWARD_ONLY_DDL                   = 30/30（每对象一份 DDL，无重复表）
AUTHORITATIVE_WRITER               = 30/30（每对象一个 Service）
NOT_PERSISTED_TABLE_LEAK           = 0（7 投影无表）
CONTRACT_GAP_TABLE_LEAK            = 0（6 未建表对象无表）
APPEND_ONLY_OBJECTS                = 6（audit_events/apt_ledger_entries/otc_trades/parameter_snapshots/ticket_messages/ticket_attachments）
SNOWFLAKE_PK                       = 30/30（bigint unsigned，$incrementing=false）
OBJECT_VERSION                     = 30/30（乐观锁）
IDEMPOTENCY_KEY                    = 29/30（NotificationDelivery 用 dedupe_key）
DECIMAL_STRING                     = 金额/数量字段 decimal，无 float
```

## 6. fail-closed 状态检查

```text
2B-1/2B-2 转移矩阵未 FROZEN          = YES（CANDIDATE，FAIL_CLOSED）
S01-P07 Affiliate Owner 未签         = YES（CONTRACT_GAP，不建表）
S01-P08 AI Ops Owner 未签            = YES（CONTRACT_GAP，不建表）
P0 增长奖励写路径                    = CLOSED（fail-closed）
C 端内部套利泄露                     = FORBIDDEN（D10 LOCKED）
AI/Prediction 预算隔离               = FORBIDDEN（02 §11）
APT-C/Migration                      = CLOSED（06 §AI.apt_migration_enabled=false）
生产参数 TBC                         = YES（06 未批准保持 null/closed）
```

## 7. 结论

```text
OBJECT_COVERAGE         = 43/43（100%）
DUPLICATE_DDL           = 0
UNKNOWN_WRITER          = 0
NOT_PERSISTED_TABLE     = 0
CONTRACT_GAP_TABLE      = 0
MC1_REDO                = 0
UNFROZEN_WRITE_PATH     = 21（2B-1 8 + 2B-2 13，FAIL_CLOSED）
PRODUCTION              = NO-GO
```

> 本矩阵为 Development Agent 自检产物。最终 STAGE-01 Gate 由 Quality Agent 独立核对（S01-P09 步骤 6-7）后输出 `STAGE-01-QUALITY-GATE.md`。
