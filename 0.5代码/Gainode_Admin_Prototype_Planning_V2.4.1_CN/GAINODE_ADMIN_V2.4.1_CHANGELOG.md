# GAINODE ADMIN V2.4.1 CHANGELOG

> FROM: Gainode Admin Prototype Planning V2.4 CN  
> TO: Gainode Admin Prototype Planning V2.4.1 CN  
> PRODUCT_DIRECTION_CHANGE = NO  
> ECONOMIC_MODEL_CHANGE = NO  
> POWER_MODEL_CHANGE = NO  
> OTC_MODEL_CHANGE = NO  
> AI_REAL_EXECUTION_CHANGE = NO

## 变更分类

### A. STRUCTURE（结构变更）

| # | 变更项 | 从 | 到 | 说明 |
|---|---|---|---|---|
| A1 | 导航数量 | 14 Root | 8 Root | 对齐 04 冻结 IA |
| A2 | 代理管理 | 03 独立 Root | 02 用户与准入（子模块） | 归入用户准入域 |
| A3 | Reward/经济模型 | 05 独立 Root | 03 资产与账本（子模块） | 经济记录归入账本 |
| A4 | 数据智能中心 | 07 独立 Root | 06 赛事预测 + 08 运维（分拆） | 赛事数据→06，数据源→08 |
| A5 | AI 运营中心 | 08 独立 Root | 07 风控/审批/参数/策略 | AI 策略工具归入风控域 |
| A6 | APT/Power | 09 独立 Root | 03 资产 + 05 OTC（分拆） | APT→03，Power→05 |
| A7 | OTC | 10 独立 Root | 05 OTC 与 Power（合并） | 合并操作资源 |
| A8 | 参数+风控 | 11+12 两个 Root | 07 一个 Root（合并） | 风控审批参数策略统一 |
| A9 | 审计+系统 | 13+14 两个 Root | 08 一个 Root（合并） | 客服审计运维统一 |

### B. PRIORITY（优先级重分类）

| # | 变更项 | 从 | 到 |
|---|---|---|---|
| B1 | P0 总数 | 49 | 32 |
| B2 | 新增 P1 类别 | 无 | 8 pages |
| B3 | 新增 P1_CONDITIONAL 类别 | 无 | 17 pages（10 Admin + 7 Agent） |
| B4 | Agent Portal | 未标 Priority | P1_CONDITIONAL 统一 |
| B5 | A-DATA-001 数据驾驶舱 | P0 | P1 |
| B6 | A-AI-001~006 AI 系列 | 5 P0 + 1 P1 | 降为 P1 或 P1_CONDITIONAL |
| B7 | A-OTC-003 撮合监控 | P0 | P1 |
| B8 | A-ROBOT-004 升级监控 | P0 | P1 |
| B9 | A-REPORT-001 报表 | P0 | P1 |
| B10 | A-AFF-001~004 代理 | 4 P0 | 4 P1_CONDITIONAL |
| B11 | A-DATA-002/4/5 数据源 | 3 P0 | 3 P1_CONDITIONAL |

### C. GOVERNANCE（治理修复）

| # | 变更项 | 说明 |
|---|---|---|
| C1 | SoD 闭合 | SELF_APPROVAL = FORBIDDEN；10 项高风险操作 Requester ≠ Approver |
| C2 | Owner Override 重定义 | 不可绕过 Ledger/Audit/SoD；当前状态 CONTRACT_GAP |
| C3 | Approval Policy | 不自定义阈值；读取 approval_policy / threshold 字段 |
| C4 | Page ID Migration Matrix | 51+7 页面全量 KEEP，SILENT_DELETE = 0 |
| C5 | Navigation Migration Matrix | 14→8 完整追溯 |

### D. STATE（状态模型扩展）

| # | 变更项 | 说明 |
|---|---|---|
| D1 | 高风险页面 Write State | 补充 SUBMITTING / PROCESSING / UNDER_REVIEW / CONFLICT / STATE_CHANGED / RESULT_UNKNOWN |
| D2 | RESULT_UNKNOWN | 所有异步写操作必须支持 NO_REPEAT_SUBMIT + Idempotency Key |
| D3 | A-CONFIG-002 完整生命周期 | Draft→Review→Approved→Scheduled→Active→Paused→RolledBack→Failed→Unknown |

### E. EVIDENCE（证据声明纠正）

| # | 变更项 | 从 | 到 |
|---|---|---|---|
| E1 | API-Football | COMPLETE | UI_SPEC=PASS, CONTRACT=GAP, RUNTIME=NOT_YET |
| E2 | BetBurger | COMPLETE | UI_SPEC=PASS, CONTRACT=GAP, RUNTIME=NOT_YET |
| E3 | AI Simulation | COMPLETE | UI_SPEC=PASS, CONTRACT=GAP, RUNTIME=NOT_YET |
| E4 | Evidence 维度 | 单一 COMPLETE | UI_SPEC / PROVIDER_CONTRACT / RUNTIME 三分 |

### F. LOCALIZATION（中文本地化）

| # | 变更项 | 说明 |
|---|---|---|
| F1 | 锁定词保留 | Gainode/Robot/APT/OTC/Power/MFA/KYC/AI 保持英文 |
| F2 | 普通操作词中文化 | Reward/Claim→奖励/领取，Signal→信号，Release→发布等 |
| F3 | 禁止英文 UI | Select/Submit/Preview 等英文消失在中文字段 |

