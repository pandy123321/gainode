# GAINODE ADMIN NAVIGATION MIGRATION V2.4 → V2.4.1

> 14 Root → 8 Root  
> NOT_A_FUNCTION_REMOVAL = TRUE  
> ARCHIVE_NAV_ONLY ≠ DELETE_CAPABILITY

## 迁移矩阵

| # | V2.4 Root | V2.4.1 Root | 迁移动作 | 影响页面 | 说明 |
|---|---|---|---|---|---|
| 1 | 01 运营总览 | 01 工作台 | RETAIN | A-WORK-001, A-WORK-002 | 不变 |
| 2 | 02 用户管理 | 02 用户与准入 | MERGE | A-USER-001/2, A-KYC-001, A-USER-003/4, A-SUPPORT-001 | 合并代理管理 |
| 3 | 03 代理管理 | 02 用户与准入 | MOVE_UNDER | A-AFF-001/2/3/4 | 归入用户准入模块 |
| 4 | 04 Robot管理 | 04 机器人与权益 | RETAIN | A-ROBOT-001/2/3/4 | 不变 |
| 5 | 05 Reward与经济模型 | 03 资产与账本 | MOVE_UNDER | A-ECON-001/2/3 | 经济模型记录归入账本 |
| 6 | 06 竞猜管理 | 06 赛事预测 | RETAIN | A-PREDICT-001/2/3/4 | 合并数据智能 |
| 7 | 07 数据智能中心 | 06 赛事预测 | SPLIT | A-DATA-001/3/4/5 | 赛事相关→06，Provider→08 |
| 8 | 08 AI运营中心 | 07 风控/审批/参数/策略 | MOVE_UNDER | A-AI-001/2/3/4/5/6 | AI策略→07 Risk |
| 9 | 09 APT/Power管理 | 03 资产与账本 + 05 OTC与Power | SPLIT | A-LEDGER-001/2/3, A-POWER-001 | APT→03, Power→05 |
| 10 | 10 OTC管理 | 05 OTC 与 Power | RETAIN | A-OTC-001/2/3 | 合并 Power |
| 11 | 11 参数与策略 | 07 风控/审批/参数/策略 | MERGE | A-CONFIG-001/2, A-POLICY-001 | 合并风控审批 |
| 12 | 12 风控与审批 | 07 风控/审批/参数/策略 | MERGE | A-RISK-001, A-APPROVAL-001 | 合并参数策略 |
| 13 | 13 审计中心 | 08 客服/审计/运维 | MERGE | A-AUDIT-001/2 | 合并系统运维 |
| 14 | 14 系统与报表 | 08 客服/审计/运维 + 07 | SPLIT | A-OPS-001, A-REPORT-001, A-MIGRATION-001 | 运维→08, 报表→07 |

## 动作释义

| ACTION | 含义 |
|---|---|
| RETAIN | 一级导航保留 |
| MOVE_UNDER | 该 Root 取消一级菜单入口，页面归入目标 Root |
| MERGE | 多个 V2.4 Root 合并为一个 V2.4.1 Root |
| SPLIT | 一个 V2.4 Root 的页面拆分到不同 V2.4.1 Root |
| ARCHIVE_NAV_ONLY | 取消一级菜单入口，不删除业务能力 |

## 核心结论

- **没有任何业务能力被删除**
- **14→8 是导航结构的归组，不是功能删减**
- **51 个 Admin 页面全部保留**
- **代理管理归入用户准入（同属用户域）**
- **数据智能分拆：赛事→06，数据源→08**
- **AI 运营归入风控/策略（AI 本质是运营策略工具，非独立产品）**
- **OTC 与 Power 合并（OTC 使用 Power 作为操作资源）**
