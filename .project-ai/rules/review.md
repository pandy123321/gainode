# 审核规则

## 已确认信息

### 审核基本原则

1. 审核必须基于已展示的证据，不得臆测。
2. 审核只看当前变更，不假设未提交的代码功能。
3. 审核须对照 01–08 号规格文档验证实现是否符合基线。

### 前端审核要点

#### 状态完整性
- [ ] 每个 P0 页面是否实现了 Loading/Content/Empty/Error/Restricted 五种状态？
- [ ] 写操作页面是否实现了 Default/Submitting/Processing/Success/Failed 状态链？
- [ ] Restricted 和 Error 是否使用了不同的文案（不是同一条通用提示）？
- [ ] Unknown Result(202) 是否显示了"正在确认结果"而非让用户重试？

#### 资格与权限
- [ ] 所有真实价值按钮是否消费 allowed_actions / FeatureEntitlement？
- [ ] 是否存在前端自行计算资格的情况（如 `if level > 20`）？
- [ ] 资产类数字是否使用了 string/decimal 而非 float 做业务计算？
- [ ] Power Cap/恢复量/Power Impact 是否全部从服务端读取？

#### 文案合规
- [ ] 是否出现 APR/APY/固定收益/保本/稳赚？
- [ ] 中文端 Prediction 是否统一使用「竞猜」？
- [ ] 是否出现下注/投注/博彩/赔率/盘口/押注？
- [ ] 正式 UI 是否出现 Demo/Mock/Sandbox/Page ID 标签？
- [ ] 所有用户可见字符串是否通过 i18n key 读取（禁止 raw enum）？

#### 视觉与交互
- [ ] 状态是否同时使用了文字 + 颜色（不禁只用颜色）？
- [ ] 卡片是否有最小化 Generic Card Feel？
- [ ] 按钮点击区域是否 ≥44×44px？
- [ ] 375/390/430 三尺寸是否已完成视觉回归？
- [ ] Root 页面 BottomNav + FloatingActionBar + safe-area 是否无冲突？
- [ ] 设计审核图是否一页一图、不加手机边框？

#### H5 响应式
- [ ] 768px 以下是否完全按 Mobile 规则？
- [ ] H5 是否保留了 Bottom Nav（不另外发明桌面导航）？
- [ ] 表单/确认页最大宽度是否 ≤520px？

### 后端审核要点

#### API 契约
- [ ] OpenAPI 实现是否与规范一致？
- [ ] 所有写操作是否包含 idempotency_key？
- [ ] 并发状态变更是否有 object_version/lock 机制？
- [ ] 统一响应是否包含 request_id、rule_version、parameter_release_id、snapshot_id？

#### 账本安全
- [ ] APT/Power 账本是否为 append-only？
- [ ] 修正是否使用 reversal（追加反向记录），无覆盖或删除历史？
- [ ] 写操作幂等是否验证（重复请求不重复资金效果）？

#### 状态机
- [ ] Market/Result/Settlement/Order 状态是否按对象独立管理？
- [ ] OTC expired 和 cancelled 是否有明确语义区分？
- [ ] OtcEligibility 是否实现为非状态机（每次请求动态计算）？

#### 资格与参数
- [ ] 资格/参数/Policy 是否在服务端解析后返回？
- [ ] TBC 生产值是否保持 null/closed，无本地默认值补齐？
- [ ] Parameter Release 是否保存≠生效、Approved≠Active？
- [ ] 历史订单是否通过 snapshot 回算（不用当前参数）？

#### 通知与异步
- [ ] 通知投递是否与业务事务解耦（Outbox Pattern）？
- [ ] 业务成功/通知失败时业务状态是否不回滚？
- [ ] 重试是否有去重 key（不重复生成等价 Notice）？

