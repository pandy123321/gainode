-- =============================================================================
-- Gainode 2.0 后台菜单 Seed（权威数据源 · V3 权威 Page ID 对齐版）
-- 来源：07_DEVELOPMENT_AND_ACCEPTANCE.md §8「显式 Page ID 注册表」= 唯一 Page ID 真源
-- V3 变更（2026-08-18）：
--   * descr 从 V2.4.1 Page Map 旧命名，机械对齐到 07 §8 权威 Page ID：
--       A-USER-003→(并入 A-USER-002 用户360)  A-AFF-001..004→A-GROWTH-001
--       A-ECON-001..003→A-LEDGER-004  A-ROBOT-004→(删)  A-OTC-003→A-OTC-002
--       A-DATA-001→A-REPORT-001  A-AUDIT-002→(删)  A-AI-003/005/006→(删)
--   * 补 A-SUPPORT-002(工单详情) 并入客服工单 descr
--   * 补「紧急操作 /system/emergency」隐藏菜单项（A-EMERGENCY-001, is_show=0）
--   * name 人话化微调：用户状态管理→用户360
-- V2 变更：
--   * 一级 8 根 → 11 根（拆出「代理管理 / 数据中心 / AI运营」）
--   * 二级 51 → 33（详情并入列表、监控并入看板、同类归并）
--   * 话术人话化（见文件内 name）
-- 说明：
--   * type: 0=导航, 1=目录, 2=菜单, 3=按钮, 4=接口
--   * platform: system=总后台, agent=代理后台
--   * descr 存权威 Page ID（合并项用逗号分隔多个 PageID），与 07 §8 机械校验
--   * route_url 为语义化前端路由（供前端同事对齐，与前端 mock / base-routes 一致）
--   * route_key 留空：2.0 后端 HTTP 接口尚未实现（STAGE-02 仅域对象），接接口时回填
--   * 幂等：停用 V1.x(id<100) + 删除旧 system 2.0 菜单(100<=id<300) + 重插；agent(300+) 保留
-- =============================================================================

SET NAMES utf8mb4;

-- 停用 V1.x 旧菜单（id < 100 均为 V1.x；数据保留，status=0 可逆）
UPDATE `sys_menus` SET `status` = 0 WHERE `status` = 1 AND `id` < 100;

-- 删除旧版 system 2.0 菜单（100 <= id < 300），agent(300+) 保留
DELETE FROM `sys_menus` WHERE `platform` = 'system' AND `id` >= 100 AND `id` < 300;

