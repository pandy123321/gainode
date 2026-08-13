# TASK-20260812-001 · 设计文档

## 当前架构（基于规范推断）

```
C 端 Mobile H5  ←REST/JSON→  API Gateway  →  Business Services  →  DB（MySQL 8.4.9，无链上依赖）
Admin Web (1440px)
```

### 数据模型

遗留 MySQL 8.4 数据库包含 **60 张表**，分两大域：

**业务域（`member_*` / `arbitrage_*`）**：
- 用户：member_user, member_user_auth, member_user_kyc, member_user_oauth, member_user_wallet, member_user_team
- 资产：member_recharge_order, member_withdraw_order, member_transfer_order, member_user_wallet_log
- Robot：arbitrage_project, arbitrage_project_order, arbitrage_day_plan, arbitrage_fixture, arbitrage_position, arbitrage_signal
- 平台：member_platform_wallet, member_level, member_order_record

**系统域（`sys_*`）**：
- 管理：sys_admin, sys_admin_auth, sys_admin_logs, sys_role, sys_menus, sys_casbin_rbac
- 配置：sys_config, sys_dict, sys_lang, sys_country, sys_crontab
- 审计：sys_operation_logs, sys_change_logs, sys_send_msg_log
- 区块链：sys_web3_network, sys_web3_network_token, sys_web3_network_wallet

### 技术选型待确认

待 Owner 确认后更新本文件。

> 关联任务：TASK-20260811-001（后端 V6.1 全模块领域对象骨架搭建）已基于 PHP/Webman + Vue 3 + MySQL + 方案 B 完成了需求/设计/验收文档。若确认沿用现有技术栈，TASK-20260812-001 的 #1/#2/#3/#5/#6 可立即标记为已确认。

## 信息来源

- `0.5代码/gainode后端/gainode/sql/database.sql`
- `05_DATA_STATE_PERMISSION_API_CONTRACT.md`
