-- =============================================================================
-- Gainode 2.0 生产参数 Seed — 经济模型 EM V4.1.1 / PARAM V1.0.0 / 生效 2026-07-15
-- 来源（权威）：E:\gainode项目\用户经济模型规则附录 V1.0.pdf + 用户经济模型说明书 V1.0.pdf
-- Owner 批准：CR-20260818-002（56级容量表 / 升级成本 / reward系数规则 / Power 规则 / 预算规则）
--
-- 落地范围（本次）：
--   * 56 级标准产能表（standard_capacity，Lv.1=50 → Lv.56=743,720）
--   * 56 级基准升级费用（upgrade_apt_requirement，Lv.1=0 → Lv.56=5,200,000）
--   * 升级冷却时间（按区间 0/1/3/5/6 天）
--   * 算力消耗（卖出 APT × 50%）
--   * 算力每日恢复率（按区间 10%~20%）
--   * P 等级升级优惠（P1=2% ~ P6=12%）
--   * 领取有效期公式（24h + (level-1)h）
--   * 今日收益系数来源规则（动态 = 当日预算 ÷ 全网标准产能，非固定值）
--
-- 未落地（文档无具体数值，保持 TBC，待 Owner 补）：
--   * AI.power_cap_by_robot_level 完整 56 级映射（附录仅 Lv.18=8,600 一个散点）
--     → 改为派生规则 AI.power_cap_factor = 0.5（Owner 决策 CR-20260818-002）：
--       power_cap = standard_capacity × 0.5
--       【注意：该决策覆盖文档 §07 案例 Lv.18=8,600（标准产能 468 × 0.5 = 234，非 8,600）】
--   * AI.ai_reward_budget_cap / period_cap 预算具体数值（文档只写「当天可分配预算」，
--     运行时由运营录入，本次不落死值）
--
-- 数据异常标注（忠实照抄文档，未擅自修正）：
--   * Lv.11 基准升级费用 = 1,500，低于 Lv.10 = 2,250（文档原值，跨越「标准→扩展」产品阶段）
--
-- 幂等：append-only 表，使用固定 Snowflake ID + idempotency_key，INSERT IGNORE 不覆盖历史
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. 参数快照（parameter_snapshots，append-only，INSERT IGNORE 幂等）
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `parameter_snapshots`
(`snapshot_id`,`release_id`,`parameter_keys`,`parameter_values`,`version`,`created_by`,`idempotency_key`,`audit_event_id`,`created_time`)
VALUES
(
  2026081800000000001,
  2026081800000000001,
  JSON_ARRAY(
    'AI.standard_capacity_rule_version',
    'AI.standard_capacity_by_level',
    'AI.upgrade_apt_requirement',
    'AI.upgrade_cooldown_by_range',
    'AI.power_sell_consumption_ratio',
    'AI.power_cap_factor',
    'AI.power_daily_restore_rate_by_range',
    'AI.upgrade_p_discount_by_level',
    'AI.ai_reward_claim_window_base_hours',
    'AI.ai_reward_claim_window_per_level_hours',
    'AI.daily_yield_coefficient_source',
    'AI.daily_yield_coefficient_precision',
    'AI.ai_reward_claim_enabled'
  ),
  JSON_OBJECT(
    'AI.standard_capacity_rule_version', 'EM_V4.1.1',
    'AI.standard_capacity_by_level', JSON_OBJECT(
      '1','50','2','52','3','55','4','60','5','68','6','78','7','90','8','106','9','126','10','155',
      '11','174','12','198','13','226','14','260','15','299','16','346','17','401','18','468','19','547','20','675',
      '21','847','22','1055','23','1312','24','1627','25','2012','26','2487','27','3072','28','3787','29','4659','30','5727',
      '31','7492','32','9635','33','12273','34','15517','35','19464','36','24336','37','30336','38','37653','39','46581','40','57279',
      '41','73433','42','91343','43','111633','44','134169','45','159511','46','187511','47','218680','48','252857','49','290511','50','332078',
      '51','391755','52','454255','53','519407','54','587054','55','657054','56','743720'
    ),
    'AI.upgrade_apt_requirement', JSON_OBJECT(
      '1','0','2','200','3','300','4','450','5','600','6','800','7','1050','8','1350','9','1750','10','2250',
      '11','1500','12','1900','13','2300','14','2800','15','3400','16','4100','17','5000','18','6100','19','7500','20','9200',
      '21','12000','22','15000','23','19000','24','24000','25','30000','26','38000','27','48000','28','60000','29','75000','30','94000',
      '31','120000','32','150000','33','190000','34','240000','35','300000','36','380000','37','480000','38','600000','39','750000','40','920000',
      '41','1050000','42','1200000','43','1400000','44','1600000','45','1850000','46','2100000','47','2400000','48','2700000','49','3050000','50','3450000',
      '51','3700000','52','4000000','53','4300000','54','4600000','55','4900000','56','5200000'
    ),
    'AI.upgrade_cooldown_by_range', JSON_ARRAY(
      JSON_OBJECT('from',1,'to',20,'days',0),
      JSON_OBJECT('from',21,'to',30,'days',1),
      JSON_OBJECT('from',31,'to',40,'days',3),
      JSON_OBJECT('from',41,'to',50,'days',5),
      JSON_OBJECT('from',51,'to',56,'days',6)
    ),
    'AI.power_sell_consumption_ratio', '0.5',
    'AI.power_cap_factor', '0.5',
    'AI.power_daily_restore_rate_by_range', JSON_ARRAY(
      JSON_OBJECT('from',1,'to',10,'rate','0.10'),
      JSON_OBJECT('from',11,'to',20,'rate','0.12'),
      JSON_OBJECT('from',21,'to',30,'rate','0.14'),
      JSON_OBJECT('from',31,'to',40,'rate','0.16'),
      JSON_OBJECT('from',41,'to',50,'rate','0.18'),
      JSON_OBJECT('from',51,'to',56,'rate','0.20')
    ),
    'AI.upgrade_p_discount_by_level', JSON_OBJECT(
      '1','0.02','2','0.04','3','0.06','4','0.08','5','0.10','6','0.12'
    ),
    'AI.ai_reward_claim_window_base_hours', '24',
    'AI.ai_reward_claim_window_per_level_hours', '1',
    'AI.daily_yield_coefficient_source', 'DYNAMIC_BUDGET_DIV_CAPACITY',
    'AI.daily_yield_coefficient_precision', '8',
    'AI.ai_reward_claim_enabled', true
  ),
  'EM_V4.1.1',
  0,
  'seed-em-v4.1.1-20260818-snapshot',
  0,
  UNIX_TIMESTAMP()
);

