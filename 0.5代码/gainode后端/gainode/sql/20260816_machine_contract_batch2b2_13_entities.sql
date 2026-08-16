-- =============================================================================
-- Machine Contract 第二批 2B-2 — 13 个非核心实体 DDL（State Contract Freeze CANDIDATE）
-- =============================================================================
-- 变更原因：
--   MC1 冻结 8 核心实体 DDL；MC2 冻结状态转移矩阵 + audit_events + ledger object_version；
--   2B-1（S01-P03）落 8 非核心实体 DDL。本文件落 2B-2 小批的 13 个 P1/P2 实体表 DDL
--   （S01-P05），对应 S01-P04 已 Owner 裁决并补入 05 §4（V2.4）的 canonical enum。
--
-- 影响范围：
--   本文件在默认主库 `webman` 中创建 13 张 V2.0 实体表（全新表，不触碰任何 V1.x 表）：
--   - approval_requests：审批请求（ApprovalRequest）
--   - parameter_releases / parameter_snapshots：参数发布 / 参数快照
--   - notices / notification_deliveries：通知 / 通知投递
--   - auth_sessions / mfa_enrollments：会话 / MFA 注册
--   - kyc_cases：KYC 案件
--   - risk_cases：风控案件
--   - tickets / ticket_messages / ticket_attachments：工单 / 工单消息 / 工单附件
--   - settlement_methods：结算方式
--
-- 权威契约：
--   Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md
--   §2.2（Session 状态）+ §3（对象最低字段）+ §4（统一状态机，V2.4）+ §8（RBAC）。
--   领域状态枚举严格取自 05 §4 V2.4 / Owner 裁决（2B2-ENUM-01..03），禁止自创。
--
-- 与 V1.x 表的关键差异（同 MC1/MC2/2B-1 约定）：
--   1. 主键：Snowflake ID（bigint unsigned），不使用 AUTO_INCREMENT。
--   2. 领域状态：ENUM 冻结（canonical enum），不使用 V1.x 的 status tinyint 软删模式。
--   3. 金额：本批 P1/P2 对象无 APT 金额；如需金额沿用 decimal(36,18)/decimal(18,8)/decimal(18,4)，禁 float。
--   4. 时间戳：created_time / updated_time 为 Unix 秒（int unsigned）。
--   5. append-only：parameter_snapshots / ticket_messages / ticket_attachments 无 updated_time、
--      无 object_version（不可变值对象/只读聚合），一经写入永不覆盖/删除。
--
-- 执行方式：forward-only migration（一次性建表，禁止内置 DROP/CREATE）。
-- 迁移阶段：阶段一（sql/YYYYMMDD_description.sql）。
-- =============================================================================

SET NAMES utf8mb4;

