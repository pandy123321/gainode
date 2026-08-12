# 审核规则

## 已确认信息

### 审核原则

- 审核必须基于已展示的证据，不得臆测
- 每个 Review 必须有明确 Verdict（APPROVED / CHANGES_REQUIRED / APPROVED_WITH_CONDITIONS）
- P0/P1 问题必须修复并由下一轮确认关闭
- 所有 Review 记录必须可追溯
- 审核只看当前变更，不假设未提交的代码功能

### 需求权威性

- **唯一需求源**：`Gainode_Development_Ready_V6.1_Latest/` 下的 `01–08` 文档
- **禁止反推来源**：历史文档 / 旧 Figma / 旧代码 / 旧原型包
- **冲突解决顺序**：产品 > 经济 > Mobile > Admin > Data/Permissions/API > Parameters > Dev/Acceptance > Visual/I18N > i18n strings > Logo

### 前端审核清单

#### 状态完整性
- [ ] 每个 P0 页面实现了 Loading/Content/Empty/Error/Restricted 五种状态
- [ ] 写操作页面实现了 Default/Submitting/Processing/Success/Failed 状态链
- [ ] Restricted 和 Error 使用不同文案（不是同一条通用提示）
- [ ] RESULT_UNKNOWN(202) 显示"正在确认结果"而非让用户重试

#### 资格与权限
- [ ] 所有真实价值按钮消费 allowed_actions / FeatureEntitlement
- [ ] 不存在前端自行计算资格（如 `if level > 20`）
- [ ] 资产类数字使用 string/decimal 而非 float 做业务计算
- [ ] Power Cap/恢复量/Power Impact 全部从服务端读取

#### 文案合规
- [ ] 无 APR/APY/固定收益/保本/稳赚
- [ ] 中文端 Prediction 统一使用「竞猜」
- [ ] 无下注/投注/博彩/赔率/盘口/押注
- [ ] 正式 UI 无 Demo/Mock/Sandbox/Page ID 标签
- [ ] 所有用户可见字符串通过 i18n key 读取

#### 视觉与交互
- [ ] 状态同时使用文字 + 颜色（不禁只用颜色）
- [ ] 卡片已最小化 Generic Card Feel
- [ ] 按钮点击区域 ≥44×44px
- [ ] 375/390/430 三尺寸视觉回归通过

### Vue 3 + TypeScript 审核清单

#### 组件规范
- [ ] 使用 Composition API + `<script setup lang="ts">`
- [ ] TypeScript `strict: true`，业务逻辑层无 `any` 类型
- [ ] 页面组件按 Page ID 命名（如 `AUser004AssetAdjustment.vue`）
- [ ] 使用 Pinia 按模块拆分 store

#### API 调用
- [ ] Axios 实例自动注入六个请求头
- [ ] 资产类数字使用 `string` 类型 + `decimal.js`
- [ ] RESULT_UNKNOWN(202) 提示"查询结果中"而非让用户重试

#### I18N
- [ ] 使用 vue-i18n 9+，模板中无硬编码文案
- [ ] 7 语言 key 集一致（`MISSING_I18N_KEY = 0`）
- [ ] 禁用了 raw enum 直接显示

#### 样式与适配
- [ ] 全局设计令牌从 CSS 变量读取
- [ ] H5 端 768px 以下完全按 Mobile 规则
- [ ] 兼容 375/390/430 三尺寸

### Flutter 审核清单

- [ ] Dart 3+ null safety 已启用
- [ ] Dio 实例自动注入六个请求头
- [ ] 金额使用 `String` + `decimal` 包（禁止 `double`）
- [ ] ARB 文件 key 集与 `ui-copy-manifest.json` 一致
- [ ] 每个 Page ID 独立 Widget，通用组件在 `lib/widgets/` 下

### 后端审核清单

#### API 契约
- [ ] OpenAPI 实现与规范一致
- [ ] 所有写操作包含 idempotency_key
- [ ] 并发状态变更有 object_version/lock 机制
- [ ] 统一响应包含 request_id、rule_version、parameter_release_id、snapshot_id

#### 账本安全
- [ ] APT/Power 账本为 append-only
- [ ] 修正使用 reversal（追加反向记录），无覆盖或删除历史
- [ ] 写操作幂等已验证（重复请求不重复资金效果）

#### 状态机
- [ ] Market/Result/Settlement/Order 状态按对象独立管理
- [ ] OTC expired 和 cancelled 有明确语义区分
- [ ] OtcEligibility 实现为非状态机（每次请求动态计算）