-- -----------------------------------------------------------------------------
-- 2. 参数发布（parameter_releases，status=active，INSERT IGNORE 幂等）
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `parameter_releases`
(`release_id`,`parameter_keys`,`status`,`draft_version`,`approved_by`,`scheduled_at`,`activated_at`,`paused_at`,`rolled_back_at`,`archived_at`,`monitoring_job_id`,`snapshot_id`,`case_id`,`audit_event_ids`,`object_version`,`idempotency_key`,`audit_event_id`,`created_time`,`updated_time`)
VALUES
(
  2026081800000000001,
  JSON_ARRAY(
    'AI.standard_capacity_rule_version',
    'AI.standard_capacity_by_level',
    'AI.upgrade_apt_requirement',
    'AI.upgrade_cooldown_by_range',
    'AI.power_sell_consumption_ratio',
    'AI.power_cap_factor',
    'AI.power_daily_restore_rate_by_range',
    'AI.upgrade_p_discount_by_level',
    'AI.ai_reward_claim_window_base_hours',
    'AI.ai_reward_claim_window_per_level_hours',
    'AI.daily_yield_coefficient_source',
    'AI.daily_yield_coefficient_precision',
    'AI.ai_reward_claim_enabled'
  ),
  'active',
  'EM_V4.1.1',
  0,
  0,
  UNIX_TIMESTAMP(),
  0,
  0,
  0,
  0,
  2026081800000000001,
  0,
  JSON_ARRAY(),
  1,
  'seed-em-v4.1.1-20260818-release',
  0,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);
