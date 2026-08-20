-- =====================================================================
-- Gainode 2.0 Admin 数据源/比赛数据 路由注册（幂等）
-- 新增 FixtureController（API-Football 比赛数据）与 DataSourceController（数据源管理）
-- 路由 key = md5(strtolower(trim(url,'/'))) 与 MakeRoute 生成规则一致
-- =====================================================================

INSERT INTO `sys_route` (`key`,`module`,`controller`,`action`,`method`,`plugins`,`url`,`path`,`middleware`,`verify`,`created_time`,`updated_time`,`descr`,`status`)
VALUES
    -- FixtureController：API-Football 比赛数据
    ('aa72c9de486ac45ed340b5c772f4fb25','admin','fixtureController','list','GET',NULL,'/admin/arbitrage/fixture','app\\admin\\controller\\arbitrage\\FixtureController','["AuthMiddleware"]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'比赛数据列表',1),
    ('bb34e905becfa6bdac827aaaa1b3e40c','admin','fixtureController','detail','GET',NULL,'/admin/arbitrage/fixture/{id}','app\\admin\\controller\\arbitrage\\FixtureController','["AuthMiddleware"]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'比赛数据详情',0),
    -- DataSourceController：数据源管理
    ('bef970018e0c17f58f44b90d5a0ccbf6','admin','dataSourceController','list','GET',NULL,'/admin/arbitrage/datasource','app\\admin\\controller\\arbitrage\\DataSourceController','["AuthMiddleware"]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'数据源列表',1),
    ('f911de8affa39988d52528854c8757f2','admin','dataSourceController','save','POST',NULL,'/admin/arbitrage/datasource/save','app\\admin\\controller\\arbitrage\\DataSourceController','["AuthMiddleware"]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'保存数据源',0),
    ('f70841c15fa60cccbf0a2357e809d092','admin','dataSourceController','test','POST',NULL,'/admin/arbitrage/datasource/test','app\\admin\\controller\\arbitrage\\DataSourceController','["AuthMiddleware"]',2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'测试数据源',0)
ON DUPLICATE KEY UPDATE
    `url`=VALUES(`url`),
    `path`=VALUES(`path`),
    `method`=VALUES(`method`),
    `middleware`=VALUES(`middleware`),
    `verify`=VALUES(`verify`),
    `updated_time`=VALUES(`updated_time`),
    `descr`=VALUES(`descr`),
    `status`=VALUES(`status`);
