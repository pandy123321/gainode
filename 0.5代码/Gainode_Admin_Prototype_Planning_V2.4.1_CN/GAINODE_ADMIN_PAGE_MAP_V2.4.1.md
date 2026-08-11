# GAINODE ADMIN PAGE MAP V2.4.1

> 配套：`Gainode_Admin_Prototype_Planning_V2.4.1_CN.md`
> 8 Root 固定 / 51 Admin Pages + 7 Agent Portal Pages

## 1. 总后台导航树（8 Root）

```text
Gainode 总后台
│
├─ 01 工作台
│  ├─ A-WORK-001 运营总览---------------------------------P0
│  └─ A-WORK-002 今日待办---------------------------------P0
│
├─ 02 用户与准入
│  ├─ A-USER-001 用户列表---------------------------------P0
│  ├─ A-USER-002 用户360----------------------------------P0
│  ├─ A-KYC-001 KYC审核-----------------------------------P0
│  ├─ A-USER-003 用户限制与恢复----------------------------P0
│  ├─ A-USER-004 用户资产调整------------------------------P1_CONDITIONAL（GAP-014/GAP-015；仅Preview）
│  ├─ A-SUPPORT-001 客服工单中心----------------------------P0
│  ├─ A-AFF-001 代理总览----------------------------------P1_CONDITIONAL
│  ├─ A-AFF-002 代理列表----------------------------------P1_CONDITIONAL
│  ├─ A-AFF-003 代理详情----------------------------------P1_CONDITIONAL
│  └─ A-AFF-004 推荐关系与代理统计--------------------------P1_CONDITIONAL
│
├─ 03 资产与账本
│  ├─ A-LEDGER-001 APT资产总览-----------------------------P0
│  ├─ A-LEDGER-002 APT账户与流水----------------------------P0
│  ├─ A-LEDGER-003 池子/对账/冲正---------------------------P0
│  ├─ A-ECON-001 经济模型运行总览---------------------------P0
│  ├─ A-ECON-002 奖励与结算执行监控---------------------------P0
│  └─ A-ECON-003 经济模型配置入口---------------------------P0
│
├─ 04 机器人与权益
│  ├─ A-ROBOT-001 Robot列表-------------------------------P0
│  ├─ A-ROBOT-002 Robot详情与运行监控-----------------------P0
│  ├─ A-ROBOT-003 奖励与领取监控----------------------------P0
│  └─ A-ROBOT-004 升级与Power Cap变化----------------------P1
│
├─ 05 OTC 与 Power
│  ├─ A-OTC-001 OTC订单中心--------------------------------P0
│  ├─ A-OTC-002 OTC订单详情/审核----------------------------P0
│  ├─ A-OTC-003 撮合/争议/Power监控-------------------------P1
│  └─ A-POWER-001 Power账户与流水---------------------------P0
│
├─ 06 赛事预测
│  ├─ A-PREDICT-001 赛事/竞猜列表---------------------------P0
│  ├─ A-PREDICT-002 竞猜详情--------------------------------P0
│  ├─ A-PREDICT-003 参与订单管理-----------------------------P0
│  ├─ A-PREDICT-004 结果/结算/退款/更正----------------------P0
│  ├─ A-DATA-001 数据驾驶舱--------------------------------P1
│  ├─ A-DATA-003 足球数据管理-------------------------------P1
│  ├─ A-DATA-004 市场/赔率/套利原始数据----------------------P1_CONDITIONAL
│  └─ A-DATA-005 信号与数据质量-----------------------------P1_CONDITIONAL
│
├─ 07 风控/审批/参数/策略
│  ├─ A-RISK-001 风险事件----------------------------------P0
│  ├─ A-APPROVAL-001 审批中心-------------------------------P0
│  ├─ A-CONFIG-001 参数定义与候选值---------------------------P0
│  ├─ A-CONFIG-002 参数发布与快照----------------------------P0
│  ├─ A-POLICY-001 地区/KYC/保护策略------------------------P0
│  ├─ A-AI-001 AI运营驾驶舱--------------------------------P1
│  ├─ A-AI-002 AI运营建议----------------------------------P1
│  ├─ A-AI-003 AI市场分析----------------------------------P1_CONDITIONAL
│  ├─ A-AI-004 AI套利策略模拟-------------------------------P1_CONDITIONAL
│  ├─ A-AI-005 AI竞猜运营助手-------------------------------P1_CONDITIONAL
│  ├─ A-AI-006 AI用户/客服/风险助手--------------------------P1
│  └─ A-REPORT-001 运营与经济报表----------------------------P1
│
└─ 08 客服/审计/运维
   ├─ A-AUDIT-001 全量操作日志------------------------------P0
   ├─ A-AUDIT-002 敏感操作审计-------------------------------P0
   ├─ A-OPS-001 系统/异步任务/错误---------------------------P0
   ├─ A-DATA-002 数据源管理---------------------------------P1_CONDITIONAL
   └─ A-MIGRATION-001 APT Migration------------------------FUTURE
```

