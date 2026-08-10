# GAINODE ADMIN CONTRACT GAP REGISTER V2.4.1

> 状态更新日期：2026-08-11  
> 临时行为：FAIL_CLOSED（不前端猜默认值）

## Contract Gap 登记

| GAP ID | 对象/能力 | 影响页面 | 缺失源 | 缺失内容 | 当前影响 | 临时UI行为 | 需 Owner 决定 |
|---|---|---|---|---|---|---|---|
| GAP-001 | Affiliate Object | A-AFF-001/2/3/4, AG-* x7 | 05 | Object/Field/State/Permission/API 均未冻结 | 代理管理全功能无法生产上线 | 显示"功能规划中"占位 | Affiliate 是否进入 V6.1 范围 |
| GAP-002 | AffiliateRelation | A-AFF-004 | 05 | 推荐关系 Object 定义 | 代理统计页面不可用 | 显示"依赖上游未就绪" | 推荐关系是否保留 |
| GAP-003 | DataProvider | A-DATA-002 | 05 | Provider Object/Contract/Health 定义 | 数据源管理不可用 | 仅显示 Provider 名称占位 | API-Football 合同是否已签 |
| GAP-004 | ProviderHealth | A-DATA-002 | 05 | Provider 健康指标对象 | 数据源状态监控不可用 | 显示"运行时不可用"（INTERNAL_ONLY/NOT_USER_VISIBLE） | BetBurger 合同是否已签 |
| GAP-005 | RawDataSnapshot | A-DATA-004 | 05 | Raw 数据快照 Object | 原始数据查看不可用 | 显示占位说明 | 是否保留原始快照能力 |
| GAP-006 | FootballEventNormalized | A-DATA-003 | 05 | 归一化赛事事件 Object | 足球数据管理页受限 | 依赖上游冻结后开放 | 归一化对象类型 |
| GAP-007 | MarketFeed | A-DATA-004 | 05 | 市场赔率 Feed Object | 市场数据页不可用 | 仅显示 Provider 状态 | BetBurger Feed 格式 |
| GAP-008 | ArbitrageOpportunity | A-DATA-004, A-AI-004 | 05 | 套利机会 Object 定义 | 套利数据和模拟不可用 | 显示"功能不可用"（INTERNAL_ONLY/NOT_USER_VISIBLE） | 是否启用套利模拟 |
| GAP-009 | AISignal | A-DATA-005 | 05 | AI 信号 Object 定义 | 信号质量页不可用 | 显示"开发中功能"（INTERNAL_ONLY/NOT_USER_VISIBLE） | AI 信号是否生产需求 |
| GAP-010 | AIRecommendation | A-AI-001/2 | 05 | AI 建议 Object 定义 | AI 运营中心不可用 | 显示占位 | AI 建议 Pipeline 设计 |
| GAP-011 | AIStrategy | A-AI-004/5 | 05 | AI 策略 Object 定义 | AI 策略/模拟不可用 | 显示占位 | AI 策略是否生产需求 |
| GAP-012 | SimulationRun | A-AI-004 | 05 | 模拟运行 Object 定义 | 策略模拟不可用 | 显示"理论功能"（INTERNAL_ONLY/NOT_USER_VISIBLE） | 模拟运行引擎 |
| GAP-013 | UserRestriction（扩展） | A-USER-003 | 05 | Restriction Object 扩展：分类型限制/解除/还原 | 限制功能受限 | 仅显示当前状态，不执行操作 | 限制类型是否需要扩展 |
| GAP-014 | AssetAdjustment | A-USER-004 | 05 | Adjustment Proposal / Approval / Execution 全链路 Object | 资产调整审批流不完整 | 仅 Preview，不执行 | 审批阈值是否需要定义 |
| GAP-015 | Owner Override | A-CONFIG-002, A-USER-004 | 05/06 | Owner Override 正式 Contract | 紧急操作走无效流程 | FAIL_CLOSED | 正式 Override Contract |
| GAP-016 | Approval Threshold | A-APPROVAL-001 | 06 | approval_policy / threshold / required_approver_count 参数 | 审批阈值前端不可见 | 从后端读取，无后端时拒绝 | 正式审批阈值定义 |
| GAP-017 | API-Football Provider Contract | A-DATA-002/3 | External | 实际 Provider 合同/字段 | Provider 数据不真实 | 仅显示 UI Spec | 签署 Provider 合同 |
| GAP-018 | BetBurger Provider Contract | A-DATA-002/4 | External | 实际 Provider 合同/字段 | BetBurger 数据不真实 | 仅显示 UI Spec | 签署 Provider 合同 |

