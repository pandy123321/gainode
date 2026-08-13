-- =============================================================================
-- Machine Contract 第一批 — 8 核心实体 DDL（Canonical State Freeze CANDIDATE）
-- =============================================================================
-- 变更原因：
--   STAGE-00 已完成并通过 Independent Review（GAINODE-STAGE00-IR-20260812-002，
--   VERDICT=CONDITIONAL_APPROVAL）。按 P1-003「两阶段冻结」决策，STAGE-01 开工前
--   必须先完成 Machine Contract 第一批：8 个核心实体的 DB DDL + Canonical State Freeze。
--
-- 影响范围：
--   本文件在默认主库 `webman` 中创建 8 张 V2.0 核心实体表（全新表，不触碰任何 V1.x 表）。
--   - apt_accounts / apt_ledger_entries：APT 数量账（append-only，四账分离中的「数量账」）
--   - robots / robot_rewards：Robot 56 级 + AI Reward 状态机
--   - prediction_markets / prediction_orders：Prediction 市场 + 订单状态机
--   - otc_orders：OTC 订单状态机
--   - power_positions：Power 持仓（scalar fields，无状态机）
--
-- 权威契约：
--   Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md
--   §3（对象最低字段）+ §4（统一状态机）。所有领域状态枚举严格取自 05 §4，禁止自创。
--
-- 与 V1.x 表的关键差异（设计决策，详见 MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md）：
--   1. 主键：Snowflake ID（bigint unsigned），不使用 AUTO_INCREMENT（V1.x 为自增 id）。
--   2. 领域状态：ENUM 冻结（canonical enum），不使用 V1.x 的 status tinyint 软删模式。
--   3. 金额：decimal(36,18) string decimal，禁 float；精度待生产参数批准后可收窄。
--   4. 账本 append-only：apt_ledger_entries 无 updated_time；经济字段不可改，仅 state 流转，
--      更正通过 reversal_of 追加反向分录（不删不覆盖原文）。
--   5. 时间戳：created_time / updated_time 为 Unix 秒（int unsigned），沿用 V1.x 约定。
--
-- 执行方式：forward-only migration（一次性建表，禁止内置 DROP/CREATE）。
--   - 首次执行创建 8 张新表。
--   - 若目标表已存在则失败（fail-fast），绝不删除已有数据。
--   - 重跑判定应通过 information_schema / migration version 记录「已应用」，而非重建。
--   - 回滚（如确需）走独立 HIGH_RISK 回滚文件 + Database Migration Gate；禁止自动回滚账本历史。
-- 迁移阶段：阶段一（sql/YYYYMMDD_description.sql），后续 DDL 变更累计超 10 次后转 Phinx。
-- =============================================================================

SET NAMES utf8mb4;

