# GAINODE ADMIN PAGE ID MIGRATION MATRIX V2.4 → V2.4.1

> 51 Admin Pages + 7 Agent Portal Pages  
> SILENT_DELETE = 0  
> DUPLICATE_PAGE_ID = 0

## 总后台 51 页迁移矩阵

| V2.4 Page ID | V2.4.1 Page ID | 动作 | Priority | Root | Contract | 路由影响 | 说明 |
|---|---|---|---|---|---|---|---|
| A-WORK-001 | A-WORK-001 | KEEP | P0 | 01 | FROZEN | 无 | 运营总览不变 |
| A-WORK-002 | A-WORK-002 | KEEP | P0 | 01 | FROZEN | 无 | 今日待办不变 |
| A-USER-001 | A-USER-001 | KEEP | P0 | 02 | FROZEN | 无 | 用户列表不变 |
| A-USER-002 | A-USER-002 | KEEP | P0 | 02 | FROZEN | 无 | 用户360不变 |
| A-KYC-001 | A-KYC-001 | KEEP | P0 | 02 | FROZEN | 无 | KYC审核不变 |
| A-USER-003 | A-USER-003 | KEEP | P0 | 02 | FROZEN（BASE=FROZEN；扩展操作=CONTRACT_GAP/GAP-013） | 无 | 用户限制不变 |
| A-USER-004 | A-USER-004 | KEEP | P1_CONDITIONAL | 02 | CONTRACT_GAP（GAP-014/GAP-015；Adjustment全链路未冻结） | 无 | 资产调整→降为P1_CONDITIONAL |
| A-SUPPORT-001 | A-SUPPORT-001 | KEEP | P0 | 02 | FROZEN | 无 | 工单中心不变 |
| A-AFF-001 | A-AFF-001 | KEEP | P1_CONDITIONAL | 02 | CONTRACT_GAP | 路由从03→02 | 代理总览 |
| A-AFF-002 | A-AFF-002 | KEEP | P1_CONDITIONAL | 02 | CONTRACT_GAP | 路由从03→02 | 代理列表 |
| A-AFF-003 | A-AFF-003 | KEEP | P1_CONDITIONAL | 02 | CONTRACT_GAP | 路由从03→02 | 代理详情 |
| A-AFF-004 | A-AFF-004 | KEEP | P1_CONDITIONAL | 02 | CONTRACT_GAP | 路由从03→02 | 推荐关系 |
| A-ROBOT-001 | A-ROBOT-001 | KEEP | P0 | 04 | FROZEN | 无 | Robot列表不变 |
| A-ROBOT-002 | A-ROBOT-002 | KEEP | P0 | 04 | FROZEN | 无 | Robot详情不变 |
| A-ROBOT-003 | A-ROBOT-003 | KEEP | P0 | 04 | FROZEN | 无 | Reward监控 |
| A-ROBOT-004 | A-ROBOT-004 | KEEP | P1 | 04 | FROZEN | 无 | 升级监控→P1 |
| A-ECON-001 | A-ECON-001 | KEEP | P0 | 03 | FROZEN | 路由从05→03 | 经济模型总览 |
| A-ECON-002 | A-ECON-002 | KEEP | P0 | 03 | FROZEN | 路由从05→03 | Reward执行 |
| A-ECON-003 | A-ECON-003 | KEEP | P0 | 03 | FROZEN | 路由从05→03 | 经济配置入口 |
| A-PREDICT-001 | A-PREDICT-001 | KEEP | P0 | 06 | FROZEN | 无 | 竞猜列表不变 |
| A-PREDICT-002 | A-PREDICT-002 | KEEP | P0 | 06 | FROZEN | 无 | 竞猜详情不变 |
| A-PREDICT-003 | A-PREDICT-003 | KEEP | P0 | 06 | FROZEN | 无 | 参与订单不变 |
| A-PREDICT-004 | A-PREDICT-004 | KEEP | P0 | 06 | FROZEN | 无 | 结算更正+State |
| A-DATA-001 | A-DATA-001 | KEEP | P1 | 06 | FROZEN | 路由从07→06 | 数据中心→P1 |
| A-DATA-002 | A-DATA-002 | KEEP | P1_CONDITIONAL | 08 | CONTRACT_GAP | 路由从07→08 | 数据源→Provider |
| A-DATA-003 | A-DATA-003 | KEEP | P1 | 06 | CONTRACT_GAP（GAP-006） | 路由从07→06 | 足球数据→赛事 |
| A-DATA-004 | A-DATA-004 | KEEP | P1_CONDITIONAL | 06 | CONTRACT_GAP | 路由从07→06 | 市场数据 |
| A-DATA-005 | A-DATA-005 | KEEP | P1_CONDITIONAL | 06 | CONTRACT_GAP | 路由从07→06 | 信号与质量 |
| A-AI-001 | A-AI-001 | KEEP | P1 | 07 | CONTRACT_GAP（GAP-010） | 路由从08→07 | AI驾驶舱→P1 |
| A-AI-002 | A-AI-002 | KEEP | P1 | 07 | CONTRACT_GAP（GAP-010） | 路由从08→07 | AI建议→P1 |
| A-AI-003 | A-AI-003 | KEEP | P1_CONDITIONAL | 07 | CONTRACT_GAP | 路由从08→07 | AI市场分析 |
| A-AI-004 | A-AI-004 | KEEP | P1_CONDITIONAL | 07 | CONTRACT_GAP | 路由从08→07 | AI策略模拟 |
| A-AI-005 | A-AI-005 | KEEP | P1_CONDITIONAL | 07 | CONTRACT_GAP | 路由从08→07 | AI竞猜助手 |
| A-AI-006 | A-AI-006 | KEEP | P1 | 07 | CONTRACT_GAP（GAP-010） | 路由从08→07 | AI客服风险 |
| A-LEDGER-001 | A-LEDGER-001 | KEEP | P0 | 03 | FROZEN | 路由从09→03 | APT总览 |
| A-LEDGER-002 | A-LEDGER-002 | KEEP | P0 | 03 | FROZEN | 路由从09→03 | APT流水 |
| A-LEDGER-003 | A-LEDGER-003 | KEEP | P0 | 03 | FROZEN | 路由从09→03 | 池子对账 |
| A-POWER-001 | A-POWER-001 | KEEP | P0 | 05 | FROZEN | 路由从09→05 | Power→OTC |
| A-OTC-001 | A-OTC-001 | KEEP | P0 | 05 | FROZEN | 无 | OTC订单不变 |
| A-OTC-002 | A-OTC-002 | KEEP | P0 | 05 | FROZEN | 无 | OTC详情+State |
| A-OTC-003 | A-OTC-003 | KEEP | P1 | 05 | FROZEN | 无 | 撮合监控→P1 |
| A-CONFIG-001 | A-CONFIG-001 | KEEP | P0 | 07 | FROZEN | 路由从11→07 | 参数定义不变 |
| A-CONFIG-002 | A-CONFIG-002 | KEEP | P0 | 07 | FROZEN（BASE=FROZEN；OwnerOverride=CONTRACT_GAP/GAP-015） | 路由从11→07 | 参数发布+State |
| A-POLICY-001 | A-POLICY-001 | KEEP | P0 | 07 | FROZEN | 路由从11→07 | 策略矩阵不变 |
| A-RISK-001 | A-RISK-001 | KEEP | P0 | 07 | FROZEN | 路由从12→07 | 风险事件+State |
| A-APPROVAL-001 | A-APPROVAL-001 | KEEP | P0 | 07 | FROZEN（BASE=FROZEN；threshold参数=CONTRACT_GAP/GAP-016） | 路由从12→07 | 审批中心+SoD |
| A-AUDIT-001 | A-AUDIT-001 | KEEP | P0 | 08 | FROZEN | 路由从13→08 | 操作日志不变 |
| A-AUDIT-002 | A-AUDIT-002 | KEEP | P0 | 08 | FROZEN | 路由从13→08 | 敏感审计不变 |
| A-OPS-001 | A-OPS-001 | KEEP | P0 | 08 | FROZEN | 路由从14→08 | 系统运维不变 |
| A-REPORT-001 | A-REPORT-001 | KEEP | P1 | 07 | FROZEN | 路由从14→07 | 报表→P1 |
| A-MIGRATION-001 | A-MIGRATION-001 | KEEP | FUTURE | 08 | FUTURE | 路由从14→08 | Migration不变 |