#### 权限与安全
- [ ] RBAC/ABAC 是否实现了页面权限≠字段权限≠数据范围权限？
- [ ] 高风险操作是否走了 Approval 工作流？
- [ ] 职责分离是否落实（参数编辑≠批准≠激活、申请≠审批、Result≠Settlement）？
- [ ] SoD 是否为 Actor-level Invariant：基于 Actor ID 检查，不可通过切换 active role 绕过？
- [ ] 授权逻辑是否使用了完整公式（canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD），而非纯 `hasRole()`？
- [ ] 紧急操作是否默认双人授权 + case_id + 审计记录？
- [ ] 超级管理员是否仍受账本/审批/审计规则约束？
- [ ] UI Persona 是否仅限导航展示，无隐含 Role Grant？

### 治理文档审核要点（V2.4.1 新增）

#### Contract Gap 审核
- [ ] 所有 P0 页面的 BLOCKING Contract Gap 是否 = 0？
- [ ] P0 + NON_BLOCKING GAP 是否仅在 BASE_CONTRACT=FROZEN 且 Gap 能力 DISABLED + FAIL_CLOSED 时允许？
- [ ] 是否存在 P0 + FROZEN + Contract Gap 三者同时存在的非法组合？
- [ ] CONTRACT_GAP 页面是否标明 Preview-Only，执行按钮不可用？
- [ ] Contract Status 是否从唯一派生源（Page→Gap JOIN）机械复算？

#### SoD 权限审核
- [ ] 权限矩阵是否使用 canonical 13 role IDs（非 3 个 UI Persona）？
- [ ] SoD 是否为 Actor-level Invariant（`candidate.created_by_actor_id != approval.approved_by_actor_id`）？
- [ ] 是否存在 UI Persona 静态授予互斥 Canonical Roles 的情况？
- [ ] 授权公式是否完整（非纯 RBAC）？

#### 状态契约审核
- [ ] Domain State 是否全部来自 05 canonical enum（无自创状态）？
- [ ] `RESULT_UNKNOWN` 是否仅在 Request Resolution 层，未进入任何 Domain Enum？
- [ ] ParameterRelease 状态是否严格为 `draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived`？

#### QA / Evidence 审核
- [ ] 任何 `VERIFIED_PASS(HIFI/Runtime)` 是否绑定了实际 artifact/hash/evidence？
- [ ] 是否存在"文档完成"被误标为"实现验证通过"的情况？
- [ ] QA 统计数字是否均可从 Registry 机械复算？

### Vue 3 + TypeScript 审核要点

#### 组件规范
- [ ] 是否使用 Composition API + `<script setup lang="ts">`？
- [ ] 是否启用 TypeScript `strict: true`，业务逻辑层无 `any` 类型？
- [ ] 页面组件是否按 Page ID 命名（如 `AUser004AssetAdjustment.vue`）？
- [ ] 是否使用 Pinia 按模块拆分 store？

#### API 调用
- [ ] Axios 实例是否自动注入六个请求头（Token/Sign/Timestamp/Version/Language/TraceId）？
- [ ] 资产类数字是否使用 `string` 类型 + `decimal.js`（禁止 `number`/`parseFloat`）？
- [ ] RESULT_UNKNOWN(202) 是否提示"查询结果中"而非让用户重试？

#### I18N
- [ ] 是否使用 vue-i18n 9+，模板中无硬编码文案？
- [ ] 7 语言 key 集是否一致（`MISSING_I18N_KEY = 0`）？
- [ ] 是否禁用了 raw enum 直接显示？

#### 样式与适配
- [ ] 全局设计令牌是否从 CSS 变量读取（颜色、间距、字体）？
- [ ] H5 端 768px 以下是否完全按 Mobile 规则？
- [ ] 是否兼容 375/390/430 三尺寸？
- [ ] 点击目标是否 ≥44×44px？

#### 安全性
- [ ] 按钮可用性是否只读 `allowed_actions`/`FeatureEntitlement`，无前端自判资格？
- [ ] 是否使用了 `decimal.js` 而非 `parseFloat` 做金额计算？