#### 资格与参数
- [ ] 资格/参数/Policy 在服务端解析后返回
- [ ] TBC 生产值保持 null/closed，无本地默认值补齐
- [ ] Parameter Release 保存≠生效、Approved≠Active
- [ ] 历史订单通过 snapshot 回算（不用当前参数）

#### 通知与异步
- [ ] 通知投递与业务事务解耦（Outbox Pattern）
- [ ] 业务成功/通知失败时业务状态不回滚
- [ ] 重试有去重 key（不重复生成等价 Notice）

#### 权限与安全
- [ ] RBAC/ABAC 实现页面权限≠字段权限≠数据范围权限
- [ ] 高风险操作走 Approval 工作流
- [ ] SoD 为 Actor-level Invariant（`candidate.created_by_actor_id != approval.approved_by_actor_id`）
- [ ] 授权逻辑使用完整公式（canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD）
- [ ] 紧急操作默认双人授权 + case_id + 审计记录
- [ ] 超级管理员仍受账本/审批/审计规则约束

### V2.4.1 治理文档审核

#### Contract Gap
- [ ] 所有 P0 页面的 BLOCKING Contract Gap = 0
- [ ] CONTRACT_GAP 页面标明 Preview-Only，执行按钮不可用
- [ ] Contract Status 从唯一派生源（Page→Gap JOIN）机械复算

#### SoD 权限
- [ ] 权限矩阵使用 canonical 13 role IDs（非 3 个 UI Persona）
- [ ] SoD 为 Actor-level Invariant
- [ ] 不存在 UI Persona 静态授予互斥 Canonical Roles
- [ ] 授权公式完整（非纯 RBAC）

#### 状态契约
- [ ] Domain State 全部来自 05 canonical enum（无自创状态）
- [ ] `RESULT_UNKNOWN` 仅在 Request Resolution 层，未进入任何 Domain Enum
- [ ] ParameterRelease 状态严格为 draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived

### 跨端一致性审核

- [ ] KYC 决定后用户端+后台同时更新
- [ ] Robot 升级后 Level/UpgradeOrder/Ledger/PowerCap 全部更新
- [ ] OTC Completed 后 Order/Trade/APT 冻结/Power 冻结同步
- [ ] Prediction 中 Result official 不等于 Settlement paid
- [ ] 风险限制后既有按钮禁用也有解释文案和支持入口
- [ ] ParameterRelease 用户端规则版本+通知同步

### 测试审核

- [ ] 覆盖 15 项极端情况测试（双击提交/超时/idempotency-key/Parameter变化/Market锁定/余额变化/KYC变化等）
- [ ] 有跨端状态一致性验收用例
- [ ] 有幂等性测试（连续双击提交）
- [ ] 有 Outbox 重放不重复资金效果测试

### 安全审核

- [ ] 敏感资料按角色最小化访问
- [ ] 登录失败不泄露账号存在性
- [ ] 通知正文不含内部风控规则或他人数据
- [ ] 敏感文案标记 PENDING_HUMAN_REVIEW 而非 AI 自签
- [ ] Consent 确认带 content_version

### 审核禁止项

- 不凭记忆或猜测判断业务规则是否正确
- 不把 Demo/Mock 数据当作生产数据审核
- 不把开发中的 TBC 参数值当作正式审核缺陷
- 不以旧大 Figma、旧 Flutter、旧 Admin 作为审核基线
- 不以历史文档中的内容反推当前需求
- 不要求缺少独立 CI 系统就直接 NO-GO
- 不因为"可以更好"而拒绝

## 基于代码的推断

- 规则来源于 01–08 号规格文档和 V2.4.1 Admin 治理包中的约束条款
- Admin 治理审核 Gate 按 BLOCKING/NON-BLOCKING Gap 区分

## 待确认事项

- [ ] 审核工具与流程（AI Code Review Assistant 或其他）
- [ ] Stage/Gate 治理流程的具体方式
- [ ] 外部审核的频率和时机
- [ ] 代码审查的审批人指派规则和门禁策略
- [ ] 安全渗透测试的范围和周期

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（DoD、验收矩阵）
- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（API 契约、状态机、RBAC/ABAC）
- `Gainode_Development_Ready_V6.1_Latest/08_VISUAL_DESIGN_SYSTEM_V2.4.md`（视觉/文案审核）
- `0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/`（V2.4.1 治理包）
