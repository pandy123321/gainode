# Gainode 后台角色权限矩阵 V2.4.1 中文版

## 1. SoD 全局规则（新增）

```text
SELF_APPROVAL = FORBIDDEN
REQUESTER_ID != APPROVER_ID
APPROVED != EXECUTED
OWNER_OVERRIDE = CONTROLLED（需 MFA + Reason + Evidence + 事后审计）
```

## 2. 角色（不变）

总后台：
- 超级管理员
- 总后台运营
- 客服

代理后台：
- 代理
- 代理客服

## 3. 权限矩阵（不变）

| 能力 | 超级管理员 | 总后台运营 | 客服 | 代理 | 代理客服 |
| --- | --- | --- | --- | --- | --- |
| 查看全平台用户 | ✓ | ✓ | 客服字段 | 仅自己代理域 | 仅自己代理域 |
| UID/手机号/邮箱 | ✓ | ✓（脱敏） | ✓（脱敏） | 所属用户/脱敏 | 所属用户/脱敏 |
| 冻结账户 | ✓ | 按权限/Case | — | — | — |
| 冻结余额 | ✓ | 仅发起Case | — | — | — |
| 冻结OTC | ✓ | 按权限/Case | — | — | — |
| 限制Robot | ✓ | 按权限/Case | — | — | — |
| 调整用户资产 | ✓（需审批） | — | — | — | — |
| KYC最终决定 | ✓ | ✓ | — | — | — |
| 客服工单 | ✓ | ✓ | ✓ | 代理工单 | ✓ |
| 查看代理全局 | ✓ | ✓ | — | 仅自己 | 仅自己 |
| 暂停/恢复代理 | ✓ | ✓（按权限） | — | — | — |
| Robot全局监控 | ✓ | ✓ | 用户只读 | — | — |
| 经济模型监控 | ✓ | ✓ | — | — | — |
| 编辑Candidate参数 | ✓ | — | — | — | — |
| 批准/发布Parameter | ✓（不能自己审批自己的Candidate） | — | — | — | — |
| 竞猜运营 | ✓ | ✓ | 只读 | — | — |
| 数据源管理 | ✓ | ✓（无Secret） | — | — | — |
| 查看Secret明文 | — | — | — | — | — |
| AI运营建议 | ✓ | ✓ | 客服助手限定 | — | — |
| AI套利模拟 | ✓ | ✓ | — | — | — |
| AI真实自动执行 | — | — | — | — | — |
| OTC运营/审核 | ✓ | ✓ | 只读用户订单 | — | — |
| 全量Audit | ✓ | 业务日志/敏感遮罩 | 本人/工单相关 | 自身日志 | 自身客服日志 |
| 删除Audit | — | — | — | — | — |

## 4. 高风险动作 SoD 映射（新增）

| 动作 | Requester | Approver | MFA | 特殊要求 |
| --- | --- | --- | --- | --- |
| 资产调整 | 超级管理员 A | 超级管理员 B 或 Owner Override | ✓ | 不可自审批 |
| 账本冲正 | 运营 | 超级管理员 | ✓ | 产生新 Ledger Entry |
| 参数发布 | 超级管理员 A | 超级管理员 B | ✓ | Creator != Approver |
| 高风险参数变更 | 超级管理员 A | 超级管理员 B | ✓ | 双人确认 |
| 结算 | 运营 | 超级管理员 | ✓ | Settlement Confirm ≠ Execute |
| 结算更正 | 运营 | 超级管理员 | ✓ | 保留原 Settlement 快照 |
| 退款更正 | 运营 | 超级管理员 | ✓ | 保留原订单 |
| 重大用户限制 | 运营 | 超级管理员 | ✓ | 按阈值 |
| 重大权限变化 | 超级管理员 A | 超级管理员 B | ✓ | 审计留痕 |
| 紧急经济操作 | 超级管理员 | Owner Override（如需） | ✓ | 事后补审 + 期限 |

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