### Flutter 审核要点

#### 代码规范
- [ ] Dart 3+ null safety 是否启用？
- [ ] 状态管理是否使用 Riverpod/Bloc（团队统一方案）？
- [ ] 路由是否使用 GoRouter，基于页面 ID 命名？

#### API 调用
- [ ] Dio 实例是否自动注入六个请求头？
- [ ] 金额是否使用 `String` + `decimal` 包（禁止 `double`）？

#### I18N
- [ ] ARB 文件 key 集是否与 `ui-copy-manifest.json` 一致？
- [ ] 是否禁用了 raw enum 直接显示？

#### 组件化
- [ ] 每个 Page ID 是否独立 Widget？
- [ ] 通用组件是否放在 `lib/widgets/` 下？

### 跨端一致性审核

- [ ] KYC 决定后用户端+后台是否同时更新（无不同步窗口）？
- [ ] Robot 升级后 Level/UpgradeOrder/Ledger/PowerCap 是否全部更新？
- [ ] OTC Completed 后 Order/Trade/APT 冻结/Power 冻结是否同步变化？
- [ ] Prediction 中 Result official 是否不等于 Settlement paid？
- [ ] 风险限制后是否既有按钮禁用也有解释文案和支持入口？

### 测试审核要点

- [ ] 是否覆盖 07 §7 列出的 15 项极端情况测试？
- [ ] 是否有跨端状态一致性验收用例？
- [ ] 是否有幂等性测试（连续双击提交）？
- [ ] 是否有 Outbox 重放不重复资金效果的测试？

### 安全审核要点

- [ ] 敏感资料是否按角色最小化访问？
- [ ] 登录失败是否不泄露账号存在性？
- [ ] 通知正文是否不含内部风控规则或他人数据？
- [ ] 敏感文案（KYC/Consent/OTC 风险）是否标记 PENDING_HUMAN_REVIEW 而非 AI 自签？
- [ ] Consent 确认是否带 content_version？

### 审核禁止项

- 不凭记忆或猜测判断业务规则是否正确——必须对照 01–08 号文档验证。
- 不把 Demo/Mock 数据当作生产数据审核。
- 不把开发中的 TBC 参数值当作正式审核缺陷。
- 不以旧大 Figma、旧 Flutter、旧 Admin 作为审核基线。
- 不以历史文档（`历史文档/`）中的内容反推当前需求。

## 基于代码的推断

- 规则来源于 01–08 号规格文档和 V2.4.1 Admin 治理包（审核 #491 零 Finding）中的约束条款。
- Admin 治理审核 Gate 按 BLOCKING/NON_BLOCKING Gap 区分；Blocking Gap 阻断页面实现，Non-blocking Gap 允许核心功能实现但子能力 FAIL_CLOSED。

## 待确认事项

- [ ] 是否需要在 CI/CD 中集成自动化审核检查（如 OpenAPI 校验、i18n key 完整性）？
- [ ] 代码审查的审批人指派规则和门禁策略
- [ ] 安全渗透测试的范围和周期

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（前端/后端 DoD、验收矩阵、跨端一致性规则）
- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（API 契约、状态机、RBAC/ABAC）
- `Gainode_Development_Ready_V6.1_Latest/04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md`（Admin 页面规范，V2.4.1 治理基线已合入）
- `Gainode_Development_Ready_V6.1_Latest/08_VISUAL_DESIGN_SYSTEM_V2.4.md`（视觉/文案审核清单）
- `Gainode_Development_Ready_V6.1_Latest/03_MOBILE_H5_HIFI_PROTOTYPE_SPEC_V2.4.md`（Mobile 验收规则）
- `Gainode_Development_Ready_V6.1_Latest/README.md`（冲突优先级、生产 Gate）
- `0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/`（V2.4.1 治理包，含 Contract Gap Register、Permission Matrix、Evidence-Based QA）
