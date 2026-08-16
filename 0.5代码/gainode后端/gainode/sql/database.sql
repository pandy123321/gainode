# ************************************************************
# Sequel Ace SQL dump
# 版本号： 20100
#
# https://sequel-ace.com/
# https://github.com/Sequel-Ace/Sequel-Ace
#
# 主机: localhost (MySQL 8.4.9)
# 数据库: webman
# 生成时间: 2026-08-05 09:13:09 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE='NO_AUTO_VALUE_ON_ZERO', SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# 转储表 arbitrage_attempt
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_attempt`;

CREATE TABLE `arbitrage_attempt` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint NOT NULL DEFAULT '0' COMMENT '用户ID',
  `plan_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联日计划ID(arbitrage_day_plan.id)',
  `signal_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联信号ID(arbitrage_signal.id; 0=选信号失败)',
  `fixture_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联比赛ID(arbitrage_fixture.id; 0=未关联)',
  `window_idx` int NOT NULL DEFAULT '0' COMMENT '对应计划窗口下标(=day_plan.schedule数组索引,非next_idx)',
  `exec_status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '执行结果: success=成功(成功时也会写position) slippage=滑点 delayed=延迟 market_closed=封盘 limited=限额 odds_reversed=赔率反转 signal_gone=信号消失',
  `stake` decimal(16,2) NOT NULL DEFAULT '0.00' COMMENT '本次尝试计算的注额(未成交也可能有值)',
  `profit_rate` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT '本次尝试时的理论利润率(小数)',
  `detail` json DEFAULT NULL COMMENT '模拟细节/错误上下文(JSON,如原始模拟参数、失败原因码等)',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间戳(Unix秒,尝试发生时刻)',
  PRIMARY KEY (`id`),
  KEY `idx_plan_window` (`plan_id`,`window_idx`),
  KEY `idx_user_created` (`user_id`,`created_time`),
  KEY `idx_exec_status` (`exec_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套利下单尝试日志(引擎审计,失败记录不进position)';



# 转储表 arbitrage_day_plan
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_day_plan`;

CREATE TABLE `arbitrage_day_plan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `project_id` int NOT NULL DEFAULT '0' COMMENT '矿机项目ID',
  `day` date NOT NULL COMMENT '交易日',
  `target_amount` decimal(11,3) DEFAULT '0.000' COMMENT '当日投入的金额',
  `target_rate` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT '当日目标利率',
  `target_profit` decimal(11,3) NOT NULL DEFAULT '0.000' COMMENT '当日利润目标',
  `realized_profit` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT '已实现利润',
  `target_trades` int NOT NULL DEFAULT '5' COMMENT '计划成交笔数',
  `done_trades` int NOT NULL DEFAULT '0' COMMENT '已成功成交笔数',
  `schedule` json DEFAULT NULL COMMENT '计划执行窗口时间戳数组',
  `next_idx` int NOT NULL DEFAULT '0' COMMENT '当前窗口游标',
  `last_attempt_at` int NOT NULL DEFAULT '0' COMMENT '上次尝试下单时间戳',
  `bailout_count` tinyint NOT NULL DEFAULT '0' COMMENT '已追加的补救窗口次数',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间戳(Unix秒)',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '更新时间戳(Unix秒)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '计划状态: -1=删除 1=待执行 2=执行中 3=已完成 4=已关闭',
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_date` (`project_id`,`day`),
  KEY `idx_day` (`day`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套利日计划(引擎调度,每用户每日唯一)';



# 转储表 arbitrage_fixture
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_fixture`;

