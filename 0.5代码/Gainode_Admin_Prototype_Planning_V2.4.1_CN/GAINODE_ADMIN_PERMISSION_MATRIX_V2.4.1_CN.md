# Gainode 后台角色权限矩阵 V2.4.1 中文版

## 1. SoD 全局规则（新增）

```text
SELF_APPROVAL = FORBIDDEN
REQUESTER_ID != APPROVER_ID
APPROVED != EXECUTED
OWNER_OVERRIDE = CONTROLLED（需 MFA + Reason + Evidence + 事后审计）
```

## 2. 角色分层架构

本权限矩阵采用三层架构，严格区分"谁能看到什么"（UI Persona）、"谁能做什么"（Canonical Role）、"谁不能同时做什么"（SoD 约束）。

### 2.0 分层原则

| 层次 | 用途 | 决定内容 | 不决定内容 |
| --- | --- | --- | --- |
| **UI_PERSONA** | 界面展示 + 导航可见性 | 用户能看到哪些页面/菜单入口 | 用户能执行哪些 API 操作 |
| **CANONICAL_ROLE** | API 权限来源（05 §8） | 用户能调用哪些 API（读写范围） | 界面导航结构 |
| **SoD 约束** | 互斥规则 | 哪些角色对不可由同一自然人同时持有 | 每个 Action 的具体审批角色 |

UI Persona 到 Canonical Role 的映射是**多对多**的——但此映射仅用于导航可见性配置，不授权任何 Role。

**关键声明**：
- UI_PERSONA = PRESENTATION_ONLY — 仅控制导航可见性和页面入口可见性
- UI_PERSONA_DOES_NOT_GRANT_ROLE = TRUE — UI Persona 不授予任何 Canonical Role
- EFFECTIVE_CANONICAL_ROLES = SERVER_RESOLVED_FROM_USER_ROLE_ASSIGNMENT — 有效角色由服务端从 User Role Assignment 解析，非从 Persona 推导
- 同一自然人的 SoD 检查基于 **actor_id**（自然人身份），非基于 current_active_role（见 §4 Actor Separation Invariant）

### 2.1 UI Persona（运营后台展示名称，仅用于导航/显示）

总后台：
- 超级管理员
- 总后台运营
- 客服

代理后台：
- 代理
- 代理客服

> UI Persona 仅控制**界面可见性**（菜单、页面入口），不是 API 权限来源。API 权限由 §2.2 的 Canonical Role 授权。

### 2.2 UI Persona → Canonical RBAC Roles 典型组合参考（EXAMPLE_ONLY / NON_AUTHORITATIVE / NOT_ROLE_GRANT）
> 以下映射仅为**典型角色组合参考**，不代表实际角色授权。Effective Canonical Roles 由服务端从 User Role Assignment 解析，不由 UI Persona 推导。

```text
UI_PERSONA             → TYPICAL_CANONICAL_ROLES (EXAMPLE_ONLY)  → ABAC_SCOPE
超级管理员               → EXAMPLE_ONLY / NON_AUTHORITATIVE:       → ALL
                           [PARAM_EDITOR, PARAM_APPROVER,
                           RELEASE_OPERATOR, RISK_ANALYST,
                           RISK_APPROVER, LEDGER_OPERATOR,
                           FINANCE_REVIEWER, OPS_OPERATOR,
                           KYC_REVIEWER, SUPPORT_AGENT,
                           ADMIN_SECURITY, AUDITOR]
                           NOT_ROLE_GRANT — 实际角色以 User Role
                           Assignment 为准，且受 Actor-level SoD
                           约束（同一 Actor 不得同时为
                           PARAM_EDITOR/PARAM_APPROVER/
                           RELEASE_OPERATOR，见 §4）
总后台运营               → EXAMPLE_ONLY: [OPS_OPERATOR,            → ALL_BUSINESS_DATA（安全Secret/高敏证据遮罩）
                           KYC_REVIEWER, SUPPORT_AGENT,
                           RISK_ANALYST, FINANCE_REVIEWER,
                           AUDITOR]
客服                     → EXAMPLE_ONLY: [SUPPORT_AGENT]           → CUSTOMER_SERVICE_SCOPE
代理                     → [（暂未生成 RBAC Role）]                → AFFILIATE_TREE(own_affiliate_id) FAIL_CLOSED
代理客服                  → [（暂未生成 RBAC Role）]                → AFFILIATE_TREE + CUSTOMER_SERVICE_FIELDS FAIL_CLOSED
```