## 2. 代理后台（P1_CONDITIONAL）

```text
代理后台（P1_CONDITIONAL）
├─ AG-HOME-001 代理首页
├─ AG-USER-001 我的用户
├─ AG-USER-002 用户服务详情
├─ AG-TEAM-001 推荐关系
├─ AG-DATA-001 代理运营数据
├─ AG-SUPPORT-001 代理客服工单
└─ AG-ACCOUNT-001 账号与安全
```

## 3. Priority 统计

| Priority | 总后台 | 代理后台 | 合计 |
|---|---|---|---|
| P0 | 31 | 0 | 31 |
| P1 | 8 | 0 | 8 |
| P1_CONDITIONAL | 11 | 7 | 18 |
| FUTURE | 1 | 0 | 1 |
| **合计** | **51** | **7** | **58** |

> 以上数字从 Page ID Migration Matrix 机械派生 [DERIVED]。

## 4. Contract Status 分类

> **派生源 = JOIN 表（唯一权威源）**：以下 Contract Status 从 `GAINODE_ADMIN_CONTRACT_GAP_REGISTER_V2.4.1.md` 的 Page→Gap JOIN 表**唯一**派生 [AUTHORITY]：
> - FROZEN = 未在 JOIN 表中出现（无任何 Gap）的页面数 + JOIN 表中派生 CONTRACT_STATUS=FROZEN 的页面数
> - CONTRACT_GAP = JOIN 表中派生 CONTRACT_STATUS=CONTRACT_GAP 的页面数（含 BLOCKING 和 NON_BLOCKING）
> - FUTURE = JOIN 表中派生 CONTRACT_STATUS=FUTURE 的页面数
>
> `GAINODE_ADMIN_PAGE_ID_MIGRATION_MATRIX_V2.4.1.md` 的 Contract 列 = **PROJECTION**（仅投影 JOIN 表结果，不是派生输入）。本表的统计数值不从 Migration Matrix 的 Contract 列计数得出。
>
> **IF PROJECTION != AUTHORITY → VALIDATION_FAIL**（若 Migration Matrix 或其他文件的 Contract Status 与 JOIN 表不一致，标记为验证失败）

| Contract Status | 页面数 |
|---|---|
| CONTRACT_FROZEN（05 已正式定义；含 BASE=FROZEN + 非阻断GAP） | 35 |
| CONTRACT_GAP（05 未冻结对应对象；含阻塞性 GAP） | 22 |
| FUTURE | 1 |

> CONTRACT_FROZEN(35) + CONTRACT_GAP(22) + FUTURE(1) = 58 ✓ [PROJECTION — 从 JOIN 表机械派生]  
> 本统计唯一派生源为 Page→Gap JOIN 表。Migration Matrix Contract 列仅作投影参考，不作为计数输入。
>
> 本表中 FROZEN 含 BASE_CONTRACT=FROZEN 但存在非阻断子能力 GAP 的页面（A-USER-003/GAP-013, A-CONFIG-002/GAP-015, A-APPROVAL-001/GAP-016）