## 代理后台 7 页迁移矩阵

| V2.4 Page ID | V2.4.1 Page ID | 动作 | Priority | 说明 |
|---|---|---|---|---|
| AG-HOME-001 | AG-HOME-001 | KEEP | P1_CONDITIONAL | 代理首页不变 |
| AG-USER-001 | AG-USER-001 | KEEP | P1_CONDITIONAL | 我的用户不变 |
| AG-USER-002 | AG-USER-002 | KEEP | P1_CONDITIONAL | 用户详情不变 |
| AG-TEAM-001 | AG-TEAM-001 | KEEP | P1_CONDITIONAL | 推荐关系不变 |
| AG-DATA-001 | AG-DATA-001 | KEEP | P1_CONDITIONAL | 运营数据不变 |
| AG-SUPPORT-001 | AG-SUPPORT-001 | KEEP | P1_CONDITIONAL | 客服工单不变 |
| AG-ACCOUNT-001 | AG-ACCOUNT-001 | KEEP | P1_CONDITIONAL | 账号安全不变 |

## 旧页面无证据消失检查

| 旧 Page ID | V2.4 中是否有直接页面 | 处理方式 |
|---|---|---|
| A-LEDGER-004 | 已合并至 A-LEDGER-003（池子/对账/冲正） | MERGE_INTO A-LEDGER-003 |
| A-SUPPORT-002 | 已合并至 A-SUPPORT-001（客服工单中心） | MERGE_INTO A-SUPPORT-001 |
| A-GROWTH-001 | 运营总览已覆盖增长指标 | MERGE_INTO A-WORK-001 |

## 汇总

```text
TOTAL_PAGES = 58（51 Admin + 7 Agent）
KEEP = 58
EXPAND = 0
MERGE_INTO = 0（全量 KEEP）
SPLIT_TO = 0
SUPERSEDE = 0
SILENT_DELETE = 0
DUPLICATE_PAGE_ID = 0
ROUTE_IMPACT = 32 pages（因 Root 变化路由变更）[DERIVED]（实际标有"路由从"的页面 = 32）
```