> 05 权威 canonical Role ID 列表（§8）：END_USER / SUPPORT_AGENT / OPS_OPERATOR / KYC_REVIEWER / RISK_ANALYST / RISK_APPROVER / LEDGER_OPERATOR / FINANCE_REVIEWER / PARAM_EDITOR / PARAM_APPROVER / RELEASE_OPERATOR / AUDITOR / ADMIN_SECURITY
>
> 低 05 角色不得被 V2.4.1 重定义或压缩。UI Persona 仅作为运营后台展示名称，不得替代 canonical RBAC 角色。
>
> **授权公式（FIX-02）**：Canonical Role 是 **Canonical RBAC Role Identity 的唯一权威来源**。
>
> ```text
> FINAL_AUTHORIZATION = canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD
> ```
>
> - ABAC / Policy Failure = FAIL_CLOSED
> - RBAC alone ≠ Authorization（RBAC 仅提供 Role Identity，不单独授权）
> - UI Persona ≠ Authorization（UI Persona 仅控制导航可见性）
> - 页面权限 ≠ 字段权限 ≠ 数据范围权限（05 §8/§11 原则）

## 3. 权限矩阵（以 UI Persona 展示，底层引用 05 canonical Role ID）

| 能力 | 超级管理员 | 总后台运营 | 客服 | 代理 | 代理客服 |
| --- | --- | --- | --- | --- | --- |
| 查看全平台用户 | ✓ | ✓ | 客服字段 | 仅自己代理域 | 仅自己代理域 |
| UID/手机号/邮箱 | ✓ | ✓（脱敏） | ✓（脱敏） | 所属用户/脱敏 | 所属用户/脱敏 |
| 冻结账户 | ✓（需 RISK_APPROVER 批准） | 按权限/Case | — | — | — |
| 冻结余额 | ✓（需 FINANCE_REVIEWER 批准） | 仅发起Case | — | — | — |
| 冻结OTC | ✓（需 RISK_APPROVER 批准） | 按权限/Case | — | — | — |
| 限制Robot | ✓（需 RISK_APPROVER 批准） | 按权限/Case | — | — | — |
| 调整用户资产 | ✓（需 FINANCE_REVIEWER 审批；05 AssetAdjustment = CONTRACT_GAP） | — | — | — | — |
| KYC最终决定 | ✓ | ✓（KYC_REVIEWER） | — | — | — |
| 客服工单 | ✓ | ✓ | ✓ | 代理工单 | ✓ |
| 查看代理全局 | ✓ | ✓ | — | 仅自己 | 仅自己 |
| 暂停/恢复代理 | ✓ | ✓（按权限） | — | — | — |
| Robot全局监控 | ✓ | ✓ | 用户只读 | — | — |
| 经济模型监控 | ✓（FINANCE_REVIEWER 只读） | ✓ | — | — | — |
| 编辑Candidate参数 | ✓（PARAM_EDITOR；不可同时为 PARAM_APPROVER/RELEASE_OPERATOR） | — | — | — | — |
| 批准/发布Parameter | ✓（PARAM_APPROVER 批准; RELEASE_OPERATOR 激活；不可自审批） | — | — | — | — |
| 竞猜运营 | ✓ | ✓ | 只读 | — | — |
| 数据源管理 | ✓ | ✓（无Secret） | — | — | — |
| 查看Secret明文 | — | — | — | — | — |
| AI运营建议 | ✓ | ✓ | 客服助手限定 | — | — |
| AI套利模拟 | ✓ | ✓ | — | — | — |
| AI真实自动执行 | — | — | — | — | — |
| OTC运营/审核 | ✓（RISK_ANALYST 分析; RISK_APPROVER 批准处置） | ✓ | 只读用户订单 | — | — |
| 全量Audit | ✓（AUDITOR 只读） | 业务日志/敏感遮罩 | 本人/工单相关 | 自身日志 | 自身客服日志 |
| 删除Audit | — | — | — | — | — |