CREATE TABLE `arbitrage_fixture` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID(内部自增,业务层统一引用此字段)',
  `source` tinyint NOT NULL DEFAULT '1' COMMENT '数据来源: 1=API-Football真实比赛 2=BetBurger占位比赛(未匹配到真实赛事时)',
  `source_id` bigint NOT NULL DEFAULT '0' COMMENT '来源侧唯一ID: source=1时为API-Football fixture_id; source=2时为BetBurger event_id',
  `betburger_event_id` bigint DEFAULT NULL COMMENT 'BetBurger event_id; 真实比赛匹配成功后回填; 占位比赛创建时等于source_id',
  `is_placeholder` tinyint NOT NULL DEFAULT '0' COMMENT '是否占位比赛: 0=真实可结算 1=占位(不可结算/不可领取,待升级为真实比赛)',
  `league` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联赛名称',
  `home` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '主队名称',
  `away` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客队名称',
  `timezone` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '比赛时区(如 UTC / Europe/London)',
  `kickoff_at` int NOT NULL DEFAULT '0' COMMENT '开赛时间戳(Unix秒,用于排序/窗口筛选/结算时机判断)',
  `status_short` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '比赛状态短码: NS=未开赛 LIVE=进行中 FT=完赛 CANC=取消 PST=延期 ABD=腰斩 等',
  `status_long` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '比赛状态描述(来自API-Football status.long)',
  `score_home` int NOT NULL DEFAULT '0' COMMENT '主队当前比分',
  `score_away` int NOT NULL DEFAULT '0' COMMENT '客队当前比分',
  `is_finished` tinyint NOT NULL DEFAULT '0' COMMENT '是否已完赛: 0=未完赛 1=已完赛(FT/AET/PEN/AWD等终态)',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间戳(Unix秒)',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '更新时间戳(Unix秒)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '记录状态: 1=正常 -1=删除(软删)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_source` (`source`,`source_id`),
  UNIQUE KEY `uk_betburger_event` (`betburger_event_id`),
  KEY `idx_kickoff` (`kickoff_at`),
  KEY `idx_finished` (`is_finished`),
  KEY `idx_placeholder` (`is_placeholder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套利比赛主数据(行情层,系统共享)';



# 转储表 arbitrage_position
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_position`;

CREATE TABLE `arbitrage_position` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `project_id` int NOT NULL DEFAULT '0' COMMENT '矿机项目ID',
  `plan_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联日计划ID(arbitrage_day_plan.id)',
  `signal_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联信号ID(arbitrage_signal.id)',
  `fixture_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联比赛ID(arbitrage_fixture.id; 结算/完赛判断强依赖)',
  `event_id` bigint NOT NULL DEFAULT '0' COMMENT 'BetBurger event_id快照(下单时冻结,便于溯源)',
  `event_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '赛事名称快照(下单时冻结,不随行情变更)',
  `phase` tinyint NOT NULL DEFAULT '1' COMMENT '资金阶段: 1=开仓锁仓中 2=赛果待结算(已完赛待入账) 3=已结算入账 4=已作废回滚',
  `league` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联赛名称快照(下单时冻结)',
  `home` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '主队名称快照(下单时冻结)',
  `away` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客队名称快照(下单时冻结)',
  `kickoff_at` int NOT NULL DEFAULT '0' COMMENT '开赛时间戳快照(Unix秒,用于展示与结算时机)',
  `leg1_bookmaker` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Leg1博彩公司名称快照',
  `leg1_bookmaker_id` int DEFAULT NULL COMMENT 'Leg1博彩公司ID快照',
  `leg1_market` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Leg1玩法快照',
  `leg1_odds` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT 'Leg1赔率快照(成交时)',
  `leg1_stake` decimal(16,3) NOT NULL DEFAULT '0.000' COMMENT 'Leg1实际投注额(锁仓本金的一部分)',
  `leg2_bookmaker` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Leg2博彩公司名称快照',
  `leg2_bookmaker_id` int DEFAULT NULL COMMENT 'Leg2博彩公司ID快照',
  `leg2_market` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Leg2玩法快照',
  `leg2_odds` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT 'Leg2赔率快照(成交时)',
  `leg2_stake` decimal(16,3) NOT NULL DEFAULT '0.000' COMMENT 'Leg2实际投注额(锁仓本金的一部分)',
  `total_stake` decimal(16,3) NOT NULL DEFAULT '0.000' COMMENT '锁仓总本金',
  `expected_rate` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT '理论利润率(小数,下单时按信号计算)',
  `expected_profit` decimal(16,3) NOT NULL DEFAULT '0.000' COMMENT '理论利润',
  `actual_rate` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT '实际利润率(小数,含滑点/模拟偏差后的最终值)',
  `actual_profit` decimal(16,3) NOT NULL DEFAULT '0.000' COMMENT '实际利润(结算入账金额=actual_profit,本金另退)',
  `locked_at` int NOT NULL DEFAULT '0' COMMENT '锁仓时间戳(Unix秒,开仓扣款时刻)',
  `settled_at` int NOT NULL DEFAULT '0' COMMENT '结算入账时间戳(Unix秒,退本金+利润到Arbitrage钱包)',
  `voided_at` int DEFAULT '0' COMMENT '作废时间戳(Unix秒; NULL=未作废)',
  `void_reason` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '作废原因: fixture_match_cancelled=取消 fixture_match_abandoned=腰斩 fixture_grace_expired=超时 grace',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间戳(Unix秒)',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '更新时间戳(Unix秒)',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(-1:删除,0:异常,1:待处理,2:已结算)',
  PRIMARY KEY (`id`),
  KEY `idx_plan` (`plan_id`),
  KEY `idx_signal` (`signal_id`),
  KEY `idx_fixture` (`fixture_id`),
  KEY `idx_user_created` (`project_id`,`created_time`),
  KEY `project_phase` (`project_id`,`phase`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='矿机套利仓位';



# 转储表 arbitrage_project
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_project`;

CREATE TABLE `arbitrage_project` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '矿机项目ID',
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '项目编号',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '矿机项目名称',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '矿机项目图片',
  `project_day` int DEFAULT '0' COMMENT '投资总天数',
  `project_rate` decimal(11,2) DEFAULT '0.00' COMMENT '总收益率',
  `project_price` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '投资金额',
  `min_day_rate` decimal(6,2) DEFAULT '0.00' COMMENT '最低日收益率',
  `max_day_rate` decimal(6,2) DEFAULT '0.00' COMMENT '最高日收益率',
  `user_amount` decimal(11,2) DEFAULT '0.00' COMMENT '购买时用户业绩',
  `start_date` datetime DEFAULT NULL COMMENT '开始时间',
  `user_invite` int DEFAULT '0' COMMENT '购买时用户邀请人数',
  `total_cnt` int DEFAULT NULL COMMENT '总库存数量',
  `limit_num` int DEFAULT '0' COMMENT '限购数量',
  `sales_cnt` int DEFAULT '0' COMMENT '销售数量',
  `position_cnt` int DEFAULT '4' COMMENT '购买记录数',
  `sort` smallint NOT NULL DEFAULT '0' COMMENT '排序',
  `descr` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '商品描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '项目状态(1:已上架,0:已关闭、-1:已删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `project_name` (`name`),
  KEY `status` (`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套利矿机项目表';



# 转储表 arbitrage_project_order
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_project_order`;

CREATE TABLE `arbitrage_project_order` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `project_id` int DEFAULT '0' COMMENT '项目ID',
  `project_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '项目名称',
  `min_day_rate` decimal(6,2) DEFAULT '0.00' COMMENT '最低日利率',
  `max_day_rate` decimal(6,2) DEFAULT '0.00' COMMENT '最高日利率',
  `amount` decimal(11,2) DEFAULT '0.00' COMMENT '订单金额',
  `fee` decimal(11,2) DEFAULT '0.00' COMMENT '交易税费',
  `order_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid' COMMENT '订单状态(unpaid,pending,paid,refunded,completed,closed)',
  `pay_method` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '支付方式',
  `pay_amount` decimal(11,2) DEFAULT '0.00' COMMENT '已付款金额',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `tx_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '交易Hash',
  `settle_amount` decimal(11,2) DEFAULT '0.00' COMMENT '结息收益金额',
  `settle_cnt` int DEFAULT '0' COMMENT '累计结息次数',
  `last_settle_time` datetime DEFAULT NULL COMMENT '上次结息时间',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '是否默认',
  `is_lock` tinyint(1) DEFAULT '0' COMMENT '是否锁住赎回',
  `is_calc_money` tinyint(1) DEFAULT '0' COMMENT '是否计算用户业绩',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '备注',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `cancel_at` timestamp NULL DEFAULT NULL COMMENT '取消时间',
  `status` tinyint(1) DEFAULT '2' COMMENT '状态(4:已赎回, 3:已到期, 2:运营中, 1:待审核、0:已取消, -1:失败)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `order_status` (`order_status`),
  KEY `status` (`status`),
  KEY `project_id` (`project_id`),
  KEY `created_time` (`created_time`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT COMMENT='矿机订单表';



# 转储表 arbitrage_project_order_day
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_project_order_day`;

CREATE TABLE `arbitrage_project_order_day` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `income_day` date NOT NULL COMMENT '收益日期',
  `project_amount` decimal(11,4) DEFAULT '0.0000' COMMENT '矿机收益金额',
  `team_amount` decimal(11,4) NOT NULL DEFAULT '0.0000' COMMENT '团队动态收益',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  `status` int NOT NULL DEFAULT '1' COMMENT '0:待结算,1:已结算',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `user_id` (`user_id`,`income_day`),
  KEY `created_time` (`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT COMMENT='用户项目订单结息日志表';



# 转储表 arbitrage_project_order_logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_project_order_logs`;

CREATE TABLE `arbitrage_project_order_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL DEFAULT '0' COMMENT '订单ID',
  `project_id` int NOT NULL DEFAULT '0' COMMENT '项目ID',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '购买人用户ID',
  `plan_id` int NOT NULL DEFAULT '0' COMMENT '套利计划ID',
  `position_id` int NOT NULL DEFAULT '0' COMMENT '套利仓位ID',
  `level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '收益级别(0:自己,其他数字:代表第几级分销)',
  `to_day` int DEFAULT '0' COMMENT '第几天收益',
  `money` decimal(11,4) NOT NULL DEFAULT '0.0000' COMMENT '计算的金额',
  `income_rate` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '收益率',
  `income_userid` int NOT NULL DEFAULT '0' COMMENT '收益人',
  `income_day` date NOT NULL COMMENT '收益日期',
  `income_amount` decimal(11,4) NOT NULL DEFAULT '0.0000' COMMENT '收益金额',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `platform_rate` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '平台佣金百分比',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  `status` int DEFAULT '0' COMMENT '状态(0:待执行,1:待领取,2已结算)',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`),
  KEY `income_userid` (`income_userid`),
  KEY `status` (`status`),
  KEY `income_amount` (`income_amount`),
  KEY `to_day` (`to_day`),
  KEY `order_day` (`order_id`,`level`,`to_day`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT COMMENT='用户项目订单结算日志表';



# 转储表 arbitrage_signal
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_signal`;

CREATE TABLE `arbitrage_signal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `event_id` bigint NOT NULL DEFAULT '0' COMMENT 'BetBurger event_id(标识一场比赛)',
  `fixture_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联比赛ID(arbitrage_fixture.id; 0=尚未匹配/未创建占位)',
  `arb_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '套利组合唯一标识(同一event_id内唯一,用于幂等upsert)',
  `event_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '赛事名称(采集时快照,展示用)',
  `is_live` tinyint NOT NULL DEFAULT '0' COMMENT '是否滚球: 0=赛前 1=滚球',
  `started_at` int NOT NULL DEFAULT '0' COMMENT '比赛开赛时间戳(Unix秒,来自BetBurger)',
  `betburger_pct` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'BetBurger原始收益率(百分比口径,如1.20表示1.2%)',
  `profit_rate` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT '理论利润率(小数口径,如0.0120表示1.20%; 由两腿赔率数学计算)',
  `leg1_bookmaker_id` int unsigned DEFAULT NULL COMMENT 'Leg1博彩公司ID(BetBurger bookmaker_id)',
  `leg1_bookmaker` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Leg1博彩公司名称(展示名)',
  `leg1_market` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Leg1玩法名称(如 Over2.5 / Home Win)',
  `leg1_odds` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Leg1赔率(>1.00)',
  `leg1_market_param` decimal(10,2) DEFAULT NULL COMMENT 'Leg1市场参数(如让球/大小球线,无则为NULL)',
  `leg1_market_type` int DEFAULT NULL COMMENT 'Leg1市场类型(BetBurger market_and_bet_type)',
  `leg2_bookmaker_id` int DEFAULT NULL COMMENT 'Leg2博彩公司ID(BetBurger bookmaker_id)',
  `leg2_bookmaker` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Leg2博彩公司名称(展示名)',
  `leg2_market` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Leg2玩法名称(与Leg1对立结果)',
  `leg2_odds` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Leg2赔率(>1.00)',
  `leg2_market_param` decimal(10,2) DEFAULT NULL COMMENT 'Leg2市场参数(如让球/大小球线,无则为NULL)',
  `leg2_market_type` int DEFAULT NULL COMMENT 'Leg2市场类型(BetBurger market_and_bet_type)',
  `preview_stake` decimal(16,2) NOT NULL DEFAULT '0.00' COMMENT '预览总注额(采集时用固定本金计算两腿分配,非真实下单金额)',
  `current_score` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '滚球当前比分(如 1-0; 赛前为NULL)',
  `first_seen_at` int NOT NULL DEFAULT '0' COMMENT '首次采集时间戳(Unix秒)',
  `last_seen_at` int NOT NULL DEFAULT '0' COMMENT '最近采集时间戳(Unix秒; 用于过期清理)',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间戳(Unix秒)',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '更新时间戳(Unix秒)',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '信号状态: -1=删除 1=有效 2=已过期 3=已用尽(已成交) 4=已关闭 5=无效(数学校验不通过)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_hash` (`event_id`,`arb_hash`),
  KEY `idx_fixture` (`fixture_id`),
  KEY `idx_status_profit` (`status`,`profit_rate`),
  KEY `idx_started` (`started_at`),
  KEY `idx_last_seen` (`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套利信号行情表';



# 转储表 arbitrage_signal_raw
# ------------------------------------------------------------

DROP TABLE IF EXISTS `arbitrage_signal_raw`;

CREATE TABLE `arbitrage_signal_raw` (
  `signal_id` bigint unsigned NOT NULL COMMENT '关联信号ID(arbitrage_signal.id,1:1)',
  `payload` json NOT NULL COMMENT 'BetBurger原始套利包(JSON,含arb/bet1/bet2等完整字段,用于审计与回放)',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间戳(Unix秒)',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '更新时间戳(Unix秒,信号刷新时同步更新)',
  PRIMARY KEY (`signal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='套利信号原始数据冷存(与arbitrage_signal 1:1)';



# 转储表 member_level
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_level`;

CREATE TABLE `member_level` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '用户等级ID',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '等级图片',
  `user_type` tinyint(1) DEFAULT '0' COMMENT '用户类型(0:普通用户,1:代理商,2:员工)',
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户等级名称',
  `grade` int DEFAULT '1' COMMENT '级别',
  `discount` tinyint(1) NOT NULL DEFAULT '100' COMMENT '分成比例百分比',
  `amount` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '业绩额度',
  `invite_cnt` tinyint(1) DEFAULT '0' COMMENT '邀请人数',
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '等级说明',
  `created_time` int NOT NULL COMMENT '创建时间',
  `updated_time` int NOT NULL COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:可用,0:隐藏,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade` (`user_type`,`grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户等级';

LOCK TABLES `member_level` WRITE;
/*!40000 ALTER TABLE `member_level` DISABLE KEYS */;

INSERT INTO `member_level` (`id`, `icon`, `user_type`, `name`, `grade`, `discount`, `amount`, `invite_cnt`, `descr`, `created_time`, `updated_time`, `status`)
VALUES
	(1,NULL,0,'LV0',0,70,30000.00,2,'新手',1784627333,1784627895,1),
	(2,NULL,0,'LV1',1,75,500000.00,5,'LV1',1784627972,1784628047,1),
	(3,NULL,0,'LV2',2,80,2000000.00,10,'LV2',1784628036,1784628036,1),
	(4,NULL,0,'LV3',3,80,5000000.00,10,'LV3',1784628144,1784628144,1);

/*!40000 ALTER TABLE `member_level` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 member_order_record
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_order_record`;

CREATE TABLE `member_order_record` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT '0' COMMENT '用户ID',
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '类型 recharge,withdraw,transfer,profit',
  `from_acc` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '来源账户',
  `to_acc` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '转向帐户',
  `currency` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'USDT' COMMENT '币种',
  `amount` decimal(26,8) NOT NULL DEFAULT '0.00000000' COMMENT '金额',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `ref_table` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '来源表',
  `ref_id` int DEFAULT '0' COMMENT '来源 ID',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` int DEFAULT '0' COMMENT '状态(0:待处理,1:已完成,2:失败,-1:删除)',
  PRIMARY KEY (`id`),
  KEY `type` (`user_id`,`type`),
  KEY `ref_id` (`ref_table`,`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单记录表';



# 转储表 member_platform_wallet
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_platform_wallet`;

CREATE TABLE `member_platform_wallet` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `currency` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USDT' COMMENT '币种',
  `balance` decimal(26,8) NOT NULL DEFAULT '0.00000000' COMMENT '当前余额',
  `total_in` decimal(26,8) NOT NULL DEFAULT '0.00000000' COMMENT '累计入账',
  `total_out` decimal(26,8) NOT NULL DEFAULT '0.00000000' COMMENT '累计出账',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_currency` (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='平台主账户';



# 转储表 member_recharge_order
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_recharge_order`;

CREATE TABLE `member_recharge_order` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '充值流水号',
  `user_id` int DEFAULT '0' COMMENT '用户ID',
  `network` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '充值网络: eth,bnb,tron',
  `address` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '充值地址',
  `from_address` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户发币钱包地址',
  `currency` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'USDT' COMMENT '充值币种',
  `money` decimal(26,8) NOT NULL DEFAULT '0.00000000' COMMENT '充值金额',
  `reward_money` decimal(26,8) DEFAULT '0.00000000' COMMENT '充值赠送',
  `fee` decimal(26,8) DEFAULT '0.00000000' COMMENT '手续费',
  `tx_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '交易hash',
  `confirmations` int unsigned NOT NULL DEFAULT '0' COMMENT '当前链上确认数',
  `required_confirmations` int unsigned NOT NULL DEFAULT '6' COMMENT '所需确认数',
  `chain_data` json DEFAULT NULL COMMENT '链上原始回执数据',
  `actual_amount` decimal(26,8) DEFAULT '0.00000000' COMMENT '实际到账',
  `order_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted' COMMENT '状态: submitted/confirming/completed/failed/rejected/closed',
  `admin_id` int DEFAULT '0' COMMENT '后台操作人员',
  `source` tinyint(1) DEFAULT '0' COMMENT '来源(0:后台新增,1:用户提交,2:链上监听)',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `credited_time` int NOT NULL DEFAULT '0' COMMENT '实际到账时间',
  `retry_count` tinyint(1) DEFAULT '0' COMMENT '调用API次数',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(-1:已删除,0:隐藏,1:待处理,2:已完成)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_no` (`order_no`),
  UNIQUE KEY `tx_hash` (`tx_hash`),
  KEY `user_id` (`user_id`),
  KEY `order_status` (`order_status`),
  KEY `network` (`network`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值订单表';



# 转储表 member_transfer_order
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_transfer_order`;

CREATE TABLE `member_transfer_order` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '用户ID',
  `order_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '划转单号',
  `from_wallet` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '来源账户类型',
  `to_wallet` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目标账户类型',
  `currency` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USDT' COMMENT '币种',
  `amount` decimal(26,8) NOT NULL COMMENT '划转金额',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '0=失败 1=成功',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`,`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='账户划转订单';



# 转储表 member_user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_user`;

CREATE TABLE `member_user` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `eid` int DEFAULT '0' COMMENT '企业ID(0:平台)',
  `user_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户编号',
  `account_type` enum('email','mobile','username') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'email' COMMENT '注册账号类型(email、mobile)',
  `account` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '登陆账号',
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密码',
  `pay_password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '支付密码',
  `encrypt` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密钥',
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '昵称',
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '姓氏',
  `last_name` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '名字',
  `email` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '邮箱',
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '手机号码',
  `google_secret` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'google验证码',
  `sex` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '性别(Male:男，Female:女，Other:其他)',
  `avatar` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '头像地址',
  `birthday` datetime DEFAULT NULL COMMENT '生日',
  `country` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '归属国家',
  `user_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户类型(0:普通用户,1:代理商 )',
  `is_verify` tinyint(1) DEFAULT '0' COMMENT '是否认证(0:未提交,1:待验证审核,2:审核通过,3:已拒绝)',
  `is_agent` tinyint(1) DEFAULT '0' COMMENT '是否代理商',
  `agent_id` int DEFAULT '0' COMMENT '所属代理商ID',
  `telegram_id` bigint DEFAULT NULL COMMENT '飞机ID',
  `login_cnt` int NOT NULL DEFAULT '0' COMMENT '登陆次数',
  `client_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP地址',
  `last_login_time` int NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `modify_pwd_time` int DEFAULT '0' COMMENT '修改密码时间',
  `pwd_strong` int DEFAULT '0' COMMENT '密码强度',
  `is_multiple_login` tinyint(1) DEFAULT '0' COMMENT '是否支持多端登录',
  `is_frozen_withdraw` tinyint(1) DEFAULT '0' COMMENT '是否冻结提现',
  `is_arbitrage` tinyint(1) DEFAULT '0' COMMENT '是否开启套利任务',
  `rewards_cnt` int DEFAULT '0' COMMENT '领取奖励次数',
  `level_id` int DEFAULT '0' COMMENT '会员等级ID',
  `level_grade` tinyint(1) DEFAULT '0' COMMENT '等级序号',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `admin_id` int DEFAULT '0' COMMENT '所属员工ID',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:可用,0:停用,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_no` (`user_no`),
  UNIQUE KEY `idx_account` (`eid`,`account`),
  KEY `agent_id` (`agent_id`),
  KEY `admin_id` (`admin_id`),
  KEY `created_time` (`created_time`),
  KEY `is_arbitrage` (`is_arbitrage`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';



# 转储表 member_user_auth
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_user_auth`;

CREATE TABLE `member_user_auth` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `eid` int NOT NULL DEFAULT '0' COMMENT '企业ID(0:平台)',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `terminal` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pc' COMMENT '终端类型(pc、mobile、app)',
  `token_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '授权类型',
  `access_token` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'access_token',
  `refresh_token` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'refresh_token',
  `client_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端ip',
  `expires_in` int NOT NULL DEFAULT '0' COMMENT '刷新失效时间',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `expired_time` int NOT NULL DEFAULT '0' COMMENT '失效时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:在线,0:不在线,-1:已删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_client_auth` (`user_id`,`terminal`),
  UNIQUE KEY `access_token` (`access_token`),
  KEY `eid` (`eid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户授权表';



# 转储表 member_user_kyc
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_user_kyc`;

CREATE TABLE `member_user_kyc` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint unsigned NOT NULL COMMENT '会员ID',
  `real_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `country` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT '' COMMENT '国家/地区',
  `id_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证件类型：(身份证:id_card,护照:passport,驾驶证:driver)',
  `id_number` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证件号码',
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '认证手机号',
  `front_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证件正面图片',
  `back_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '证件反面图片',
  `hand_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手持证件图片',
  `reject_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '拒绝原因',
  `review_admin_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `review_status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'created' COMMENT '审核状态（创建:created,审核通过:approved,已拒绝:rejected）',
  `review_time` int unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `deleted_time` int unsigned NOT NULL DEFAULT '0' COMMENT '软删除时间',
  `status` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态：0:待审核,1:已批准,2:已拒绝,-1:删除',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`user_id`),
  KEY `idx_status_time` (`status`,`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户KYC实名认证表';



# 转储表 member_user_logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_user_logs`;

CREATE TABLE `member_user_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `eid` int DEFAULT '0' COMMENT '企业ID(0:平台)',
  `user_id` int DEFAULT '0' COMMENT '用户ID',
  `account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '用户账号',
  `token` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户token',
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '用户行为',
  `os` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作系统',
  `browser` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '浏览器类型',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '访问标识',
  `client_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '客户端ip',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `eid` (`eid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户登陆日志表';



# 转储表 member_user_oauth
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_user_oauth`;

CREATE TABLE `member_user_oauth` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `client_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tiktok' COMMENT '第三方类型',
  `client_id` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '第三方账号',
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '授权的数据',
  `client_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '客户端ip',
  `bind_time` int NOT NULL DEFAULT '0' COMMENT '绑定时间',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '登录时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,-1:已删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_type`,`client_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户第三方授权表';



# 转储表 member_user_team
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_user_team`;

CREATE TABLE `member_user_team` (
  `user_id` int NOT NULL COMMENT '用户ID',
  `account` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `invite_code` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '邀请码',
  `parent_id` int NOT NULL DEFAULT '0' COMMENT '上级邀请人ID',
  `parent_level` int DEFAULT '0' COMMENT '上级层级',
  `parent_path` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上级邀请节点',
  `invite_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '下级邀请节点',
  `invite_cnt` int unsigned NOT NULL DEFAULT '0' COMMENT '直推人数',
  `invite_income_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '直推收益金额',
  `invite_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '直推业绩',
  `invite_paid_money` decimal(11,2) DEFAULT '0.00' COMMENT '直推支付金额',
  `team_cnt` int NOT NULL DEFAULT '0' COMMENT '团队人数',
  `team_income_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '团队收益金额',
  `team_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '团队业绩',
  `team_paid_money` decimal(11,2) DEFAULT '0.00' COMMENT '团队支付金额',
  `order_cnt` int DEFAULT '0' COMMENT '订单数量',
  `order_money` decimal(11,2) unsigned DEFAULT '0.00' COMMENT '消费金额',
  `invite_order_money` decimal(11,2) DEFAULT '0.00' COMMENT '直推消费金额',
  `team_order_money` decimal(11,2) DEFAULT '0.00' COMMENT '团队消费金额',
  `total_fee` decimal(11,3) DEFAULT '0.000' COMMENT '累计手续费',
  `team_income_fee` decimal(11,3) DEFAULT '0.000' COMMENT '团队手续费收益',
  `reward` decimal(11,2) DEFAULT '0.00' COMMENT '邀请奖励金',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(1:可用)',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `parent_id` (`parent_id`),
  KEY `parent_path` (`parent_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户邀请信息表';



# 转储表 member_user_wallet
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_user_wallet`;

CREATE TABLE `member_user_wallet` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int NOT NULL COMMENT '用户id',
  `wallet_type` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Funding' COMMENT '账户类型: Funding/Arbitrage/Integral',
  `balance` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '可用余额',
  `frozen` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '冻结金额',
  `total_deposit` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '累计充值入账',
  `total_trade` decimal(18,4) DEFAULT '0.0000' COMMENT '累计交易划出',
  `total_withdraw` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '累计提现',
  `total_in` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '历史累计所有入账',
  `total_out` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '历史累计所有出账',
  `sort` int DEFAULT '0' COMMENT '排序值',
  `version` int DEFAULT '0' COMMENT '版本号',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(1:可用,0:冻结)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_wallet_type` (`user_id`,`wallet_type`),
  KEY `sort` (`sort`),
  KEY `status` (`status`),
  KEY `balance` (`balance`),
  KEY `wallet_type` (`wallet_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户钱包账户表';



# 转储表 member_user_wallet_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_user_wallet_log`;

CREATE TABLE `member_user_wallet_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT '用户 ID',
  `wallet_id` int unsigned NOT NULL COMMENT '关联 member_wallet.id',
  `wallet_type` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户类型快照:Funding/Arbitrage/Integral',
  `event_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '事件类型',
  `ref_table` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '来源表名',
  `ref_id` bigint unsigned NOT NULL COMMENT '来源记录 ID',
  `direction` tinyint NOT NULL DEFAULT '1' COMMENT '1=收入 -1=支出 0=冻结变动',
  `amount` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '发生金额（正值）',
  `balance_before` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '变动前余额',
  `balance_after` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '变动后余额',
  `frozen_before` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '变动前冻结余额',
  `frozen_after` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT '变动后冻结余额',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注',
  `admin_id` int DEFAULT '0' COMMENT '操作人',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(1:正常，0:隐藏)',
  PRIMARY KEY (`id`),
  KEY `idx_user_event` (`user_id`,`event_type`),
  KEY `idx_wallet` (`wallet_id`),
  KEY `idx_ref` (`ref_table`,`ref_id`),
  KEY `idx_created` (`created_time`),
  KEY `ids_wallet_type` (`wallet_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资金流水总账';



# 转储表 member_withdraw_order
# ------------------------------------------------------------

DROP TABLE IF EXISTS `member_withdraw_order`;

CREATE TABLE `member_withdraw_order` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提现流水号',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '提现类型',
  `currency` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'USDT' COMMENT '提现币种(USDT)',
  `money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '申请提现金额',
  `fee` decimal(11,2) DEFAULT '0.00' COMMENT '手续费',
  `actual_amount` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '实际到账 = money - fee',
  `address` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '目标收款地址',
  `risk_score` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '风控评分 0-100，>70 需人工审核',
  `tx_hash` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '交易hash',
  `retry_count` int DEFAULT '0' COMMENT '调用次数',
  `order_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested' COMMENT '状态: requested/approved/rejected/broadcasting/completed/failed/closed',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `review_admin_id` int NOT NULL DEFAULT '0' COMMENT '审核管理员ID',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `approved_time` int NOT NULL DEFAULT '0' COMMENT '审核通过时间',
  `broadcasted_time` int NOT NULL DEFAULT '0' COMMENT '链上广播时间',
  `confirmed_time` int NOT NULL DEFAULT '0' COMMENT '链上确认时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(-1:已删除,0:隐藏,1:待处理,2:已完成)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `user_id` (`user_id`),
  KEY `order_status` (`order_status`),
  KEY `created_time` (`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现订单表';



# 转储表 sys_admin
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_admin`;

CREATE TABLE `sys_admin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `eid` int NOT NULL DEFAULT '0' COMMENT '企业ID(0:平台)',
  `account` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登陆账号',
  `password` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `role_id` int NOT NULL DEFAULT '0' COMMENT '所属角色',
  `dept_id` int NOT NULL DEFAULT '0' COMMENT '所属部门',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否管理员',
  `encrypt` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密钥',
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '名字',
  `email` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '邮箱',
  `mobile` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '手机号码',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '头像地址',
  `modify_pwd_time` int NOT NULL DEFAULT '0' COMMENT '修改密码时间',
  `login_time` int NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `login_cnt` int NOT NULL DEFAULT '0' COMMENT '登陆次数',
  `login_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '登陆IP地址',
  `menu_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '权限菜单',
  `is_multiple_login` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否支持多端登录',
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `deleted_time` int NOT NULL DEFAULT '0' COMMENT '删除时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,0:已锁定,-1:已删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `account` (`eid`,`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员表';

LOCK TABLES `sys_admin` WRITE;
/*!40000 ALTER TABLE `sys_admin` DISABLE KEYS */;

INSERT INTO `sys_admin` (`id`, `eid`, `account`, `password`, `role_id`, `dept_id`, `is_admin`, `encrypt`, `name`, `email`, `mobile`, `avatar`, `modify_pwd_time`, `login_time`, `login_cnt`, `login_ip`, `menu_ids`, `is_multiple_login`, `descr`, `created_time`, `updated_time`, `deleted_time`, `status`)
VALUES
	(1,0,'admin','',1,1,1,'',NULL,'','',NULL,1785213293,1785754678,82,'175.0.225.133','1',0,NULL,1741161752,1785754678,0,1);

/*!40000 ALTER TABLE `sys_admin` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_admin_auth
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_admin_auth`;

CREATE TABLE `sys_admin_auth` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `eid` int NOT NULL DEFAULT '0' COMMENT '企业ID(0:平台)',
  `admin_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `terminal` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pc' COMMENT '终端类型(pc、mobile、app)',
  `token_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '授权类型',
  `access_token` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'access_token',
  `refresh_token` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT 'refresh_token',
  `client_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端ip',
  `expires_in` int NOT NULL DEFAULT '0' COMMENT '刷新失效时间',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `expired_time` int NOT NULL DEFAULT '0' COMMENT '失效时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:在线,0:不在线,-1:已删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_client_auth` (`admin_id`,`terminal`),
  UNIQUE KEY `access_token` (`access_token`),
  KEY `eid` (`eid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='账号授权表';



# 转储表 sys_admin_logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_admin_logs`;

CREATE TABLE `sys_admin_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `eid` int DEFAULT '0' COMMENT '企业ID(0:平台)',
  `admin_id` int DEFAULT '0' COMMENT '用户ID',
  `account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '用户账号',
  `token` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户token',
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '用户行为',
  `os` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作系统',
  `browser` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '浏览器类型',
  `client_ip` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客户端ip',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `token` (`token`),
  KEY `eid` (`eid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户登陆日志表';



# 转储表 sys_article
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_article`;

CREATE TABLE `sys_article` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `eid` int NOT NULL DEFAULT '0' COMMENT '企业ID(0:平台)',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文章标题',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文章内容',
  `category_id` int unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '文章图片',
  `link_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '链接地址',
  `author` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '作者',
  `is_rec` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐(1:推荐,0:不推荐)',
  `visit_num` int unsigned NOT NULL DEFAULT '0' COMMENT '阅读量',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '最后修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,0:不显示,-1:删除)',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `eid` (`eid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='文章表';

LOCK TABLES `sys_article` WRITE;
/*!40000 ALTER TABLE `sys_article` DISABLE KEYS */;

INSERT INTO `sys_article` (`id`, `eid`, `title`, `content`, `category_id`, `image_url`, `link_url`, `author`, `is_rec`, `visit_num`, `sort`, `descr`, `created_time`, `updated_time`, `status`)
VALUES
	(1,0,'如何进行充值？','在首页点击充值按钮，选择网络和币种后，向显示的地址转账即可。到账时间取决于网络拥堵情况，通常为 10-30 分钟。',2,'','',NULL,0,0,0,NULL,1784275979,1784275979,1),
	(2,0,'提现需要多长时间？','提现申请提交后，平台会进行审核和处理。一般会在 3 个工作日内到账。\n到账时间按提交申请的具体时间开始计算，例如：周一下午 4:00 提交提现申请，正常情况下会在周四下午 4:00 前完成处理并到账。具体到账时间可能受审核、区块链网络及收款方处理速度影响。',3,'','',NULL,0,0,0,NULL,1784276052,1784276224,1),
	(3,0,'什么是套利交易？','套利交易是利用不同平台之间的价格差异，同时买入和卖出同一资产以获取利润的交易策略。我们的 AI 系统会自动监控多个平台的价格差异。',4,'','',NULL,0,0,0,NULL,1784276073,1784276073,1),
	(4,0,'如何提升账户等级？','账户等级通过邀请好友和团队业绩来提升。邀请更多好友加入并参与套利，您的等级会逐步提升，同时享受更高的套利额度。',5,'','',NULL,0,0,0,NULL,1784276094,1784276094,1),
	(5,0,'账户安全如何保障？','我们采用多重安全措施保护您的账户，包括冷钱包存储、二次验证和多签机制。建议您绑定 Telegram 和邮箱以增强账户安全性。',5,'','',NULL,0,0,0,NULL,1784276116,1784276116,1),
	(6,0,'什么是投资账户和余额账户？','余额账户用于存放可用资金，可以随时提现。投资账户用于参与 AI 套利系统，转入后次日开始产生收益。两个账户之间可以自由划转。',6,'','',NULL,0,0,0,NULL,1784276209,1784276209,1),
	(7,0,'不同等级的收益率是多少？','不同用户等级对应不同的日收益率：',7,'','',NULL,0,0,0,NULL,1784276266,1784276266,1),
	(8,0,'IP 激活是什么功能？','IP 激活功能用于启动 AI 自动套利系统。激活后系统会使用您的 IP 节点进行全球博彩平台的赔率监控和套利交易，确保收益最大化。',8,'','',NULL,0,0,0,NULL,1784276286,1784276286,1),
	(9,0,'转入投资账户后何时开始获得收益？','资金转入投资账户后，次日开始计算收益。收益每日自动结算并添加到投资账户中，可随时转出到余额账户进行提现。',9,'','',NULL,0,0,0,NULL,1784276309,1784276309,1),
	(10,0,'如何查看我的邀请记录？','进入 “我的” 页面，点击 用户等级卡片，即可查看详细的邀请人数、团队交易额度和晋级进度。\n\n好友通过您的邀请链接完成注册后，将自动计入您的团队。',10,'','',NULL,0,0,0,NULL,1784276330,1784276330,1);

/*!40000 ALTER TABLE `sys_article` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_article_category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_article_category`;

CREATE TABLE `sys_article_category` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `eid` int DEFAULT '0' COMMENT '企业ID(0:平台)',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父分类',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '最后修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,0:不显示,-1:删除)',
  PRIMARY KEY (`id`),
  KEY `eid` (`eid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='文章分类表';

LOCK TABLES `sys_article_category` WRITE;
/*!40000 ALTER TABLE `sys_article_category` DISABLE KEYS */;

INSERT INTO `sys_article_category` (`id`, `eid`, `name`, `pid`, `sort`, `created_time`, `updated_time`, `status`)
VALUES
	(2,0,'充值',0,1,1784275135,1784275135,1),
	(3,0,'提现',0,2,1784275848,1784275848,1),
	(4,0,'套利',0,0,1784275879,1784275879,1),
	(5,0,'账户',0,0,1784275892,1784275892,1),
	(6,0,'安全',0,0,1784275905,1784275905,1),
	(7,0,'等级',0,0,1784276153,1784276153,1),
	(8,0,'功能',0,0,1784276161,1784276161,1),
	(9,0,'收益',0,0,1784276168,1784276168,1),
	(10,0,'邀请',0,0,1784276183,1784276183,1);

/*!40000 ALTER TABLE `sys_article_category` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_article_lang
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_article_lang`;

CREATE TABLE `sys_article_lang` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned DEFAULT NULL COMMENT '内容 ID',
  `lang` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '语言',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标题',
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '最后修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang` (`article_id`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容表';



# 转储表 sys_casbin_rbac
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_casbin_rbac`;

CREATE TABLE `sys_casbin_rbac` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '规则ID',
  `ptype` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则类型',
  `v0` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v1` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v2` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v3` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v4` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v5` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_time` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ptype` (`ptype`,`v0`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='rbac权限规则表';

LOCK TABLES `sys_casbin_rbac` WRITE;
/*!40000 ALTER TABLE `sys_casbin_rbac` DISABLE KEYS */;

INSERT INTO `sys_casbin_rbac` (`id`, `ptype`, `v0`, `v1`, `v2`, `v3`, `v4`, `v5`, `created_time`)
VALUES
	(1,'p','role1','9d8e424a2c91819ef8af3ce8360577b8','POST','','','',1784041758);

/*!40000 ALTER TABLE `sys_casbin_rbac` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_casbin_restful
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_casbin_restful`;

CREATE TABLE `sys_casbin_restful` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '规则ID',
  `ptype` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '规则类型',
  `v0` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v1` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v2` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v3` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v4` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `v5` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_time` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ptype` (`ptype`,`v0`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='restful权限规则表';



# 转储表 sys_change_logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_change_logs`;

CREATE TABLE `sys_change_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `change_table` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '修改的表',
  `primary_id` bigint NOT NULL COMMENT '主键ID',
  `original` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原来的值',
  `change` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '修改的值',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `change_table` (`change_table`,`primary_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT COMMENT='接口请求店铺授权表';



# 转储表 sys_config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_config`;

CREATE TABLE `sys_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `eid` int NOT NULL DEFAULT '0' COMMENT '企业ID(0:平台)',
  `name` varchar(255) NOT NULL COMMENT '键',
  `value` longtext NOT NULL COMMENT '值',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`eid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='选项配置表';



# 转储表 sys_country
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_country`;

CREATE TABLE `sys_country` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '国家中文名称',
  `name_en` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '国家英文名称',
  `continent` tinyint(1) DEFAULT '0' COMMENT '所在地域',
  `code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '二字码',
  `flag` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标',
  `three_code` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '三字码',
  `dial` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话区号',
  `sort` int NOT NULL DEFAULT '100' COMMENT '排序值',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(1:可用,0:隐藏,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `name_en` (`name_en`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='国家表\nhttp://114.xixik.com/country-code/';

LOCK TABLES `sys_country` WRITE;
/*!40000 ALTER TABLE `sys_country` DISABLE KEYS */;

INSERT INTO `sys_country` (`id`, `name`, `name_en`, `continent`, `code`, `flag`, `three_code`, `dial`, `sort`, `descr`, `create_at`, `update_at`, `status`)
VALUES
	(185,'中国','China',1,'CN','🇨🇳','CHN','86',1,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(186,'日本','Japan',1,'JP','🇯🇵','JPN','81',2,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(187,'韩国','South Korea',1,'KR','🇰🇷','KOR','82',3,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(188,'朝鲜','North Korea',1,'KP','🇰🇵','PRK','850',4,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(189,'蒙古','Mongolia',1,'MN','🇲🇳','MNG','976',5,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(190,'印度','India',1,'IN','🇮🇳','IND','91',6,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(191,'巴基斯坦','Pakistan',1,'PK','🇵🇰','PAK','92',7,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(192,'孟加拉国','Bangladesh',1,'BD','🇧🇩','BGD','880',8,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(193,'斯里兰卡','Sri Lanka',1,'LK','🇱🇰','LKA','94',9,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(194,'尼泊尔','Nepal',1,'NP','🇳🇵','NPL','977',10,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(195,'不丹','Bhutan',1,'BT','🇧🇹','BTN','975',11,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(196,'马尔代夫','Maldives',1,'MV','🇲🇻','MDV','960',12,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(197,'缅甸','Myanmar',1,'MM','🇲🇲','MMR','95',13,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(198,'泰国','Thailand',1,'TH','🇹🇭','THA','66',14,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(199,'老挝','Laos',1,'LA','🇱🇦','LAO','856',15,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(200,'越南','Vietnam',1,'VN','🇻🇳','VNM','84',16,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(201,'柬埔寨','Cambodia',1,'KH','🇰🇭','KHM','855',17,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(202,'马来西亚','Malaysia',1,'MY','🇲🇾','MYS','60',18,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(203,'新加坡','Singapore',1,'SG','🇸🇬','SGP','65',19,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(204,'印度尼西亚','Indonesia',1,'ID','🇮🇩','IDN','62',20,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(205,'文莱','Brunei',1,'BN','🇧🇳','BRN','673',21,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(206,'菲律宾','Philippines',1,'PH','🇵🇭','PHL','63',22,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(207,'东帝汶','Timor-Leste',1,'TL','🇹🇱','TLS','670',23,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(208,'哈萨克斯坦','Kazakhstan',1,'KZ','🇰🇿','KAZ','7',24,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(209,'乌兹别克斯坦','Uzbekistan',1,'UZ','🇺🇿','UZB','998',25,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(210,'土库曼斯坦','Turkmenistan',1,'TM','🇹🇲','TKM','993',26,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(211,'吉尔吉斯斯坦','Kyrgyzstan',1,'KG','🇰🇬','KGZ','996',27,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(212,'塔吉克斯坦','Tajikistan',1,'TJ','🇹🇯','TJK','992',28,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(213,'阿富汗','Afghanistan',1,'AF','🇦🇫','AFG','93',29,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(214,'伊朗','Iran',1,'IR','🇮🇷','IRN','98',30,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(215,'伊拉克','Iraq',1,'IQ','🇮🇶','IRQ','964',31,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(216,'土耳其','Turkey',1,'TR','🇹🇷','TUR','90',32,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(217,'沙特阿拉伯','Saudi Arabia',1,'SA','🇸🇦','SAU','966',33,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(218,'也门','Yemen',1,'YE','🇾🇪','YEM','967',34,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(219,'阿曼','Oman',1,'OM','🇴🇲','OMN','968',35,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(220,'阿联酋','United Arab Emirates',1,'AE','🇦🇪','ARE','971',36,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(221,'卡塔尔','Qatar',1,'QA','🇶🇦','QAT','974',37,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(222,'科威特','Kuwait',1,'KW','🇰🇼','KWT','965',38,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(223,'巴林','Bahrain',1,'BH','🇧🇭','BHR','973',39,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(224,'约旦','Jordan',1,'JO','🇯🇴','JOR','962',40,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(225,'黎巴嫩','Lebanon',1,'LB','🇱🇧','LBN','961',41,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(226,'叙利亚','Syria',1,'SY','🇸🇾','SYR','963',42,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(227,'巴勒斯坦','Palestine',1,'PS','🇵🇸','PSE','970',43,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(228,'以色列','Israel',1,'IL','🇮🇱','ISR','972',44,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(229,'格鲁吉亚','Georgia',1,'GE','🇬🇪','GEO','995',45,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(230,'亚美尼亚','Armenia',1,'AM','🇦🇲','ARM','374',46,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(231,'阿塞拜疆','Azerbaijan',1,'AZ','🇦🇿','AZE','994',47,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(232,'塞浦路斯','Cyprus',1,'CY','🇨🇾','CYP','357',48,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(233,'英国','United Kingdom',2,'GB','🇬🇧','GBR','44',101,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(234,'法国','France',2,'FR','🇫🇷','FRA','33',102,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(235,'德国','Germany',2,'DE','🇩🇪','DEU','49',103,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(236,'意大利','Italy',2,'IT','🇮🇹','ITA','39',104,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(237,'西班牙','Spain',2,'ES','🇪🇸','ESP','34',105,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(238,'葡萄牙','Portugal',2,'PT','🇵🇹','PRT','351',106,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(239,'荷兰','Netherlands',2,'NL','🇳🇱','NLD','31',107,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(240,'比利时','Belgium',2,'BE','🇧🇪','BEL','32',108,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(241,'瑞士','Switzerland',2,'CH','🇨🇭','CHE','41',109,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(242,'奥地利','Austria',2,'AT','🇦🇹','AUT','43',110,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(243,'波兰','Poland',2,'PL','🇵🇱','POL','48',111,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(244,'捷克','Czech Republic',2,'CZ','🇨🇿','CZE','420',112,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(245,'斯洛伐克','Slovakia',2,'SK','🇸🇰','SVK','421',113,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(246,'匈牙利','Hungary',2,'HU','🇭🇺','HUN','36',114,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(247,'罗马尼亚','Romania',2,'RO','🇷🇴','ROU','40',115,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(248,'保加利亚','Bulgaria',2,'BG','🇧🇬','BGR','359',116,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(249,'希腊','Greece',2,'GR','🇬🇷','GRC','30',117,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(250,'克罗地亚','Croatia',2,'HR','🇭🇷','HRV','385',118,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(251,'斯洛文尼亚','Slovenia',2,'SI','🇸🇮','SVN','386',119,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(252,'塞尔维亚','Serbia',2,'RS','🇷🇸','SRB','381',120,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(253,'黑山','Montenegro',2,'ME','🇲🇪','MNE','382',121,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(254,'波黑','Bosnia and Herzegovina',2,'BA','🇧🇦','BIH','387',122,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(255,'北马其顿','North Macedonia',2,'MK','🇲🇰','MKD','389',123,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(256,'阿尔巴尼亚','Albania',2,'AL','🇦🇱','ALB','355',124,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(257,'丹麦','Denmark',2,'DK','🇩🇰','DNK','45',125,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(258,'挪威','Norway',2,'NO','🇳🇴','NOR','47',126,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(259,'瑞典','Sweden',2,'SE','🇸🇪','SWE','46',127,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(260,'芬兰','Finland',2,'FI','🇫🇮','FIN','358',128,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(261,'冰岛','Iceland',2,'IS','🇮🇸','ISL','354',129,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(262,'爱沙尼亚','Estonia',2,'EE','🇪🇪','EST','372',130,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(263,'拉脱维亚','Latvia',2,'LV','🇱🇻','LVA','371',131,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(264,'立陶宛','Lithuania',2,'LT','🇱🇹','LTU','370',132,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(265,'白俄罗斯','Belarus',2,'BY','🇧🇾','BLR','375',133,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(266,'乌克兰','Ukraine',2,'UA','🇺🇦','UKR','380',134,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(267,'摩尔多瓦','Moldova',2,'MD','🇲🇩','MDA','373',135,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(268,'俄罗斯','Russia',2,'RU','🇷🇺','RUS','7',136,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(269,'爱尔兰','Ireland',2,'IE','🇮🇪','IRL','353',137,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(270,'卢森堡','Luxembourg',2,'LU','🇱🇺','LUX','352',138,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(271,'马耳他','Malta',2,'MT','🇲🇹','MLT','356',139,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(272,'安道尔','Andorra',2,'AD','🇦🇩','AND','376',140,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(273,'摩纳哥','Monaco',2,'MC','🇲🇨','MCO','377',141,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(274,'圣马力诺','San Marino',2,'SM','🇸🇲','SMR','378',142,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(275,'列支敦士登','Liechtenstein',2,'LI','🇱🇮','LIE','423',143,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(276,'梵蒂冈','Vatican City',2,'VA','🇻🇦','VAT','379',144,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(277,'直布罗陀','Gibraltar',2,'GI','🇬🇮','GIB','350',145,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(278,'美国','United States',3,'US','🇺🇸','USA','1',201,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(279,'加拿大','Canada',3,'CA','🇨🇦','CAN','1',202,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(280,'墨西哥','Mexico',3,'MX','🇲🇽','MEX','52',203,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(281,'危地马拉','Guatemala',3,'GT','🇬🇹','GTM','502',204,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(282,'伯利兹','Belize',3,'BZ','🇧🇿','BLZ','501',205,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(283,'萨尔瓦多','El Salvador',3,'SV','🇸🇻','SLV','503',206,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(284,'洪都拉斯','Honduras',3,'HN','🇭🇳','HND','504',207,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(285,'尼加拉瓜','Nicaragua',3,'NI','🇳🇮','NIC','505',208,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(286,'哥斯达黎加','Costa Rica',3,'CR','🇨🇷','CRI','506',209,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(287,'巴拿马','Panama',3,'PA','🇵🇦','PAN','507',210,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(288,'古巴','Cuba',3,'CU','🇨🇺','CUB','53',211,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(289,'牙买加','Jamaica',3,'JM','🇯🇲','JAM','1',212,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(290,'海地','Haiti',3,'HT','🇭🇹','HTI','509',213,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(291,'多米尼加','Dominican Republic',3,'DO','🇩🇴','DOM','1',214,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(292,'波多黎各','Puerto Rico',3,'PR','🇵🇷','PRI','1',215,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(293,'巴哈马','Bahamas',3,'BS','🇧🇸','BHS','1',216,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(294,'特立尼达和多巴哥','Trinidad and Tobago',3,'TT','🇹🇹','TTO','1',217,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(295,'巴巴多斯','Barbados',3,'BB','🇧🇧','BRB','1',218,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(296,'格林纳达','Grenada',3,'GD','🇬🇩','GRD','1',219,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(297,'多米尼克','Dominica',3,'DM','🇩🇲','DMA','1',220,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(298,'圣卢西亚','Saint Lucia',3,'LC','🇱🇨','LCA','1',221,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(299,'圣文森特和格林纳丁斯','Saint Vincent and the Grenadines',3,'VC','🇻🇨','VCT','1',222,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(300,'安提瓜和巴布达','Antigua and Barbuda',3,'AG','🇦🇬','ATG','1',223,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(301,'圣基茨和尼维斯','Saint Kitts and Nevis',3,'KN','🇰🇳','KNA','1',224,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(302,'巴西','Brazil',4,'BR','🇧🇷','BRA','55',301,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(303,'阿根廷','Argentina',4,'AR','🇦🇷','ARG','54',302,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(304,'智利','Chile',4,'CL','🇨🇱','CHL','56',303,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(305,'哥伦比亚','Colombia',4,'CO','🇨🇴','COL','57',304,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(306,'秘鲁','Peru',4,'PE','🇵🇪','PER','51',305,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(307,'委内瑞拉','Venezuela',4,'VE','🇻🇪','VEN','58',306,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(308,'厄瓜多尔','Ecuador',4,'EC','🇪🇨','ECU','593',307,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(309,'玻利维亚','Bolivia',4,'BO','🇧🇴','BOL','591',308,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(310,'巴拉圭','Paraguay',4,'PY','🇵🇾','PRY','595',309,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(311,'乌拉圭','Uruguay',4,'UY','🇺🇾','URY','598',310,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(312,'圭亚那','Guyana',4,'GY','🇬🇾','GUY','592',311,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(313,'苏里南','Suriname',4,'SR','🇸🇷','SUR','597',312,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(314,'法属圭亚那','French Guiana',4,'GF','🇬🇫','GUF','594',313,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(315,'埃及','Egypt',5,'EG','🇪🇬','EGY','20',401,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(316,'南非','South Africa',5,'ZA','🇿🇦','ZAF','27',402,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(317,'摩洛哥','Morocco',5,'MA','🇲🇦','MAR','212',403,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(318,'阿尔及利亚','Algeria',5,'DZ','🇩🇿','DZA','213',404,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(319,'突尼斯','Tunisia',5,'TN','🇹🇳','TUN','216',405,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(320,'利比亚','Libya',5,'LY','🇱🇾','LBY','218',406,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(321,'苏丹','Sudan',5,'SD','🇸🇩','SDN','249',407,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(322,'埃塞俄比亚','Ethiopia',5,'ET','🇪🇹','ETH','251',408,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(323,'肯尼亚','Kenya',5,'KE','🇰🇪','KEN','254',409,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(324,'坦桑尼亚','Tanzania',5,'TZ','🇹🇿','TZA','255',410,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(325,'乌干达','Uganda',5,'UG','🇺🇬','UGA','256',411,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(326,'尼日利亚','Nigeria',5,'NG','🇳🇬','NGA','234',412,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(327,'加纳','Ghana',5,'GH','🇬🇭','GHA','233',413,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(328,'科特迪瓦','Ivory Coast',5,'CI','🇨🇮','CIV','225',414,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(329,'塞内加尔','Senegal',5,'SN','🇸🇳','SEN','221',415,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(330,'喀麦隆','Cameroon',5,'CM','🇨🇲','CMR','237',416,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(331,'刚果（金）','DR Congo',5,'CD','🇨🇩','COD','243',417,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(332,'刚果（布）','Republic of the Congo',5,'CG','🇨🇬','COG','242',418,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(333,'安哥拉','Angola',5,'AO','🇦🇴','AGO','244',419,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(334,'莫桑比克','Mozambique',5,'MZ','🇲🇿','MOZ','258',420,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(335,'赞比亚','Zambia',5,'ZM','🇿🇲','ZMB','260',421,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(336,'津巴布韦','Zimbabwe',5,'ZW','🇿🇼','ZWE','263',422,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(337,'博茨瓦纳','Botswana',5,'BW','🇧🇼','BWA','267',423,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(338,'纳米比亚','Namibia',5,'NA','🇳🇦','NAM','264',424,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(339,'卢旺达','Rwanda',5,'RW','🇷🇼','RWA','250',425,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(340,'马达加斯加','Madagascar',5,'MG','🇲🇬','MDG','261',426,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(341,'毛里求斯','Mauritius',5,'MU','🇲🇺','MUS','230',427,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(342,'塞舌尔','Seychelles',5,'SC','🇸🇨','SYC','248',428,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(343,'毛里塔尼亚','Mauritania',5,'MR','🇲🇷','MRT','222',429,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(344,'马里','Mali',5,'ML','🇲🇱','MLI','223',430,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(345,'布基纳法索','Burkina Faso',5,'BF','🇧🇫','BFA','226',431,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(346,'尼日尔','Niger',5,'NE','🇳🇪','NER','227',432,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(347,'乍得','Chad',5,'TD','🇹🇩','TCD','235',433,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(348,'中非','Central African Republic',5,'CF','🇨🇫','CAF','236',434,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(349,'南苏丹','South Sudan',5,'SS','🇸🇸','SSD','211',435,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(350,'索马里','Somalia',5,'SO','🇸🇴','SOM','252',436,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(351,'吉布提','Djibouti',5,'DJ','🇩🇯','DJI','253',437,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(352,'厄立特里亚','Eritrea',5,'ER','🇪🇷','ERI','291',438,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(353,'加蓬','Gabon',5,'GA','🇬🇦','GAB','241',439,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(354,'赤道几内亚','Equatorial Guinea',5,'GQ','🇬🇶','GNQ','240',440,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(355,'澳大利亚','Australia',6,'AU','🇦🇺','AUS','61',501,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(356,'新西兰','New Zealand',6,'NZ','🇳🇿','NZL','64',502,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(357,'巴布亚新几内亚','Papua New Guinea',6,'PG','🇵🇬','PNG','675',503,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(358,'斐济','Fiji',6,'FJ','🇫🇯','FJI','679',504,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(359,'所罗门群岛','Solomon Islands',6,'SB','🇸🇧','SLB','677',505,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(360,'瓦努阿图','Vanuatu',6,'VU','🇻🇺','VUT','678',506,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(361,'萨摩亚','Samoa',6,'WS','🇼🇸','WSM','685',507,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(362,'汤加','Tonga',6,'TO','🇹🇴','TON','676',508,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(363,'密克罗尼西亚','Micronesia',6,'FM','🇫🇲','FSM','691',509,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(364,'帕劳','Palau',6,'PW','🇵🇼','PLW','680',510,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(365,'马绍尔群岛','Marshall Islands',6,'MH','🇲🇭','MHL','692',511,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(366,'基里巴斯','Kiribati',6,'KI','🇰🇮','KIR','686',512,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(367,'图瓦卢','Tuvalu',6,'TV','🇹🇻','TUV','688',513,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1),
	(368,'瑙鲁','Nauru',6,'NR','🇳🇷','NRU','674',514,NULL,'2026-05-17 21:26:07','2026-05-17 21:26:07',1);

/*!40000 ALTER TABLE `sys_country` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_crontab
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_crontab`;

CREATE TABLE `sys_crontab` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '任务ID',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '任务名称',
  `group_id` int NOT NULL DEFAULT '0' COMMENT '任务分组ID',
  `command` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '执行命令',
  `expression` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'cron执行表达式',
  `timeout` int NOT NULL DEFAULT '0' COMMENT '超时时间(秒)',
  `is_notify` tinyint(1) DEFAULT '0' COMMENT '是否邮件通知',
  `notify_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '通知邮件',
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `exec_cnt` int DEFAULT '0' COMMENT '执行次数',
  `prev_time` int DEFAULT '0' COMMENT '上一次执行时间',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态（1:正常,2:暂停,0:异常,-1:删除）',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='定时任务调度表';

LOCK TABLES `sys_crontab` WRITE;
/*!40000 ALTER TABLE `sys_crontab` DISABLE KEYS */;

INSERT INTO `sys_crontab` (`id`, `name`, `group_id`, `command`, `expression`, `timeout`, `is_notify`, `notify_email`, `descr`, `exec_cnt`, `prev_time`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'回填库里的展示名',2,'php webman arbitrage:entity-map --from-cache --backfill','0 5 */2 * * *',0,0,'',NULL,178,1785917100,1784163617,1785917100,1),
	(2,'抓BetBurger对应的名称',2,'php webman crontab:arbitrage --action=entity-map','5 0 */2 * * *',0,0,'',NULL,165,1785916805,1784163617,1785916805,1),
	(3,'拉信号 ingestSignals',2,'php webman crontab:arbitrage --action=ingest','10,40 * * * * *',0,0,'',NULL,182,1785413410,1784163617,1785413410,-1),
	(4,'同步比赛 syncFixtures',2,'php webman crontab:arbitrage --action=sync','5 */2 * * * *',0,0,'',NULL,119,1785413405,1784163617,1785413405,-1),
	(5,'窗口下单 runOrderGeneration',2,'php webman crontab:arbitrage --action=orders','15,45 * * * * *',0,0,'',NULL,170,1785413415,1784163617,1785413415,-1),
	(6,'结算订单 settle',2,'php webman crontab:arbitrage --action=settle','20 * * * * *',0,0,'',NULL,134,1785413360,1784163617,1785413360,-1),
	(7,'创建计划单 create_plan',2,'php webman crontab:arbitrage --action=create_plan','1 0 0 * * *',0,0,'',NULL,140,1785859201,1784163617,1785859201,1),
	(8,'结算矿机订单 settleProjectOrderDay',2,'php webman crontab:order --action=settle','5 58 23 * * *',0,0,'',NULL,140,1785859201,1784163617,1785859201,1),
	(9,'订单过期处理 releaseProjectOrders',2,'php webman crontab:order --action=release','30 */30 * * * *',0,0,'',NULL,146,1785920431,1784163617,1785920431,1);

/*!40000 ALTER TABLE `sys_crontab` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_crontab_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_crontab_group`;

CREATE TABLE `sys_crontab_group` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '分组id',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分组名称',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `descr` varchar(255) DEFAULT NULL COMMENT '描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC COMMENT='任务分类表';

LOCK TABLES `sys_crontab_group` WRITE;
/*!40000 ALTER TABLE `sys_crontab_group` DISABLE KEYS */;

INSERT INTO `sys_crontab_group` (`id`, `name`, `sort`, `descr`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'系统任务',0,NULL,1784163617,1784163617,1),
	(2,'套利任务',0,NULL,1784163617,1784163617,1);

/*!40000 ALTER TABLE `sys_crontab_group` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_crontab_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_crontab_log`;

CREATE TABLE `sys_crontab_log` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '任务日志ID',
  `cron_id` int NOT NULL COMMENT '任务ID',
  `cron_command` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '执行命令',
  `run_start_time` bigint DEFAULT '0' COMMENT '运行开始时间',
  `run_end_time` bigint DEFAULT '0' COMMENT '运行结束时间',
  `exec_cnt` int DEFAULT '0' COMMENT '执行次数',
  `duration` int DEFAULT '0' COMMENT '消耗时间/毫秒',
  `message` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '日志信息',
  `exception_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '异常信息',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '执行状态（-1:不符合条件不运行,0:未开始,1:准备运行,2:运行成功,3:运行失败）',
  PRIMARY KEY (`id`),
  KEY `cron_id` (`cron_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='定时任务调度日志表';



# 转储表 sys_dept
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_dept`;

CREATE TABLE `sys_dept` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'id',
  `eid` int NOT NULL DEFAULT '0' COMMENT '企业ID(0:平台)',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '部门名称',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级部门id',
  `admin_id` int DEFAULT '0' COMMENT '负责人ID',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `descr` text COMMENT '描述',
  `created_time` int NOT NULL COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `deleted_time` int NOT NULL DEFAULT '0' COMMENT '删除时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,0:停用,-1:删除)',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `eid` (`eid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='部门表';

LOCK TABLES `sys_dept` WRITE;
/*!40000 ALTER TABLE `sys_dept` DISABLE KEYS */;

INSERT INTO `sys_dept` (`id`, `eid`, `name`, `pid`, `admin_id`, `sort`, `descr`, `created_time`, `updated_time`, `deleted_time`, `status`)
VALUES
	(1,0,'技术部',0,1,2,'技术部',1741235191,1768578805,0,1),
	(2,0,'运营部',0,3,0,NULL,1741235191,1741658697,0,1);

/*!40000 ALTER TABLE `sys_dept` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_dict
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_dict`;

CREATE TABLE `sys_dict` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字典名称',
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '字典标识码',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '字典类型',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序值',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `created_time` int DEFAULT '0' COMMENT '创建时间',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,0:隐藏,-1:删除)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `config_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='系统配置表';

LOCK TABLES `sys_dict` WRITE;
/*!40000 ALTER TABLE `sys_dict` DISABLE KEYS */;

INSERT INTO `sys_dict` (`id`, `name`, `code`, `type`, `sort`, `descr`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'邮件设置','email',0,2,'',1669567129,1669567129,1),
	(2,'网站设置','website',0,11,'',1669567129,1669567129,1),
	(3,'OSS设置','oss',3,0,'',1669567129,1669567129,1),
	(4,'分销基础设置','commission',1,10,'',1669567129,1669567129,1),
	(5,'推广奖励设置','reward',1,9,'',1669567129,1669567129,1),
	(6,'App信息','app',0,10,'',1669567129,1669567129,1),
	(7,'充值设置','recharge',1,8,'',1669567129,1669567129,1),
	(8,'注册奖励设置','register',0,9,'',1669567129,1669567129,1),
	(9,'提现设置','withdraw',1,7,'',1669567129,1669567129,1),
	(10,'比赛API基础信息','api_football',2,1,'比赛API基础信息',1784708751,1784708751,1),
	(11,'套利API基础信息','betburger',2,1,'套利API基础信息',1784709012,1784709012,1);

/*!40000 ALTER TABLE `sys_dict` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_dict_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_dict_list`;

CREATE TABLE `sys_dict_list` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `dict_code` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '字典标识码',
  `field_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字段代码',
  `field_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段名称',
  `field_type` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段类型',
  `field_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '字段值',
  `field_required` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'N' COMMENT '是否必填',
  `field_tips` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '字段提示',
  `field_sort` int DEFAULT '0' COMMENT '字段排序',
  `value_range_txt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '范围值名称',
  `value_range` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '范围值',
  `addon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '扩展符号',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,-1:删除)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `field_code` (`dict_code`,`field_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='系统配置数据表';

LOCK TABLES `sys_dict_list` WRITE;
/*!40000 ALTER TABLE `sys_dict_list` DISABLE KEYS */;

INSERT INTO `sys_dict_list` (`id`, `dict_code`, `field_code`, `field_name`, `field_type`, `field_value`, `field_required`, `field_tips`, `field_sort`, `value_range_txt`, `value_range`, `addon`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'email','mail_port','SMTP端口','number','587','Y','SMTP端口号(默认：25)',4,NULL,NULL,NULL,1669567129,1707923938,1),
	(2,'email','mail_address','发件地址','text','cbmerchants888@gmail.com','Y',NULL,5,'','',NULL,1669567129,1718631478,1),
	(3,'email','mail_password','SMTP密码','text','','Y',NULL,3,NULL,NULL,NULL,1669567129,1718631478,1),
	(4,'email','mail_user','SMTP用户名','text','cbmerchants888@gmail.com','Y',NULL,2,'','',NULL,1669567129,1718631478,1),
	(5,'email','mail_smtp','SMTP地址','text','smtp.gmail.com','Y',NULL,1,NULL,NULL,NULL,1669567129,1669567129,1),
	(6,'email','mail_safe','安全协议','radio','tls','Y',NULL,6,'默认|SSL|TLS','0|ssl|tls',NULL,1669567129,1707923543,1),
	(7,'website','site_webname','网站名称','text','Cbmerchants电商','Y',NULL,1,NULL,NULL,NULL,1669567129,1718540706,1),
	(8,'website','site_contacts','联系人','text','cbmerchants888@gmail.com','Y',NULL,2,NULL,NULL,NULL,1669567129,1718697712,1),
	(9,'website','site_telphone','联系电话','text','+856-2095491098-6666','N',NULL,3,NULL,NULL,NULL,1669567129,1784532614,1),
	(10,'website','site_email','邮箱','text','smbusiness.cc@gmail.com','N',NULL,4,NULL,NULL,NULL,1669567129,1706669373,1),
	(12,'website','site_copyright','版权','textarea','2024','Y','',9,NULL,NULL,NULL,1669567129,1708082392,1),
	(13,'website','site_logo','LOGO','file','https://gainode.s3.us-east-2.amazonaws.com/images/1784532604639_4d011296-c5ce-465c-af45-a16ddd9f063b.png','N',NULL,8,NULL,NULL,NULL,1669567129,1784532614,1),
	(14,'website','site_descr','备注','textarea','1855cf38bc0b8cb56bfb418ee567f8f8\r\n917e6ebdf71d86c68f55c2e67f961b76','N',NULL,10,NULL,NULL,NULL,1669567129,1729342297,1),
	(15,'oss','key','AccessKey','text','','Y',NULL,1,NULL,NULL,NULL,1669567129,1784532977,1),
	(16,'oss','secret','AccessSecret','text','','Y',NULL,2,NULL,NULL,NULL,1669567129,1712039422,1),
	(17,'oss','endpoint_in','内网地址','text','oss-ap-southeast-1-internal.aliyuncs.com','Y',NULL,3,NULL,NULL,NULL,1669567129,1672112498,1),
	(18,'oss','endpoint_out','外网地址','text','oss-ap-southeast-1.aliyuncs.com','Y',NULL,4,NULL,NULL,NULL,1669567129,1672112498,1),
	(19,'oss','bucket','Bucket','text','doshop','Y',NULL,5,NULL,NULL,NULL,1669567129,1672112498,1),
	(20,'commission','is_open','是否开启分销','radio','Y','Y','分销奖励的开关',1,'开启|关闭','Y|N','',1669567129,1784626397,1),
	(21,'commission','level1','一级分销','number','60','Y','一级分销获得的奖励百分比',4,NULL,NULL,'%',1669567129,1784626445,1),
	(22,'commission','level2','二级分销','number','20','Y','二级分销获得的奖励百分比',5,NULL,NULL,'%',1669567129,1784626445,1),
	(23,'commission','level3','三级分销','number','10','Y','三级分销获得的奖励百分比',6,NULL,NULL,'%',1669567129,1784626445,1),
	(24,'commission','level_num','支持分销层级','radio','3','Y','最高支持的分销层级',2,'一级分销|二级分销|三级分销','1|2|3',NULL,1669567129,1669567129,1),
	(25,'recharge','reward_open','首次充值奖励开关','radio','N','Y','首次充值奖励开关',1,'开启|关闭','Y|N',NULL,1669567129,1669567129,-1),
	(26,'recharge','reward_money','首次充值赠送','number','5','Y','首冲直接赠送比例',2,NULL,NULL,'%',1669567129,1669567129,-1),
	(27,'recharge','reward_max','首次充值最高奖励','number','1000','Y','首冲奖励最高限额',3,NULL,NULL,'元',1669567129,1669567129,-1),
	(29,'recharge','min_money','最低充值金额','number','100','Y','单次最低充值金额',5,NULL,NULL,'元',1669567129,1669567129,1),
	(30,'recharge','max_money','最高充值金额','number','1000000','Y','单次最高充值金额',6,NULL,NULL,'元',1669567129,1673625135,1),
	(31,'recharge','descr','充值描述','textarea','Dear user, sinc​e the mall re​charge system is being upgraded and maintained, please contact the online customer service directly for recharge. Thank you for your cooperation','N','充值描述',7,NULL,NULL,NULL,1669567129,1713097901,1),
	(32,'withdraw','is_open','提现开关','radio','Y','Y','提现开关，关闭后客户不能发起提现',1,'开启|关闭','Y|N',NULL,1669567129,1669567129,1),
	(33,'withdraw','min_money','最低提现金额','number','100','Y','提现最低金额',2,NULL,NULL,NULL,1669567129,1784532963,1),
	(34,'withdraw','max_money','最高提现金额','number','1000000','1','提现最高金额',3,'','',NULL,1669567129,1784431306,1),
	(35,'withdraw','withdraw_rate','提现手续费','number','1','Y','提现手续费',3,'','',NULL,1669567129,1784431384,1),
	(36,'withdraw','descr','提现描述','textarea','','N','提现说明',4,NULL,NULL,NULL,1669567129,1669567129,1),
	(37,'register','is_open','注册奖励开关','radio','Y','Y','注册获得奖励的开关',1,'开启|关闭','Y|N',NULL,1669567129,1669567129,1),
	(38,'register','point','赠送积分','number','0','Y','注册成功后赠送的积分',3,NULL,NULL,'分',1669567129,1669567129,1),
	(39,'register','wallet','赠送余额','number','0','Y','注册成功后赠送的钱包余额',4,NULL,NULL,'元',1669567129,1669567129,1),
	(40,'app','version','版本号','number','1.06','Y',NULL,1,NULL,NULL,NULL,1669567129,1718697751,1),
	(41,'app','descr','说明','textarea','系统','Y',NULL,2,NULL,NULL,NULL,1669567129,1669567129,1),
	(42,'app','android_url','Android地址','text','https://d3ledbmdsrn3q2.cloudfront.net/s4c5g9','N',NULL,3,NULL,NULL,NULL,1669567129,1725876100,1),
	(43,'app','ios_url','IOS地址','text','https://d3ledbmdsrn3q2.cloudfront.net/s4c5g9','N',NULL,4,NULL,NULL,NULL,1669567129,1725876100,1),
	(44,'api_football','source_name','数据源网站名称','text','API-Football','1','API-Football',1,'','',NULL,1784708825,1784708825,1),
	(45,'api_football','base_url','API地址','text','https://v3.football.api-sports.io','1','API地址',2,'','',NULL,1784708854,1784708854,1),
	(46,'api_football','api_key','当前API Token','text','','1','当前API Token',3,'','',NULL,1784708872,1784708872,1),
	(47,'api_football','expire_at','到期日期','date','2026-11-09 09:20:13','1','到期日期',4,'','',NULL,1784708926,1784708926,1),
	(48,'betburger','source_name','数据源网站名称','text','BetBurger','1','数据源网站名称',1,'','',NULL,1784709176,1784709176,1),
	(49,'betburger','base_url','API地址','text','https://rest-api-lv.betburger.com','1','API地址',2,'','',NULL,1784709200,1784709200,1),
	(50,'betburger','access_token','当前API Token','text','','0','当前API Token',3,'','',NULL,1784709234,1784709234,1),
	(51,'betburger','search_filter','规则id','text','2189778','1','规则id',4,'','',NULL,1784709254,1784709254,1),
	(52,'betburger','expire_at','到期日期','date','2026-07-06 09:25:36','1','到期日期',5,'','',NULL,1784709276,1784709276,1);

/*!40000 ALTER TABLE `sys_dict_list` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_flow_numbers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_flow_numbers`;

CREATE TABLE `sys_flow_numbers` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '流水单据名称',
  `table` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源表单',
  `prefix` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '流水前缀',
  `rule` tinyint DEFAULT '0' COMMENT '流水号规则(0:无,1:年,2:年月,3:年月日)',
  `random` tinyint(1) DEFAULT '0' COMMENT '流水号是否随机(0:不随机,1:随机)',
  `start_val` tinyint NOT NULL DEFAULT '1' COMMENT '流水号起始值，最大不超过100',
  `digit` tinyint NOT NULL DEFAULT '5' COMMENT '流水号位数(如：00001)',
  `suffix` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '流水后缀',
  `sn` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '流水号值',
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:可用,0:停用,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_table` (`table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='订单流水单号表';

LOCK TABLES `sys_flow_numbers` WRITE;
/*!40000 ALTER TABLE `sys_flow_numbers` DISABLE KEYS */;

INSERT INTO `sys_flow_numbers` (`id`, `name`, `table`, `prefix`, `rule`, `random`, `start_val`, `digit`, `suffix`, `sn`, `descr`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'会员编号','member_user','6',0,1,0,7,'','64117847','',1669567129,1673923474,1),
	(2,'充值订单','member_recharge_order','R',4,1,0,4,'','R20220918127038','',1669567129,1669567129,1),
	(3,'提现订单','member_withdraw_order','W',3,1,0,4,'','W202209183524','',1669567129,1669567129,1),
	(4,'项目订单','arbitrage_project_order','P',3,1,0,4,'','P202209183524','',1669567129,1669567129,1),
	(5,'项目编号','arbitrage_project','8',0,1,0,7,'','87587847','',1669567129,1673923474,1);

/*!40000 ALTER TABLE `sys_flow_numbers` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_ip_visit
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_ip_visit`;

CREATE TABLE `sys_ip_visit` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `client_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访问IP',
  `user_id` int DEFAULT '0' COMMENT '用户ID',
  `country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '国家',
  `total_visit_num` int NOT NULL DEFAULT '0' COMMENT '访问次数',
  `today_visit_num` int NOT NULL DEFAULT '0' COMMENT '今日访问次数',
  `last_visit_time` datetime DEFAULT NULL COMMENT '最后访问时间',
  `limit_type` tinyint(1) DEFAULT '0' COMMENT '限制类型(0:不限制,1:黑名单,2:白名单)',
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '描述',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:可用,0:停用,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_ip` (`client_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IP访问信息表';



# 转储表 sys_lang
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_lang`;

CREATE TABLE `sys_lang` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
  `code` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '编码',
  `locale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '浏览器语言标识',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言图标',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '是否默认',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(1:启用,0:停用,-1:删除)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言';

LOCK TABLES `sys_lang` WRITE;
/*!40000 ALTER TABLE `sys_lang` DISABLE KEYS */;

INSERT INTO `sys_lang` (`id`, `name`, `code`, `locale`, `image`, `is_default`, `sort`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'简体中文','zh_CN','中文(简体)','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/cn.png',0,0,1727236800,1727236800,1),
	(2,'繁体中文','zh_TW','中文(繁体)','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/hk.png',0,1,1727236800,1727236800,0),
	(3,'英语','en_US','English','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/us.png',1,2,1727236800,1727236800,1),
	(4,'日语','ja','日語','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/jp.png',0,3,1727236800,1727236800,1),
	(5,'韩语','ko','한국어','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/kr.png',0,4,1727236800,1727236800,1),
	(6,'俄语','ru','Русский','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/ru.png',0,5,1727236800,1727236800,0),
	(7,'德语','de','Deutsch','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/de.png',0,6,1727236800,1727236800,0),
	(8,'法语','fr','Français','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/fr.png',0,7,1727236800,1727236800,0),
	(9,'西班牙语','es','Español','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/es.png',0,8,1727236800,1727236800,0),
	(10,'葡萄牙语','pt','Português','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/pt.png',0,9,1727236800,1727236800,0),
	(11,'意大利语','it','Italiano','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/it.png',0,10,1727236800,1727236800,0),
	(12,'泰语','th','ภาษาไทย','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/th.png',0,11,1727236800,1727236800,0),
	(13,'越南语','vi','Tiếng Việt','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/vi.png',0,12,1727236800,1727236800,0),
	(14,'印尼语','id','Indonesia','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/id.png',0,13,1727236800,1727236800,0),
	(15,'阿拉伯语','ar','بالعربية','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/ar.png',0,14,1727236800,1727236800,0),
	(16,'荷兰语','nl','Nederlands','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/nl.png',0,15,1727236800,1727236800,0),
	(17,'缅甸语','mm','ဗာရမီဘာသာစကား','https://u.alicdn.com/mobile/g/common/flags/1.0.0/assets/mm.png',0,17,1727236800,1783952651,0);

/*!40000 ALTER TABLE `sys_lang` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_lang_key
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_lang_key`;

CREATE TABLE `sys_lang_key` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'lang键名',
  `parent_id` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '类型',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序值',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '翻译内容',
  `source` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '来源',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT COMMENT='Lang键名存储表';



# 转储表 sys_lang_value
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_lang_value`;

CREATE TABLE `sys_lang_value` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key_id` int NOT NULL COMMENT 'lang_key表id',
  `lang` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'lang语言',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '翻译后的文字值',
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `lang_key` (`key_id`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT COMMENT='Lang翻译值存储表';



# 转储表 sys_make_logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_make_logs`;

CREATE TABLE `sys_make_logs` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建类型',
  `table` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '创建表名',
  `file_class` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件地址',
  `is_modify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '表结构是否修改',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(1:正常,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `create_table` (`type`,`table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='系统操作日志表';

LOCK TABLES `sys_make_logs` WRITE;
/*!40000 ALTER TABLE `sys_make_logs` DISABLE KEYS */;

INSERT INTO `sys_make_logs` (`id`, `type`, `table`, `file_class`, `is_modify`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'model','sys_admin','library\\model\\sys\\AdminModel',0,1741090432,1741090432,1),
	(2,'model','sys_admin_auth','library\\model\\sys\\AdminAuthModel',0,1741090432,1741090432,1),
	(3,'model','sys_admin_logs','library\\model\\sys\\AdminLogsModel',0,1741090432,1741090432,1),
	(4,'model','sys_article','library\\model\\sys\\ArticleModel',0,1741090432,1741090432,1),
	(5,'model','sys_article_category','library\\model\\sys\\ArticleCategoryModel',0,1741090432,1741090432,1),
	(6,'model','sys_casbin_rbac','library\\model\\sys\\CasbinRbacModel',0,1741090432,1741090432,1),
	(7,'model','sys_casbin_restful','library\\model\\sys\\CasbinRestfulModel',0,1741090432,1741090432,1),
	(8,'model','sys_change_logs','library\\model\\sys\\ChangeLogsModel',0,1741090432,1741090432,1),
	(9,'model','sys_config','library\\model\\sys\\ConfigModel',0,1741090432,1741090432,1),
	(10,'model','sys_crontab','library\\model\\sys\\CrontabModel',0,1741090432,1741090432,1),
	(11,'model','sys_crontab_group','library\\model\\sys\\CrontabGroupModel',0,1741090432,1741090432,1),
	(12,'model','sys_crontab_log','library\\model\\sys\\CrontabLogModel',0,1741090432,1741090432,1),
	(13,'model','sys_dept','library\\model\\sys\\DeptModel',0,1741090432,1741090432,1),
	(14,'model','sys_dict','library\\model\\sys\\DictModel',0,1741090432,1741090432,1),
	(15,'model','sys_dict_list','library\\model\\sys\\DictListModel',0,1741090432,1741090432,1),
	(16,'model','sys_flow_numbers','library\\model\\sys\\FlowNumbersModel',0,1741090432,1741090432,1),
	(17,'model','sys_ip_visit','library\\model\\sys\\IpVisitModel',0,1741090432,1741090432,1),
	(18,'model','sys_lang','library\\model\\sys\\LangModel',0,1741090432,1741090432,1),
	(20,'model','sys_make_logs','library\\model\\sys\\MakeLogsModel',0,1741090432,1741090432,1),
	(21,'model','sys_menus','library\\model\\sys\\MenusModel',0,1741090432,1741090432,1),
	(22,'model','sys_operation_logs','library\\model\\sys\\OperationLogsModel',0,1741090432,1741090432,1),
	(23,'model','sys_role','library\\model\\sys\\RoleModel',0,1741090432,1741090432,1),
	(24,'model','sys_route','library\\model\\sys\\RouteModel',0,1741090432,1741090432,1),
	(25,'model','sys_send_msg_log','library\\model\\sys\\SendMsgLogModel',0,1741090432,1741090432,1),
	(26,'model','sys_short_url','library\\model\\sys\\ShortUrlModel',0,1741090432,1741090432,1),
	(27,'model','sys_table_field','library\\model\\sys\\TableFieldModel',0,1741090432,1741090432,1),
	(28,'model','sys_table_list','library\\model\\sys\\TableListModel',0,1741090432,1741090432,1),
	(29,'dao','sys_admin','library\\dao\\sys\\AdminDao',0,1741090438,1741090438,1),
	(30,'dao','sys_admin_auth','library\\dao\\sys\\AdminAuthDao',0,1741090438,1741090438,1),
	(31,'dao','sys_admin_logs','library\\dao\\sys\\AdminLogsDao',0,1741090438,1741090438,1),
	(32,'dao','sys_article','library\\dao\\sys\\ArticleDao',0,1741090438,1741090438,1),
	(33,'dao','sys_article_category','library\\dao\\sys\\ArticleCategoryDao',0,1741090438,1741090438,1),
	(34,'dao','sys_casbin_rbac','library\\dao\\sys\\CasbinRbacDao',0,1741090438,1741090438,1),
	(35,'dao','sys_casbin_restful','library\\dao\\sys\\CasbinRestfulDao',0,1741090438,1741090438,1),
	(36,'dao','sys_change_logs','library\\dao\\sys\\ChangeLogsDao',0,1741090438,1741090438,1),
	(37,'dao','sys_config','library\\dao\\sys\\ConfigDao',0,1741090438,1741090438,1),
	(38,'dao','sys_crontab','library\\dao\\sys\\CrontabDao',0,1741090438,1741090438,1),
	(39,'dao','sys_crontab_group','library\\dao\\sys\\CrontabGroupDao',0,1741090438,1741090438,1),
	(40,'dao','sys_crontab_log','library\\dao\\sys\\CrontabLogDao',0,1741090438,1741090438,1),
	(41,'dao','sys_dept','library\\dao\\sys\\DeptDao',0,1741090438,1741090438,1),
	(42,'dao','sys_dict','library\\dao\\sys\\DictDao',0,1741090438,1741090438,1),
	(43,'dao','sys_dict_list','library\\dao\\sys\\DictListDao',0,1741090438,1741090438,1),
	(44,'dao','sys_flow_numbers','library\\dao\\sys\\FlowNumbersDao',0,1741090438,1741090438,1),
	(45,'dao','sys_ip_visit','library\\dao\\sys\\IpVisitDao',0,1741090438,1741090438,1),
	(46,'dao','sys_lang','library\\dao\\sys\\LangDao',0,1741090438,1741090438,1),
	(48,'dao','sys_make_logs','library\\dao\\sys\\MakeLogsDao',0,1741090438,1741090438,1),
	(49,'dao','sys_menus','library\\dao\\sys\\MenusDao',0,1741090438,1741090438,1),
	(50,'dao','sys_operation_logs','library\\dao\\sys\\OperationLogsDao',0,1741090438,1741090438,1),
	(51,'dao','sys_role','library\\dao\\sys\\RoleDao',0,1741090438,1741090438,1),
	(52,'dao','sys_route','library\\dao\\sys\\RouteDao',0,1741090438,1741090438,1),
	(53,'dao','sys_send_msg_log','library\\dao\\sys\\SendMsgLogDao',0,1741090438,1741090438,1),
	(54,'dao','sys_short_url','library\\dao\\sys\\ShortUrlDao',0,1741090438,1741090438,1),
	(55,'dao','sys_table_field','library\\dao\\sys\\TableFieldDao',0,1741090438,1741090438,1),
	(56,'dao','sys_table_list','library\\dao\\sys\\TableListDao',0,1741090438,1741090438,1),
	(57,'service','sys_admin','library\\service\\sys\\AdminService',0,1741090443,1741090443,1),
	(58,'service','sys_admin_auth','library\\service\\sys\\AdminAuthService',0,1741090443,1741090443,1),
	(59,'service','sys_admin_logs','library\\service\\sys\\AdminLogsService',0,1741090443,1741090443,1),
	(60,'service','sys_article','library\\service\\sys\\ArticleService',0,1741090443,1741090443,1),
	(61,'service','sys_article_category','library\\service\\sys\\ArticleCategoryService',0,1741090443,1741090443,1),
	(62,'service','sys_casbin_rbac','library\\service\\sys\\CasbinRbacService',0,1741090443,1741090443,1),
	(63,'service','sys_casbin_restful','library\\service\\sys\\CasbinRestfulService',0,1741090443,1741090443,1),
	(64,'service','sys_change_logs','library\\service\\sys\\ChangeLogsService',0,1741090443,1741090443,1),
	(65,'service','sys_config','library\\service\\sys\\ConfigService',0,1741090443,1741090443,1),
	(66,'service','sys_crontab','library\\service\\sys\\CrontabService',0,1741090443,1741090443,1),
	(67,'service','sys_crontab_group','library\\service\\sys\\CrontabGroupService',0,1741090443,1741090443,1),
	(68,'service','sys_crontab_log','library\\service\\sys\\CrontabLogService',0,1741090443,1741090443,1),
	(69,'service','sys_dept','library\\service\\sys\\DeptService',0,1741090443,1741090443,1),
	(70,'service','sys_dict','library\\service\\sys\\DictService',0,1741090443,1741090443,1),
	(71,'service','sys_dict_list','library\\service\\sys\\DictListService',0,1741090443,1741090443,1),
	(72,'service','sys_flow_numbers','library\\service\\sys\\FlowNumbersService',0,1741090443,1741090443,1),
	(73,'service','sys_ip_visit','library\\service\\sys\\IpVisitService',0,1741090443,1741090443,1),
	(74,'service','sys_lang','library\\service\\sys\\LangService',0,1741090443,1741090443,1),
	(76,'service','sys_make_logs','library\\service\\sys\\MakeLogsService',0,1741090443,1741090443,1),
	(77,'service','sys_menus','library\\service\\sys\\MenusService',0,1741090443,1741090443,1),
	(78,'service','sys_operation_logs','library\\service\\sys\\OperationLogsService',0,1741090443,1741090443,1),
	(79,'service','sys_role','library\\service\\sys\\RoleService',0,1741090443,1741090443,1),
	(80,'service','sys_route','library\\service\\sys\\RouteService',0,1741090443,1741090443,1),
	(81,'service','sys_send_msg_log','library\\service\\sys\\SendMsgLogService',0,1741090443,1741090443,1),
	(82,'service','sys_short_url','library\\service\\sys\\ShortUrlService',0,1741090443,1741090443,1),
	(83,'service','sys_table_field','library\\service\\sys\\TableFieldService',0,1741090443,1741090443,1),
	(84,'service','sys_table_list','library\\service\\sys\\TableListService',0,1741090443,1741090443,1),
	(90,'model','sys_upload_files','library\\model\\sys\\UploadFilesModel',0,1741091613,1741091613,1),
	(91,'dao','sys_upload_files','library\\dao\\sys\\UploadFilesDao',0,1741093130,1741093130,1),
	(92,'service','sys_upload_files','library\\service\\sys\\UploadFilesService',0,1741093134,1741093134,1),
	(93,'model','sys_notice','library\\model\\sys\\NoticeModel',0,1741309518,1741309518,1),
	(94,'model','sys_notice_category','library\\model\\sys\\NoticeCategoryModel',0,1741309518,1741309518,1),
	(95,'dao','sys_notice','library\\dao\\sys\\NoticeDao',0,1741309522,1741309522,1),
	(96,'dao','sys_notice_category','library\\dao\\sys\\NoticeCategoryDao',0,1741309522,1741309522,1),
	(97,'service','sys_notice','library\\service\\sys\\NoticeService',0,1741309526,1741309526,1),
	(98,'service','sys_notice_category','library\\service\\sys\\NoticeCategoryService',0,1741309526,1741309526,1),
	(100,'controller','sys_admin','app\\admin\\controller\\sys\\AdminController',0,1741442407,1741442407,1),
	(101,'controller','sys_role','app\\admin\\controller\\sys\\RoleController',0,1741442739,1741442739,1),
	(102,'controller','sys_menus','app\\admin\\controller\\sys\\MenusController',0,1741442819,1741442819,1),
	(103,'validator','sys_role','library\\validator\\sys\\RoleValidation',0,1741480503,1741480503,1),
	(104,'validator','sys_admin','library\\validator\\sys\\AdminValidation',0,1741537224,1741537224,1),
	(105,'controller','sys_admin_logs','app\\admin\\controller\\sys\\AdminLogsController',0,1741670518,1741670518,1),
	(106,'controller','sys_operation_logs','app\\admin\\controller\\sys\\OperationLogsController',0,1741670522,1741670522,1),
	(111,'response','sys_role','library\\response\\sys\\RoleResponse',0,1783939097,1783939097,1),
	(112,'response','sys_dept','library\\response\\sys\\DeptResponse',0,1783948090,1783948090,1),
	(113,'response','sys_admin_logs','library\\response\\sys\\AdminLogsResponse',0,1783948654,1783948654,1),
	(114,'response','sys_operation_logs','library\\response\\sys\\OperationLogsResponse',0,1783948658,1783948658,1),
	(115,'response','sys_lang','library\\response\\sys\\LangResponse',0,1783952133,1783952133,1),
	(116,'controller','sys_dict','app\\admin\\controller\\sys\\DictController',0,1783954555,1783954555,1),
	(117,'controller','sys_dict_list','app\\admin\\controller\\sys\\DictListController',0,1783954557,1783954557,1),
	(118,'controller','sys_article','app\\admin\\controller\\sys\\ArticleController',0,1783954567,1783954567,1),
	(119,'controller','sys_article_category','app\\admin\\controller\\sys\\ArticleCategoryController',0,1783954570,1783954570,1),
	(120,'controller','sys_notice','app\\admin\\controller\\sys\\NoticeController',0,1783954576,1783954576,1),
	(121,'controller','sys_notice_category','app\\admin\\controller\\sys\\NoticeCategoryController',0,1783954581,1783954581,1),
	(122,'validator','sys_dict','library\\validator\\sys\\DictValidation',0,1783954610,1783954610,1),
	(123,'validator','sys_dict_list','library\\validator\\sys\\DictListValidation',0,1783954614,1783954614,1),
	(124,'validator','sys_article_category','library\\validator\\sys\\ArticleCategoryValidation',0,1783954622,1783954622,1),
	(125,'validator','sys_article','library\\validator\\sys\\ArticleValidation',0,1783954628,1783954628,1),
	(126,'validator','sys_notice','library\\validator\\sys\\NoticeValidation',0,1783954634,1783954634,1),
	(127,'validator','sys_notice_category','library\\validator\\sys\\NoticeCategoryValidation',0,1783954640,1783954640,1),
	(128,'response','sys_dict','library\\response\\sys\\DictResponse',0,1783954655,1783954655,1),
	(129,'response','sys_dict_list','library\\response\\sys\\DictListResponse',0,1783954659,1783954659,1),
	(130,'response','sys_article','library\\response\\sys\\ArticleResponse',0,1783954664,1783954664,1),
	(131,'response','sys_article_category','library\\response\\sys\\ArticleCategoryResponse',0,1783954668,1783954668,1),
	(132,'response','sys_notice','library\\response\\sys\\NoticeResponse',0,1783954673,1783954673,1),
	(133,'response','sys_notice_category','library\\response\\sys\\NoticeCategoryResponse',0,1783954682,1783954682,1),
	(135,'validator','sys_menus','library\\validator\\sys\\MenusValidation',0,1784003194,1784003194,1),
	(136,'response','sys_menus','library\\response\\sys\\MenusResponse',0,1784003201,1784003201,1),
	(138,'response','sys_route','library\\response\\sys\\RouteResponse',0,1784013206,1784013206,1),
	(139,'controller','sys_route','app\\admin\\controller\\sys\\RouteController',0,1784013521,1784013521,1),
	(141,'model','member_level','library\\model\\member\\LevelModel',0,1784092599,1784092599,1),
	(142,'model','member_recharge_order','library\\model\\member\\RechargeOrderModel',0,1784092599,1784092599,1),
	(143,'model','member_user','library\\model\\member\\UserModel',0,1784092599,1784092599,1),
	(144,'model','member_user_auth','library\\model\\member\\UserAuthModel',0,1784092599,1784092599,1),
	(145,'model','member_user_logs','library\\model\\member\\UserLogsModel',0,1784092599,1784092599,1),
	(146,'model','member_user_team','library\\model\\member\\UserTeamModel',0,1784092599,1784092599,1),
	(149,'model','member_withdraw_order','library\\model\\member\\WithdrawOrderModel',0,1784092599,1784092599,1),
	(150,'model','sys_article_lang','library\\model\\sys\\ArticleLangModel',0,1784092599,1784092599,1),
	(151,'model','sys_country','library\\model\\sys\\CountryModel',0,1784092599,1784092599,1),
	(153,'dao','member_level','library\\dao\\member\\LevelDao',0,1784092604,1784092604,1),
	(154,'dao','member_recharge_order','library\\dao\\member\\RechargeOrderDao',0,1784092604,1784092604,1),
	(155,'dao','member_user','library\\dao\\member\\UserDao',0,1784092604,1784092604,1),
	(156,'dao','member_user_auth','library\\dao\\member\\UserAuthDao',0,1784092604,1784092604,1),
	(157,'dao','member_user_logs','library\\dao\\member\\UserLogsDao',0,1784092604,1784092604,1),
	(158,'dao','member_user_team','library\\dao\\member\\UserTeamDao',0,1784092604,1784092604,1),
	(161,'dao','member_withdraw_order','library\\dao\\member\\WithdrawOrderDao',0,1784092604,1784092604,1),
	(162,'dao','sys_article_lang','library\\dao\\sys\\ArticleLangDao',0,1784092604,1784092604,1),
	(163,'dao','sys_country','library\\dao\\sys\\CountryDao',0,1784092604,1784092604,1),
	(165,'service','member_level','library\\service\\member\\LevelService',0,1784092608,1784092608,1),
	(166,'service','member_recharge_order','library\\service\\member\\RechargeOrderService',0,1784092608,1784092608,1),
	(167,'service','member_user','library\\service\\member\\UserService',0,1784092608,1784092608,1),
	(168,'service','member_user_auth','library\\service\\member\\UserAuthService',0,1784092608,1784092608,1),
	(169,'service','member_user_logs','library\\service\\member\\UserLogsService',0,1784092608,1784092608,1),
	(170,'service','member_user_team','library\\service\\member\\UserTeamService',0,1784092608,1784092608,1),
	(173,'service','member_withdraw_order','library\\service\\member\\WithdrawOrderService',0,1784092608,1784092608,1),
	(174,'service','sys_article_lang','library\\service\\sys\\ArticleLangService',0,1784092608,1784092608,1),
	(175,'service','sys_country','library\\service\\sys\\CountryService',0,1784092608,1784092608,1),
	(177,'validator','member_recharge_order','library\\validator\\member\\RechargeOrderValidation',0,1784099626,1784099626,1),
	(179,'validator','member_withdraw_order','library\\validator\\member\\WithdrawOrderValidation',0,1784099660,1784099660,1),
	(180,'response','member_withdraw_order','library\\response\\member\\WithdrawOrderResponse',0,1784099671,1784099671,1),
	(183,'response','member_recharge_order','library\\response\\member\\RechargeOrderResponse',0,1784099712,1784099712,1),
	(185,'validator','member_user','library\\validator\\member\\UserValidation',0,1784100724,1784100724,1),
	(186,'response','member_user','library\\response\\member\\UserResponse',0,1784100746,1784100746,1),
	(187,'model','member_user_kyc','library\\model\\member\\UserKycModel',0,1784102743,1784102743,1),
	(188,'model','member_user_wallet','library\\model\\member\\UserWalletModel',0,1784102743,1784102743,1),
	(189,'model','member_user_wallet_log','library\\model\\member\\UserWalletLogModel',0,1784102743,1784102743,1),
	(190,'dao','member_user_kyc','library\\dao\\member\\UserKycDao',0,1784102746,1784102746,1),
	(191,'dao','member_user_wallet','library\\dao\\member\\UserWalletDao',0,1784102746,1784102746,1),
	(192,'dao','member_user_wallet_log','library\\dao\\member\\UserWalletLogDao',0,1784102746,1784102746,1),
	(193,'service','member_user_kyc','library\\service\\member\\UserKycService',0,1784102750,1784102750,1),
	(194,'service','member_user_wallet','library\\service\\member\\UserWalletService',0,1784102750,1784102750,1),
	(195,'service','member_user_wallet_log','library\\service\\member\\UserWalletLogService',0,1784102750,1784102750,1),
	(196,'validator','member_user_kyc','library\\validator\\member\\UserKycValidation',0,1784102869,1784102869,1),
	(197,'response','member_user_kyc','library\\response\\member\\UserKycResponse',0,1784102875,1784102875,1),
	(198,'model','sys_lang_key','library\\model\\sys\\LangKeyModel',0,1784123593,1784123593,1),
	(199,'model','sys_lang_value','library\\model\\sys\\LangValueModel',0,1784123593,1784123593,1),
	(200,'dao','sys_lang_key','library\\dao\\sys\\LangKeyDao',0,1784123598,1784123598,1),
	(201,'dao','sys_lang_value','library\\dao\\sys\\LangValueDao',0,1784123598,1784123598,1),
	(202,'service','sys_lang_key','library\\service\\sys\\LangKeyService',0,1784123601,1784123601,1),
	(203,'service','sys_lang_value','library\\service\\sys\\LangValueService',0,1784123601,1784123601,1),
	(207,'validator','member_user_team','library\\validator\\member\\UserTeamValidation',0,1784177673,1784177673,1),
	(208,'response','member_user_team','library\\response\\member\\UserTeamResponse',0,1784177680,1784177680,1),
	(210,'controller','member_user_team','app\\admin\\controller\\member\\UserTeamController',0,1784177916,1784177916,1),
	(211,'controller','member_user','app\\admin\\controller\\member\\UserController',0,1784185877,1784185877,1),
	(212,'controller','member_level','app\\admin\\controller\\member\\LevelController',0,1784185880,1784185880,1),
	(213,'controller','member_recharge_order','app\\admin\\controller\\member\\RechargeOrderController',0,1784185888,1784185888,1),
	(214,'controller','member_user_kyc','app\\admin\\controller\\member\\UserKycController',0,1784185896,1784185896,1),
	(215,'controller','member_withdraw_order','app\\admin\\controller\\member\\WithdrawOrderController',0,1784185906,1784185906,1),
	(216,'validator','member_level','library\\validator\\member\\LevelValidation',0,1784186003,1784186003,1),
	(217,'response','member_level','library\\response\\member\\LevelResponse',0,1784186068,1784186068,1),
	(218,'model','member_order_record','library\\model\\member\\OrderRecordModel',0,1784193966,1784193966,1),
	(219,'model','member_user_oauth','library\\model\\member\\UserOauthModel',0,1784193966,1784193966,1),
	(220,'model','sys_web3_network','library\\model\\sys\\Web3NetworkModel',0,1784193966,1784193966,1),
	(221,'model','sys_web3_network_sweep_task','library\\model\\sys\\Web3NetworkSweepTaskModel',0,1784193966,1784193966,1),
	(222,'model','sys_web3_network_token','library\\model\\sys\\Web3NetworkTokenModel',0,1784193966,1784193966,1),
	(223,'model','sys_web3_network_wallet','library\\model\\sys\\Web3NetworkWalletModel',0,1784193966,1784193966,1),
	(224,'dao','member_order_record','library\\dao\\member\\OrderRecordDao',0,1784193974,1784193974,1),
	(225,'dao','member_user_oauth','library\\dao\\member\\UserOauthDao',0,1784193974,1784193974,1),
	(226,'dao','sys_web3_network','library\\dao\\sys\\Web3NetworkDao',0,1784193974,1784193974,1),
	(227,'dao','sys_web3_network_sweep_task','library\\dao\\sys\\Web3NetworkSweepTaskDao',0,1784193974,1784193974,1),
	(228,'dao','sys_web3_network_token','library\\dao\\sys\\Web3NetworkTokenDao',0,1784193974,1784193974,1),
	(229,'dao','sys_web3_network_wallet','library\\dao\\sys\\Web3NetworkWalletDao',0,1784193974,1784193974,1),
	(230,'service','member_order_record','library\\service\\member\\OrderRecordService',0,1784193978,1784193978,1),
	(231,'service','member_user_oauth','library\\service\\member\\UserOauthService',0,1784193978,1784193978,1),
	(232,'service','sys_web3_network','library\\service\\sys\\Web3NetworkService',0,1784193978,1784193978,1),
	(233,'service','sys_web3_network_sweep_task','library\\service\\sys\\Web3NetworkSweepTaskService',0,1784193978,1784193978,1),
	(234,'service','sys_web3_network_token','library\\service\\sys\\Web3NetworkTokenService',0,1784193978,1784193978,1),
	(235,'service','sys_web3_network_wallet','library\\service\\sys\\Web3NetworkWalletService',0,1784193978,1784193978,1),
	(236,'model','member_transfer_order','library\\model\\member\\TransferOrderModel',0,1784194160,1784194160,1),
	(237,'dao','member_transfer_order','library\\dao\\member\\TransferOrderDao',0,1784194163,1784194163,1),
	(238,'service','member_transfer_order','library\\service\\member\\TransferOrderService',0,1784194165,1784194165,1),
	(239,'model','member_platform_wallet','library\\model\\member\\PlatformWalletModel',0,1784220520,1784220520,1),
	(240,'dao','member_platform_wallet','library\\dao\\member\\PlatformWalletDao',0,1784220523,1784220523,1),
	(241,'service','member_platform_wallet','library\\service\\member\\PlatformWalletService',0,1784220527,1784220527,1),
	(242,'model','arbitrage_attempt','library\\model\\arbitrage\\AttemptModel',0,1784797773,1784797773,1),
	(243,'model','arbitrage_day_plan','library\\model\\arbitrage\\DayPlanModel',0,1784797773,1784797773,1),
	(244,'model','arbitrage_fixture','library\\model\\arbitrage\\FixtureModel',0,1784797773,1784797773,1),
	(245,'model','arbitrage_position','library\\model\\arbitrage\\PositionModel',0,1784797773,1784797773,1),
	(246,'model','arbitrage_signal','library\\model\\arbitrage\\SignalModel',0,1784797773,1784797773,1),
	(247,'model','arbitrage_signal_raw','library\\model\\arbitrage\\SignalRawModel',0,1784797773,1784797773,1),
	(248,'dao','arbitrage_attempt','library\\dao\\arbitrage\\AttemptDao',0,1784797777,1784797777,1),
	(249,'dao','arbitrage_day_plan','library\\dao\\arbitrage\\DayPlanDao',0,1784797777,1784797777,1),
	(250,'dao','arbitrage_fixture','library\\dao\\arbitrage\\FixtureDao',0,1784797777,1784797777,1),
	(251,'dao','arbitrage_position','library\\dao\\arbitrage\\PositionDao',0,1784797777,1784797777,1),
	(252,'dao','arbitrage_signal','library\\dao\\arbitrage\\SignalDao',0,1784797777,1784797777,1),
	(253,'dao','arbitrage_signal_raw','library\\dao\\arbitrage\\SignalRawDao',0,1784797777,1784797777,1),
	(254,'service','arbitrage_attempt','library\\service\\arbitrage\\AttemptService',0,1784797780,1784797780,1),
	(255,'service','arbitrage_day_plan','library\\service\\arbitrage\\DayPlanService',0,1784797780,1784797780,1),
	(256,'service','arbitrage_fixture','library\\service\\arbitrage\\FixtureService',0,1784797780,1784797780,1),
	(257,'service','arbitrage_position','library\\service\\arbitrage\\PositionService',0,1784797780,1784797780,1),
	(258,'service','arbitrage_signal','library\\service\\arbitrage\\SignalService',0,1784797780,1784797780,1),
	(259,'service','arbitrage_signal_raw','library\\service\\arbitrage\\SignalRawService',0,1784797780,1784797780,1),
	(260,'model','arbitrage_project','library\\model\\arbitrage\\ProjectModel',0,1785144393,1785144393,1),
	(261,'model','arbitrage_project_order','library\\model\\arbitrage\\ProjectOrderModel',0,1785144393,1785144393,1),
	(262,'model','arbitrage_project_order_day','library\\model\\arbitrage\\ProjectOrderDayModel',0,1785144393,1785144393,1),
	(263,'model','arbitrage_project_order_logs','library\\model\\arbitrage\\ProjectOrderLogsModel',0,1785144393,1785144393,1),
	(264,'dao','arbitrage_project','library\\dao\\arbitrage\\ProjectDao',0,1785144396,1785144396,1),
	(265,'dao','arbitrage_project_order','library\\dao\\arbitrage\\ProjectOrderDao',0,1785144396,1785144396,1),
	(266,'dao','arbitrage_project_order_day','library\\dao\\arbitrage\\ProjectOrderDayDao',0,1785144396,1785144396,1),
	(267,'dao','arbitrage_project_order_logs','library\\dao\\arbitrage\\ProjectOrderLogsDao',0,1785144396,1785144396,1),
	(268,'service','arbitrage_project','library\\service\\arbitrage\\ProjectService',0,1785144400,1785144400,1),
	(269,'service','arbitrage_project_order','library\\service\\arbitrage\\ProjectOrderService',0,1785144400,1785144400,1),
	(270,'service','arbitrage_project_order_day','library\\service\\arbitrage\\ProjectOrderDayService',0,1785144400,1785144400,1),
	(271,'service','arbitrage_project_order_logs','library\\service\\arbitrage\\ProjectOrderLogsService',0,1785144400,1785144400,1),
	(272,'controller','arbitrage_project','app\\admin\\controller\\arbitrage\\ProjectController',0,1785145273,1785145273,1),
	(273,'controller','arbitrage_project_order','app\\admin\\controller\\arbitrage\\ProjectOrderController',0,1785145293,1785145293,1),
	(274,'response','arbitrage_project','library\\response\\arbitrage\\ProjectResponse',0,1785145313,1785145313,1),
	(275,'validator','arbitrage_project','library\\validator\\arbitrage\\ProjectValidation',0,1785145319,1785145319,1),
	(276,'response','arbitrage_project_order','library\\response\\arbitrage\\ProjectOrderResponse',0,1785145328,1785145328,1),
	(277,'validator','arbitrage_project_order','library\\validator\\arbitrage\\ProjectOrderValidation',0,1785145334,1785145334,1),
	(278,'validator','arbitrage_position','library\\validator\\arbitrage\\PositionValidation',0,1785472801,1785472801,1),
	(279,'response','arbitrage_position','library\\response\\arbitrage\\PositionResponse',0,1785472808,1785472808,1),
	(280,'response','arbitrage_signal','library\\response\\arbitrage\\SignalResponse',0,1785482092,1785482092,1),
	(281,'validator','arbitrage_signal','library\\validator\\arbitrage\\SignalValidation',0,1785482115,1785482115,1);

/*!40000 ALTER TABLE `sys_make_logs` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_menus
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_menus`;

CREATE TABLE `sys_menus` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system' COMMENT '所属平台',
  `name` char(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '模块类型(0:导航,1:目录,2:菜单,3:按钮,4:接口)',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '上级菜单ID',
  `path` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '菜单路径',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标',
  `btn_style` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '按钮颜色标识',
  `route_key` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '接口路由标识符',
  `route_url` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前端路由地址',
  `params` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '参数',
  `choice_ids` tinyint NOT NULL DEFAULT '0' COMMENT '选择数据操作(0:不需选择,1:只能选择一个,2:可选择多个)',
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
  `is_show` tinyint(1) DEFAULT '1' COMMENT '是否显示(1:显示,0:隐藏)',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,0:停用,-1:删除)',
  PRIMARY KEY (`id`),
  KEY `parentid` (`pid`) USING BTREE,
  KEY `menu_type` (`type`),
  KEY `url` (`route_url`),
  KEY `route_id` (`route_key`),
  KEY `platform` (`platform`),
  KEY `ids_status` (`status`,`is_show`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单';

LOCK TABLES `sys_menus` WRITE;
/*!40000 ALTER TABLE `sys_menus` DISABLE KEYS */;

INSERT INTO `sys_menus` (`id`, `platform`, `name`, `type`, `pid`, `path`, `icon`, `btn_style`, `route_key`, `route_url`, `params`, `choice_ids`, `descr`, `sort`, `is_show`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'system','系统管理',1,0,'','layui-icon-set','','','/system',NULL,0,'系统管理主目录',1,1,1785721131,1785721131,1),
	(2,'system','内容管理',1,0,'','layui-icon-template','','','/content',NULL,0,'内容管理主目录',1,1,1785721519,1785721519,1),
	(3,'system','团队管理',1,0,'','layui-icon-user','','','/team',NULL,0,'团队管理主目录',3,1,1785721573,1785721573,1),
	(4,'system','用户',1,0,'','layui-icon-friends','','','/user',NULL,0,'用户主目录',4,1,1785721645,1785721645,1),
	(5,'system','资产管理',1,0,'','layui-icon-rmb','','','/assets',NULL,0,'资产管理主目录',5,1,1785721862,1785721862,1),
	(6,'system','配置',1,0,'','layui-icon-set','','','/configuration',NULL,0,'配置主目录',6,1,1785721924,1785721924,1),
	(7,'system','机器人管理',1,0,'','layui-icon-senior','','','/mining',NULL,0,'机器人管理主目录',7,1,1785721978,1785721978,1),
	(8,'system','内容',2,2,'','layui-icon-list','','0a26f5a309dd08ccc4cae5b59896adbe','/content/list',NULL,0,'内容列表',1,1,1785722981,1785725455,1),
	(9,'system','分类',2,2,'','layui-icon-app','','84d72c33c36bd595e65d749565fc5ca6','/content/classification',NULL,0,'分类列表',2,1,1785723058,1785723102,1),
	(10,'system','团队关系图',2,3,'','layui-icon-user','','847fa604ea1674b322e72756b09cf022','/team/relationship',NULL,0,'团队关系图',1,1,1785723209,1785723209,1),
	(11,'system','用户列表',2,4,'','layui-icon-friends','','6dc225af65c278f9d2834c49d71f993c','/user/index',NULL,0,'用户列表',1,1,1785723273,1785725560,1),
	(12,'system','等级列表',2,4,'','layui-icon-next','','42ec0efe3add050a778986c779c74bd9','/user/grade',NULL,0,'等级列表',2,1,1785723321,1785725588,1),
	(13,'system','充值',2,5,'','layui-icon-prev','','a60af14f02abd1759a5f54d8a3145136','/assets/recharge',NULL,0,'充值',1,1,1785723419,1785723419,1),
	(14,'system','提现',2,5,'','layui-icon-tabs','','5f87b9c7a3c8da9d7f57146c74bd6eb3','/assets/recharge',NULL,0,'提现列表',2,1,1785725681,1785725681,1),
	(15,'system','套利配置',2,6,'','layui-icon-transfer','','98c462802bc69012cfb19a55bc3a2361','/configuration/arbitrage',NULL,0,'套利配置',1,1,1785725823,1785725823,1),
	(16,'system','资金配置',2,6,'','layui-icon-auz','','98c462802bc69012cfb19a55bc3a2361','/configuration/funds',NULL,0,'资金配置',2,1,1785726142,1785726142,1),
	(17,'system','其他配置',2,6,'','layui-icon-template-one','','98c462802bc69012cfb19a55bc3a2361','/configuration/other',NULL,0,'其他配置',3,1,1785726195,1785726195,1),
	(18,'system','支付配置',2,6,'','layui-icon-template','','98c462802bc69012cfb19a55bc3a2361','/configuration/payment',NULL,0,'支付配置',4,1,1785726238,1785726238,1),
	(19,'system','储存配置',2,6,'','layui-icon-radio','','98c462802bc69012cfb19a55bc3a2361','/configuration/storage',NULL,0,'储存配置',5,1,1785726296,1785726296,1),
	(20,'system','系统配置',2,6,'','layui-icon-layouts','','98c462802bc69012cfb19a55bc3a2361','/configuration/system',NULL,0,'系统配置',6,1,1785726346,1785726346,1),
	(21,'system','项目管理',2,7,'','layui-icon-unlink','','76a63f1bcb888638c4e2f07ce5f85dda','/mining/project',NULL,0,'项目管理',1,1,1785726405,1785726405,1),
	(22,'system','订单管理',2,7,'','layui-icon-form','','0790eba6f6bc5573fc13e23e984e0e4c','/mining/order',NULL,0,'订单管理',1,1,1785726461,1785726461,1),
	(23,'system','后台管理员',2,1,'','layui-icon-user','','b1def6ab29f1171f2a0108c8aa0177ae','/system/admin',NULL,0,'后台管理员',1,1,1785726526,1785727177,1),
	(24,'system','角色管理',2,1,'','layui-icon-rate-half','','fd1f20fd14b4a5069725d641696b23ca','/system/role',NULL,0,'角色管理',1,1,1785727996,1785727996,1),
	(25,'system','菜单管理',2,1,'','layui-icon-app','','798e62cbb47439489f05797842d13337','/system/menu',NULL,0,'菜单管理',3,1,1785728065,1785728065,1),
	(26,'system','部门管理',2,1,'','layui-icon-align-right','','487a917aca8d08e23359abe864fd4e67','/system/dept',NULL,0,'菜单管理',3,1,1785728124,1785728142,1),
	(27,'system','字典管理',2,1,'','layui-icon-date','','81d29e98044346e6cead15795ee5c05b','/system/dictionary',NULL,0,'字典管理',0,1,1785728233,1785728233,1),
	(28,'system','语言管理',2,1,'','layui-icon-cellphone','','9ed8c8f878bc90a43acdb18ee2b49553','/language/index',NULL,0,'语言管理',0,1,1785728293,1785728502,1),
	(29,'system','登录日志',2,1,'','layui-icon-list','','0ce6e268e7bc75cad6c052348a084b37','/system/login',NULL,0,'登录日志',10,1,1785728419,1785728419,1),
	(30,'system','操作日志',2,1,'','layui-icon-tabs','','d1e52d4edd44b2442a35f36820e23b3a','/system/option',NULL,0,'操作日志',11,1,1785728490,1785728490,1),
	(31,'system','信号',1,0,'','layui-icon-website','','','/signal',NULL,0,'信号',1,1,1785729927,1785729927,1),
	(32,'system','信号',2,31,'','layui-icon-loading','','fc3b72d83aa52794523b76f4831f92c4','/signal/signal',NULL,0,'信号',0,1,1785729995,1785729995,1),
	(33,'system','套利记录',2,31,'','layui-icon-form','','9fa01032739553fb70dc8f3b9ec90515','/signal/arbitrage.',NULL,0,'套利记录',0,1,1785730048,1785730062,1),
	(34,'system','新增',3,8,'','','','3fcc1459d079b1eed82f12c27c6d7252','',NULL,0,'新增按钮',0,1,1785737609,1785737609,1),
	(35,'system','编辑',3,8,'','','','73ffb464df6adfbce481be563b3dc6b9','',NULL,0,'编辑按钮',0,1,1785742916,1785742916,1),
	(36,'system','删除',3,8,'','','','81396900f2ccb757beb06786e8f72158','',NULL,0,'删除按钮',0,1,1785742996,1785742996,1),
	(37,'system','添加',3,9,'','','','4161a1be84d778795fd4f2008bdebe89','',NULL,0,'添加按钮',0,1,1785743068,1785743068,1),
	(38,'system','编辑',3,9,'','','','a2f7d3f10aa388138375ce2c00c9b613','',NULL,0,'编辑按钮',0,1,1785743118,1785743118,1),
	(39,'system','删除',3,9,'','','','ef815cf91250b3c575f607600a3a242e','',NULL,0,'删除按钮',0,1,1785743153,1785743153,1),
	(40,'system','设置状态',3,9,'','','','a048c33b02047db774e33756bf4779db','',NULL,0,'设置状态开关',0,1,1785743188,1785743188,1),
	(41,'system','设置状态',3,9,'','','','c5dd4e8077050c0b14a128219863731a','',NULL,0,'设置状态开关',0,1,1785743291,1785743291,1),
	(42,'system','添加',3,11,'','','','0007c3e23fd96c19e16a276f60d518f9','',NULL,0,'添加按钮',0,1,1785743450,1785743450,1),
	(43,'system','编辑',3,11,'','','','fc81b5192831cd5a391e2d30ed74f935','',NULL,0,'编辑按钮',0,1,1785743484,1785743484,1),
	(44,'system','备注',3,11,'','','','8326f94ea7f9321502708a24834de053','',NULL,0,'备注按钮',0,1,1785743530,1785743530,1),
	(45,'system','添加余额',3,11,'','','','074e864162923ebd1dfaa13746c1ce01','',NULL,0,'添加余额按钮',0,1,1785743572,1785743572,1),
	(46,'system','删除',3,11,'','','','3d31797a1d5324d8657ee80e1a472d6c','',NULL,0,'删除按钮',0,1,1785743606,1785743606,1),
	(47,'system','编辑',3,12,'','','','ae11f4682f0aa61e901386da8c489f85','',NULL,0,'编辑按钮',0,1,1785743661,1785743661,1),
	(48,'system','添加',3,12,'','','','c76809fea81f9a682ee6025cd150ca9f','',NULL,0,'添加按钮',0,1,1785743697,1785743697,1),
	(49,'system','设置状态',3,12,'','','','afb070141ca2c663702e8ef921118aa0','',NULL,0,'设置状态',0,1,1785743732,1785743732,1),
	(50,'system','删除',3,12,'','','','536af82790774d3f81c5e834f54e4221','',NULL,0,'删除按钮',0,1,1785743760,1785743760,1),
	(51,'system','审核',3,14,'','','','1186de5090f642de3f017787e813a024','',NULL,0,'审核按钮',0,1,1785744135,1785744135,1),
	(52,'system','添加',3,21,'','','','edf2dad1314eeff56f6427f52ae7407a','',NULL,0,'添加按钮',0,1,1785744291,1785744291,1),
	(53,'system','修改',3,21,'','','','928b16d3f11d2209b67db14ed60999a2','',NULL,0,'修改按钮',0,1,1785744370,1785744370,1),
	(54,'system','删除',3,21,'','','','fcf30868abb640fff87dfe3c3f8f5139','',NULL,0,'删除按钮',0,1,1785744404,1785744404,1),
	(55,'system','设置状态',3,21,'','','','ffb3fd3e89f299b93a14a9a516405c18','',NULL,0,'设置状态',0,1,1785744429,1785744429,1),
	(56,'system','添加',3,28,'','','','e8463662c25de6a96b4293fee3f1d63b','',NULL,0,'添加按钮',0,1,1785744735,1785744735,1),
	(57,'system','修改',3,28,'','','','590557b06171d81325d10aa93a5a32ad','',NULL,0,'修改按钮',0,1,1785744764,1785744764,1),
	(58,'system','删除',3,28,'','','','4b0707d6aa89bdb541824d73934768b7','',NULL,0,'删除按钮',0,1,1785744795,1785744795,1),
	(59,'system','设置状态',3,28,'','','','37ad2148c546e8565076a0057cd455d4','',NULL,0,'设置状态',0,1,1785744822,1785744822,1),
	(60,'system','添加',3,24,'','','','ee94097ddd3e8568490999d04f74b560','',NULL,0,'添加按钮',0,1,1785744880,1785744880,1),
	(61,'system','修改',3,24,'','','','724c7e124c425f5a5855885690caf373','',NULL,0,'修改按钮',0,1,1785744920,1785744920,1),
	(62,'system','删除',3,24,'','','','62773a18a3d2348c37d3e9f5dbdc1df1','',NULL,0,'删除按钮',0,1,1785744952,1785744952,1),
	(63,'system','设置状态',3,24,'','','','699505050ae6625a01348624e3faea6e','',NULL,0,'设置状态',0,1,1785744997,1785744997,1),
	(64,'system','设置角色菜单权限',3,24,'','','','b033477aebbbfec1b6642c42c4615d42','',NULL,0,'设置角色菜单权限',0,1,1785745043,1785745043,1),
	(65,'system','添加',3,25,'','','','523d30782471c54c3cd34b094f4aff62','',NULL,0,'添加按钮',0,1,1785745131,1785745131,1),
	(66,'system','修改菜单',3,25,'','','','cfb5a178fc114fb4fcf8f254938c632f','',NULL,0,'修改菜单',0,1,1785745182,1785745182,1),
	(67,'system','删除',3,25,'','','','c491b9996e25a146256fae1049e0d84b','',NULL,0,'删除按钮',0,1,1785745230,1785745230,1),
	(68,'system','获取菜单所有数据',4,25,'','','','798e62cbb47439489f05797842d13337','',NULL,0,'获取菜单所有数据',0,1,1785745317,1785745317,1),
	(69,'system','获取上级菜单数据',4,25,'','','','744d30b7288363e8d68ed16f57965cdb','',NULL,0,'获取上级菜单数据',0,1,1785745357,1785745357,1),
	(70,'system','获取用户的菜单权限',4,25,'','','','5cef6eab2c93abfed373bf85e4e605fc','',NULL,0,'获取用户的菜单权限',0,1,1785745408,1785746531,1),
	(71,'system','添加',3,26,'','','','b050360b6d8e09e28034bb4f1e9bd79f','',NULL,0,'添加按钮',0,1,1785745452,1785745452,1),
	(72,'system','修改',3,26,'','','','8c86bb9bca3c5730aa91bdc1ca439723','',NULL,0,'修改按钮',0,1,1785745485,1785745485,1),
	(73,'system','删除',3,26,'','','','ae0451f2f3b0cbb52396e14a0bcd2bb2','',NULL,0,'删除按钮',0,1,1785745521,1785745521,1),
	(74,'system','设置状态',3,26,'','','','b8f3a0a8aca9ded1a1ce4e5dd40e6a38','',NULL,0,'设置状态',0,1,1785745564,1785745564,1),
	(75,'system','保存',3,15,'','','','2ec4e5f3fd8dfbc075e52f128d46532c','',NULL,0,'保存按钮',0,1,1785745793,1785745793,1),
	(76,'system','保存',3,16,'','','','2ec4e5f3fd8dfbc075e52f128d46532c','',NULL,0,'保存',0,1,1785745826,1785745826,1),
	(77,'system','保存',3,17,'','','','2ec4e5f3fd8dfbc075e52f128d46532c','',NULL,0,'保存',0,1,1785746575,1785746575,1),
	(78,'system','保存',3,18,'','','','2ec4e5f3fd8dfbc075e52f128d46532c','',NULL,0,'保存',0,1,1785746601,1785746601,1),
	(79,'system','保存',3,19,'','','','2ec4e5f3fd8dfbc075e52f128d46532c','',NULL,0,'保存',0,1,1785746619,1785746619,1),
	(80,'system','保存',3,20,'','','','2ec4e5f3fd8dfbc075e52f128d46532c','',NULL,0,'保存',0,1,1785746635,1785746635,1),
	(81,'system','添加',3,23,'','','','9d8e424a2c91819ef8af3ce8360577b8','',NULL,0,'保存',0,1,1785746727,1785746727,1),
	(82,'system','修改',3,23,'','','','257b9c5817aba11bfd689fa048ccea31','',NULL,0,'修改',0,1,1785746753,1785746753,1),
	(83,'system','删除',3,23,'','','','19c35f4689fcc0a488192dabf04da1f4','',NULL,0,'删除',0,1,1785746796,1785746796,1);

/*!40000 ALTER TABLE `sys_menus` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_notice
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_notice`;

CREATE TABLE `sys_notice` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `eid` int NOT NULL DEFAULT '0' COMMENT '企业ID(0:平台)',
  `admin_id` int NOT NULL DEFAULT '0' COMMENT '用户ID(0:所有)',
  `category_id` int unsigned DEFAULT NULL COMMENT '公告分类',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序值',
  `is_rec` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '最后修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,0:不显示,-1:删除)',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表';



# 转储表 sys_notice_category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_notice_category`;

CREATE TABLE `sys_notice_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序值',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '最后修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,-1:删除)',
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告分类';



# 转储表 sys_operation_logs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_operation_logs`;

CREATE TABLE `sys_operation_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `module` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'backend' COMMENT '模块类型',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '操作人',
  `request_url` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访问URL',
  `request_method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '请求类型',
  `request_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '请求的数据',
  `request_date` date NOT NULL COMMENT '记录日期',
  `refer_url` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '来源URL',
  `client_ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '访问IP',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `url` (`request_url`),
  KEY `log_date` (`request_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='系统操作日志表';



# 转储表 sys_role
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_role`;

CREATE TABLE `sys_role` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `eid` int NOT NULL DEFAULT '0' COMMENT '企业ID(0:平台)',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名称',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父级角色',
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `menu_ids` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '权限菜单',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `deleted_time` int DEFAULT '0' COMMENT '删除时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:正常,0:停用,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`eid`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT COMMENT='角色表';

LOCK TABLES `sys_role` WRITE;
/*!40000 ALTER TABLE `sys_role` DISABLE KEYS */;

INSERT INTO `sys_role` (`id`, `eid`, `name`, `pid`, `descr`, `sort`, `menu_ids`, `created_time`, `updated_time`, `deleted_time`, `status`)
VALUES
	(1,0,'管理员',0,NULL,23,'120,121,122,123,124,125,126',1741485122,1784080808,0,1);

/*!40000 ALTER TABLE `sys_role` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_route
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_route`;

CREATE TABLE `sys_route` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '路由KEY',
  `module` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模块',
  `controller` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '控制器',
  `action` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '操作',
  `method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '请求类型',
  `plugins` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '插件',
  `url` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL地址',
  `path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '文件类路径',
  `middleware` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '应用的中间件',
  `verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '验证权限(0:不需要登陆,1:需要登陆,2:需要登陆和权限,3:仅限超管访问)',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `status` tinyint(1) DEFAULT '0' COMMENT '是否加入菜单表(0:未加入,1:已加入)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `route_key` (`key`),
  UNIQUE KEY `method_url` (`method`,`url`),
  KEY `module` (`module`,`controller`,`action`),
  KEY `status` (`status`,`verify`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='路由信息表';

LOCK TABLES `sys_route` WRITE;
/*!40000 ALTER TABLE `sys_route` DISABLE KEYS */;

INSERT INTO `sys_route` (`id`, `key`, `module`, `controller`, `action`, `method`, `plugins`, `url`, `path`, `middleware`, `verify`, `created_time`, `updated_time`, `descr`, `status`)
VALUES
	(1,'ab975edc90f29134188638eb3651bf56','admin','uploadImageController','file','POST',NULL,'/admin/uploadImage/file','app\\admin\\controller\\UploadImageController','[\"AuthMiddleware\"]',2,1741165433,1741165433,'本地图片上传',0),
	(2,'8451b5aa9e43f79fd013b905bd4d2f6f','admin','uploadImageController','curl','POST',NULL,'/admin/uploadImage/curl','app\\admin\\controller\\UploadImageController','[\"AuthMiddleware\"]',2,1741165433,1741165433,'curl上传',0),
	(16,'b78a400a115ff81d58e79c83c913e23a','admin','loginController','captcha','GET',NULL,'/admin/login/captcha','app\\admin\\controller\\LoginController','[\"AuthMiddleware\"]',0,1741174188,1741174188,'输出验证码图像',0),
	(17,'bb070dbd19aec2955476c6171baa21a8','admin','loginController','login','POST',NULL,'/admin/login','app\\admin\\controller\\LoginController','[\"AuthMiddleware\"]',0,1741174188,1741174188,'登录',0),
	(19,'31ff290b71f38faf557d1baa60f6749b','admin','loginController','logout','POST',NULL,'/admin/logout','app\\admin\\controller\\LoginController','[\"AuthMiddleware\"]',1,1741174188,1741174188,'退出登录',0),
	(20,'ab0d5d26a193d1b43be05c54f2368587','admin','loginController','sendSmsCode','POST',NULL,'/admin/sendSmsCode','app\\admin\\controller\\LoginController','[\"AuthMiddleware\"]',0,1741174188,1741174188,'发送邮箱验证码',0),
	(22,'9043babd64ee3f046d76c516ff23e15e','admin','loginController','qrcode','GET',NULL,'/admin/login/qrcode','app\\admin\\controller\\LoginController','[\"AuthMiddleware\"]',0,1741229550,1741229550,'扫码登录',-1),
	(23,'ba0b60366285399f1ea3591fd80481c9','admin','accountController','getUserInfo','GET',NULL,'/admin/account/getUserInfo','app\\admin\\controller\\AccountController','[\"AuthMiddleware\"]',2,1741241194,1741241194,'获取用户信息',0),
	(24,'5ef5e3b88d842e1cbebc572669367c8f','admin','accountController','console','GET',NULL,'/admin/account/console','app\\admin\\controller\\AccountController','[\"AuthMiddleware\"]',2,1741241194,1741241194,'获取工作台信息',0),
	(25,'a4c425d697af1e2617bc0c5cecb647ed','admin','accountController','analysis','GET',NULL,'/admin/account/analysis','app\\admin\\controller\\AccountController','[\"AuthMiddleware\"]',2,1741241194,1741241194,'数据分析',0),
	(26,'318b2286f87bb474faa313ce6153f761','admin','schemaFormController','getSearchConfig','GET',NULL,'/admin/schemaForm/search/{code}','app\\admin\\controller\\SchemaFormController','[\"AuthMiddleware\"]',2,1741325106,1741325106,'获取搜索表单配置',0),
	(27,'647f36a9f3cabfa0e841d8c5ad21c9bd','admin','schemaFormController','getListConfig','GET',NULL,'/admin/schemaForm/list/{code}','app\\admin\\controller\\SchemaFormController','[\"AuthMiddleware\"]',2,1741325106,1741325106,'获取列表表单配置',0),
	(28,'c793a86592ef1e6d6093b44663c4cb6b','admin','schemaFormController','getCreateConfig','GET',NULL,'/admin/schemaForm/create/{code}','app\\admin\\controller\\SchemaFormController','[\"AuthMiddleware\"]',2,1741325106,1741325106,'获取创建表单配置',0),
	(29,'3477c73484234093bc725ca23095ae8b','admin','schemaFormController','getUpdateConfig','GET',NULL,'/admin/schemaForm/update/{code}','app\\admin\\controller\\SchemaFormController','[\"AuthMiddleware\"]',2,1741325106,1741325106,'获取修改表单配置',0),
	(30,'b1def6ab29f1171f2a0108c8aa0177ae','admin','adminController','list','GET',NULL,'/admin/sys/admin','app\\admin\\controller\\sys\\AdminController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'后台账号列表',0),
	(31,'9d8e424a2c91819ef8af3ce8360577b8','admin','adminController','add','POST',NULL,'/admin/sys/admin','app\\admin\\controller\\sys\\AdminController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'后台账号添加',0),
	(32,'257b9c5817aba11bfd689fa048ccea31','admin','adminController','update','PUT',NULL,'/admin/sys/admin/{id}','app\\admin\\controller\\sys\\AdminController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'后台账号修改',0),
	(33,'19c35f4689fcc0a488192dabf04da1f4','admin','adminController','delete','DELETE',NULL,'/admin/sys/admin/{id}','app\\admin\\controller\\sys\\AdminController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'后台账号删除',0),
	(34,'ece22fa9948a130ffee57c6ff5438d60','admin','adminController','detail','GET',NULL,'/admin/sys/admin/{id}','app\\admin\\controller\\sys\\AdminController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'后台账号详情',0),
	(35,'37f46ff68f9fd08e58054add095c96cb','admin','menusController','list','GET',NULL,'/admin/sys/menus','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'菜单列表',0),
	(36,'523d30782471c54c3cd34b094f4aff62','admin','menusController','add','POST',NULL,'/admin/sys/menus','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'添加菜单',0),
	(37,'cfb5a178fc114fb4fcf8f254938c632f','admin','menusController','update','PUT',NULL,'/admin/sys/menus/{id}','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'修改菜单',0),
	(38,'c491b9996e25a146256fae1049e0d84b','admin','menusController','delete','DELETE',NULL,'/admin/sys/menus/{id}','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'删除菜单',0),
	(39,'63e444cfe7ed71b78517d7a464d93a17','admin','menusController','detail','GET',NULL,'/admin/sys/menus/{id}','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'菜单详情',0),
	(40,'fd1f20fd14b4a5069725d641696b23ca','admin','roleController','list','GET',NULL,'/admin/sys/role','app\\admin\\controller\\sys\\RoleController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'角色列表',0),
	(41,'ee94097ddd3e8568490999d04f74b560','admin','roleController','add','POST',NULL,'/admin/sys/role','app\\admin\\controller\\sys\\RoleController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'添加角色',0),
	(42,'724c7e124c425f5a5855885690caf373','admin','roleController','update','PUT',NULL,'/admin/sys/role/{id}','app\\admin\\controller\\sys\\RoleController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'修改角色',0),
	(43,'62773a18a3d2348c37d3e9f5dbdc1df1','admin','roleController','delete','DELETE',NULL,'/admin/sys/role/{id}','app\\admin\\controller\\sys\\RoleController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'删除角色',0),
	(44,'8b57b11a758266d0e20248d9892f392b','admin','roleController','detail','GET',NULL,'/admin/sys/role/{id}','app\\admin\\controller\\sys\\RoleController','[\"AuthMiddleware\"]',2,1741443132,1741443132,'角色详情',0),
	(46,'699505050ae6625a01348624e3faea6e','admin','roleController','setStatus','PUT',NULL,'/admin/sys/role/setStatus/{id}','app\\admin\\controller\\sys\\RoleController','[\"AuthMiddleware\"]',2,1741481339,1741481339,'设置角色状态',0),
	(50,'487a917aca8d08e23359abe864fd4e67','admin','deptController','list','GET',NULL,'/admin/sys/dept','app\\admin\\controller\\sys\\DeptController','[\"AuthMiddleware\"]',2,1741657781,1741657781,'部门列表',0),
	(51,'b050360b6d8e09e28034bb4f1e9bd79f','admin','deptController','add','POST',NULL,'/admin/sys/dept','app\\admin\\controller\\sys\\DeptController','[\"AuthMiddleware\"]',2,1741657781,1741657781,'添加部门',0),
	(52,'8c86bb9bca3c5730aa91bdc1ca439723','admin','deptController','update','PUT',NULL,'/admin/sys/dept/{id}','app\\admin\\controller\\sys\\DeptController','[\"AuthMiddleware\"]',2,1741657781,1741657781,'修改部门',0),
	(53,'ae0451f2f3b0cbb52396e14a0bcd2bb2','admin','deptController','delete','DELETE',NULL,'/admin/sys/dept/{id}','app\\admin\\controller\\sys\\DeptController','[\"AuthMiddleware\"]',2,1741657781,1741657781,'删除部门',0),
	(55,'b8f3a0a8aca9ded1a1ce4e5dd40e6a38','admin','deptController','setStatus','PUT',NULL,'/admin/sys/dept/setStatus/{id}','app\\admin\\controller\\sys\\DeptController','[\"AuthMiddleware\"]',2,1741657781,1741657781,'设置部门状态',0),
	(56,'a2ee9b850d79d96e9bf71ebd78dc951d','admin','deptController','detail','GET',NULL,'/admin/sys/dept/{id}','app\\admin\\controller\\sys\\DeptController','[\"AuthMiddleware\"]',2,1741657781,1741657781,'部门详情',0),
	(57,'0ce6e268e7bc75cad6c052348a084b37','admin','adminLogsController','list','GET',NULL,'/admin/sys/adminLogs','app\\admin\\controller\\sys\\AdminLogsController','[\"AuthMiddleware\"]',2,1741671651,1741671651,'列表',0),
	(58,'6c361d461eed71daf7020d948871bada','admin','adminLogsController','delete','DELETE',NULL,'/admin/sys/adminLogs/{id}','app\\admin\\controller\\sys\\AdminLogsController','[\"AuthMiddleware\"]',2,1741671651,1741671651,'删除',0),
	(59,'a1652fcdc72827ffbe2ceca2b6f7c7f7','admin','adminLogsController','detail','GET',NULL,'/admin/sys/adminLogs/{id}','app\\admin\\controller\\sys\\AdminLogsController','[\"AuthMiddleware\"]',2,1741671651,1741671651,'详情',0),
	(60,'d1e52d4edd44b2442a35f36820e23b3a','admin','operationLogsController','list','GET',NULL,'/admin/sys/operationLogs','app\\admin\\controller\\sys\\OperationLogsController','[\"AuthMiddleware\"]',2,1741671651,1741671651,'列表',0),
	(61,'59a24069350a33a5bcb54bc12a0861a2','admin','operationLogsController','delete','DELETE',NULL,'/admin/sys/operationLogs/{id}','app\\admin\\controller\\sys\\OperationLogsController','[\"AuthMiddleware\"]',2,1741671651,1741671651,'删除',0),
	(62,'512122adf8901fabc665a2a1dd4639b7','admin','operationLogsController','detail','GET',NULL,'/admin/sys/operationLogs/{id}','app\\admin\\controller\\sys\\OperationLogsController','[\"AuthMiddleware\"]',2,1741671651,1741671651,'详情',0),
	(63,'6cead666c882834a6e9c58e610a72798','admin','schemaFormController','list','GET',NULL,'/admin/schemaForm/list','app\\admin\\controller\\SchemaFormController','[\"AuthMiddleware\"]',2,1783753527,1783753527,'获取所有表单',0),
	(64,'e5240b156e76c4cb34a96e8b2ef2f5a5','api','uploadImageController','file','POST',NULL,'/api/uploadImage/file','app\\api\\controller\\UploadImageController','[\"AuthMiddleware\"]',1,1783873528,1783873528,'本地图片上传',0),
	(65,'5fc7e74e0965d7cd490bec509366ee1e','api','uploadImageController','curl','POST',NULL,'/api/uploadImage/curl','app\\api\\controller\\UploadImageController','[\"AuthMiddleware\"]',1,1783873528,1783873528,'curl上传',0),
	(114,'c6cf1997aa02fce74ea8f3f360ca6c50','admin','roleController','all','GET',NULL,'/admin/sys/roleAll','app\\admin\\controller\\sys\\RoleController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'获取角色数据',0),
	(115,'c3065f44f8ce89ce77fb81b8e32aab20','admin','noticeController','list','GET',NULL,'/admin/sys/notice','app\\admin\\controller\\sys\\NoticeController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'通知列表',0),
	(116,'8c432ec8979308596529d2bf9cbe2e5a','admin','noticeController','detail','GET',NULL,'/admin/sys/notice/{id}','app\\admin\\controller\\sys\\NoticeController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'通知详情',0),
	(117,'a4db5dea8237c990d16f08bf353abab7','admin','noticeController','add','POST',NULL,'/admin/sys/notice','app\\admin\\controller\\sys\\NoticeController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'添加通知',0),
	(118,'4f303b8f297616aa95b5470ded1adef1','admin','noticeController','update','PUT',NULL,'/admin/sys/notice/{id}','app\\admin\\controller\\sys\\NoticeController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'修改通知',0),
	(119,'cedfd867f90b3ba8f4035e8178ca6f5b','admin','noticeController','setStatus','PUT',NULL,'/admin/sys/notice/setStatus/{id}','app\\admin\\controller\\sys\\NoticeController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'设置通知状态',0),
	(120,'6d17966efc8663eca792e8c6497b8470','admin','noticeController','delete','DELETE',NULL,'/admin/sys/notice/{id}','app\\admin\\controller\\sys\\NoticeController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'删除通知',0),
	(121,'ab7fe9ecc2746f878dd7f365c82d3f18','admin','noticeController','deleteAll','DELETE',NULL,'/admin/sys/notice/deleteAll/{ids}','app\\admin\\controller\\sys\\NoticeController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'批量删除通知',0),
	(122,'81d29e98044346e6cead15795ee5c05b','admin','dictController','list','GET',NULL,'/admin/sys/dict','app\\admin\\controller\\sys\\DictController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'字典列表',0),
	(123,'7ce370b651592e37e0c794d74cc6bed5','admin','dictController','detail','GET',NULL,'/admin/sys/dict/{id}','app\\admin\\controller\\sys\\DictController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'字典详情',0),
	(124,'271e37fbb6dcaaf603c8c42f7f478fa4','admin','dictController','add','POST',NULL,'/admin/sys/dict','app\\admin\\controller\\sys\\DictController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'添加字典',0),
	(125,'b3de66ba66fe9fc3038dab5acfc7c7a8','admin','dictController','update','PUT',NULL,'/admin/sys/dict/{id}','app\\admin\\controller\\sys\\DictController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'修改字典',0),
	(126,'8654e626d93772a32cfa3b5fd9c95250','admin','dictController','setStatus','PUT',NULL,'/admin/sys/dict/setStatus/{id}','app\\admin\\controller\\sys\\DictController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'设置字典状态',0),
	(127,'88a951d537b62f676b3b12e5fc41e5cb','admin','dictController','delete','DELETE',NULL,'/admin/sys/dict/{id}','app\\admin\\controller\\sys\\DictController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'删除字典',0),
	(128,'27072d71c773fdae4d36de9b6d112d67','admin','dictListController','list','GET',NULL,'/admin/sys/dictList','app\\admin\\controller\\sys\\DictListController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'字典数据列表',0),
	(129,'b47ac0771854ebee9a461156a9d8170f','admin','dictListController','detail','GET',NULL,'/admin/sys/dictList/{id}','app\\admin\\controller\\sys\\DictListController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'字典数据详情',0),
	(130,'eba0f7faa2200baa3c465e77b97eb54d','admin','dictListController','add','POST',NULL,'/admin/sys/dictList','app\\admin\\controller\\sys\\DictListController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'添加字典数据',0),
	(131,'34d79d460451ae8898322c2db88c9d4b','admin','dictListController','update','PUT',NULL,'/admin/sys/dictList/{id}','app\\admin\\controller\\sys\\DictListController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'修改字典数据',0),
	(132,'e43fabd92118707c32636b614d4f1a08','admin','dictListController','setStatus','PUT',NULL,'/admin/sys/dictList/setStatus/{id}','app\\admin\\controller\\sys\\DictListController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'设置字典数据状态',0),
	(133,'76bce4d5ac6508987f88987dea695f83','admin','dictListController','delete','DELETE',NULL,'/admin/sys/dictList/{id}','app\\admin\\controller\\sys\\DictListController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'删除字典数据',0),
	(134,'6fe51ed85895665bc66c3f1a5d9e34d9','admin','dictListController','deleteAll','DELETE',NULL,'/admin/sys/dictList/deleteAll/{ids}','app\\admin\\controller\\sys\\DictListController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'批量删除字典数据',0),
	(135,'ecee2ccaaa900ba7e0f187662d2d545c','admin','articleCategoryController','all','GET',NULL,'/admin/sys/articleCategoryAll','app\\admin\\controller\\sys\\ArticleCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'获取部门数据',0),
	(136,'84d72c33c36bd595e65d749565fc5ca6','admin','articleCategoryController','list','GET',NULL,'/admin/sys/articleCategory','app\\admin\\controller\\sys\\ArticleCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'内容分类列表',0),
	(137,'71e99d8c64143f773592277cd5a861e8','admin','articleCategoryController','detail','GET',NULL,'/admin/sys/articleCategory/{id}','app\\admin\\controller\\sys\\ArticleCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'内容分类详情',0),
	(138,'4161a1be84d778795fd4f2008bdebe89','admin','articleCategoryController','add','POST',NULL,'/admin/sys/articleCategory','app\\admin\\controller\\sys\\ArticleCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'添加内容分类',0),
	(139,'a2f7d3f10aa388138375ce2c00c9b613','admin','articleCategoryController','update','PUT',NULL,'/admin/sys/articleCategory/{id}','app\\admin\\controller\\sys\\ArticleCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'修改内容分类',0),
	(140,'a048c33b02047db774e33756bf4779db','admin','articleCategoryController','setStatus','PUT',NULL,'/admin/sys/articleCategory/setStatus/{id}','app\\admin\\controller\\sys\\ArticleCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'设置内容分类状态',0),
	(141,'ef815cf91250b3c575f607600a3a242e','admin','articleCategoryController','delete','DELETE',NULL,'/admin/sys/articleCategory/{id}','app\\admin\\controller\\sys\\ArticleCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'删除内容分类',0),
	(142,'0a26f5a309dd08ccc4cae5b59896adbe','admin','articleController','list','GET',NULL,'/admin/sys/article','app\\admin\\controller\\sys\\ArticleController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'内容列表',0),
	(143,'14f36ce128202cfd61fa1c6e9c7c0763','admin','articleController','detail','GET',NULL,'/admin/sys/article/{id}','app\\admin\\controller\\sys\\ArticleController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'内容详情',0),
	(144,'3fcc1459d079b1eed82f12c27c6d7252','admin','articleController','add','POST',NULL,'/admin/sys/article','app\\admin\\controller\\sys\\ArticleController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'添加内容',0),
	(145,'73ffb464df6adfbce481be563b3dc6b9','admin','articleController','update','PUT',NULL,'/admin/sys/article/{id}','app\\admin\\controller\\sys\\ArticleController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'修改内容',0),
	(146,'c5dd4e8077050c0b14a128219863731a','admin','articleController','setStatus','PUT',NULL,'/admin/sys/article/setStatus/{id}','app\\admin\\controller\\sys\\ArticleController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'设置内容状态',0),
	(147,'81396900f2ccb757beb06786e8f72158','admin','articleController','delete','DELETE',NULL,'/admin/sys/article/{id}','app\\admin\\controller\\sys\\ArticleController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'删除内容',0),
	(148,'9ed8c8f878bc90a43acdb18ee2b49553','admin','langController','list','GET',NULL,'/admin/sys/lang','app\\admin\\controller\\sys\\LangController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'语言列表',0),
	(149,'db0fab9c7149a0bb7ba9dd28e2ba2c91','admin','langController','detail','GET',NULL,'/admin/sys/lang/{id}','app\\admin\\controller\\sys\\LangController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'语言详情',0),
	(150,'e8463662c25de6a96b4293fee3f1d63b','admin','langController','add','POST',NULL,'/admin/sys/lang','app\\admin\\controller\\sys\\LangController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'添加语言',0),
	(151,'590557b06171d81325d10aa93a5a32ad','admin','langController','update','PUT',NULL,'/admin/sys/lang/{id}','app\\admin\\controller\\sys\\LangController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'修改语言',0),
	(152,'37ad2148c546e8565076a0057cd455d4','admin','langController','setStatus','PUT',NULL,'/admin/sys/lang/setStatus/{id}','app\\admin\\controller\\sys\\LangController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'设置语言状态',0),
	(153,'4b0707d6aa89bdb541824d73934768b7','admin','langController','delete','DELETE',NULL,'/admin/sys/lang/{id}','app\\admin\\controller\\sys\\LangController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'删除语言',0),
	(154,'d08852be5590d713db0f82cc1d4b2fc7','admin','noticeCategoryController','all','GET',NULL,'/admin/sys/noticeCategoryAll','app\\admin\\controller\\sys\\NoticeCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'获取分类数据',0),
	(155,'7210305f9a854eb7099c556812c800b1','admin','noticeCategoryController','list','GET',NULL,'/admin/sys/noticeCategory','app\\admin\\controller\\sys\\NoticeCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'通知分类列表',0),
	(156,'88739692c9dd8d8a02ad1f4ee5561b1b','admin','noticeCategoryController','detail','GET',NULL,'/admin/sys/noticeCategory/{id}','app\\admin\\controller\\sys\\NoticeCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'通知分类详情',0),
	(157,'aff396f96c51a50cc1f6b1d764c2c056','admin','noticeCategoryController','add','POST',NULL,'/admin/sys/noticeCategory','app\\admin\\controller\\sys\\NoticeCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'添加通知分类',0),
	(158,'595def3932c39d32fae01f717477ede7','admin','noticeCategoryController','update','PUT',NULL,'/admin/sys/noticeCategory/{id}','app\\admin\\controller\\sys\\NoticeCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'修改通知分类',0),
	(159,'4645922f7c79f7921e21066400921cb6','admin','noticeCategoryController','setStatus','PUT',NULL,'/admin/sys/noticeCategory/setStatus/{id}','app\\admin\\controller\\sys\\NoticeCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'设置通知分类状态',0),
	(160,'3223285db471c516286e584ae9e7a482','admin','noticeCategoryController','delete','DELETE',NULL,'/admin/sys/noticeCategory/{id}','app\\admin\\controller\\sys\\NoticeCategoryController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'删除通知分类',0),
	(161,'da001f614e807c85649957a0bbd45458','admin','deptController','all','GET',NULL,'/admin/sys/deptAll','app\\admin\\controller\\sys\\DeptController','[\"AuthMiddleware\"]',2,1783959751,1783959751,'获取部门数据',0),
	(162,'adc8df0c4c414fd86d018a7ad85afdfa','admin','menusController','setStatus','PUT',NULL,'/admin/sys/menus/setStatus/{id}','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1784014020,1784014020,'设置菜单状态',0),
	(163,'a15979ee75b8ae1531de6f9bbb71eb5b','admin','routeController','all','GET',NULL,'/admin/sys/routeAll','app\\admin\\controller\\sys\\RouteController','[\"AuthMiddleware\"]',2,1784014020,1784014020,'获取路由地址数据',0),
	(164,'b531ba2a4c6bb1e9755c38f69223579e','admin','routeController','list','GET',NULL,'/admin/sys/route','app\\admin\\controller\\sys\\RouteController','[\"AuthMiddleware\"]',2,1784014020,1784014020,'路由地址列表',0),
	(165,'1baef79836c446cf028212ee0fc211c8','admin','routeController','detail','GET',NULL,'/admin/sys/route/{id}','app\\admin\\controller\\sys\\RouteController','[\"AuthMiddleware\"]',2,1784014020,1784014020,'路由地址详情',0),
	(166,'751bf12684cb7062232dd126d172c100','admin','schemaFormController','fields','GET',NULL,'/admin/schemaForm/fields','app\\admin\\controller\\SchemaFormController','[\"AuthMiddleware\"]',2,1784014020,1784014020,'获取表格的字段',0),
	(167,'dcdd4239c9d81baf9dab956b0caed20d','admin','schemaFormController','setting','GET',NULL,'/admin/schemaForm/setting','app\\admin\\controller\\SchemaFormController','[\"AuthMiddleware\"]',2,1784014020,1784014020,'设置表单配置',0),
	(168,'798e62cbb47439489f05797842d13337','admin','menusController','all','GET',NULL,'/admin/sys/menusAll','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1784016178,1784016178,'获取菜单数据',0),
	(171,'744d30b7288363e8d68ed16f57965cdb','admin','menusController','parent','GET',NULL,'/admin/sys/menusParent','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1784024106,1784024106,'获取上级菜单数据',0),
	(175,'6076082daf7a1fa77a386c8656f470c2','admin','adminController','setStatus','PUT',NULL,'/admin/sys/admin/setStatus/{id}','app\\admin\\controller\\sys\\AdminController','[\"AuthMiddleware\"]',2,1784038697,1784038697,'设置后台账号状态',0),
	(176,'e2f4c0f0df6068337f4191d8ee4eadf5','admin','adminController','setMenuIds','PUT',NULL,'/admin/sys/admin/setMenuIds/{id}','app\\admin\\controller\\sys\\AdminController','[\"AuthMiddleware\"]',2,1784038697,1784038697,'设置后台账号菜单',0),
	(177,'b033477aebbbfec1b6642c42c4615d42','admin','roleController','setMenuIds','PUT',NULL,'/admin/sys/role/setMenuIds/{id}','app\\admin\\controller\\sys\\RoleController','[\"AuthMiddleware\"]',2,1784038697,1784038697,'设置角色菜单',0),
	(178,'e6ffd76753c2882a4f0cfaeecd8fafd0','admin','adminController','modifyPassword','PUT',NULL,'/admin/sys/admin/modifyPassword/{id}','app\\admin\\controller\\sys\\AdminController','[\"AuthMiddleware\"]',2,1784042570,1784042570,'设置后台账号状态',0),
	(179,'3241a9807816c59c688699e27c967ead','admin','loginController','codeLogin','POST',NULL,'/admin/codeLogin','app\\admin\\controller\\LoginController','[\"AuthMiddleware\"]',2,1784108125,1784108125,'验证码登录',0),
	(182,'2dd931ccf30da5d004fe8747d4354df4','api','loginController','login','POST',NULL,'/api/login','app\\api\\controller\\LoginController','[\"AuthMiddleware\"]',0,1784108129,1784108129,'登录',0),
	(183,'c4133064064ad404e3586ebbd118e9d1','api','loginController','codeLogin','POST',NULL,'/api/mobileLogin','app\\api\\controller\\LoginController','[\"AuthMiddleware\"]',0,1784108129,1784108129,'验证码登录',0),
	(184,'33c1bdc5c9f2c87b1b7e0334779d5dc5','api','loginController','logout','POST',NULL,'/api/logout','app\\api\\controller\\LoginController','[\"AuthMiddleware\"]',0,1784108129,1784108129,'退出登录',0),
	(185,'fd4895a620d309a41e0db3cbdc6e1f5f','api','loginController','sendSmsCode','POST',NULL,'/api/sendSmsCode','app\\api\\controller\\LoginController','[\"AuthMiddleware\"]',0,1784108129,1784108129,'发送验证码',0),
	(186,'223e28d6cc284aaf4b8c8fcf29d9b105','api','loginController','register','POST',NULL,'/api/register','app\\api\\controller\\LoginController','[\"AuthMiddleware\"]',0,1784128890,1784128890,'注册',0),
	(188,'f7450511eb6b72942c6d2a4f99544cff','api','loginController','forget','POST',NULL,'/api/forget','app\\api\\controller\\LoginController','[\"AuthMiddleware\"]',0,1784173020,1784173020,'忘记密码',0),
	(193,'847fa604ea1674b322e72756b09cf022','admin','userTeamController','all','GET',NULL,'/admin/member/userTeamAll','app\\admin\\controller\\member\\UserTeamController','[\"AuthMiddleware\"]',2,1784183751,1784183751,'获取用户团队所有数据',0),
	(194,'b446c8be143e6e0600dbc705a4be3058','admin','userTeamController','list','GET',NULL,'/admin/member/userTeam','app\\admin\\controller\\member\\UserTeamController','[\"AuthMiddleware\"]',2,1784183751,1784183751,'用户团队列表',0),
	(195,'feb0b9e13631113524c509099de50c51','admin','userTeamController','detail','GET',NULL,'/admin/member/userTeam/{id}','app\\admin\\controller\\member\\UserTeamController','[\"AuthMiddleware\"]',2,1784183751,1784183751,'用户团队详情',0),
	(196,'98c462802bc69012cfb19a55bc3a2361','admin','dictController','getDictList','GET',NULL,'/admin/sys/dictGroup/{type}','app\\admin\\controller\\sys\\DictController','[\"AuthMiddleware\"]',2,1784183751,1784183751,'字典群组列表',0),
	(197,'6dc225af65c278f9d2834c49d71f993c','admin','userController','list','GET',NULL,'/admin/member/user','app\\admin\\controller\\member\\UserController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'列表',0),
	(198,'dade81b95a848a3cdd49f98f3339ff3a','admin','userController','detail','GET',NULL,'/admin/member/user/{id}','app\\admin\\controller\\member\\UserController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'详情',0),
	(199,'0007c3e23fd96c19e16a276f60d518f9','admin','userController','add','POST',NULL,'/admin/member/user','app\\admin\\controller\\member\\UserController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'添加',0),
	(200,'fc81b5192831cd5a391e2d30ed74f935','admin','userController','update','PUT',NULL,'/admin/member/user/{id}','app\\admin\\controller\\member\\UserController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'修改',0),
	(201,'1cdda4cc039a8cdb995ad116b3a6161d','admin','userController','setStatus','PUT',NULL,'/admin/member/user/setStatus/{id}','app\\admin\\controller\\member\\UserController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'设置状态',0),
	(202,'3d31797a1d5324d8657ee80e1a472d6c','admin','userController','delete','DELETE',NULL,'/admin/member/user/{id}','app\\admin\\controller\\member\\UserController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'删除',0),
	(203,'9206a4a0121f7f242e67a72b039ddfd9','admin','levelController','all','GET',NULL,'/admin/member/levelAll','app\\admin\\controller\\member\\LevelController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'获取数据',0),
	(204,'42ec0efe3add050a778986c779c74bd9','admin','levelController','list','GET',NULL,'/admin/member/level','app\\admin\\controller\\member\\LevelController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'列表',0),
	(205,'18831fd9466fcbaf499a437b95ebf941','admin','levelController','detail','GET',NULL,'/admin/member/level/{id}','app\\admin\\controller\\member\\LevelController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'详情',0),
	(206,'c76809fea81f9a682ee6025cd150ca9f','admin','levelController','add','POST',NULL,'/admin/member/level','app\\admin\\controller\\member\\LevelController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'添加',0),
	(207,'ae11f4682f0aa61e901386da8c489f85','admin','levelController','update','PUT',NULL,'/admin/member/level/{id}','app\\admin\\controller\\member\\LevelController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'修改',0),
	(208,'afb070141ca2c663702e8ef921118aa0','admin','levelController','setStatus','PUT',NULL,'/admin/member/level/setStatus/{id}','app\\admin\\controller\\member\\LevelController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'设置状态',0),
	(209,'536af82790774d3f81c5e834f54e4221','admin','levelController','delete','DELETE',NULL,'/admin/member/level/{id}','app\\admin\\controller\\member\\LevelController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'删除',0),
	(210,'a60af14f02abd1759a5f54d8a3145136','admin','rechargeOrderController','list','GET',NULL,'/admin/member/rechargeOrder','app\\admin\\controller\\member\\RechargeOrderController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'列表',0),
	(211,'d4513bc9742d3f453aa4e65e7600a6df','admin','rechargeOrderController','detail','GET',NULL,'/admin/member/rechargeOrder/{id}','app\\admin\\controller\\member\\RechargeOrderController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'详情',0),
	(212,'62277d48b9caa45a7f92eba8e9e12c8e','admin','rechargeOrderController','delete','DELETE',NULL,'/admin/member/rechargeOrder/{id}','app\\admin\\controller\\member\\RechargeOrderController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'删除',0),
	(213,'c19b19bab96437bb1fd669cff6a42791','admin','userKycController','list','GET',NULL,'/admin/member/userKyc','app\\admin\\controller\\member\\UserKycController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'实名认证列表',0),
	(214,'d201cfb773d6f00779fd0e849a1c423c','admin','userKycController','detail','GET',NULL,'/admin/member/userKyc/{id}','app\\admin\\controller\\member\\UserKycController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'实名认证详情',0),
	(215,'1b78545305175bec2d089f0a819bbd9a','admin','userKycController','verify','PUT',NULL,'/admin/member/userKyc/verify/{id}','app\\admin\\controller\\member\\UserKycController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'审核实名认证',0),
	(216,'543336c2a6feb7afee30ffe47fd9eee2','admin','userKycController','delete','DELETE',NULL,'/admin/member/userKyc/{id}','app\\admin\\controller\\member\\UserKycController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'删除实名认证',0),
	(217,'5f87b9c7a3c8da9d7f57146c74bd6eb3','admin','withdrawOrderController','list','GET',NULL,'/admin/member/withdrawOrder','app\\admin\\controller\\member\\WithdrawOrderController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'提现订单列表',0),
	(218,'ec2d536bbe54e760c79ee8223eafc2d7','admin','withdrawOrderController','detail','GET',NULL,'/admin/member/withdrawOrder/{id}','app\\admin\\controller\\member\\WithdrawOrderController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'提现订单详情',0),
	(219,'1186de5090f642de3f017787e813a024','admin','withdrawOrderController','verify','PUT',NULL,'/admin/member/withdrawOrder/verify/{id}','app\\admin\\controller\\member\\WithdrawOrderController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'审核提现订单',0),
	(220,'e5f26b51e6aa0abb12a1c084e06837c4','admin','withdrawOrderController','delete','DELETE',NULL,'/admin/member/withdrawOrder/{id}','app\\admin\\controller\\member\\WithdrawOrderController','[\"AuthMiddleware\"]',2,1784197476,1784197476,'删除提现订单',0),
	(224,'32568d10f8308b5e66d7ab1c64a29641','api','commonController','getLangList','GET',NULL,'/api/common/getLangList','app\\api\\controller\\CommonController','[\"AuthMiddleware\"]',0,1784278806,1784278806,'获取语言列表',0),
	(225,'a635158bbe22418a58299c1488979ad3','api','commonController','getCountryList','GET',NULL,'/api/common/getCountryList','app\\api\\controller\\CommonController','[\"AuthMiddleware\"]',0,1784278806,1784278806,'获取国家数据',0),
	(229,'fb27e85defc5d416930d014f23704acc','api','accountController','getUserInfo','GET',NULL,'/api/account/getUserInfo','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1784279908,1784279908,'获取我的用户信息',0),
	(230,'9caeba39234665ebcae5805a238acd0a','api','accountController','getWalletList','GET',NULL,'/api/account/getWalletList','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1784280646,1784280646,'获取我的钱包数据',0),
	(232,'a7a2198abe975606e9a4ea4bfefd5094','api','commonController','getHelpList','GET',NULL,'/api/common/getHelpList','app\\api\\controller\\CommonController','[\"AuthMiddleware\"]',0,1784281569,1784281569,'获取帮助内容',0),
	(233,'86ba503b88993cd024e0a85f1fe25b66','admin','rechargeOrderController','verify','PUT',NULL,'/admin/member/rechargeOrder/verify/{id}','app\\admin\\controller\\member\\RechargeOrderController','[\"AuthMiddleware\"]',2,1784283225,1784283225,'审核充值订单',0),
	(234,'2ec4e5f3fd8dfbc075e52f128d46532c','admin','dictController','saveDictList','PUT',NULL,'/admin/sys/dictGroup/{code}','app\\admin\\controller\\sys\\DictController','[\"AuthMiddleware\"]',2,1784283225,1784283225,'保存字典组数据',0),
	(236,'a8cce7f5424207e929ec1cfe8439d664','api','accountController','setArbitrageStatus','PUT',NULL,'/api/account/setArbitrageStatus','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1784537258,1784537258,'设置套利开启状态',0),
	(237,'3f2168740de2a17eecee373c4ca19409','api','teamController','detail','GET',NULL,'/api/team/detail','app\\api\\controller\\TeamController','[\"AuthMiddleware\"]',1,1784537258,1784537258,'获取我的团队数据',0),
	(239,'3e54dfc709ead3e5dd23bac0a1f33291','api','teamController','list','GET',NULL,'/api/team/list','app\\api\\controller\\TeamController','[\"AuthMiddleware\"]',1,1784537258,1784537258,'获取我的团队列表',0),
	(240,'5cef6eab2c93abfed373bf85e4e605fc','admin','menusController','tree','GET',NULL,'/admin/sys/userTreeMenus','app\\admin\\controller\\sys\\MenusController','[\"AuthMiddleware\"]',2,1784537264,1784537264,'获取用户的菜单权限',0),
	(241,'f552cbaadc57ffa6dd31d6e76f6758fb','api','accountController','modifyPassword','PUT',NULL,'/api/account/modifyPassword','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1784537664,1784537664,'修改账号密码',0),
	(242,'62ab146e8bb4739b05acc4a4ea9f3598','api','accountController','getNetworkWallet','GET',NULL,'/api/account/getNetworkWallet','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1784606375,1784606375,'获取链路钱包地址',0),
	(244,'8326f94ea7f9321502708a24834de053','admin','userController','setRemark','PUT',NULL,'/admin/member/user/setRemark/{id}','app\\admin\\controller\\member\\UserController','[\"AuthMiddleware\"]',2,1784619677,1784619677,'设置用户备注',0),
	(247,'074e864162923ebd1dfaa13746c1ce01','admin','userController','addMoney','POST',NULL,'/admin/member/user/addMoney','app\\admin\\controller\\member\\UserController','[\"AuthMiddleware\"]',2,1784621009,1784621009,'添加用户余额',0),
	(248,'e0a0f9569b5c1bf7bd472f98dad55a6b','api','rechargeController','config','GET',NULL,'/api/recharge/config','app\\api\\controller\\RechargeController','[\"AuthMiddleware\"]',1,1784623734,1784623734,'获取充值配置',0),
	(250,'c32b2aecbb1f17304dcaf541df2ff6f9','api','withdrawController','config','GET',NULL,'/api/withdraw/config','app\\api\\controller\\WithdrawController','[\"AuthMiddleware\"]',1,1784623734,1784623734,'获取提现配置',0),
	(252,'18f51cc3ce5962750084d770b1ace14c','api','withdrawController','create','POST',NULL,'/api/withdraw/create','app\\api\\controller\\WithdrawController','[\"AuthMiddleware\"]',1,1784623734,1784623734,'获取提现配置',0),
	(253,'36438a1afff1571f144b53ae31e58302','api','accountController','getNetworkToken','GET',NULL,'/api/account/getNetworkToken','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1784625111,1784625111,'获取链路钱包地址',0),
	(254,'4e20737f5c3a79e24142b4411d5c43a4','api','rechargeController','lists','GET',NULL,'/api/recharge/lists','app\\api\\controller\\RechargeController','[\"AuthMiddleware\"]',1,1784629422,1784629422,'充值订单列表',0),
	(255,'5fa37e1806a5108bc70178ff6ecd612c','api','withdrawController','lists','GET',NULL,'/api/withdraw/lists','app\\api\\controller\\WithdrawController','[\"AuthMiddleware\"]',1,1784629422,1784629422,'提现订单列表',0),
	(256,'99324a54c1c1f367cac74cea5d3228f4','api','accountController','submitKycData','POST',NULL,'/api/account/submitKycData','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1784695943,1784695943,'提交KYC数据',0),
	(257,'2c70d2c77eb830b5aaea860b7ff9db73','api','accountController','getKycData','GET',NULL,'/api/account/getKycData','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1784708058,1784708058,'获取KYC数据',0),
	(264,'303e53a0c9f07c552b677bb70071aa11','api','commonController','getLevelList','GET',NULL,'/api/common/getLevelList','app\\api\\controller\\CommonController','[\"AuthMiddleware\"]',0,1784786217,1784786217,'获取等级数据',0),
	(265,'ba3ead0aa9dcaac1ea2ff4a348f5ea2c','api','projectController','list','GET',NULL,'/api/project/list','app\\api\\controller\\ProjectController','[\"AuthMiddleware\"]',0,1785160197,1785160197,'矿机项目列表',0),
	(266,'666ab853997e8e12b63edaf8d4c72292','api','projectController','detail','GET',NULL,'/api/project/detail/{id}','app\\api\\controller\\ProjectController','[\"AuthMiddleware\"]',0,1785160197,1785160197,'矿机项目详情',0),
	(267,'131dd56d29f550d065878633127373c7','api','projectOrderController','create','POST',NULL,'/api/projectOrder/create','app\\api\\controller\\ProjectOrderController','[\"AuthMiddleware\"]',1,1785160197,1785160197,'购买矿机',0),
	(268,'de3facd6c3d3df38c6cb659b71b9bfff','api','projectOrderController','list','GET',NULL,'/api/projectOrder/list','app\\api\\controller\\ProjectOrderController','[\"AuthMiddleware\"]',1,1785160197,1785160197,'矿机订单列表',0),
	(269,'b6b6068cdb561edc3b38f187ce215236','api','projectOrderController','detail','GET',NULL,'/api/projectOrder/detail/{id}','app\\api\\controller\\ProjectOrderController','[\"AuthMiddleware\"]',1,1785160197,1785160197,'矿机订单详情',0),
	(270,'76a63f1bcb888638c4e2f07ce5f85dda','admin','projectController','list','GET',NULL,'/admin/arbitrage/project','app\\admin\\controller\\arbitrage\\ProjectController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'矿机项目列表',0),
	(271,'5ae1ed4f9db8b71bec3a7c01c4cefddf','admin','projectController','detail','GET',NULL,'/admin/arbitrage/project/{id}','app\\admin\\controller\\arbitrage\\ProjectController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'矿机项目详情',0),
	(272,'edf2dad1314eeff56f6427f52ae7407a','admin','projectController','add','POST',NULL,'/admin/arbitrage/project','app\\admin\\controller\\arbitrage\\ProjectController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'添加矿机项目',0),
	(273,'928b16d3f11d2209b67db14ed60999a2','admin','projectController','update','PUT',NULL,'/admin/arbitrage/project/{id}','app\\admin\\controller\\arbitrage\\ProjectController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'修改矿机项目',0),
	(274,'ffb3fd3e89f299b93a14a9a516405c18','admin','projectController','setStatus','PUT',NULL,'/admin/arbitrage/project/setStatus/{id}','app\\admin\\controller\\arbitrage\\ProjectController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'设置矿机项目状态',0),
	(275,'fcf30868abb640fff87dfe3c3f8f5139','admin','projectController','delete','DELETE',NULL,'/admin/arbitrage/project/{id}','app\\admin\\controller\\arbitrage\\ProjectController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'删除矿机项目',0),
	(276,'0790eba6f6bc5573fc13e23e984e0e4c','admin','projectOrderController','list','GET',NULL,'/admin/arbitrage/projectOrder','app\\admin\\controller\\arbitrage\\ProjectOrderController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'矿机订单列表',0),
	(277,'f3f23b51ab1e7034cf2a9d49f061e097','admin','projectOrderController','detail','GET',NULL,'/admin/arbitrage/projectOrder/{id}','app\\admin\\controller\\arbitrage\\ProjectOrderController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'矿机订单详情',0),
	(278,'d9bf7816906a369721855698f495aec4','admin','projectOrderController','delete','DELETE',NULL,'/admin/arbitrage/projectOrder/{id}','app\\admin\\controller\\arbitrage\\ProjectOrderController','[\"AuthMiddleware\"]',2,1785160201,1785160201,'删除矿机订单',0),
	(280,'44320e3bc8215d8742460fe96e09343a','api','projectOrderController','projectIds','GET',NULL,'/api/projectOrder/productIds','app\\api\\controller\\ProjectOrderController','[\"AuthMiddleware\"]',1,1785310609,1785310609,'获取我买的矿机ID',0),
	(281,'c66544bc9f3bc661358193b63c198ad5','api','projectOrderController','getIncomeLogs','GET',NULL,'/api/projectOrder/getIncomeLogs','app\\api\\controller\\ProjectOrderController','[\"AuthMiddleware\"]',1,1785459730,1785459730,'矿机订单收益记录',0),
	(282,'2225ff6951edc9552e4e223a70717d77','api','projectOrderController','receive','POST',NULL,'/api/projectOrder/receive','app\\api\\controller\\ProjectOrderController','[\"AuthMiddleware\"]',1,1785459730,1785459730,'领取矿机收益',0),
	(285,'c21a420637ef87a4d5e7b3a6cb867216','api','arbitrageController','tradeLogs','GET',NULL,'/api/arbitrage/tradeLogs','app\\api\\controller\\ArbitrageController','[\"AuthMiddleware\"]',1,1785475138,1785475138,'套利交易记录',0),
	(286,'e0ead0f52a3c26a2d523a29257bfc388','api','arbitrageController','tradeDetail','GET',NULL,'/api/arbitrage/tradeDetail/{id}','app\\api\\controller\\ArbitrageController','[\"AuthMiddleware\"]',1,1785475138,1785475138,'套利交易详情',0),
	(287,'243fedfef7157c7eb794ce34357dd75e','api','projectOrderController','setDefaultOrder','PUT',NULL,'/api/projectOrder/setDefaultOrder/{id}','app\\api\\controller\\ProjectOrderController','[\"AuthMiddleware\"]',1,1785476533,1785476533,'设置默认矿机订单',0),
	(288,'3694d40c492bf0f7ae84acad28600f3a','api','signalController','list','GET',NULL,'/api/signal/list','app\\api\\controller\\SignalController','[\"AuthMiddleware\"]',1,1785482844,1785482844,'信号数据',0),
	(289,'2637989a3842852d64afc178cca90af5','api','signalController','detail','GET',NULL,'/api/signal/detail/{id}','app\\api\\controller\\SignalController','[\"AuthMiddleware\"]',1,1785482844,1785482844,'套利信号详情',0),
	(291,'ffe153a4d78093f8d4a2b925fc2a431b','api','accountController','getWalletLogs','GET',NULL,'/api/account/getWalletLogs','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',1,1785483731,1785483731,'获取我的钱包数据',0),
	(292,'63f6225a798fea8c41ac69aeaf615446','api','accountController','updateUserInfo','PUT',NULL,'/api/account/updateUserInfo','app\\api\\controller\\AccountController','[\"AuthMiddleware\"]',0,1785489784,1785489784,'修改用户信息',0),
	(293,'dac764f94d13a913f16f0f9b5b3c5f6a','admin','userWalletController','detail','GET',NULL,'/admin/member/userWallet/{id}','app\\admin\\controller\\member\\UserWalletController','[\"AuthMiddleware\"]',2,1785746465,1785746465,'钱包账户详情',0),
	(294,'e96bf14781f5d8052aedc35752c1aa0d','admin','userWalletController','list','GET',NULL,'/admin/member/userWallet','app\\admin\\controller\\member\\UserWalletController','[\"AuthMiddleware\"]',2,1785746465,1785746465,'钱包账户列表',0),
	(295,'dc162b7813df8c9fc8a08a199e2b1815','admin','positionController','detail','GET',NULL,'/admin/arbitrage/position/{id}','app\\admin\\controller\\arbitrage\\PositionController','[\"AuthMiddleware\"]',2,1785720086,1785720086,'套利交易详情',0),
	(296,'9fa01032739553fb70dc8f3b9ec90515','admin','positionController','list','GET',NULL,'/admin/arbitrage/position','app\\admin\\controller\\arbitrage\\PositionController','[\"AuthMiddleware\"]',2,1785720086,1785730048,'套利交易列表',1),
	(297,'41f51b543dc753c2c04e2998c4bd8d1a','admin','signalController','detail','GET',NULL,'/admin/arbitrage/signal/{id}','app\\admin\\controller\\arbitrage\\SignalController','[\"AuthMiddleware\"]',2,1785720086,1785720086,'信号详情',0),
	(298,'fc3b72d83aa52794523b76f4831f92c4','admin','signalController','list','GET',NULL,'/admin/arbitrage/signal','app\\admin\\controller\\arbitrage\\SignalController','[\"AuthMiddleware\"]',2,1785720086,1785729995,'信号列表',1),
	(299,'c5ec3179b006c8d834743c3ac08b4b3e','api','commonController','getIpInfo','GET',NULL,'/api/common/getIpInfo','app\\api\\controller\\CommonController','[\"AuthMiddleware\"]',0,1785813090,1785813090,'获取等级数据',0);

/*!40000 ALTER TABLE `sys_route` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_send_msg_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_send_msg_log`;

CREATE TABLE `sys_send_msg_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `send_type` enum('mobile','email') NOT NULL DEFAULT 'email' COMMENT '发送类型',
  `send_to` varchar(30) NOT NULL DEFAULT '' COMMENT '发送的终端(手机邮箱)',
  `title` varchar(100) DEFAULT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `result` text COMMENT '结果',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态(0:未处理,1:发送成功,2:发送失败,-1:删除)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC COMMENT='信息发送表';



# 转储表 sys_short_url
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_short_url`;

CREATE TABLE `sys_short_url` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `code` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '短网址code',
  `long_url` varchar(1024) NOT NULL DEFAULT '' COMMENT '长网址',
  `long_url_hash` varchar(32) NOT NULL DEFAULT '' COMMENT '长网址做hash后的值',
  `request_num` int NOT NULL DEFAULT '0' COMMENT '请求次数',
  `max_num` int DEFAULT '0' COMMENT '最大访问次数',
  `client_ip` varchar(32) NOT NULL DEFAULT '' COMMENT '请求ip',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` int DEFAULT '1' COMMENT '状态(1:可用,0:停用,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `long_url_hash` (`long_url_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='长短网址对应表';



# 转储表 sys_table_field
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_table_field`;

CREATE TABLE `sys_table_field` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tb_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表名称',
  `fd_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字段名称',
  `fd_desc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '字段描述',
  `fd_sort` tinyint NOT NULL DEFAULT '0' COMMENT '字段排序',
  `fd_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '字段类型',
  `is_null` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Y' COMMENT '是否允许为空',
  `is_primary` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'N' COMMENT '是否主键',
  `is_list` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT '是否在列表',
  `is_add` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT '是否添加',
  `is_edit` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT '是否编辑',
  `is_query` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT '是否查询',
  `is_required` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'N' COMMENT '是否必填 ',
  `is_sort` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT '是否排序',
  `query_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '查询模式(eq,neq,gt,egt,lt,elt,like,not_like,in,not_in,between,not_between)',
  `view_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'text' COMMENT '显示类型(text,password,number,textarea,select,radio,checkbox,datetime,date,datepicker,rate,switch,upload,editor)',
  `default_value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '默认值',
  `width` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '宽度',
  `fixed` enum('left','right','center') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '固定类型',
  `customSlot` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '定制显示类型(status,avatar)',
  `placeholder` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '输入描述',
  `colProps` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '["md":24]' COMMENT '响应设计',
  `model_func` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '模型层方法',
  `listen_func` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '前端监听方法(onInput,onChange)',
  `rules` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '正则验证',
  `module_id` char(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '模块ID',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `td_name` (`tb_name`,`fd_name`),
  KEY `is_list` (`is_list`),
  KEY `is_query` (`is_query`),
  KEY `is_add` (`is_add`),
  KEY `is_edit` (`is_edit`),
  KEY `is_must` (`is_required`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表字段';

LOCK TABLES `sys_table_field` WRITE;
/*!40000 ALTER TABLE `sys_table_field` DISABLE KEYS */;

INSERT INTO `sys_table_field` (`id`, `tb_name`, `fd_name`, `fd_desc`, `fd_sort`, `fd_type`, `is_null`, `is_primary`, `is_list`, `is_add`, `is_edit`, `is_query`, `is_required`, `is_sort`, `query_mode`, `view_type`, `default_value`, `width`, `fixed`, `customSlot`, `placeholder`, `colProps`, `model_func`, `listen_func`, `rules`, `module_id`, `created_time`, `updated_time`)
VALUES
	(1,'sys_admin','id','用户ID',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(2,'sys_admin','eid','企业ID(0:平台)',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(3,'sys_admin','account','登陆账号',2,'varchar(32)','N','N','Y','Y','N','Y','Y','N','like','text','',NULL,NULL,NULL,'请输入账号','{\"md\":12}',NULL,NULL,NULL,NULL,1741310000,1741310000),
	(4,'sys_admin','password','密码',3,'char(40)','N','N','N','Y','N','N','Y','N',NULL,'password','',NULL,NULL,NULL,'请输入密码','{\"md\":12}',NULL,NULL,'{\"type\":\"string\",\"min\":8,\"max\":16}',NULL,1741310000,1741310000),
	(5,'sys_admin','role_id','所属角色',4,'int(11)','N','N','Y','Y','Y','Y','Y','N',NULL,'select','',NULL,NULL,'role','请选择所属角色','{\"md\":12}','getRoleList',NULL,NULL,NULL,1741310000,1741310000),
	(6,'sys_admin','dept_id','所属部门',5,'int(11)','N','N','Y','Y','Y','Y','Y','N',NULL,'select','',NULL,NULL,'dept','请选择所属部门','{\"md\":12}','getDeptList',NULL,NULL,NULL,1741310000,1741310000),
	(7,'sys_admin','is_admin','是否管理员',6,'tinyint(1)','N','N','N','Y','Y','N','Y','N',NULL,'radio','0',NULL,NULL,NULL,'请选择是否管理员','{\"md\":12}','getIsAdminStatus',NULL,NULL,NULL,1741310000,1741310000),
	(8,'sys_admin','encrypt','密钥',7,'char(10)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(9,'sys_admin','name','名字',8,'varchar(30)','Y','N','Y','Y','Y','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,'请输入姓名',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(10,'sys_admin','email','邮箱',9,'varchar(60)','Y','N','Y','Y','Y','N','N','N',NULL,'text','',NULL,NULL,NULL,'请输入邮箱',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(11,'sys_admin','mobile','手机号码',10,'char(20)','Y','N','N','Y','Y','N','N','N',NULL,'text','',NULL,NULL,NULL,'请输入手机号',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(12,'sys_admin','avatar','头像地址',11,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(13,'sys_admin','modify_pwd_time','修改密码时间',12,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(14,'sys_admin','login_time','最后登陆时间',13,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(15,'sys_admin','login_cnt','登陆次数',14,'int(11)','N','N','Y','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(16,'sys_admin','login_ip','登陆IP地址',15,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(17,'sys_admin','is_multiple_login','支持多端登录',6,'tinyint(1)','N','N','N','Y','Y','N','Y','N',NULL,'radio','1','',NULL,NULL,'支持多端登录','{\"md\":12}','getMultipleStatus',NULL,NULL,NULL,1741310000,1741310000),
	(18,'sys_admin','descr','描述',17,'varchar(255)','Y','N','N','Y','Y','N','N','N',NULL,'textarea',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(19,'sys_admin','created_time','创建时间',18,'int(11)','N','N','Y','N','N','Y','N','N','','datepicker','0',NULL,NULL,NULL,'请选择时间',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(20,'sys_admin','updated_time','修改时间',19,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(21,'sys_admin','deleted_time','删除时间',20,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(22,'sys_admin','status','状态(1:正常,0:已锁定,-1:已删除)',21,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(23,'sys_admin_auth','id','',0,'bigint(16)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(24,'sys_admin_auth','eid','企业ID(0:平台)',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(25,'sys_admin_auth','admin_id','用户ID',2,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(26,'sys_admin_auth','terminal','终端类型(pc、mobile、app)',3,'char(20)','N','N','N','N','N','N','N','N',NULL,'text','pc',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(27,'sys_admin_auth','token_type','授权类型',4,'varchar(20)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(28,'sys_admin_auth','access_token','access_token',5,'char(40)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(29,'sys_admin_auth','refresh_token','refresh_token',6,'char(40)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(30,'sys_admin_auth','client_ip','客户端ip',7,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(31,'sys_admin_auth','expires_in','刷新失效时间',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(32,'sys_admin_auth','created_time','创建时间',9,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(33,'sys_admin_auth','updated_time','修改时间',10,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(34,'sys_admin_auth','expired_time','失效时间',11,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(35,'sys_admin_auth','status','状态(1:在线,0:不在线,-1:已删除)',12,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(36,'sys_admin_logs','id','',0,'bigint(16)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(37,'sys_admin_logs','eid','企业ID(0:平台)',1,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(38,'sys_admin_logs','account','账号',2,'int(11)','Y','N','Y','N','N','Y','N','N',NULL,'text','0',NULL,NULL,NULL,'请输入账号',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(39,'sys_admin_logs','token','用户token',3,'char(40)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(40,'sys_admin_logs','action','用户行为',4,'varchar(255)','Y','N','Y','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(41,'sys_admin_logs','os','操作系统',5,'varchar(50)','N','N','Y','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(42,'sys_admin_logs','browser','浏览器类型',6,'varchar(100)','N','N','Y','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(43,'sys_admin_logs','client_ip','客户端ip',7,'varchar(30)','N','N','Y','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(44,'sys_admin_logs','descr','描述',8,'text','Y','N','Y','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(45,'sys_admin_logs','created_time','创建时间',9,'int(11)','N','N','Y','N','N','Y','N','N',NULL,'datepicker','0',NULL,NULL,NULL,'请选择时间',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(46,'sys_article','id','文章id',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(47,'sys_article','eid','企业ID(0:平台)',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(48,'sys_article','title','文章标题',2,'varchar(255)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(49,'sys_article','content','文章内容',3,'longtext','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(50,'sys_article','category_id','分类id',4,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(51,'sys_article','image_url','文章图片',5,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(52,'sys_article','link_url','链接地址',6,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(53,'sys_article','author','作者',7,'varchar(50)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(54,'sys_article','is_rec','是否推荐(1:推荐,0:不推荐)',8,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(55,'sys_article','visit_num','阅读量',9,'int(10)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(56,'sys_article','sort','排序',10,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(57,'sys_article','descr','描述',11,'text','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(58,'sys_article','created_time','创建时间',12,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(59,'sys_article','updated_time','最后修改时间',13,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(60,'sys_article','status','状态(1:正常,0:不显示,-1:删除)',14,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(61,'sys_article_category','id','分类id',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(62,'sys_article_category','eid','企业ID(0:平台)',1,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(63,'sys_article_category','name','分类名称',2,'varchar(100)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(64,'sys_article_category','pid','父分类',3,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(65,'sys_article_category','sort','排序',4,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(66,'sys_article_category','created_time','创建时间',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(67,'sys_article_category','updated_time','最后修改时间',6,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(68,'sys_article_category','status','状态(1:正常,0:不显示,-1:删除)',7,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(69,'sys_casbin_rbac','id','规则ID',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(70,'sys_casbin_rbac','ptype','规则类型',1,'char(8)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(71,'sys_casbin_rbac','v0','',2,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(72,'sys_casbin_rbac','v1','',3,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(73,'sys_casbin_rbac','v2','',4,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(74,'sys_casbin_rbac','v3','',5,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(75,'sys_casbin_rbac','v4','',6,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(76,'sys_casbin_rbac','v5','',7,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(77,'sys_casbin_rbac','created_time','',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(78,'sys_casbin_restful','id','规则ID',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(79,'sys_casbin_restful','ptype','规则类型',1,'char(8)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(80,'sys_casbin_restful','v0','',2,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(81,'sys_casbin_restful','v1','',3,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(82,'sys_casbin_restful','v2','',4,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(83,'sys_casbin_restful','v3','',5,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(84,'sys_casbin_restful','v4','',6,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(85,'sys_casbin_restful','v5','',7,'varchar(128)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(86,'sys_casbin_restful','created_time','',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(87,'sys_change_logs','id','自增id',0,'bigint(16)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(88,'sys_change_logs','change_table','修改的表',1,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(89,'sys_change_logs','primary_id','主键ID',2,'bigint(16)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(90,'sys_change_logs','original','原来的值',3,'longtext','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(91,'sys_change_logs','change','修改的值',4,'longtext','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(92,'sys_change_logs','created_time','创建时间',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(93,'sys_config','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(94,'sys_config','eid','企业ID(0:平台)',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(95,'sys_config','name','键',2,'varchar(255)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(96,'sys_config','value','值',3,'longtext','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(97,'sys_config','created_time','创建时间',4,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(98,'sys_config','updated_time','更新时间',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(99,'sys_config','status','状态(1:正常,-1:删除)',6,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(100,'sys_crontab','id','任务ID',0,'int(20)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(101,'sys_crontab','name','任务名称',1,'varchar(64)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(102,'sys_crontab','group_id','任务分组ID',2,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(103,'sys_crontab','command','执行命令',3,'varchar(255)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(104,'sys_crontab','expression','cron执行表达式',4,'varchar(255)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(105,'sys_crontab','timeout','超时时间(秒)',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(106,'sys_crontab','is_notify','是否邮件通知',6,'tinyint(1)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(107,'sys_crontab','notify_email','通知邮件',7,'varchar(100)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(108,'sys_crontab','descr','描述',8,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(109,'sys_crontab','exec_cnt','执行次数',9,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(110,'sys_crontab','prev_time','上一次执行时间',10,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(111,'sys_crontab','created_time','创建时间',11,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(112,'sys_crontab','updated_time','修改时间',12,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(113,'sys_crontab','status','状态（1:正常,2:暂停,0:异常,-1:删除）',13,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(114,'sys_crontab_group','id','分组id',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(115,'sys_crontab_group','name','分组名称',1,'varchar(100)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(116,'sys_crontab_group','sort','排序',2,'int(8)','N','N','N','N','N','N','N','N',NULL,'text','100',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(117,'sys_crontab_group','descr','描述',3,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(118,'sys_crontab_group','created_time','创建时间',4,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(119,'sys_crontab_group','updated_time','修改时间',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(120,'sys_crontab_group','status','状态(1:正常,-1:删除)',6,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(121,'sys_crontab_log','id','任务日志ID',0,'bigint(20)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(122,'sys_crontab_log','cron_id','任务ID',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(123,'sys_crontab_log','cron_command','执行命令',2,'varchar(500)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(124,'sys_crontab_log','run_start_time','运行开始时间',3,'bigint(15)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(125,'sys_crontab_log','run_end_time','运行结束时间',4,'bigint(15)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(126,'sys_crontab_log','duration','消耗时间/毫秒',5,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(127,'sys_crontab_log','message','日志信息',6,'varchar(500)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(128,'sys_crontab_log','exception_info','异常信息',7,'varchar(500)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(129,'sys_crontab_log','created_time','创建时间',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(130,'sys_crontab_log','updated_time','修改时间',9,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(131,'sys_crontab_log','status','执行状态（-1:不符合条件不运行,0:未开始,1:准备运行,2:运行成功,3:运行失败）',10,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(132,'sys_dept','id','id',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(133,'sys_dept','eid','企业ID(0:平台)',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(134,'sys_dept','name','部门名称',2,'varchar(30)','N','N','Y','Y','Y','Y','Y','N','like','text','',NULL,NULL,NULL,'请输入部门名称',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(135,'sys_dept','pid','上级部门',3,'int(11)','N','N','Y','Y','Y','Y','Y','N',NULL,'select','',NULL,NULL,'parent','请选择上级部门',NULL,'getParentList',NULL,NULL,NULL,1741310000,1741310000),
	(136,'sys_dept','admin_id','负责人',4,'int(11)','Y','N','Y','Y','Y','N','Y','N',NULL,'select','0',NULL,NULL,'admin','请选择负责人',NULL,'getAdminList',NULL,NULL,NULL,1741310000,1741310000),
	(137,'sys_dept','sort','排序',5,'int(11)','N','N','Y','Y','Y','N','Y','N',NULL,'number','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(138,'sys_dept','descr','描述',6,'text','Y','N','Y','Y','Y','Y','N','N',NULL,'textarea',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(139,'sys_dept','created_time','创建时间',7,'int(11)','N','N','Y','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(140,'sys_dept','updated_time','修改时间',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(141,'sys_dept','deleted_time','删除时间',9,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(142,'sys_dept','status','状态',10,'tinyint(1)','N','N','Y','N','N','N','N','N',NULL,'text','1',NULL,NULL,'status',NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(143,'sys_dict','id','自增ID',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(144,'sys_dict','name','字典名称',1,'varchar(50)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(145,'sys_dict','code','字典标识码',2,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(146,'sys_dict','type','字典类型',3,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(147,'sys_dict','sort','排序值',4,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(148,'sys_dict','descr','描述',5,'text','Y','N','Y','Y','Y','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(149,'sys_dict','created_time','创建时间',6,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(150,'sys_dict','updated_time','修改时间',7,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(151,'sys_dict','status','状态(1:正常,0:隐藏,-1:删除)',8,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(152,'sys_dict_list','id','自增ID',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(153,'sys_dict_list','dict_code','字典标识码',1,'char(15)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(154,'sys_dict_list','field_code','字段代码',2,'varchar(20)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(155,'sys_dict_list','field_name','字段名称',3,'varchar(50)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(156,'sys_dict_list','field_type','字段类型',4,'char(10)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(157,'sys_dict_list','field_value','字段值',5,'text','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(158,'sys_dict_list','field_required','是否必填',6,'char(1)','Y','N','N','N','N','N','N','N',NULL,'text','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(159,'sys_dict_list','field_tips','字段提示',7,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(160,'sys_dict_list','field_sort','字段排序',8,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(161,'sys_dict_list','value_range_txt','范围值名称',9,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(162,'sys_dict_list','value_range','范围值',10,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(163,'sys_dict_list','addon','扩展符号',11,'varchar(20)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(164,'sys_dict_list','created_time','创建时间',12,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(165,'sys_dict_list','updated_time','修改时间',13,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(166,'sys_dict_list','status','状态(1:正常,-1:删除)',14,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(167,'sys_flow_numbers','id','自增ID',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(168,'sys_flow_numbers','name','流水单据名称',1,'varchar(100)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(169,'sys_flow_numbers','table','来源表单',2,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(170,'sys_flow_numbers','prefix','流水前缀',3,'varchar(20)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(171,'sys_flow_numbers','rule','流水号规则(0:无,1:年,2:年月,3:年月日)',4,'tinyint(4)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(172,'sys_flow_numbers','random','流水号是否随机(0:不随机,1:随机)',5,'tinyint(1)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(173,'sys_flow_numbers','start_val','流水号起始值，最大不超过100',6,'tinyint(4)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(174,'sys_flow_numbers','digit','流水号位数(如：00001)',7,'tinyint(4)','N','N','N','N','N','N','N','N',NULL,'text','5',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(175,'sys_flow_numbers','suffix','流水后缀',8,'varchar(20)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(176,'sys_flow_numbers','sn','流水号值',9,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(177,'sys_flow_numbers','descr','描述',10,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(178,'sys_flow_numbers','created_time','创建时间',11,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(179,'sys_flow_numbers','updated_time','修改时间',12,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(180,'sys_flow_numbers','status','状态(1:可用,0:停用,-1:删除)',13,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(181,'sys_ip_visit','id','自增id',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(182,'sys_ip_visit','client_ip','访问IP',1,'varchar(50)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(183,'sys_ip_visit','user_id','用户ID',2,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(184,'sys_ip_visit','country','国家',3,'varchar(50)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(185,'sys_ip_visit','total_visit_num','访问次数',4,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(186,'sys_ip_visit','today_visit_num','今日访问次数',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(187,'sys_ip_visit','last_visit_time','最后访问时间',6,'datetime','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(188,'sys_ip_visit','limit_type','限制类型(0:不限制,1:黑名单,2:白名单)',7,'tinyint(1)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(189,'sys_ip_visit','descr','描述',8,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(190,'sys_ip_visit','created_time','创建时间',9,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(191,'sys_ip_visit','updated_time','修改时间',10,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(192,'sys_ip_visit','status','状态(1:可用,0:停用,-1:删除)',11,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(193,'sys_lang','id','ID',0,'bigint(20)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(194,'sys_lang','name','名称',1,'varchar(64)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(195,'sys_lang','code','编码',2,'varchar(16)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(196,'sys_lang','locale','浏览器语言标识',3,'varchar(255)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(197,'sys_lang','image','语言图标',4,'varchar(255)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(198,'sys_lang','is_default','是否默认',5,'tinyint(1)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(199,'sys_lang','sort','排序',6,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(200,'sys_lang','created_time','创建时间',7,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(201,'sys_lang','updated_time','修改时间',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(202,'sys_lang','status','是否启用',9,'tinyint(4)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(203,'sys_lang_data','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(204,'sys_lang_data','pid','父级ID',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(205,'sys_lang_data','type','类型',2,'varchar(50)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(206,'sys_lang_data','name','翻译键名',3,'varchar(150)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(207,'sys_lang_data','values','翻译值',4,'text','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(208,'sys_lang_data','sort','排序',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(209,'sys_lang_data','descr','描述',6,'text','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(210,'sys_lang_data','created_time','创建时间',7,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(211,'sys_lang_data','updated_time','修改时间',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(212,'sys_lang_data','status','状态(0:停用,1:待处理,2:已翻译)',9,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(213,'sys_make_logs','id','ID',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(214,'sys_make_logs','type','创建类型',1,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(215,'sys_make_logs','table','创建表名',2,'varchar(50)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(216,'sys_make_logs','file_class','文件地址',3,'varchar(200)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(217,'sys_make_logs','is_modify','表结构是否修改',4,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(218,'sys_make_logs','created_time','创建时间',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(219,'sys_make_logs','updated_time','修改时间',6,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(220,'sys_make_logs','status','状态(1:正常,-1:删除)',7,'tinyint(1)','Y','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(221,'sys_menus','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(222,'sys_menus','platform','所属平台',1,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','system',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(223,'sys_menus','name','菜单名称',2,'char(40)','N','N','Y','N','N','Y','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(224,'sys_menus','type','类型',3,'tinyint(1)','N','N','Y','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(225,'sys_menus','pid','上级菜单',4,'int(11)','N','N','N','N','N','Y','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(226,'sys_menus','path','菜单路径',5,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(227,'sys_menus','icon','图标',6,'varchar(50)','Y','N','Y','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(228,'sys_menus','btn_style','颜色标识(default,primary normal,warm,danger)',7,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(229,'sys_menus','route_key','权限标识',8,'char(32)','Y','N','Y','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(230,'sys_menus','route_url','路由地址',9,'varchar(150)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(231,'sys_menus','component','前端组件地址',10,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(232,'sys_menus','choice_ids','选择数据操作(0:不需选择,1:只能选择一个,2:可选择多个)',11,'tinyint(4)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(233,'sys_menus','descr','描述',12,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(234,'sys_menus','sort','排序',13,'int(11)','N','N','Y','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(235,'sys_menus','created_time','创建时间',14,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(236,'sys_menus','updated_time','修改时间',15,'int(11)','N','N','Y','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(237,'sys_menus','status','状态',14,'tinyint(1)','N','N','Y','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(238,'sys_notice','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(239,'sys_notice','eid','企业ID(0:平台)',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(240,'sys_notice','admin_id','用户ID(0:所有)',2,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(241,'sys_notice','category_id','公告分类',3,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(242,'sys_notice','title','标题',4,'varchar(200)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(243,'sys_notice','content','内容',5,'text','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(244,'sys_notice','sort','排序值',6,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(245,'sys_notice','is_rec','是否推荐',7,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(246,'sys_notice','created_time','创建时间',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(247,'sys_notice','updated_time','最后修改时间',9,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(248,'sys_notice','status','状态(1:正常,0:不显示,-1:删除)',10,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(249,'sys_notice_category','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(250,'sys_notice_category','name','分类名称',1,'varchar(100)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(251,'sys_notice_category','sort','排序值',2,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(252,'sys_notice_category','descr','描述',3,'text','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(253,'sys_notice_category','created_time','创建时间',4,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(254,'sys_notice_category','updated_time','最后修改时间',5,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(255,'sys_notice_category','status','状态(1:正常,-1:删除)',6,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(256,'sys_operation_logs','id','ID',0,'bigint(20)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(257,'sys_operation_logs','module','模块类型',1,'varchar(30)','N','N','Y','N','N','N','N','N',NULL,'text','backend','100',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(258,'sys_operation_logs','user_id','操作人',2,'int(11)','N','N','Y','N','N','N','N','N',NULL,'text','0','80',NULL,'user',NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(259,'sys_operation_logs','request_url','访问URL',4,'varchar(100)','N','N','Y','N','N','Y','N','N','like','text','',NULL,NULL,NULL,'请输入访问URL',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(260,'sys_operation_logs','request_method','请求类型',3,'varchar(30)','N','N','Y','N','N','Y','N','N',NULL,'select','','80',NULL,NULL,'请选择请求类型',NULL,'getMethodList',NULL,NULL,NULL,1741310000,1741310000),
	(261,'sys_operation_logs','request_data','请求的数据',5,'text','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(262,'sys_operation_logs','request_date','记录日期',6,'date','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(263,'sys_operation_logs','refer_url','来源URL',7,'varchar(300)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(264,'sys_operation_logs','client_ip','访问IP',8,'varchar(200)','N','N','Y','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(265,'sys_operation_logs','created_time','创建时间',9,'int(11)','N','N','Y','N','N','Y','N','Y',NULL,'datepicker','0',NULL,NULL,NULL,'请选择时间',NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(266,'sys_role','id','角色ID',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,'60',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(267,'sys_role','eid','企业ID(0:平台)',1,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(268,'sys_role','name','角色名称',2,'varchar(50)','N','N','Y','Y','Y','Y','Y','N','like','text',NULL,NULL,NULL,NULL,'请输入角色名称','{\"md\":18}',NULL,NULL,NULL,NULL,1741310000,1741310000),
	(269,'sys_role','pid','父级角色',3,'int(11)','N','N','Y','Y','Y','Y','Y','N',NULL,'select','','',NULL,'parent','请选择父级角色','','getParentList',NULL,NULL,NULL,1741310000,1741310000),
	(270,'sys_role','descr','描述',4,'varchar(255)','Y','N','N','Y','Y','N','N','N',NULL,'textarea',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(271,'sys_role','sort','排序',3,'int(11)','N','N','Y','Y','Y','N','N','N',NULL,'number','0',NULL,NULL,NULL,'请输入排序值','{\"md\":18}',NULL,NULL,NULL,NULL,1741310000,1741310000),
	(272,'sys_role','menu_ids','权限菜单',6,'text','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(273,'sys_role','created_time','创建时间',7,'int(11)','N','N','Y','N','N','N','N','Y',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(274,'sys_role','updated_time','修改时间',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(275,'sys_role','deleted_time','删除时间',9,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(276,'sys_role','status','可用状态',10,'tinyint(1)','N','N','Y','N','N','N','N','N',NULL,'text','1',NULL,NULL,'status',NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(277,'sys_route','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(278,'sys_route','key','路由KEY',1,'char(32)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(279,'sys_route','module','模块',2,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(280,'sys_route','controller','控制器',3,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(281,'sys_route','action','操作',4,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(282,'sys_route','method','请求类型',5,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(283,'sys_route','plugins','插件',6,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(284,'sys_route','url','URL地址',7,'varchar(150)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(285,'sys_route','path','文件类路径',8,'varchar(100)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(286,'sys_route','middleware','应用的中间件',9,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(287,'sys_route','verify','验证权限(0:不需要登陆,1:需要登陆,2:需要登陆和权限)',10,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(288,'sys_route','created_time','创建时间',11,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(289,'sys_route','updated_time','修改时间',12,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(290,'sys_route','descr','描述',13,'text','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(291,'sys_route','status','是否加入菜单表(0:未加入,1:已加入)',14,'tinyint(1)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(292,'sys_send_msg_log','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(293,'sys_send_msg_log','send_type','发送类型',1,'enum(\'mobile\',\'email\')','N','N','N','N','N','N','N','N',NULL,'text','email',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(294,'sys_send_msg_log','send_to','发送的终端(手机邮箱)',2,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(295,'sys_send_msg_log','title','标题',3,'varchar(100)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(296,'sys_send_msg_log','content','内容',4,'text','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(297,'sys_send_msg_log','result','结果',5,'text','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(298,'sys_send_msg_log','created_time','创建时间',6,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(299,'sys_send_msg_log','updated_time','修改时间',7,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(300,'sys_send_msg_log','status','状态(0:未处理,1:发送成功,2:发送失败,-1:删除)',8,'tinyint(1)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(301,'sys_short_url','id','',0,'bigint(20)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(302,'sys_short_url','code','短网址code',1,'varchar(8)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(303,'sys_short_url','long_url','长网址',2,'varchar(1024)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(304,'sys_short_url','long_url_hash','长网址做hash后的值',3,'varchar(32)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(305,'sys_short_url','request_num','请求次数',4,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(306,'sys_short_url','max_num','最大访问次数',5,'int(11)','Y','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(307,'sys_short_url','client_ip','请求ip',6,'varchar(32)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(308,'sys_short_url','created_time','创建时间',7,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(309,'sys_short_url','updated_time','修改时间',8,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(310,'sys_short_url','status','状态(1:可用,0:停用,-1:删除)',9,'int(1)','Y','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(311,'sys_table_field','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(312,'sys_table_field','tb_name','表名称',1,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(313,'sys_table_field','fd_name','字段名称',2,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(314,'sys_table_field','fd_desc','字段描述',3,'varchar(100)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(315,'sys_table_field','fd_sort','字段排序',4,'tinyint(4)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(316,'sys_table_field','fd_type','字段类型',5,'varchar(50)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(317,'sys_table_field','is_null','是否允许为空',6,'enum(\'Y\',\'N\')','Y','N','N','N','N','N','N','N',NULL,'text','Y',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(318,'sys_table_field','is_primary','是否主键',7,'enum(\'Y\',\'N\')','Y','N','N','N','N','N','N','N',NULL,'text','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(319,'sys_table_field','is_list','是否在列表',8,'enum(\'Y\',\'N\')','N','N','N','N','N','N','N','N',NULL,'text','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(320,'sys_table_field','is_add','是否添加',9,'enum(\'Y\',\'N\')','N','N','N','N','N','N','N','N',NULL,'text','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(321,'sys_table_field','is_edit','是否编辑',10,'enum(\'Y\',\'N\')','N','N','N','N','N','N','N','N',NULL,'text','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(322,'sys_table_field','is_query','是否查询',11,'enum(\'Y\',\'N\')','N','N','N','N','N','N','N','N',NULL,'text','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(323,'sys_table_field','is_must','是否必填 ',12,'enum(\'Y\',\'N\')','Y','N','N','N','N','N','N','N',NULL,'text','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(324,'sys_table_field','is_sort','是否排序',13,'enum(\'Y\',\'N\')','N','N','N','N','N','N','N','N',NULL,'text','N',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(325,'sys_table_field','query_mode','查询模式(eq,neq,gt,egt,lt,elt,like,not_like,in,not_in,between,not_between)',14,'varchar(20)','Y','N','N','N','N','N','N','N',NULL,'text','eq',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(326,'sys_table_field','view_type','显示类型(text,password,number,textarea,select,radio,checkbox,datetime,date,upload,editor)',15,'varchar(20)','Y','N','N','N','N','N','N','N',NULL,'text','text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(327,'sys_table_field','default_value','默认值',16,'varchar(50)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(328,'sys_table_field','descr','描述',17,'varchar(100)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(329,'sys_table_field','module_id','模块ID',18,'char(16)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(330,'sys_table_field','created_time','创建时间',19,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(331,'sys_table_field','updated_time','修改时间',20,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(332,'sys_table_list','id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(333,'sys_table_list','tb_name','表名称',1,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(334,'sys_table_list','tb_code','表编码',2,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(335,'sys_table_list','tb_desc','表格描述',3,'varchar(100)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(336,'sys_table_list','tb_type','表格类型',4,'varchar(20)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(337,'sys_table_list','entity_name','实体类名称',5,'varchar(50)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(338,'sys_table_list','module_id','模块ID',6,'char(16)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(339,'sys_table_list','descr','描述',7,'varchar(100)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(340,'sys_table_list','is_modify','是否修改',8,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(341,'sys_table_list','is_sync','是否同步',9,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(342,'sys_table_list','created_time','创建时间',10,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(343,'sys_table_list','updated_time','修改时间',11,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(344,'sys_table_list','status','状态',12,'tinyint(1)','N','N','N','N','N','N','N','N',NULL,'text','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(345,'sys_upload_files','file_id','',0,'int(11)','N','Y','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(346,'sys_upload_files','file_hash','文件hash值',1,'varchar(50)','N','N','N','N','N','N','N','N',NULL,'text','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(347,'sys_upload_files','source','类型',2,'varchar(30)','Y','N','N','N','N','N','N','N',NULL,'text','admin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(348,'sys_upload_files','user_id','用户ID',3,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(349,'sys_upload_files','from_type','类型',4,'varchar(30)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(350,'sys_upload_files','engine','存储引擎',5,'varchar(20)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(351,'sys_upload_files','file_name','图片名称',6,'varchar(255)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(352,'sys_upload_files','file_path','文件路径',7,'varchar(100)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(353,'sys_upload_files','origin_name','原始图片名称',8,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(354,'sys_upload_files','file_url','图片地址',9,'varchar(255)','Y','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(355,'sys_upload_files','file_ext','后缀名称',10,'varchar(20)','N','N','N','N','N','N','N','N',NULL,'text',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(356,'sys_upload_files','file_size','文件大小',11,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(357,'sys_upload_files','width','宽度',12,'smallint(6)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(358,'sys_upload_files','height','高度',13,'smallint(6)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(359,'sys_upload_files','created_time','创建时间',14,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(360,'sys_upload_files','updated_time','最后修改时间',15,'int(11)','N','N','N','N','N','N','N','N',NULL,'text','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1741310000,1741310000),
	(361,'sys_menus','params','参数',10,'varchar(50)','Y','N','N','N','N','N','N','N','','text',NULL,NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(362,'sys_table_field','is_required','是否必填 ',12,'enum(\'Y\',\'N\')','Y','N','N','N','N','N','N','N','','text','N',NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(363,'sys_table_field','width','宽度',17,'varchar(50)','Y','N','N','N','N','N','N','N','','text',NULL,NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(364,'sys_table_field','fixed','固定类型',18,'enum(\'left\',\'right\',\'center\')','Y','N','N','N','N','N','N','N','','text',NULL,NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(365,'sys_table_field','customSlot','定制显示类型(status,avatar)',19,'varchar(50)','Y','N','N','N','N','N','N','N','','text',NULL,NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(366,'sys_table_field','placeholder','输入描述',20,'varchar(100)','Y','N','N','N','N','N','N','N','','text',NULL,NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(367,'sys_table_field','colProps','响应设计',21,'varchar(100)','Y','N','N','N','N','N','N','N','','text','[\"md\":24]',NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(368,'sys_table_field','model_func','模型层方法',22,'varchar(30)','Y','N','N','N','N','N','N','N','','text',NULL,NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(369,'sys_table_field','listen_func','前端监听方法(onInput,onChange)',23,'varchar(100)','Y','N','N','N','N','N','N','N','','text',NULL,NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(370,'sys_table_field','rules','正则验证',24,'text','Y','N','N','N','N','N','N','N','','text',NULL,NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(371,'sys_table_list','is_select','是否支持选择',8,'tinyint(1)','Y','N','N','N','N','N','N','N','','text','1',NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447),
	(372,'sys_table_list','is_operate','是否支持操作',11,'tinyint(1)','Y','N','N','N','N','N','N','N','','text','1',NULL,NULL,NULL,NULL,'[\"md\":24]',NULL,NULL,NULL,NULL,1784025447,1784025447);

/*!40000 ALTER TABLE `sys_table_field` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_table_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_table_list`;

CREATE TABLE `sys_table_list` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tb_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表名称',
  `tb_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '表编码',
  `tb_desc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '表格描述',
  `tb_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '表格类型',
  `entity_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '实体类名称',
  `module_id` char(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '模块ID',
  `descr` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `is_select` tinyint(1) DEFAULT '1' COMMENT '是否支持选择',
  `is_modify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否修改',
  `is_sync` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否同步',
  `is_operate` tinyint(1) DEFAULT '1' COMMENT '是否支持操作',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tb_code` (`tb_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表列表';

LOCK TABLES `sys_table_list` WRITE;
/*!40000 ALTER TABLE `sys_table_list` DISABLE KEYS */;

INSERT INTO `sys_table_list` (`id`, `tb_name`, `tb_code`, `tb_desc`, `tb_type`, `entity_name`, `module_id`, `descr`, `is_select`, `is_modify`, `is_sync`, `is_operate`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'sys_admin','BpClodRouO','后台管理员表','sys','library\\model\\sys\\AdminModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(2,'sys_admin_auth','cz9aEiOSK2','账号授权表','sys','library\\model\\sys\\AdminAuthModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(3,'sys_admin_logs','3e0sOqaYly','用户登陆日志表','sys','library\\model\\sys\\AdminLogsModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(4,'sys_article','eJ3ziq01bE','文章表','sys','library\\model\\sys\\ArticleModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(5,'sys_article_category','ADc3UqcdcO','文章分类表','sys','library\\model\\sys\\ArticleCategoryModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(6,'sys_casbin_rbac','41PdZFsHOO','rbac权限规则表','sys','library\\model\\sys\\CasbinRbacModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(7,'sys_casbin_restful','JrDdzA4zr4','restful权限规则表','sys','library\\model\\sys\\CasbinRestfulModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(8,'sys_change_logs','Y1xRKqkyDY','接口请求店铺授权表','sys','library\\model\\sys\\ChangeLogsModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(9,'sys_config','o8wwzS7PFT','选项配置表','sys','library\\model\\sys\\ConfigModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(10,'sys_crontab','rUNkW6RLct','定时任务调度表','sys','library\\model\\sys\\CrontabModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(11,'sys_crontab_group','w5JrrqiOO9','任务分类表','sys','library\\model\\sys\\CrontabGroupModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(12,'sys_crontab_log','8e6KJq00gq','定时任务调度日志表','sys','library\\model\\sys\\CrontabLogModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(13,'sys_dept','6T8mlbZZpt','部门表','sys','library\\model\\sys\\DeptModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(14,'sys_dict','4nrApBbsJs','系统配置表','sys','library\\model\\sys\\DictModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(15,'sys_dict_list','dX19PkN4Vm','系统配置数据表','sys','library\\model\\sys\\DictListModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(16,'sys_flow_numbers','1GohrMOQxJ','订单流水单号表','sys','library\\model\\sys\\FlowNumbersModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(17,'sys_ip_visit','Hoq4YdMUMF','IP访问信息表','sys','library\\model\\sys\\IpVisitModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(18,'sys_lang','DoSlXIbJjz','语言','sys','library\\model\\sys\\LangModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(19,'sys_lang_data','C4fBsT3DIc','Lang键名存储表','sys','library\\model\\sys\\LangDataModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(20,'sys_make_logs','qfzt98zhTS','系统操作日志表','sys','library\\model\\sys\\MakeLogsModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(21,'sys_menus','hjStQYUEZJ','菜单','sys','library\\model\\sys\\MenusModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(22,'sys_notice','5Ss7bo4ZM0','公告表','sys','library\\model\\sys\\NoticeModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(23,'sys_notice_category','6BUJK1xrPK','公告分类','sys','library\\model\\sys\\NoticeCategoryModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(24,'sys_operation_logs','tgFB2u62An','系统操作日志表','sys','library\\model\\sys\\OperationLogsModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(25,'sys_role','CYVGnu9Sp1','角色表','sys','library\\model\\sys\\RoleModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(26,'sys_route','7FPBWqieck','路由信息表','sys','library\\model\\sys\\RouteModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(27,'sys_send_msg_log','OrcQPuYvRg','信息发送表','sys','library\\model\\sys\\SendMsgLogModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(28,'sys_short_url','CJFr1cYrkz','长短网址对应表','sys','library\\model\\sys\\ShortUrlModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(29,'sys_table_field','OKkyxvY2R8','表字段','sys','library\\model\\sys\\TableFieldModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(30,'sys_table_list','9e3GC33G0Z','表列表','sys','library\\model\\sys\\TableListModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1),
	(31,'sys_upload_files','HptA8AXavV','文件上传表','sys','library\\model\\sys\\UploadFilesModel',NULL,NULL,1,0,0,1,1741309996,1741309996,1);

/*!40000 ALTER TABLE `sys_table_list` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_upload_files
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_upload_files`;

CREATE TABLE `sys_upload_files` (
  `file_id` int NOT NULL AUTO_INCREMENT,
  `file_hash` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件hash值',
  `source` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'admin' COMMENT '类型',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `from_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型',
  `engine` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '存储引擎',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '图片名称',
  `file_path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件路径',
  `origin_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '原始图片名称',
  `file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图片地址',
  `file_ext` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '后缀名称',
  `file_size` int NOT NULL DEFAULT '0' COMMENT '文件大小',
  `width` smallint NOT NULL DEFAULT '0' COMMENT '宽度',
  `height` smallint unsigned NOT NULL DEFAULT '0' COMMENT '高度',
  `created_time` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int NOT NULL DEFAULT '0' COMMENT '最后修改时间',
  PRIMARY KEY (`file_id`),
  UNIQUE KEY `file_md5` (`file_hash`,`user_id`),
  KEY `userid` (`user_id`,`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='文件上传表';



# 转储表 sys_web3_network
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_web3_network`;

CREATE TABLE `sys_web3_network` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '编码',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '网络名称',
  `family` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '链路名称',
  `chain_id` int DEFAULT '0' COMMENT '链路ID',
  `native_symbol` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '原声符号',
  `native_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '原声名称',
  `native_decimals` tinyint(1) DEFAULT '0' COMMENT '原声精度',
  `rpc_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RPC地址',
  `explorer_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '浏览器网址',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标',
  `is_mainnet` tinyint(1) DEFAULT '1' COMMENT '是否主网络（1:是，0:否）',
  `sort` int DEFAULT '0' COMMENT '排序',
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '描述',
  `created_time` int DEFAULT '0' COMMENT '创建时间',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(1:可用,0:隐藏,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `symbol` (`native_symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='web3网络';

LOCK TABLES `sys_web3_network` WRITE;
/*!40000 ALTER TABLE `sys_web3_network` DISABLE KEYS */;

INSERT INTO `sys_web3_network` (`id`, `code`, `name`, `family`, `chain_id`, `native_symbol`, `native_name`, `native_decimals`, `rpc_url`, `explorer_url`, `icon`, `is_mainnet`, `sort`, `descr`, `created_time`, `updated_time`, `status`)
VALUES
	(1,'ethereum','Ethereum','EVM',1,'ETH','Ether',18,'https://ethereum-rpc.publicnode.com','https://etherscan.io','',1,1,'Ethereum 主网络',1784639971,1784639971,1),
	(2,'bsc','BNB Smart Chain','EVM',56,'BNB','BNB',18,'https://bsc-dataseed.binance.org','https://bscscan.com','',1,2,'BNB Smart Chain 主网络',1784639971,1784639971,1),
	(3,'tron','TRON','TRON',728126428,'TRX','TRON',6,'https://api.trongrid.io','https://tronscan.org','',1,5,'TRON 主网络',1784639971,1784639971,1),
	(4,'base','Base','EVM',8453,'ETH','Ether',18,'https://mainnet.base.org','https://basescan.org','',1,4,'Base Layer2 网络',1784639971,1784639971,-1),
	(5,'polygon','Polygon','EVM',137,'POL','POL',18,'https://polygon-rpc.com','https://polygonscan.com','',1,3,'Polygon PoS 主网络',1784639971,1784639971,-1),
	(6,'ton','TON','TON',0,'TON','Toncoin',9,'https://toncenter.com/api/v2/jsonRPC','https://tonscan.org','',1,6,'The Open Network 主网络',1784639971,1784639971,-1),
	(7,'solana','Solana','SOL',101,'SOL','Solana',9,'https://api.mainnet-beta.solana.com','https://solscan.io','',1,7,'Solana 主网络',1784639971,1784639971,-1);

/*!40000 ALTER TABLE `sys_web3_network` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_web3_network_sweep_task
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_web3_network_sweep_task`;

CREATE TABLE `sys_web3_network_sweep_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recharge_order_id` bigint NOT NULL DEFAULT '0' COMMENT '关联 member_recharge_order.id',
  `user_id` int NOT NULL DEFAULT '0' COMMENT '用户ID',
  `network_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链编码',
  `token_symbol` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '代币符号',
  `from_address` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户充值地址',
  `to_address` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '归集地址',
  `amount` decimal(36,18) NOT NULL DEFAULT '0.000000000000000000' COMMENT '归集数量(显示单位)',
  `decimals` int NOT NULL DEFAULT '0' COMMENT '代币精度',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态:1待处理 2处理中 3成功 4失败',
  `retry_count` int NOT NULL DEFAULT '0' COMMENT '重试次数',
  `next_retry_at` datetime DEFAULT NULL COMMENT '下次重试时间',
  `sweep_tx_hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '归集交易hash',
  `gas_tx_hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '补gas交易hash(EVM)',
  `last_error` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最后错误',
  `created_time` int DEFAULT '0' COMMENT '创建时间',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_deposit_order` (`recharge_order_id`) USING BTREE,
  KEY `idx_status_retry` (`status`,`next_retry_at`) USING BTREE,
  KEY `idx_chain_status` (`network_code`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='充值自动归集任务';



# 转储表 sys_web3_network_token
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_web3_network_token`;

CREATE TABLE `sys_web3_network_token` (
  `id` int NOT NULL AUTO_INCREMENT,
  `network_id` int DEFAULT '0' COMMENT '所属网络',
  `network_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链编码(eth)',
  `contract_address` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '代币合约地址',
  `symbol` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '代币符号',
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '代币名称',
  `standard` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '代币标准(ERC20|TRC20|BEP20)',
  `decimals` tinyint NOT NULL DEFAULT '18' COMMENT '代币精度',
  `is_native` tinyint(1) DEFAULT '0' COMMENT '是否原生代币(1=是 0=否)',
  `confirm_required` int NOT NULL DEFAULT '12' COMMENT '入账需确认区块数',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标',
  `is_recharge` tinyint(1) DEFAULT '1' COMMENT '是否允许充值',
  `is_withdraw` tinyint(1) DEFAULT '1' COMMENT '是否允许提现',
  `is_transfer` tinyint(1) DEFAULT '1' COMMENT '是否允许划转',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序(越小越前)',
  `created_time` int DEFAULT '0' COMMENT '创建时间',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态(1=启用 0=禁用)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `token_code` (`network_id`,`symbol`),
  KEY `chain_code` (`network_code`),
  KEY `sort` (`sort`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='链+币种配置';

LOCK TABLES `sys_web3_network_token` WRITE;
/*!40000 ALTER TABLE `sys_web3_network_token` DISABLE KEYS */;

INSERT INTO `sys_web3_network_token` (`id`, `network_id`, `network_code`, `contract_address`, `symbol`, `name`, `standard`, `decimals`, `is_native`, `confirm_required`, `icon`, `is_recharge`, `is_withdraw`, `is_transfer`, `sort`, `created_time`, `updated_time`, `status`)
VALUES
	(1,1,'ethereum','0xdAC17F958D2ee523a2206206994597C13D831ec7','USDT','Tether USD','ERC20',6,0,12,'',1,1,1,10,1784640932,1784640932,1),
	(2,1,'ethereum','0xA0b86991c6218b36c1d19d4a2e9eb0ce3606eb48','USDC','USD Coin','ERC20',6,0,12,'',1,1,1,20,1784640932,1784640932,1),
	(3,2,'bsc','0x55d398326f99059fF775485246999027B3197955','USDT','Tether USD','BEP20',18,0,15,'',1,1,1,10,1784640932,1784640932,1),
	(4,2,'bsc','0x8ac76a51cc950d9822d68b83fe1ad97b32cd580d','USDC','USD Coin','BEP20',18,0,15,'',1,1,1,20,1784640932,1784640932,1),
	(5,3,'tron','TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t','USDT','Tether USD','TRC20',6,0,20,'',1,1,1,10,1784640932,1784640932,1),
	(6,3,'tron','TEkxiTehnzSmSe2XqrBj4w32RUN966rdz8','USDC','USD Coin','TRC20',6,0,20,'',1,1,1,20,1784640932,1784640932,1);

/*!40000 ALTER TABLE `sys_web3_network_token` ENABLE KEYS */;
UNLOCK TABLES;


# 转储表 sys_web3_network_wallet
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sys_web3_network_wallet`;

CREATE TABLE `sys_web3_network_wallet` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT '0' COMMENT '锁定的订单ID',
  `network_id` int unsigned DEFAULT '0' COMMENT '网络ID',
  `network_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链编码',
  `wallet_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'user' COMMENT '钱包类型(user/deposit/hot/cold)',
  `wallet_address` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '钱包地址',
  `total_in` decimal(14,6) DEFAULT '0.000000' COMMENT '入账总额',
  `total_out` decimal(14,6) DEFAULT '0.000000' COMMENT '出账总额',
  `success_cnt` int DEFAULT '0' COMMENT '成功次数',
  `last_transfer_at` timestamp NULL DEFAULT NULL COMMENT '转账时间',
  `private_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '私钥',
  `public_key` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '公钥',
  `descr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '描述',
  `created_time` int DEFAULT '0' COMMENT '创建时间 ',
  `updated_time` int DEFAULT '0' COMMENT '修改时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态(1:可用,0:不可用,-1:删除)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ids_network` (`user_id`,`network_id`),
  KEY `chain_code` (`network_code`),
  KEY `wallet_address` (`wallet_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='web3网络收款钱包表';




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
