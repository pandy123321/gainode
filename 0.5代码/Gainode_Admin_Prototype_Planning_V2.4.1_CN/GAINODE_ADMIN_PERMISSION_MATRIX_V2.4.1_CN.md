# Gainode 后台角色权限矩阵 V2.4.1 中文版

## 1. SoD 全局规则（新增）

```text
SELF_APPROVAL = FORBIDDEN
REQUESTER_ID != APPROVER_ID
APPROVED != EXECUTED
OWNER_OVERRIDE = CONTROLLED（需 MFA + Reason + Evidence + 事后审计）
```

## 2. 角色

### 2.1 UI Persona（运营后台展示名称）

总后台：
- 超级管理员
- 总后台运营
- 客服

代理后台：
- 代理
- 代理客服

### 2.2 UI Persona → Canonical RBAC Roles 映射（权威来源：05 §8/§11）

```text
UI_PERSONA             → CANONICAL_ROLE_IDS (from 05)           → ABAC_SCOPE
超级管理员               → [PARAM_EDITOR, PARAM_APPROVER,        → ALL（但 PARAM_EDITOR≠PARAM_APPROVER≠RELEASE_OPERATOR）
                           RELEASE_OPERATOR, RISK_ANALYST,
                           RISK_APPROVER, LEDGER_OPERATOR,
                           FINANCE_REVIEWER, OPS_OPERATOR,
                           KYC_REVIEWER, SUPPORT_AGENT,
                           ADMIN_SECURITY, AUDITOR]
总后台运营               → [OPS_OPERATOR, KYC_REVIEWER,          → ALL_BUSINESS_DATA（安全Secret/高敏证据遮罩）
                           SUPPORT_AGENT, RISK_ANALYST,
                           FINANCE_REVIEWER, AUDITOR]
客服                     → [SUPPORT_AGENT]                        → CUSTOMER_SERVICE_SCOPE
代理                     → [（暂未生成 RBAC Role）]               → AFFILIATE_TREE(own_affiliate_id) FAIL_CLOSED
代理客服                  → [（暂未生成 RBAC Role）]               → AFFILIATE_TREE + CUSTOMER_SERVICE_FIELDS FAIL_CLOSED
```

> 05 权威 canonical Role ID 列表（§8）：END_USER / SUPPORT_AGENT / OPS_OPERATOR / KYC_REVIEWER / RISK_ANALYST / RISK_APPROVER / LEDGER_OPERATOR / FINANCE_REVIEWER / PARAM_EDITOR / PARAM_APPROVER / RELEASE_OPERATOR / AUDITOR / ADMIN_SECURITY
>
> 低 05 角色不得被 V2.4.1 重定义或压缩。UI Persona 仅作为运营后台展示名称，不得替代 canonical RBAC 角色。

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

## 4. 高风险动作 SoD 映射（新增）

| 动作 | Requester（规范角色） | Approver（规范角色） | MFA | 特殊要求 |
| --- | --- | --- | --- | --- |
| 资产调整 | LEDGER_OPERATOR | FINANCE_REVIEWER 或 RISK_APPROVER | ✓ | 不可自审批；05 AssetAdjustment Contract = CONTRACT_GAP |
| 账本冲正 | LEDGER_OPERATOR | FINANCE_REVIEWER | ✓ | 产生新 Ledger Entry |
| 参数发布 | PARAM_EDITOR | PARAM_APPROVER | ✓ | Creator ≠ Approver; 激活由 RELEASE_OPERATOR 执行 |
| 高风险参数变更 | PARAM_EDITOR | PARAM_APPROVER + RISK_APPROVER | ✓ | 双角色确认；激活由 RELEASE_OPERATOR 执行 |
| 结算 | OPS_OPERATOR | FINANCE_REVIEWER | ✓ | Settlement Confirm ≠ Execute |
| 结算更正 | LEDGER_OPERATOR | FINANCE_REVIEWER | ✓ | 保留原 Settlement 快照 |
| 退款更正 | LEDGER_OPERATOR | FINANCE_REVIEWER | ✓ | 保留原订单 |
| 重大用户限制 | RISK_ANALYST | RISK_APPROVER | ✓ | 按阈值 |
| 重大权限变化 | OPS_OPERATOR | ADMIN_SECURITY | ✓ | 审计留痕 |
| 紧急经济操作 | LEDGER_OPERATOR | FINANCE_REVIEWER 或 RISK_APPROVER | ✓ | 事后补审 + 期限；OWNER_OVERRIDE_CONTRACT_STATUS = CONTRACT_GAP |

> **SoD 强制要求**：
> - PARAM_EDITOR ≠ PARAM_APPROVER ≠ RELEASE_OPERATOR（参数三段分离）
> - RISK_ANALYST ≠ RISK_APPROVER（风险分析与处置批准分离）
> - LEDGER_OPERATOR ≠ FINANCE_REVIEWER（账本操作与财务审核分离）
> - 申请人不能审批自己的申请（跨所有角色）

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