-- -----------------------------------------------------------------------------
-- 11 个一级导航（目录 type=1, pid=0, platform=system）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(100,'system','工作台',1,0,'','layui-icon-form','','','/workbench',NULL,0,'ROOT-01',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(101,'system','用户管理',1,0,'','layui-icon-user','','','/user',NULL,0,'ROOT-02',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(102,'system','代理管理',1,0,'','layui-icon-group','','','/affiliate',NULL,0,'ROOT-03',3,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(103,'system','财务管理',1,0,'','layui-icon-rmb','','','/finance',NULL,0,'ROOT-04',4,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(104,'system','机器人管理',1,0,'','layui-icon-senior','','','/robot',NULL,0,'ROOT-05',5,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(105,'system','交易管理',1,0,'','layui-icon-transfer','','','/trade',NULL,0,'ROOT-06',6,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(106,'system','赛事竞猜',1,0,'','layui-icon-website','','','/predict',NULL,0,'ROOT-07',7,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(107,'system','数据中心',1,0,'','layui-icon-chart','','','/data',NULL,0,'ROOT-08',8,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(108,'system','风控与配置',1,0,'','layui-icon-set','','','/risk',NULL,0,'ROOT-09',9,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(109,'system','AI运营',1,0,'','layui-icon-service','','','/ai',NULL,0,'ROOT-10',10,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(110,'system','系统管理',1,0,'','layui-icon-template','','','/system',NULL,0,'ROOT-11',11,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 01 工作台（pid=100）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(200,'system','运营总览',2,100,'','layui-icon-list','','','/workbench/overview',NULL,0,'A-WORK-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(201,'system','今日待办',2,100,'','layui-icon-list','','','/workbench/todo',NULL,0,'A-WORK-002',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 02 用户管理（pid=101）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(210,'system','用户列表',2,101,'','layui-icon-list','','','/user/list',NULL,0,'A-USER-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(211,'system','实名认证',2,101,'','layui-icon-list','','','/user/kyc',NULL,0,'A-KYC-001',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(212,'system','用户360',2,101,'','layui-icon-list','','','/user/status',NULL,0,'A-USER-002,A-USER-004',3,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(213,'system','客服工单',2,101,'','layui-icon-list','','','/user/support',NULL,0,'A-SUPPORT-001,A-SUPPORT-002',4,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 03 代理管理（pid=102）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(214,'system','代理列表',2,102,'','layui-icon-list','','','/affiliate/list',NULL,0,'A-GROWTH-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 04 财务管理（pid=103）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(220,'system','资产总览',2,103,'','layui-icon-list','','','/finance/overview',NULL,0,'A-LEDGER-001,A-LEDGER-002',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(221,'system','对账与冲正',2,103,'','layui-icon-list','','','/finance/reconciliation',NULL,0,'A-LEDGER-003',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(222,'system','结算管理',2,103,'','layui-icon-list','','','/finance/settlement',NULL,0,'A-LEDGER-004',3,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(223,'system','Power 账户',2,103,'','layui-icon-list','','','/finance/power',NULL,0,'A-POWER-001',4,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 05 机器人管理（pid=104）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(230,'system','机器人列表',2,104,'','layui-icon-list','','','/robot/list',NULL,0,'A-ROBOT-001,A-ROBOT-002',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(231,'system','收益记录',2,104,'','layui-icon-list','','','/robot/revenue',NULL,0,'A-ROBOT-003',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 06 交易管理（pid=105）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(240,'system','OTC 订单',2,105,'','layui-icon-list','','','/trade/otc-order',NULL,0,'A-OTC-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(241,'system','争议处理',2,105,'','layui-icon-list','','','/trade/dispute',NULL,0,'A-OTC-002',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 07 赛事竞猜（pid=106）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(250,'system','赛事管理',2,106,'','layui-icon-list','','','/predict/match',NULL,0,'A-PREDICT-001,A-PREDICT-002',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(251,'system','投注订单',2,106,'','layui-icon-list','','','/predict/order',NULL,0,'A-PREDICT-003',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(252,'system','结算管理',2,106,'','layui-icon-list','','','/predict/settlement',NULL,0,'A-PREDICT-004',3,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 08 数据中心（pid=107）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(260,'system','数据看板',2,107,'','layui-icon-list','','','/data/dashboard',NULL,0,'A-REPORT-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(261,'system','足球数据',2,107,'','layui-icon-list','','','/data/football',NULL,0,'A-DATA-003',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(262,'system','市场与赔率',2,107,'','layui-icon-list','','','/data/market',NULL,0,'A-DATA-004',3,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(263,'system','信号质量',2,107,'','layui-icon-list','','','/data/signal',NULL,0,'A-DATA-005',4,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(264,'system','数据源管理',2,107,'','layui-icon-list','','','/data/source',NULL,0,'A-DATA-002',5,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 09 风控与配置（pid=108）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(270,'system','风控事件',2,108,'','layui-icon-list','','','/risk/event',NULL,0,'A-RISK-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(271,'system','审批中心',2,108,'','layui-icon-list','','','/risk/approval',NULL,0,'A-APPROVAL-001',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(272,'system','参数管理',2,108,'','layui-icon-list','','','/risk/param',NULL,0,'A-CONFIG-001,A-CONFIG-002',3,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(273,'system','策略配置',2,108,'','layui-icon-list','','','/risk/policy',NULL,0,'A-POLICY-001',4,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 10 AI运营（pid=109）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(280,'system','AI 看板',2,109,'','layui-icon-list','','','/ai/dashboard',NULL,0,'A-AI-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(281,'system','运营建议',2,109,'','layui-icon-list','','','/ai/suggestion',NULL,0,'A-AI-002',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(282,'system','策略模拟',2,109,'','layui-icon-list','','','/ai/simulation',NULL,0,'A-AI-004',3,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 11 系统管理（pid=110）
-- -----------------------------------------------------------------------------
INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(290,'system','操作日志',2,110,'','layui-icon-list','','','/system/log',NULL,0,'A-AUDIT-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(291,'system','系统监控',2,110,'','layui-icon-list','','','/system/monitor',NULL,0,'A-OPS-001',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(292,'system','紧急操作',2,110,'','layui-icon-list','','','/system/emergency',NULL,0,'A-EMERGENCY-001',3,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(293,'system','APT 迁移',2,110,'','layui-icon-list','','','/system/migration',NULL,0,'A-MIGRATION-001',4,0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);

-- -----------------------------------------------------------------------------
-- 代理后台（platform=agent，7 页，pid=0 顶级菜单）— 本次未改动，保留（幂等：先删再插）
-- -----------------------------------------------------------------------------
DELETE FROM `sys_menus` WHERE `platform` = 'agent' AND `id` >= 300;

INSERT INTO `sys_menus`
(`id`,`platform`,`name`,`type`,`pid`,`path`,`icon`,`btn_style`,`route_key`,`route_url`,`params`,`choice_ids`,`descr`,`sort`,`is_show`,`created_time`,`updated_time`,`status`)
VALUES
(300,'agent','代理首页',2,0,'','layui-icon-list','','','/agent/home',NULL,0,'AG-HOME-001',1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(301,'agent','我的用户',2,0,'','layui-icon-list','','','/agent/user',NULL,0,'AG-USER-001',2,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(302,'agent','用户服务详情',2,0,'','layui-icon-list','','','/agent/user-detail',NULL,0,'AG-USER-002',3,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(303,'agent','推荐关系',2,0,'','layui-icon-list','','','/agent/team',NULL,0,'AG-TEAM-001',4,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(304,'agent','代理运营数据',2,0,'','layui-icon-list','','','/agent/data',NULL,0,'AG-DATA-001',5,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(305,'agent','代理客服工单',2,0,'','layui-icon-list','','','/agent/support',NULL,0,'AG-SUPPORT-001',6,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1),
(306,'agent','账号与安全',2,0,'','layui-icon-list','','','/agent/account',NULL,0,'AG-ACCOUNT-001',7,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1);
