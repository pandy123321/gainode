# 07 · Gainode 开发计划与功能验收

> 版本：V2.2 · Cross-End Consistency & Notification Acceptance Closure
> 目标：让产品、原型、前端、后端、测试对“什么时候可以开始”和“什么叫做完”一致

## 1. 现在能不能开发

**能。**

允许立即开始：
- 新 Mobile/H5 高保真可视化原型；
- 新 Admin 高保真可视化原型；
- 前端路由、组件、状态、Mock/Contract 联调；
- 后端对象、API、状态机、幂等、权限、账本沙盒实现；
- Parameter Center 结构；
- 自动测试和集成测试环境。

现在不需要等待：
- 某个国家正式年龄值；
- 某个正式手续费；
- 某个 Robot 正式系数；
- 正式生产流动性参数。

这些值没有时，生产状态 `closed/null`，开发和 Sandbox 使用独立 mock/simulation。

## 2. 推荐开发顺序

### Stage 1 · Prototype Freeze

1. Mobile/H5 全 P0 页面高保真。
2. Admin 全 P0 页面高保真。
3. 5 条主流程可点击。
4. Loading/Empty/Error/Restricted/Processing 补齐。

### Stage 2 · Contract Freeze

1. OpenAPI 3.1。
2. Auth/Session。
3. Objects/Enums。
4. allowed_actions。
5. Parameter schema。
6. RBAC/ABAC。

### Stage 3 · Backend Core

优先：

```text
Auth/KYC
User/Eligibility
Robot/Reward
APT Ledger
Prediction
OTC/Power
Approval/Parameter
Support/Audit
```

### Stage 4 · Frontend Integration

按流程联调，不按页面随机联调。

### Stage 5 · Sandbox E2E

真实验证幂等、状态恢复、账本守恒、退款、更正、审批和回滚。

## 3. P0 功能验收矩阵

| 模块 | 必须通过 |
|---|---|
| Auth | 注册、OTP、登录、MFA、找回、session refresh/revoke；频控与失败状态完整 |
| KYC | not_started→pending→needs_info/approved/rejected；功能可用性与 KYC 分开 |
| Home | 任一卡片失败不拖死整页；下一步明确 |
| Robot | 1–56 展示；状态真实；启动/停止幂等；升级报价/结果可追踪 |
| Reward | `pending=capacity×coefficient`；系数0合法；Claim 不重复 posting；过期/审核/冲正可追溯 |
| APT | available/frozen/pending 分开；每笔变化可追踪；历史不可覆盖 |
| Power | Available/Frozen/Consumed/Released/Recovering 可解释；Power Cap 来自 Robot Level Rule；OTC Sell freeze/consume/release 正确；Withdrawal/Robot Start 如使用 Power 必须先有服务端 Preview |
| OTC | submitted≠completed；partial 正确；取消只释放未成交；幂等不重复资产效果 |
| Prediction | P0 只 Football 1X2；三方向；Consent；不可撤销/换向；锁定/结果/结算分开 |
| Refund | 作废后合法本金可恢复；关联 ledger 完整 |
| Correction | old snapshot 保留；reversal + new result/posting |
| Support | 关联对象、附件、补件、状态、结论完整 |
| Admin | 无直接改余额/资格/Active参数；高危操作走审批 |
| Parameter | Candidate/Release/Snapshot；TBC=null；Active immutable |
| Audit | 高风险写操作可按 request/object/user/approval 追踪 |

## 4. 前端 DoD

每个页面：

- [ ] Route / page_id 与文档一致。
- [ ] Default/Loading/Empty/Error/Restricted 完整。
- [ ] 写操作有 Submitting/Processing/Success/Failed。
- [ ] 所有按钮消费 allowed_actions / entitlement。
- [ ] 资产类数字不使用 JS float 做业务计算。
- [ ] 页面刷新后从服务端恢复真实状态。
- [ ] 错误不会清空用户不该丢的输入。
- [ ] 返回列表恢复筛选和滚动位置。
- [ ] 状态不仅靠颜色。
- [ ] 不含固定收益/保本/保价/保证成交文案。

## 5. 后端 DoD

- [ ] OpenAPI 与实现一致。
- [ ] 所有写操作有 idempotency。
- [ ] 并发状态变更有 object version / lock。
- [ ] 资格、参数、Policy 在服务端解析。
- [ ] 业务对象状态与 ledger posting 状态分离。
- [ ] 账本 append-only，修正用 reversal。
- [ ] Snapshot 能解释历史订单。
- [ ] Async/Outbox 重试不重复资金效果。
- [ ] 高风险 API 有 RBAC/ABAC + MFA/SoD（适用时）。
- [ ] TBC 生产值不使用本地默认值补齐。

## 6. 原型验收