-- =============================================================================
-- 1. approval_requests — 审批请求（05 §3 ApprovalRequest + §4 Approval 状态机）
-- =============================================================================
CREATE TABLE `approval_requests` (
  `approval_id` bigint unsigned NOT NULL COMMENT '审批ID(Snowflake，主键)',
  `request_type` varchar(32) NOT NULL DEFAULT '' COMMENT '申请类型',
  `request_object_type` varchar(32) NOT NULL DEFAULT '' COMMENT '申请对象类型',
  `request_object_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '申请对象ID',
  `status` enum('draft','pending','changes_requested','approved','rejected','executing','executed','failed') NOT NULL DEFAULT 'draft' COMMENT '审批状态(05 §4 canonical)',
  `submitted_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '申请人 user_id',
  `submitter_role` varchar(32) NOT NULL DEFAULT '' COMMENT '申请人角色',
  `assigned_to` bigint unsigned NOT NULL DEFAULT '0' COMMENT '审批人 user_id',
  `decided_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '裁决人 user_id',
  `decided_at` int unsigned NOT NULL DEFAULT '0' COMMENT '裁决时间(Unix秒)',
  `reason_key` varchar(64) NOT NULL DEFAULT '' COMMENT '裁决理由 I18N key',
  `changes_requested_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '要求修改理由',
  `execution_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '执行对象ID',
  `case_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联案件ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`approval_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_status` (`status`),
  KEY `idx_object` (`request_object_type`, `request_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批请求(状态机 draft/pending/changes_requested/approved/rejected/executing/executed/failed)';

-- =============================================================================
-- 2. parameter_releases — 参数发布（05 §3 ParameterRelease + §4 Parameter Release 状态机）
-- =============================================================================
CREATE TABLE `parameter_releases` (
  `release_id` bigint unsigned NOT NULL COMMENT '发布ID(Snowflake，主键)',
  `parameter_keys` json DEFAULT NULL COMMENT '参数键列表(JSON 数组)',
  `status` enum('draft','pending_approval','approved','scheduled','active','paused','rolled_back','archived') NOT NULL DEFAULT 'draft' COMMENT '发布状态(05 §4 canonical)',
  `draft_version` varchar(64) NOT NULL DEFAULT '' COMMENT '草稿版本号',
  `approved_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '批准人 user_id',
  `scheduled_at` int unsigned NOT NULL DEFAULT '0' COMMENT '排期激活时间(Unix秒)',
  `activated_at` int unsigned NOT NULL DEFAULT '0' COMMENT '实际激活时间(Unix秒)',
  `paused_at` int unsigned NOT NULL DEFAULT '0' COMMENT '暂停时间(Unix秒)',
  `rolled_back_at` int unsigned NOT NULL DEFAULT '0' COMMENT '回滚时间(Unix秒)',
  `archived_at` int unsigned NOT NULL DEFAULT '0' COMMENT '归档时间(Unix秒)',
  `monitoring_job_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '监控任务ID',
  `snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联参数快照ID',
  `case_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联案件ID',
  `audit_event_ids` json DEFAULT NULL COMMENT '审计事件ID列表(JSON 数组)',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`release_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_status` (`status`),
  KEY `idx_snapshot` (`snapshot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='参数发布(状态机 draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived)';

-- =============================================================================
-- 3. parameter_snapshots — 参数快照（05 §3 ParameterSnapshot，append-only 只读聚合）
-- =============================================================================
CREATE TABLE `parameter_snapshots` (
  `snapshot_id` bigint unsigned NOT NULL COMMENT '快照ID(Snowflake，主键)',
  `release_id` bigint unsigned NOT NULL COMMENT '发布ID(parameter_releases.release_id)',
  `parameter_keys` json DEFAULT NULL COMMENT '参数键列表(JSON 数组)',
  `parameter_values` json DEFAULT NULL COMMENT '参数值(JSON 键值对)',
  `version` varchar(64) NOT NULL DEFAULT '' COMMENT '快照版本号',
  `created_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '创建人 user_id',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  PRIMARY KEY (`snapshot_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_release` (`release_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='参数快照(append-only 只读聚合，无UPDATE/DELETE)';

-- =============================================================================
-- 4. notices — 通知（05 §3 Notice，只读聚合，read_state 可变）
-- =============================================================================
CREATE TABLE `notices` (
  `notice_id` bigint unsigned NOT NULL COMMENT '通知ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '目标用户ID',
  `notice_type` varchar(32) NOT NULL DEFAULT '' COMMENT '通知事件类型',
  `title_key` varchar(64) NOT NULL DEFAULT '' COMMENT 'I18N 标题 key',
  `body_key` varchar(64) NOT NULL DEFAULT '' COMMENT 'I18N 正文 key',
  `priority` enum('INFO','WARNING','CRITICAL') NOT NULL DEFAULT 'INFO' COMMENT '优先级',
  `related_object_type` varchar(32) NOT NULL DEFAULT '' COMMENT '关联对象类型',
  `related_object_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联对象ID',
  `read_state` enum('unread','read') NOT NULL DEFAULT 'unread' COMMENT '已读状态',
  `content_version` varchar(64) NOT NULL DEFAULT '' COMMENT '文案版本号',
  `locale` varchar(16) NOT NULL DEFAULT '' COMMENT '生成时 locale',
  `expires_at` int unsigned NOT NULL DEFAULT '0' COMMENT '过期时间(Unix秒)',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`notice_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`read_state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知(只读聚合，read_state unread/read)';

-- =============================================================================
-- 5. notification_deliveries — 通知投递（05 §3 NotificationDelivery + §4 V2.4）
-- =============================================================================
CREATE TABLE `notification_deliveries` (
  `delivery_id` bigint unsigned NOT NULL COMMENT '投递ID(Snowflake，主键)',
  `notice_id` bigint unsigned NOT NULL COMMENT '通知ID(notices.notice_id)',
  `channel` enum('PUSH','EMAIL','SMS','IN_APP') NOT NULL DEFAULT 'IN_APP' COMMENT '投递渠道',
  `delivery_status` enum('pending','delivered','failed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '投递状态(05 §4 V2.4 canonical)',
  `dedupe_key` varchar(64) NOT NULL DEFAULT '' COMMENT '去重 key(幂等)',
  `attempt_count` int unsigned NOT NULL DEFAULT '0' COMMENT '尝试次数',
  `last_attempt_at` int unsigned NOT NULL DEFAULT '0' COMMENT '最后尝试时间(Unix秒)',
  `next_retry_at` int unsigned NOT NULL DEFAULT '0' COMMENT '下次重试时间(Unix秒)',
  `delivered_at` int unsigned NOT NULL DEFAULT '0' COMMENT '投递成功时间(Unix秒)',
  `failure_reason_code` varchar(64) NOT NULL DEFAULT '' COMMENT '失败原因码',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`delivery_id`),
  UNIQUE KEY `uk_dedupe` (`dedupe_key`),
  KEY `idx_notice` (`notice_id`),
  KEY `idx_status` (`delivery_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知投递(状态机 pending/delivered/failed/cancelled)';

-- =============================================================================
-- 6. auth_sessions — 会话（05 §3 AuthSession + §2.2 Session 状态机）
-- =============================================================================
CREATE TABLE `auth_sessions` (
  `session_id` bigint unsigned NOT NULL COMMENT '会话ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `token_hash` varchar(64) NOT NULL DEFAULT '' COMMENT 'token 哈希',
  `status` enum('active','mfa_required','restricted','expired','revoked') NOT NULL DEFAULT 'active' COMMENT '会话状态(05 §2.2 canonical)',
  `device_info` json DEFAULT NULL COMMENT '设备信息(JSON)',
  `ip_address` varchar(45) NOT NULL DEFAULT '' COMMENT 'IP 地址',
  `mfa_verified` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'MFA 是否已验证',
  `expires_at` int unsigned NOT NULL DEFAULT '0' COMMENT '过期时间(Unix秒)',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会话(状态机 active/mfa_required/restricted/expired/revoked)';

-- =============================================================================
-- 7. mfa_enrollments — MFA 注册（05 §3 MfaEnrollment + §4 V2.4）
-- =============================================================================
CREATE TABLE `mfa_enrollments` (
  `enrollment_id` bigint unsigned NOT NULL COMMENT '注册ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `method_type` varchar(32) NOT NULL DEFAULT '' COMMENT 'MFA 方法类型',
  `status` enum('pending','active','revoked') NOT NULL DEFAULT 'pending' COMMENT '注册状态(05 §4 V2.4 canonical)',
  `enrolled_at` int unsigned NOT NULL DEFAULT '0' COMMENT '注册发起时间(Unix秒)',
  `last_verified_at` int unsigned NOT NULL DEFAULT '0' COMMENT '最后验证时间(Unix秒)',
  `backup_codes_active` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '备份码是否激活',
  `device_info` json DEFAULT NULL COMMENT '设备信息(JSON)',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MFA注册(状态机 pending/active/revoked)';

-- =============================================================================
-- 8. kyc_cases — KYC 案件（05 §3 KycCase + §4 KYC 状态机）
-- =============================================================================
CREATE TABLE `kyc_cases` (
  `case_id` bigint unsigned NOT NULL COMMENT '案件ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `kyc_level` varchar(32) NOT NULL DEFAULT '' COMMENT 'KYC 等级',
  `status` enum('not_started','pending','needs_info','approved','rejected','review') NOT NULL DEFAULT 'not_started' COMMENT 'KYC 状态(05 §4 canonical)',
  `submitted_at` int unsigned NOT NULL DEFAULT '0' COMMENT '提交时间(Unix秒)',
  `reviewed_at` int unsigned NOT NULL DEFAULT '0' COMMENT '复核时间(Unix秒)',
  `reviewed_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '复核人 user_id',
  `reason_code` varchar(64) NOT NULL DEFAULT '' COMMENT '原因码',
  `reason_text_key` varchar(64) NOT NULL DEFAULT '' COMMENT '原因文案 I18N key',
  `next_action` varchar(32) NOT NULL DEFAULT '' COMMENT '下一步动作',
  `policy_version` varchar(64) NOT NULL DEFAULT '' COMMENT '策略版本号',
  `rule_version` varchar(64) NOT NULL DEFAULT '' COMMENT '规则版本号',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`case_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KYC案件(状态机 not_started/pending/needs_info/approved/rejected/review)';

-- =============================================================================
-- 9. risk_cases — 风控案件（05 §3 RiskCase + §4 V2.4）
-- =============================================================================
CREATE TABLE `risk_cases` (
  `case_id` bigint unsigned NOT NULL COMMENT '案件ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `risk_type` varchar(32) NOT NULL DEFAULT '' COMMENT '风险类型',
  `severity` varchar(16) NOT NULL DEFAULT '' COMMENT '严重等级',
  `status` enum('open','investigating','under_review','resolved','closed') NOT NULL DEFAULT 'open' COMMENT '风控状态(05 §4 V2.4 canonical)',
  `detected_at` int unsigned NOT NULL DEFAULT '0' COMMENT '检测时间(Unix秒)',
  `detected_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '检测人 user_id',
  `reviewed_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '处置审批人 user_id',
  `disposition` varchar(32) NOT NULL DEFAULT '' COMMENT '处置结论',
  `disposition_reason_key` varchar(64) NOT NULL DEFAULT '' COMMENT '处置理由 I18N key',
  `restrictions` json DEFAULT NULL COMMENT '限制措施(JSON 数组)',
  `appeal_eligible` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否可申诉',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`case_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='风控案件(状态机 open/investigating/under_review/resolved/closed)';

-- =============================================================================
-- 10. tickets — 工单（05 §3 Ticket + §4 Ticket 状态机）
-- =============================================================================
CREATE TABLE `tickets` (
  `ticket_id` bigint unsigned NOT NULL COMMENT '工单ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `category` varchar(32) NOT NULL DEFAULT '' COMMENT '工单分类',
  `status` enum('submitted','in_progress','waiting_user','under_review','resolved','closed') NOT NULL DEFAULT 'submitted' COMMENT '工单状态(05 §4 canonical)',
  `assigned_to` bigint unsigned NOT NULL DEFAULT '0' COMMENT '处理人 user_id',
  `last_activity_at` int unsigned NOT NULL DEFAULT '0' COMMENT '最后活动时间(Unix秒)',
  `resolution_type` varchar(32) NOT NULL DEFAULT '' COMMENT '解决类型',
  `resolution_summary_key` varchar(64) NOT NULL DEFAULT '' COMMENT '解决摘要 I18N key',
  `appeal_eligible` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否可申诉',
  `ticket_message_ids` json DEFAULT NULL COMMENT '工单消息ID列表(JSON 数组)',
  `case_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联案件ID',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`ticket_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单(状态机 submitted/in_progress/waiting_user/under_review/resolved/closed)';

-- =============================================================================
-- 11. ticket_messages — 工单消息（05 §3 TicketMessage，append-only 值对象）
-- =============================================================================
CREATE TABLE `ticket_messages` (
  `message_id` bigint unsigned NOT NULL COMMENT '消息ID(Snowflake，主键)',
  `ticket_id` bigint unsigned NOT NULL COMMENT '工单ID(tickets.ticket_id)',
  `sender_role` varchar(32) NOT NULL DEFAULT '' COMMENT '发送方角色',
  `body_key` varchar(64) NOT NULL DEFAULT '' COMMENT '正文 I18N key',
  `attachments` json DEFAULT NULL COMMENT '附件列表(JSON 数组)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  PRIMARY KEY (`message_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单消息(append-only 值对象，无UPDATE/DELETE)';

-- =============================================================================
-- 12. ticket_attachments — 工单附件（05 §3 TicketAttachment，append-only 值对象）
-- =============================================================================
CREATE TABLE `ticket_attachments` (
  `attachment_id` bigint unsigned NOT NULL COMMENT '附件ID(Snowflake，主键)',
  `ticket_id` bigint unsigned NOT NULL COMMENT '工单ID(tickets.ticket_id)',
  `ticket_message_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '工单消息ID(ticket_messages.message_id)',
  `file_type` varchar(32) NOT NULL DEFAULT '' COMMENT '文件类型',
  `file_url` varchar(512) NOT NULL DEFAULT '' COMMENT '文件URL',
  `file_hash` varchar(64) NOT NULL DEFAULT '' COMMENT '文件哈希',
  `uploaded_by` bigint unsigned NOT NULL DEFAULT '0' COMMENT '上传人 user_id',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  PRIMARY KEY (`attachment_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_message` (`ticket_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单附件(append-only 值对象，无UPDATE/DELETE)';

-- =============================================================================
-- 13. settlement_methods — 结算方式（05 §3 SettlementMethod，值对象/只读聚合）
-- =============================================================================
CREATE TABLE `settlement_methods` (
  `method_id` bigint unsigned NOT NULL COMMENT '结算方式ID(Snowflake，主键)',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `currency` varchar(16) NOT NULL DEFAULT '' COMMENT '币种',
  `method_type` varchar(32) NOT NULL DEFAULT '' COMMENT '结算方式类型',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否默认',
  `verification_status` varchar(32) NOT NULL DEFAULT '' COMMENT '验证状态',
  `object_version` int unsigned NOT NULL DEFAULT '0' COMMENT '并发控制版本号(乐观锁)',
  `idempotency_key` varchar(64) DEFAULT NULL COMMENT '幂等键',
  `audit_event_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审计事件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间(Unix秒)',
  PRIMARY KEY (`method_id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='结算方式(值对象，verification_status 可变)';
