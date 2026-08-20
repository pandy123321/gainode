-- Gainode 2.0 Admin V2 routes seed (OPTION_A: /api/v1/admin group, sys_route)
-- Owner decision ADMIN-V2-AUTH-01 = OPTION_A (2026-08-20)
-- module='admin_v2'; url is relative (group prefix + url = /api/v1/admin/<url>)
-- auth: controller under app/admin/controller/v2 -> app='admin' -> AdminAuth
-- NOTE: keep this file ASCII-only to avoid git binary detection.

INSERT INTO `sys_route` (`key`,`module`,`controller`,`action`,`method`,`plugins`,`url`,`path`,`middleware`,`verify`,`created_time`,`updated_time`,`descr`,`status`) VALUES
    ('53312708d4ae75b162e40bc9e4a0456e','admin_v2','v2\adminv2','auditLog','GET',NULL,'/audit-log','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'auditLog',1),
    ('6989bb9c6e12b23265f92a8012e5c640','admin_v2','v2\adminv2','asyncJob','GET',NULL,'/async-jobs/{id}','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'asyncJob',1),
    ('0e50cacf6ea1fccb5e6d12785354bcdf','admin_v2','v2\adminv2','exportTask','POST',NULL,'/export-tasks','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'exportTask',1),
    ('5f369bceea81219b029d44423e5b93ad','admin_v2','v2\adminv2','users','GET',NULL,'/admission/users','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'users',1),
    ('33ec532927c38ee6e08489c1a093743d','admin_v2','v2\adminv2','otcOrders','GET',NULL,'/otc/orders','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'otcOrders',1),
    ('4e016e293594e2154e53628a049e1b23','admin_v2','v2\adminv2','robots','GET',NULL,'/robot/list','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'robots',1),
    ('dbf81db272574a8ffd8c69ed2079bfd2','admin_v2','v2\adminv2','tickets','GET',NULL,'/support/tickets','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'tickets',1),
    ('3c4e93abbffd5ef126a668121edc9f7f','admin_v2','v2\adminv2','ledgerAccounts','GET',NULL,'/ledger/accounts','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'ledgerAccounts',1),
    ('f98494865f13a671e5c70c5f9d1b92d6','admin_v2','v2\adminv2','riskCases','GET',NULL,'/risk/cases','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'riskCases',1),
    ('6ec92182d0e6b516af1596b99ecea9d4','admin_v2','v2\adminv2','approvalTasks','GET',NULL,'/approval/tasks','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'approvalTasks',1),
    ('f9c459a61eaa0cf45d7350a8a1112c26','admin_v2','v2\adminv2','parameterDefinitions','GET',NULL,'/parameter/definitions','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'parameterDefinitions',1),
    ('3d7fe0289161ba22fcf0ead0d660e464','admin_v2','v2\adminv2','predictionMarkets','GET',NULL,'/prediction/markets','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'predictionMarkets',1),
    ('4823b18a988070da59a987dfd784484f','admin_v2','v2\adminv2','powerAccounts','GET',NULL,'/power/accounts','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'powerAccounts',1),
    ('50a809b4180a6bb4d2068d353208371c','admin_v2','v2\adminv2','rewardOps','GET',NULL,'/robot/rewards','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'rewardOps',1),
    ('69849b74b7ba324dea7b074030fa36d4','admin_v2','v2\adminv2','otcOrderDetail','GET',NULL,'/otc/orders/{id}','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'otcOrderDetail',1),
    ('c60bf1ee1b9a2fd668215b022f18d6ed','admin_v2','v2\adminv2','robotDetail','GET',NULL,'/robot/{id}','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'robotDetail',1),
    ('744f22c35a22f8f316d30105295dced8','admin_v2','v2\adminv2','ticketDetail','GET',NULL,'/support/tickets/{id}','app\admin\controller\v2\AdminV2Controller','[]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'ticketDetail',1);
ON DUPLICATE KEY UPDATE
    `url`=VALUES(`url`),`path`=VALUES(`path`),`method`=VALUES(`method`),
    `middleware`=VALUES(`middleware`),`verify`=VALUES(`verify`),`updated_time`=VALUES(`updated_time`),`descr`=VALUES(`descr`),`status`=VALUES(`status`);
