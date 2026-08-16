-- =============================================================================
-- Machine Contract 第二批 2B-1 — 8 个非核心实体 DDL（State Contract Freeze CANDIDATE）
-- =============================================================================
-- 变更原因：
--   MC1 冻结了 8 个核心实体 DDL；MC2 冻结了状态转移矩阵 + audit_events DDL +
--   ledger object_version 补列。本文件落 2B-1 小批的 8 个非核心实体表 DDL
--   （S01-P03），对应 S01-P02 已 Owner 裁决并补入 05 §4（V2.3）的 canonical enum。
--
-- 影响范围：
--   本文件在默认主库 `webman` 中创建 8 张 V2.0 非核心实体表（全新表，不触碰任何 V1.x 表）：
--   - results / settlements：Result + Settlement 工作流对象（enum 复制 05 §4）
--   - settlement_batches / refund_cases / correction_cases：结算批 / 退款 / 纠错工作流
--   - otc_trades：OTC 成交事实（append-only 单态）
--   - robot_upgrade_orders：Robot 升级订单工作流
--   - consent_receipts：同意回执（两态）
--   audit_events 复用 MC2 `20260815_machine_contract_batch2_audit_events.sql`，
--   本文件不重复 CREATE TABLE。
--
-- 权威契约：
--   Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md
--   §3（对象最低字段）+ §4（统一状态机，V2.3）+ §8（RBAC）。
--   领域状态枚举严格取自 05 §4 V2.3，禁止自创。
--
-- 与 V1.x 表的关键差异（同 MC1/MC2 约定）：
--   1. 主键：Snowflake ID（bigint unsigned），不使用 AUTO_INCREMENT。
--   2. 领域状态：ENUM 冻结（canonical enum），不使用 V1.x 的 status tinyint 软删模式。
--   3. 金额：decimal(36,18)（APT 数量）/ decimal(18,8)（price）/ decimal(18,4)（Power）；禁 float。
--   4. 时间戳：created_time / updated_time 为 Unix 秒（int unsigned）。
--   5. append-only：otc_trades 无 updated_time；成交事实一经写入永不覆盖/删除。
--
-- 执行方式：forward-only migration（一次性建表，禁止内置 DROP/CREATE）。
--   - 首次执行创建 8 张新表；若目标表已存在则失败（fail-fast），绝不删除已有数据。
--   - 重跑判定应通过 information_schema / migration version 记录「已应用」，而非重建。
-- 迁移阶段：阶段一（sql/YYYYMMDD_description.sql）。
-- =============================================================================

SET NAMES utf8mb4;