> 上表中"超级管理员"可承载多个 05 canonical Role，但**实际操作时必须满足相应 canonical Role 的 SoD 约束**。同一自然人不得在同一事务中同时占用冲突角色（如 PARAM_EDITOR + PARAM_APPROVER）。

## 4. SoD 互斥约束（Actor Separation Invariant）

以下 SoD 规则声明 **Actor Separation Invariant**——互斥检查基于 **actor_id**（自然人身份），非基于 current_active_role。同一 Actor 不可通过切换角色绕过约束。

### Actor-level Invariant 检查

```text
FORBIDDEN: candidate.created_by_actor_id == approval.approved_by_actor_id
FORBIDDEN: approval.approved_by_actor_id == release.executed_by_actor_id
FORBIDDEN: candidate.created_by_actor_id == release.executed_by_actor_id
FORBIDDEN: requester.actor_id == approver.actor_id（跨所有 Canonical Role）
```

> 上述 invariant 是 **Actor-level** 的——即使同一 Actor 先后以不同 Canonical Role 身份执行两个步骤，只要 actor_id 相同即判定违规。不可通过"当前只激活一个 Role"绕过。

| 互斥对 | Actor Separation Invariant | 来源 |
| --- | --- | --- |
| PARAM_EDITOR vs PARAM_APPROVER | 同一 Actor（actor_id 相同）不得同时为二者 | 05 §8 三段分离 |
| PARAM_EDITOR vs RELEASE_OPERATOR | 同一 Actor 不得同时为二者 | 05 §8 三段分离 |
| PARAM_APPROVER vs RELEASE_OPERATOR | 同一 Actor 不得同时为二者 | 05 §8 三段分离 |
| RISK_ANALYST vs RISK_APPROVER | 同一 Actor 不得同时为二者（分析≠批准处置） | 05 §8 |
| LEDGER_OPERATOR vs FINANCE_REVIEWER | 同一 Actor 不得同时为二者（账本操作≠财务审核） | 05 §8/§11 |
| 任意 Requester vs Approver | requester.actor_id != approver.actor_id | SoD 全局规则 |

### Fail-Closed 原则

- 当审批路由在 05 Contract 中未定义时，拒绝执行（FAIL_CLOSED），不猜测默认批准者。
- 无 05 Contract 支持的操作一律标记为 CONTRACT_GAP，前端仅展示占位或 Preview，不开放实际执行。
- Owner Override 不可绕过 Ledger/Audit/SoD 约束。
- 上述 SoD 检查为 Actor Separation Invariant——基于 actor_id 比较，不基于 current_active_role。同一 Actor 切换角色后仍需满足所有 invariant。

> **重要**：上表的互斥对声明的是 Actor-level "禁止组合"。本表**不指定**"某个具体 Action 必须由哪个特定角色审批"——此类决策属于 05 API Contract 设计范畴和运行时 Policy Engine 职责，不由本治理矩阵越权指定。

## 5. 数据范围（不变）

- 超级管理员：`ALL`
- 总后台运营：`ALL_BUSINESS_DATA`，安全Secret/高敏证据遮罩
- 客服：`CUSTOMER_SERVICE_SCOPE`
- 代理：`AFFILIATE_TREE(own_affiliate_id)` — FAIL_CLOSED
- 代理客服：`AFFILIATE_TREE + CUSTOMER_SERVICE_FIELDS` — FAIL_CLOSED

## 6. 按钮授权（不变）

```text
role
+ data_scope
+ object_state
+ allowed_actions
+ risk/policy
+ REQUESTER_ID != APPROVER_ID（高风险动作）
```

## 7. 超级管理员也不能绕过（不变）

- 不直接改数据库
- 不删除日志
- 不覆盖历史Ledger
- 不覆盖历史Result/Snapshot
- 不绕过Idempotency
- 不能自审批高风险操作（新增）