## 汇总

```text
TOTAL_CONTRACT_GAPS = 18
SOURCE_OF_TRUTH_GAPS = 16（05/06 未定义）
EXTERNAL_PROVIDER_GAPS = 2
REQUIRED_OWNER_DECISION = 18
```

## PAGE_ID → GAP_ID JOIN 表（用于派生 CONTRACT_STATUS / PRIORITY）

| Page ID | 关联 GAP ID | BLOCKING_LEVEL | 派生 CONTRACT_STATUS | 派生 PRIORITY | 临时行为 |
|---|---|---|---|---|---|
| A-AFF-001 | GAP-001 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 显示"功能规划中" |
| A-AFF-002 | GAP-001 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 显示"功能规划中" |
| A-AFF-003 | GAP-001 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 显示"功能规划中" |
| A-AFF-004 | GAP-001, GAP-002 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 显示"依赖上游未就绪" |
| AG-* x7 | GAP-001 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 占位 |
| A-DATA-002 | GAP-003, GAP-004, GAP-017, GAP-018 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 仅显示 Provider 名称 |
| A-DATA-003 | GAP-006 | NON_BLOCKING（基础数据结构可用） | CONTRACT_GAP | P1 | 依赖上游冻结后开放；BASE_CONTRACT = FROZEN |
| A-DATA-004 | GAP-005, GAP-007, GAP-008, GAP-018 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 仅显示 Provider 状态 |
| A-DATA-005 | GAP-009 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 显示"开发中功能" |
| A-AI-001 | GAP-010 | BLOCKING | CONTRACT_GAP | P1 | 显示占位 |
| A-AI-002 | GAP-010 | BLOCKING | CONTRACT_GAP | P1 | 显示占位 |
| A-AI-003 | GAP-010, GAP-011 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 显示占位 |
| A-AI-004 | GAP-008, GAP-011, GAP-012 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 显示"理论功能" |
| A-AI-005 | GAP-010, GAP-011 | BLOCKING | CONTRACT_GAP | P1_CONDITIONAL | 显示占位 |
| A-AI-006 | GAP-010 | BLOCKING | CONTRACT_GAP | P1 | 显示占位 |
| A-USER-003 | GAP-013 | NON_BLOCKING（基础限制功能可用） | FROZEN（BASE_CONTRACT=FROZEN + 扩展=GAP） | P0 | 仅显示当前状态；扩展操作关闭 |
| A-USER-004 | GAP-014, GAP-015 | BLOCKING（核心 Adjustment 全链路未冻结） | CONTRACT_GAP | P1_CONDITIONAL | 仅 Preview 用户资产，不执行 Adjustment |
| A-CONFIG-002 | GAP-015 | NON_BLOCKING（ParameterRelease 核心 Contract 已冻结） | FROZEN（BASE_CONTRACT=FROZEN + OwnerOverride=GAP） | P0 | 正常参数发布流程可用；Owner Override 关闭 |
| A-APPROVAL-001 | GAP-016 | NON_BLOCKING（Approval 核心 Contract 已冻结） | FROZEN（BASE_CONTRACT=FROZEN + threshold参数=GAP） | P0 | 从后端读取阈值；无后端时拒绝 |
| A-MIGRATION-001 | — | — | FUTURE | FUTURE | 无 |