### Mobile/H5
- [ ] 本包 03 文档中全部 P0 页面已生成。
- [ ] Flow A–E 全部可点击。
- [ ] H5 不出现超宽弹窗/PC 式表格压缩。
- [ ] 不使用旧大 Figma 作为页面模板。
- [ ] 每个页面单独输出设计画板/截图；不加手机设备边框；不把四个 Root 拼成一张交付图。
- [ ] 375×812、390×844、430×932 三个尺寸逐页回归，无 CTA 掉位、异常换行或 Bottom Sheet/Floating Bar 冲突。
- [ ] Home 成长榜位于页面内容最底部，且不是财富/资产/收益排行。
- [ ] Robot Root 不出现 Upgrade Progress Bar；必须有 Power Battery、运行状态/过程、Reward 和 Floating Action Bar。
- [ ] Prediction Root 正常运营态能明显看到多场赛事，不能用单场大 Hero 造成“只有一场”的感觉。
- [ ] 正式 UI 不出现 Demo/Mock/Sandbox/Page ID/Scenario 文案；这些只存在开发工具。
- [ ] 视觉完成度符合 Western / Premium / Sports-Tech / Operational，并消除明显 Generic Card Feel。

### Admin
- [ ] 8 个一级导航完整。
- [ ] User360、Ledger、Robot、OTC、Prediction、Risk、Approval、Parameter、Support、Audit 可闭环。
- [ ] 1440/1280 分辨率协调；大屏不无限拉宽。
- [ ] 高危动作都有影响预览、理由、审批和结果。

## 7. 测试必须覆盖的极端情况

1. 用户连续双击升级/Claim/Prediction/OTC 提交。
2. 客户端超时但服务端已经成功。
3. Parameter Release 在用户打开确认页后变化。
4. Market 在确认页停留期间进入 Locked。
5. 用户余额在 quote 后发生变化。
6. KYC/地区资格在提交瞬间变为 restricted。
7. OTC 部分成交后取消剩余。
8. Result 主备源冲突。
9. Settlement posting 成功但通知失败。
10. Refund 中途某个 batch 失败。
11. Correction 重复执行。
12. Audit/Outbox 重放时不能重复资金效果。
13. Policy 服务超时。
14. D10 无 Active Release。
15. 用户已受限，但需要查看历史/退款/工单。

### 7.1 跨端状态与对象一致性验收

#### 规则 1：KYC 决定
- 用户端更新：KYC、FeatureEntitlement、通知。
- 后台更新：KycCase、User360、Audit。
- 不允许：用户端显示"通过"而后台仍显示"待审"；通知只发一端。

#### 规则 2：Robot 升级
- 用户端更新：Robot（level、status、capabilities、power_cap）、UpgradeOrder、APT Ledger、Activity。
- 后台更新：Robot、User360、Ledger、Audit。
- 不允许：只改 Level 而没有 UpgradeOrder、账本记录和规则版本；升级成功但 Power Cap 未同步。

#### 规则 3：OTC 订单
- 用户端更新：订单、Trade、APT 冻结/解冻、Power 冻结/消耗/释放、通知。
- 后台更新：Order、Risk、Trade、Ledger、Power Ledger、Ticket、Audit。
- 不允许：订单 Completed 但资产或 Power 未同步；Expired 显示成 Cancelled。

#### 规则 4：Prediction
- 用户端分别更新：Market、Result、Settlement、Order、Refund/Correction、Ledger、Notice。
- 后台更新：赛事、订单、审批、账本、审计。
- 不允许：Result official 直接显示成 Settlement paid；只改结算状态不改账本。

#### 规则 5：风险限制
- 用户端更新：受影响功能、原因分类、下一步和支持入口。
- 后台更新：RiskCase、User360、Approval、Audit。
- 不允许：只禁用按钮而没有解释文案和支持入口。

#### 规则 6：ParameterRelease
- 用户端：必要时更新规则版本、提示和重新确认。
- 后台更新：Release、Snapshot、Approval、Monitoring、Audit。
- 不允许：前端静默换规则而不通知用户；用当前规则重算历史订单。

### 7.2 状态定义完整性

每个关键状态必须定义：
- 进入条件：什么事件或条件触发进入该状态。
- 退出条件：什么事件或条件可以离开该状态。
- 责任角色：谁可以触发状态转换。
- 允许动作：该状态下允许哪些操作。
- 通知方式：状态变化后如何通知相关方。
- 超时/异常处理：超时或异常时的默认行为。

不能只列状态名称，必须覆盖以上六项。

### 7.3 通知验收

补充规则：
- 业务状态不得因通知失败回滚。领域业务提交与通知投递不是同一事务结果。
- 通知失败进入可重试状态（NotificationDelivery.retry）。
- 重试不能重复业务效果；不能重复生成等价 Notice（去重 key）。
- 用户读取通知失败不应影响业务对象查询。
- 深链对象失效时仍应显示安全正文（Notice 可读，但关联对象数据不可泄露）。
- 邮件/推送失败不影响站内 Notice（IN_APP）的可读性。

## 8. 生产上线不是本轮开发阻塞项

生产真实价值开放前还需要：

```text
正式地区/渠道 ALLOW
正式 Parameter Active Release
安全/RBAC/MFA 证据
账本/对账证据
结果源/结算证据
退款/申诉路径
监控/回滚演练
```

这些未完成时：

```text
Development = GO
Sandbox = GO
Production Real-Value = NO-GO
```

**人话备注：** 不要因为生产参数还没定就卡住写代码；也不要因为代码写完了就把生产按钮偷偷打开。

## 9. 本轮文档完成标准

从现在开始，需求修改必须先改本 Markdown 基线，再改原型/代码。

不要再新增一份“差不多一样”的说明书。优先修改对应这 7 份文件中的一份。