-- =============================================================================
-- 1. apt_accounts — APT 数量账主账号表（05 §3 AptAccount，无状态机）
-- =============================================================================
CREATE TABLE `apt_accounts` (
  `account_id` bigint unsigned NOT NULL COMMENT '账号ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID(引用 member_user.id，V2.0 拟加宽)',
  `balance_apt_i` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT 'APT-I 可用余额',
  `balance_apt_c` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT 'APT-C 可用余额(Future，余额结构预留，不代表开通 APT-C 记账能力)',
  `frozen_apt_i` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT 'APT-I 冻结余额',
  `frozen_apt_c` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT 'APT-C 冻结余额(Future，同上)',
  `total_earned_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '历史累计获得 APT 总额',
  `total_spent_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '历史累计支出 APT 总额',
  `last_ledger_entry_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '最近一笔账本分录ID(apt_ledger_entries.ledger_entry_id)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁，每次更新+1)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uk_user` (`user_id`),
  KEY `idx_last_ledger` (`last_ledger_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='APT数量账主账号表(四账分离之数量账，余额+冻结)';

-- =============================================================================
-- 2. apt_ledger_entries — APT 账本分录（append-only，05 §3 AptLedgerEntry）
-- =============================================================================
CREATE TABLE `apt_ledger_entries` (
  `ledger_entry_id` bigint unsigned NOT NULL COMMENT '分录ID(Snowflake，主键)',
  `account_id` bigint unsigned NOT NULL COMMENT '账号ID(apt_accounts.account_id)',
  `asset` enum('APT-I') NOT NULL DEFAULT 'APT-I' COMMENT '资产类型: 仅 APT-I(Internal)。APT-C=Future/OUT_OF_SCOPE，禁止入账，须经正式 Product/Contract Change 后方可扩展',
  `quantity` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '变动数量(正数，方向见 entry_direction)',
  `entry_direction` tinyint NOT NULL DEFAULT '1' COMMENT '分录方向: 1=入账(CREDIT) -1=出账(DEBIT)',
  `entry_type` varchar(64) NOT NULL DEFAULT '' COMMENT '分录类型(业务事件码，与 Event Catalog 对齐，TBC)',
  `state` enum('pending','posted','reversed','disputed') NOT NULL DEFAULT 'pending' COMMENT '分录状态(05 §4 canonical，唯一可变列，仅 Authoritative Writer 流转)',
  `source_object_type` varchar(64) NOT NULL DEFAULT '' COMMENT '来源对象类型(如 robot_reward/prediction_order/otc_order)',
  `source_object_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '来源对象ID',
  `journal_batch_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '日记账批次ID(同批次多分录)',
  `reversal_of` bigint unsigned NOT NULL DEFAULT '0' COMMENT '冲正引用：指向被冲正的原分录 ledger_entry_id(0=非冲正)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(写操作去重)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID(state 流转的证据)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  PRIMARY KEY (`ledger_entry_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_account` (`account_id`),
  KEY `idx_state` (`state`),
  KEY `idx_source` (`source_object_type`,`source_object_id`),
  KEY `idx_batch` (`journal_batch_id`),
  KEY `idx_reversal` (`reversal_of`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='APT账本分录(append-only：经济字段不可UPDATE/DELETE，仅 state 流转，更正走 reversal_of 追加，不删原文)';

-- =============================================================================
-- 3. robots — Robot 主表（56 级，05 §3 Robot + §4 Robot 状态机）
-- =============================================================================
CREATE TABLE `robots` (
  `robot_id` bigint unsigned NOT NULL COMMENT 'Robot ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID(引用 member_user.id)',
  `level` int unsigned NOT NULL DEFAULT '1' COMMENT 'Robot 等级(1-56)',
  `status` enum('inactive','active','cooling','review','restricted','paused') NOT NULL DEFAULT 'inactive' COMMENT 'Robot 状态(05 §4 canonical)',
  `standard_capacity` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '等级对应分配容量(权重，APT 数量维度)',
  `capabilities` json DEFAULT NULL COMMENT '能力列表(JSON 数组，服务端下发)',
  `allowed_actions` json DEFAULT NULL COMMENT '允许动作列表(JSON 数组，服务端下发，前端只读)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(创建去重)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `parameter_release_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '参数发布版本ID',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`robot_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Robot主表(56级AI代理，状态机 inactive/active/cooling/review/restricted/paused)';

-- =============================================================================
-- 4. robot_rewards — AI Reward 记录（05 §3 AIReward + §4 AI Reward 状态机）
-- =============================================================================
CREATE TABLE `robot_rewards` (
  `reward_id` bigint unsigned NOT NULL COMMENT 'Reward ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `robot_id` bigint unsigned NOT NULL COMMENT 'Robot ID(robots.robot_id)',
  `period` varchar(32) NOT NULL DEFAULT '' COMMENT '结算周期标识(日期/周期键)',
  `standard_capacity` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '当期分配容量快照',
  `daily_reward_coefficient` decimal(18,8) NOT NULL DEFAULT '0.00000000' COMMENT '当天服务端系数(可为0)',
  `quantity_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '待领取/已发放 APT 数量(standard_capacity × daily_reward_coefficient)',
  `state` enum('candidate','held','pending_claim','claiming','claimed','expired_returned','review','reversed') NOT NULL DEFAULT 'candidate' COMMENT 'Reward 状态(05 §4 canonical，8态)',
  `eligibility_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '资格快照ID',
  `budget_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '预算快照ID',
  `claim_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '领取记录ID(claimed 后回填)',
  `ledger_entry_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联账本分录ID(held/posted 后回填)',
  `expires_at` int unsigned NOT NULL DEFAULT '0' COMMENT '领取窗口过期时间(Unix秒)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(生成去重)',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`reward_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_robot` (`robot_id`),
  KEY `idx_state` (`state`),
  KEY `idx_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI Reward记录(动态奖励，8态状态机)';

-- =============================================================================
-- 5. prediction_markets — 预测市场（05 §3 Market + §4 Market 状态机）
-- =============================================================================
CREATE TABLE `prediction_markets` (
  `market_id` bigint unsigned NOT NULL COMMENT '市场ID(Snowflake，主键)',
  `event_id` bigint unsigned NOT NULL COMMENT '赛事ID(引用 Fixture)',
  `template_id` varchar(32) NOT NULL DEFAULT 'FOOTBALL_PREMATCH_1X2' COMMENT '市场模板(05: FOOTBALL_PREMATCH_1X2)',
  `market_status` enum('draft','open','closing','locked','awaiting_result','settlement','settled','void','exception') NOT NULL DEFAULT 'draft' COMMENT '市场状态(05 §4 canonical，9态)',
  `lock_at` int unsigned NOT NULL DEFAULT '0' COMMENT '锁定时间戳(Unix秒)',
  `selections` json DEFAULT NULL COMMENT '选项定义 JSON(05: [HOME,DRAW,AWAY])',
  `liquidity_summary` json DEFAULT NULL COMMENT '流动性汇总 JSON(服务端计算)',
  `result_status` varchar(32) DEFAULT NULL COMMENT '赛果状态投影(Result.status canonical: provisional/official/disputed/corrected，独立Result对象DDL后续建立)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(创建去重)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `parameter_release_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '参数发布版本ID',
  `policy_version` varchar(64) NOT NULL DEFAULT '' COMMENT '策略版本号',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`market_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_event` (`event_id`),
  KEY `idx_market_status` (`market_status`),
  KEY `idx_lock_at` (`lock_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='预测市场(P0足球赛前1X2，9态状态机)';

-- =============================================================================
-- 6. prediction_orders — 预测订单（05 §3 PredictionOrder + §4 Prediction Order 状态机）
-- =============================================================================
CREATE TABLE `prediction_orders` (
  `order_id` bigint unsigned NOT NULL COMMENT '订单ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `market_id` bigint unsigned NOT NULL COMMENT '市场ID(prediction_markets.market_id)',
  `selection` enum('HOME','DRAW','AWAY') NOT NULL COMMENT '投注选项(1X2: HOME/DRAW/AWAY)',
  `amount_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '参与数量 APT',
  `order_status` enum('submitted','locked','awaiting_result','settling','settled','refunding','refunded','correcting','corrected') NOT NULL DEFAULT 'submitted' COMMENT '订单状态(05 §4 canonical，9态)',
  `asset_status` varchar(32) DEFAULT NULL COMMENT '资产状态(05 §4 未定义，TBC，待 Contract Freeze)',
  `risk_status` varchar(32) DEFAULT NULL COMMENT '风险状态(05 §4 未定义，TBC，待 Contract Freeze)',
  `consent_receipt_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '同意确认回执ID(consent_receipts.receipt_id)',
  `submit_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '提交时参数快照ID',
  `parameter_release_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '参数发布版本ID',
  `policy_version` varchar(64) NOT NULL DEFAULT '' COMMENT '策略版本号',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(下单去重)',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_market` (`market_id`),
  KEY `idx_order_status` (`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='预测订单(P0足球1X2，9态状态机，含纠错状态流)';

-- =============================================================================
-- 7. otc_orders — OTC 订单（05 §3 OtcOrder + §4 OTC 状态机）
-- =============================================================================
CREATE TABLE `otc_orders` (
  `otc_order_id` bigint unsigned NOT NULL COMMENT 'OTC订单ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `side` enum('BUY','SELL') NOT NULL COMMENT '方向: BUY=买入 SELL=卖出',
  `price` decimal(18,8) NOT NULL DEFAULT '0.00000000' COMMENT '价格(每 APT 单位，string decimal)',
  `quantity_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '挂单数量 APT',
  `filled_quantity_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '已成交数量 APT',
  `remaining_quantity_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '剩余数量 APT',
  `fee_apt` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '手续费 APT',
  `power_required` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '所需 Power(Preview 下发)',
  `power_consumed` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '已消耗 Power',
  `power_frozen` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '冻结 Power',
  `status` enum('draft','review','matching','partial','completed','cancelled','expired','rejected','disputed') NOT NULL DEFAULT 'draft' COMMENT 'OTC订单状态(05 §4 canonical，9态)',
  `review_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需审核(0=否 1=是)',
  `quote_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '报价ID(关联报价快照)',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `parameter_release_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '参数发布版本ID',
  `policy_version` varchar(64) NOT NULL DEFAULT '' COMMENT '策略版本号',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键(挂单去重)',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`otc_order_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_side` (`side`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OTC订单(点对点撮合，9态状态机)';

-- =============================================================================
-- 8. power_positions — Power 持仓（05 §3 PowerPosition，scalar fields，无状态机）
-- =============================================================================
CREATE TABLE `power_positions` (
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID(主键，一用户一持仓)',
  `available` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '可用 Power',
  `frozen` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '冻结 Power',
  `consumed_period` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '本周期已消耗 Power',
  `released_period` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '本周期已释放 Power',
  `recovering` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '恢复中 Power',
  `limit` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT 'Power 上限/Cap(注意: limit 为 MySQL 保留字，须反引号)',
  `power_cap_source_robot_level` int unsigned NOT NULL DEFAULT '0' COMMENT 'Power Cap 来源 Robot 等级',
  `last_restore_at` int unsigned NOT NULL DEFAULT '0' COMMENT '上次恢复时间(Unix秒)',
  `next_restore_at` int unsigned NOT NULL DEFAULT '0' COMMENT '下次恢复时间(Unix秒)',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '生效规则版本号',
  `parameter_release_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '参数发布版本ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`user_id`),
  KEY `idx_next_restore` (`next_restore_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Power持仓(可消耗可恢复操作资源，scalar fields，无状态机)';