-- =============================================================================
-- 1. results — 预测结果（05 §3 Result + §4 Result 状态机）
-- =============================================================================
CREATE TABLE `results` (
  `result_id` bigint unsigned NOT NULL COMMENT '结果ID(Snowflake，主键)',
  `market_id` bigint unsigned NOT NULL COMMENT '市场ID(prediction_markets.market_id)',
  `event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '赛事事件ID',
  `scores` json DEFAULT NULL COMMENT '比分(JSON，如 {"home":2,"away":1})',
  `outcome` enum('HOME','DRAW','AWAY') NOT NULL DEFAULT 'HOME' COMMENT '结果(1X2，与 PredictionOrder.selection 对齐)',
  `status` enum('provisional','official','disputed','corrected') NOT NULL DEFAULT 'provisional' COMMENT '结果状态(05 §4 canonical)',
  `confirmed_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '确认人 user_id',
  `confirmed_at` int unsigned NOT NULL DEFAULT '0' COMMENT '确认时间(Unix秒)',
  `evidence_ids` json DEFAULT NULL COMMENT '证据ID列表(JSON 数组)',
  `dispute_reason_code` varchar(64) NOT NULL DEFAULT '' COMMENT '争议原因码',
  `correction_version` int unsigned NOT NULL DEFAULT '0' COMMENT '纠错版本号(仅一次，MC2 Owner 裁决 #11)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(确认去重)',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`result_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_market` (`market_id`),
  KEY `idx_status` (`status`),
  KEY `idx_event` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='预测结果(状态机 provisional/official/disputed/corrected)';

-- =============================================================================
-- 2. settlements — 结算单（05 §3 Settlement + §4 Settlement 状态机）
-- =============================================================================
CREATE TABLE `settlements` (
  `settlement_id` bigint unsigned NOT NULL COMMENT '结算单ID(Snowflake，主键)',
  `market_id` bigint unsigned NOT NULL COMMENT '市场ID(prediction_markets.market_id)',
  `batch_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '结算批ID(settlement_batches.batch_id)',
  `status` enum('queued','calculating','review','payable','paid','failed') NOT NULL DEFAULT 'queued' COMMENT '结算状态(05 §4 canonical)',
  `principal_total_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '本金总额 APT',
  `reward_total_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '盈利总额 APT',
  `service_fee_total_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '服务费总额 APT',
  `ledger_batch_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联账本批次ID',
  `approved_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '批准人 user_id',
  `executed_at` int unsigned NOT NULL DEFAULT '0' COMMENT '执行时间(Unix秒)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `parameter_release_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '参数发布版本ID',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`settlement_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_market` (`market_id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结算单(状态机 queued/calculating/review/payable/paid/failed)';

-- =============================================================================
-- 3. settlement_batches — 结算批（05 §3 SettlementBatch）
-- =============================================================================
CREATE TABLE `settlement_batches` (
  `batch_id` bigint unsigned NOT NULL COMMENT '结算批ID(Snowflake，主键)',
  `status` enum('created','processing','completed','partially_failed','failed') NOT NULL DEFAULT 'created' COMMENT '结算批状态(05 §4 V2.3)',
  `market_count` int unsigned NOT NULL DEFAULT '0' COMMENT '市场数量',
  `order_count` int unsigned NOT NULL DEFAULT '0' COMMENT '订单数量',
  `settlement_ids` json DEFAULT NULL COMMENT '结算单ID列表(JSON 数组)',
  `total_principal_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '本金总额 APT',
  `total_reward_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '盈利总额 APT',
  `total_service_fee_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '服务费总额 APT',
  `executed_at` int unsigned NOT NULL DEFAULT '0' COMMENT '执行时间(Unix秒)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`batch_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结算批(状态机 created/processing/completed/partially_failed/failed)';

-- =============================================================================
-- 4. refund_cases — 退款案件（05 §3 RefundCase）
-- =============================================================================
CREATE TABLE `refund_cases` (
  `refund_id` bigint unsigned NOT NULL COMMENT '退款案件ID(Snowflake，主键)',
  `market_id` bigint unsigned NOT NULL COMMENT '市场ID(prediction_markets.market_id)',
  `batch_size` int unsigned NOT NULL DEFAULT '0' COMMENT '批次订单数',
  `principal_total_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '退款本金总额 APT',
  `service_fee_total_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '退款服务费总额 APT',
  `status` enum('pending','approved','executing','completed','rejected','failed') NOT NULL DEFAULT 'pending' COMMENT '退款状态(05 §4 V2.3)',
  `approved_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '批准人 user_id',
  `executed_at` int unsigned NOT NULL DEFAULT '0' COMMENT '执行时间(Unix秒)',
  `ledger_batch_ids` json DEFAULT NULL COMMENT '账本批次ID列表(JSON 数组)',
  `reason_code` varchar(64) NOT NULL DEFAULT '' COMMENT '退款原因码',
  `case_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联案件ID',
  `approval_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审批ID',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`refund_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_market` (`market_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='退款案件(状态机 pending/approved/executing/completed/rejected/failed)';

-- =============================================================================
-- 5. correction_cases — 纠错案件（05 §3 CorrectionCase）
-- =============================================================================
CREATE TABLE `correction_cases` (
  `correction_id` bigint unsigned NOT NULL COMMENT '纠错案件ID(Snowflake，主键)',
  `market_id` bigint unsigned NOT NULL COMMENT '市场ID(prediction_markets.market_id)',
  `result_id_old` bigint unsigned NOT NULL DEFAULT '0' COMMENT '旧结果ID(results.result_id)',
  `result_id_new` bigint unsigned NOT NULL DEFAULT '0' COMMENT '新结果ID(results.result_id)',
  `settlement_ids_old` json DEFAULT NULL COMMENT '旧结算单ID列表(JSON 数组)',
  `settlement_ids_new` json DEFAULT NULL COMMENT '新结算单ID列表(JSON 数组)',
  `status` enum('pending','approved','executing','completed','rejected','failed') NOT NULL DEFAULT 'pending' COMMENT '纠错状态(05 §4 V2.3)',
  `approved_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '批准人 user_id',
  `executed_at` int unsigned NOT NULL DEFAULT '0' COMMENT '执行时间(Unix秒)',
  `ledger_reversal_ids` json DEFAULT NULL COMMENT '冲正分录ID列表(JSON 数组)',
  `ledger_new_ids` json DEFAULT NULL COMMENT '新增分录ID列表(JSON 数组)',
  `case_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联案件ID',
  `approval_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审批ID',
  `evidence_ids` json DEFAULT NULL COMMENT '证据ID列表(JSON 数组)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`correction_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_market` (`market_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='纠错案件(状态机 pending/approved/executing/completed/rejected/failed)';

-- =============================================================================
-- 6. otc_trades — OTC 成交事实（05 §3 OtcTrade，append-only 单态）
-- =============================================================================
CREATE TABLE `otc_trades` (
  `trade_id` bigint unsigned NOT NULL COMMENT '成交ID(Snowflake，主键)',
  `otc_order_id` bigint unsigned NOT NULL COMMENT '订单ID(otc_orders.otc_order_id)',
  `buyer_user_id` bigint unsigned NOT NULL COMMENT '买方用户ID',
  `seller_user_id` bigint unsigned NOT NULL COMMENT '卖方用户ID',
  `quantity_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '成交数量 APT',
  `price_apt` decimal(18,8) NOT NULL DEFAULT '0.00000000' COMMENT '成交价格 APT',
  `fee_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '手续费 APT',
  `power_consumed` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '消耗 Power',
  `status` enum('completed') NOT NULL DEFAULT 'completed' COMMENT '成交状态(单态，append-only 成交事实)',
  `ledger_entry_ids` json DEFAULT NULL COMMENT '账本分录ID列表(JSON 数组)',
  `ledger_batch_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '账本批次ID',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  PRIMARY KEY (`trade_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_order` (`otc_order_id`),
  KEY `idx_buyer` (`buyer_user_id`),
  KEY `idx_seller` (`seller_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OTC成交事实(append-only 单态 completed，无UPDATE/DELETE)';

-- =============================================================================
-- 7. robot_upgrade_orders — Robot 升级订单（05 §3 RobotUpgradeOrder）
-- =============================================================================
CREATE TABLE `robot_upgrade_orders` (
  `upgrade_order_id` bigint unsigned NOT NULL COMMENT '升级订单ID(Snowflake，主键)',
  `robot_id` bigint unsigned NOT NULL COMMENT 'Robot ID(robots.robot_id)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `from_level` int unsigned NOT NULL DEFAULT '0' COMMENT '原等级',
  `to_level` int unsigned NOT NULL DEFAULT '0' COMMENT '目标等级',
  `apt_cost` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '升级花费 APT',
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '升级状态(05 §4 V2.3)',
  `power_cap_after` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '升级后 Power 上限',
  `capacities_after` json DEFAULT NULL COMMENT '升级后能力列表(JSON)',
  `cooling_end_at` int unsigned NOT NULL DEFAULT '0' COMMENT '冷却结束时间(Unix秒)',
  `review_case_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '复核案件ID',
  `approval_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审批ID',
  `ledger_entry_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联账本分录ID',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `parameter_release_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '参数发布版本ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`upgrade_order_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_robot` (`robot_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Robot升级订单(状态机 pending/processing/completed/failed/cancelled)';

-- =============================================================================
-- 8. consent_receipts — 同意回执（05 §3 ConsentReceipt）
-- =============================================================================
CREATE TABLE `consent_receipts` (
  `receipt_id` bigint unsigned NOT NULL COMMENT '回执ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `consent_type` varchar(32) NOT NULL DEFAULT '' COMMENT '同意类型',
  `consent_version` varchar(32) NOT NULL DEFAULT '' COMMENT '同意版本',
  `content_hash` varchar(64) NOT NULL DEFAULT '' COMMENT '同意内容哈希',
  `status` enum('active','expired') NOT NULL DEFAULT 'active' COMMENT '回执状态(05 §4 V2.3，两态)',
  `agreed_at` int unsigned NOT NULL DEFAULT '0' COMMENT '同意时间(Unix秒)',
  `expires_at` int unsigned NOT NULL DEFAULT '0' COMMENT '过期时间(Unix秒)',
  `policy_version` varchar(64) NOT NULL DEFAULT '' COMMENT '策略版本号',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(consent_type+consent_version 去重)',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`receipt_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='同意回执(状态机 active/expired 两态)';