### G. QA（质量保障升级）

| # | 变更项 | 从 | 到 |
|---|---|---|---|
| G1 | QA 维度 | PASS/FAIL | PAGE_REGISTERED / POLICY_DEFINED / UPSTREAM_CONTRACT / PROVIDER_CONTRACT / HIFI_IMPLEMENTATION / RUNTIME_VALIDATION |
| G2 | 状态粒度 | PASS | VERIFIED_PASS / VERIFIED_FAIL / POLICY_PRESENT / CONTRACT_GAP / NOT_VERIFIED / NOT_YET_EXECUTED |
| G3 | 禁止 Overclaim | — | 不能把"文档存在"等同于"实现完成" |

### H. REVIEW（审核升级）

| # | 变更项 | 说明 |
|---|---|---|
| H1 | 硬 Gate 增加 | ADMIN_ROOT_NAV_COUNT = 8 / PAGE_ID_MIGRATION_MATRIX / HIGH_RISK_SELF_APPROVAL = FORBIDDEN / PRODUCTION_P0_WITH_CONTRACT_GAP = FORBIDDEN |
| H2 | 新增审核项 | OWNER_OVERRIDE_CONTRACT / WRITE_STATE_VARIANT / AGENT_SCOPE_FAIL_CLOSED |

## NOT CHANGED

- 51 个 Admin Page ID 全部保留
- 7 个 Agent Portal Page ID 全部保留
- 运营闭环 / 用户管理 / 限制 / 资产 / AI / OTC / Power / Audit 方向不变
- 后台视觉方向不变
- 不做的功能不变

### I. GOVERNANCE REMEDIATION (Review #482 · 2026-08-11)

| # | 变更项 | 说明 |
|---|---|---|
| I1 | RBAC/SoD 恢复 canonical 角色 | 权限矩阵增加 UI_PERSONA→CANONICAL_ROLE_IDS[] 映射；SoD 映射替换为 05 canonical Role ID；参数流程确认为 PARAM_EDITOR→PARAM_APPROVER→RELEASE_OPERATOR 三段分离 |
| I2 | Contract Status/Priority 重算 | A-USER-004 降为 P1_CONDITIONAL（GAP-014/GAP-015）；A-DATA-003/A-AI-001/A-AI-002 Contract 从 FROZEN 修正为 CONTRACT_GAP；P0_COUNT: 32→31；P1_CONDITIONAL: 17→18；增加 PAGE_ID→GAP_ID JOIN 表 |
| I3 | 领域状态三轴分离 | 拆分 DOMAIN_OBJECT_STATE / UI_OPERATION_STATE / REQUEST_RESOLUTION_STATE；A-CONFIG-002 对齐 05 canonical enum；RESULT_UNKNOWN 不得作为领域状态；A-USER-004 字段标 UI_CANDIDATE_ONLY |
| I4 | QA Overclaim 删除 | HIFI_IMPLEMENTATION: 40/58 VERIFIED_PASS → 0/58 NOT_VERIFIED；增加 artifact_sha256/evidence_path 证据字段占位 |
| I5 | AI 页面 Root 冲突修复 | A-AI-005/A-AI-006 统一归入 Root 07；Navigation Migration 补全；Main Planning Root 08 移除 AI 条目 |
| I6 | 统计数字可复算 | ROUTE_IMPACT: 34→32 [DERIVED]；Contract Status: 33+24+1=58；Priority: 31+8+18+1=58 |
| I7 | 中文 UI 本地化收口 | Page Map 英文混合词中文化；Gap Register 英文临时行为→中文+INTERNAL_ONLY 标记 |
| I8 | OTA→OTC 拼写修正 | Navigation Migration "OTA 与 Power"→"OTC 与 Power" |

### J. GOVERNANCE MICRO-REMEDIATION (Review #485 · 2026-08-11)

| # | 变更项 | 严重度 | 说明 |
|---|---|---|---|
| J1 | UI Persona / Canonical Role 严格分层 | P1 | 增加三层架构声明（§2.0）；UI Persona 仅控制导航可见性，API 权限由 Canonical Role 授权。移除高风险动作 Action→Requester→Approver 逐条映射表（§4），替换为 SoD 互斥约束 + Fail-Closed 原则。具体审批路由由 05 Contract 和 Policy Engine 决定，治理文档不越权指定 |
| J2 | Contract Status 派生源统一 | P1 | 在 Page→Gap JOIN 表声明为 Contract Status 唯一派生源；A-AI-006 Contract 从 FROZEN 修正为 CONTRACT_GAP（GAP-010/BLOCKING），与 JOIN 表一致 |
| J3 | Contract Status 统计更正 | P2 | 从 Migration Matrix 逐行加总重算：FROZEN(33→35)、CONTRACT_GAP(24→22)、FUTURE(1)；Page Map §4 增加机械复算说明，人工可独立验证 |
| J4 | Gate 4 语义对齐 | P2 | 独立审核 Gate 4 从"P0 + Contract Gap 禁止共存"更新为"P0 + BLOCKING Contract Gap 禁止共存"；明确 NON_BLOCKING Gap 不触发阻断；验证方式引用 JOIN 表交叉检查 |

```text
V2_4_1_DOCUMENT_STATUS = READY_FOR_INDEPENDENT_REVIEW
READY_TO_MERGE_INTO_04 = NO
```
