# 04 · Gainode Admin 高保真原型逐页规范

> 版本：V2.3 · Ledger Reserve & RBAC & Emergency Closure
> 目标：重新生成桌面管理后台高保真可视化原型
> 重要：不沿用旧大 Figma，也不照搬旧 Admin 菜单；新后台按 8 个一级导航组织。
> 视觉依赖：全局颜色、Logo、字体、间距、表格密度、Drawer、状态、响应式与无障碍统一遵守 `08_VISUAL_DESIGN_SYSTEM_V2.3.md`。

> 文档权威：本文件是该端逐页 HIFI Page Execution Spec。页面级布局、状态、交互与组件以本文件为准；全局视觉与 I18N/L10N 读取 `08_VISUAL_DESIGN_SYSTEM_V2.3.md`；业务字段、状态、权限、API 与参数继续读取 `05/06`。
> 合并说明：已确认的全量视觉/交互策划内容只作为合并来源，不再作为并行开发基线。

> **权限权威说明**：全局角色、字段权限、数据范围和职责分离以 05「RBAC / ABAC 最小角色」及「RBAC / ABAC 补充」为准；页面中的"权限/限制"只定义页面特例，不构成完整权限矩阵。

## 1. Admin 设计原则

- 桌面优先，1440px 基准；1280px 不破版；大屏只增加留白/列宽，不无限拉伸。
- 左侧 8 个一级导航，二级页面按对象组织。
- 所有列表支持筛选、保存视图、cursor/分页、字段权限和 Empty/Error。
- 详情页结构：Header Summary → Tabs → Related objects → Timeline/Audit。
- 高风险动作不直接放“编辑”按钮：先创建 case/proposal，再审批，再执行。
- 状态不能只靠颜色；文字 + icon + tooltip。
- TBC/Closed/No Permission 必须有真实页面状态，不用假数据填满。
- 参数页保存 ≠ 生效；审批通过 ≠ 执行成功。

## 2. 8 个一级导航

```text
01 工作台
02 用户与准入
03 资产与账本
04 机器人与权益
05 OTC 与 Power
06 赛事预测
07 风控 / 审批 / 参数 / 策略
08 客服 / 审计 / 运维
```

## 3. 页面规格

### `A-WORK-001｜运营总览` · P0
- **页面目标**：看平台健康、异常、对账和待办入口。
- **高保真布局**：环境标识；KPI摘要；异常；待办；对账；系统状态；快捷入口。
- **I18N Copy Contract**：`page.a_work_001.title` / `page.a_work_001.description` / `page.a_work_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`运营总览`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：摘要卡 + 趋势/异常 + 待办，浅色内容区。
- **首屏视觉结构**：沿用本页业务布局——环境标识；KPI摘要；异常；待办；对账；系统状态；快捷入口。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：摘要卡 min 220×104px；图表高 240px；区块 gap 24px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不做黑色收益大屏或全屏数字墙。
- **读取数据**：MetricSnapshot、RiskSummary、Reconciliation、WorkItem summary。
- **主要交互**：点异常/待办/对账详情；保存视图。
- **跳转/返回**：→A-WORK-002 / 对象详情。
- **必须画出的状态**：局部加载失败；无异常；数据延迟。
- **权限/限制**：按角色隐藏敏感财务字段。
- **接口参考**：`GET /admin/dashboard（聚合读模型）`
- **页面验收**：任何指标都能点到口径/对象；不能把失败数据显示为0。
- **人话备注**：后台首页用于发现问题，不用于直接执行高风险操作。

### `A-WORK-002｜今日待办` · P0
- **页面目标**：统一处理审核、补件、异常和到期任务。
- **高保真布局**：筛选；优先级；SLA；object type；assignee；table；drawer preview。
- **I18N Copy Contract**：`page.a_work_002.title` / `page.a_work_002.description` / `page.a_work_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`工作队列`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：筛选栏 + 高密度但可读表格 + 右侧轻详情。
- **首屏视觉结构**：沿用本页业务布局——筛选；优先级；SLA；object type；assignee；table；drawer preview。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：筛选栏 56px；表格行 48px；Drawer 480/640px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不使用卡片瀑布替代表格；优先级不能只靠颜色。
- **读取数据**：WorkItem/ApprovalTask/AdminCase。
- **主要交互**：领取；转派；打开对象；补件；建 case。
- **跳转/返回**：→对应对象页。
- **必须画出的状态**：Empty；加载失败；任务被他人领取。
- **权限/限制**：只能转派到允许队列。
- **接口参考**：`GET/POST /admin/work-items`
- **页面验收**：并发领取用版本控制，不能两个人同时处理成已领取。
- **人话备注**：待办是入口，不复制一套业务编辑页。

### `A-USER-001｜用户列表` · P0
- **页面目标**：查用户并进入 User360/KYC/风险。
- **高保真布局**：搜索；KYC/状态/风险/资格筛选；表格；保存筛选。
- **I18N Copy Contract**：`page.a_user_001.title` / `page.a_user_001.description` / `page.a_user_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象列表`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Page Header + Filter + Table + Drawer/Detail，状态字段固定列。
- **首屏视觉结构**：沿用本页业务布局——搜索；KYC/状态/风险/资格筛选；表格；保存筛选。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：表格行 48px；Header 64px；筛选 56px；分页 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不在列表直接放高风险“万能修改”按钮。
- **读取数据**：User summary、KYC、Eligibility safe summary。
- **主要交互**：打开 User360；去 KYC；创建限制 case。
- **跳转/返回**：→A-USER-002 / A-KYC-001。
- **必须画出的状态**：Empty；搜索失败；字段无权限。
- **权限/限制**：按字段脱敏。
- **接口参考**：`GET /admin/users`
- **页面验收**：列表不能直接改余额/资格。
- **人话备注**：用户列表就是找人，不塞所有操作按钮。

### `A-USER-002｜用户 360` · P0
- **页面目标**：一个页面看用户身份、准入、Robot、APT、OTC、Prediction、Power、Risk、Ticket、Audit。
- **高保真布局**：header summary；tabs：Admission/Robot/APT/Power/OTC/Prediction/Risk/Support/Audit。
- **I18N Copy Contract**：`page.a_user_002.title` / `page.a_user_002.description` / `page.a_user_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：对象头 + 状态 + Tabs + 关联对象/时间线；高风险动作集中在 Action 区。
- **首屏视觉结构**：沿用本页业务布局——header summary；tabs：Admission/Robot/APT/Power/OTC/Prediction/Risk/Support/Audit。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：对象头 88–104px；Tabs 44px；详情宽 960–1280px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **读取数据**：User、Identity、Eligibility、关联对象聚合。
- **主要交互**：打开关联对象；创建 case/approval；只读资格。
- **跳转/返回**：深链到各模块详情。
- **必须画出的状态**：Tab级 Loading/NoPermission/Empty。
- **权限/限制**：三字段 global_p/AI eligibility/Prediction eligibility 独立展示；不能直接编辑。
- **接口参考**：`GET /users/{id}/rights；GET /users/{id}/eligibility`
- **页面验收**：每个 Tab 的数字可回到原始对象。
- **人话备注**：User360 是看全貌，不是万能“直接修改用户”页。

### `A-KYC-001｜KYC 审核队列` · P0
- **页面目标**：处理 submitted/needs_info 的 KYC case。
- **高保真布局**：queue；case preview；资料；证据；decision；reason template；history。
- **I18N Copy Contract**：`page.a_kyc_001.title` / `page.a_kyc_001.description` / `page.a_kyc_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`工作队列`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：筛选栏 + 高密度但可读表格 + 右侧轻详情。
- **首屏视觉结构**：沿用本页业务布局——queue；case preview；资料；证据；decision；reason template；history。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：筛选栏 56px；表格行 48px；Drawer 480/640px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不使用卡片瀑布替代表格；优先级不能只靠颜色。
- **读取数据**：KycCase、Policy safe summary、Evidence。
- **主要交互**：approve/reject/needs_info；转派；建 appeal/admin case。
- **跳转/返回**：→User360。
- **必须画出的状态**：资料缺失；证据服务不可用；并发决定冲突。
- **权限/限制**：KYC reviewer 权限；敏感资料严格最小化。
- **接口参考**：`GET/POST /admin/kyc-cases`
- **页面验收**：决定必须有 reason_code 和 decision_version；不可覆盖旧决定。
- **人话备注**：审核人要看得到证据，但不该看无关用户数据。

### `A-LEDGER-001｜资产总览` · P0
- **页面目标**：看 APT 总量、状态拆分、异常和对账。
- **高保真布局**：summary cards；status breakdown；reconciliation；exceptions；drilldown。
- **I18N Copy Contract**：`page.a_ledger_001.title` / `page.a_ledger_001.description` / `page.a_ledger_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`账本总览`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：摘要 + 状态拆分 + 对账 + 异常，财务信息用中性色。
- **首屏视觉结构**：沿用本页业务布局——summary cards；status breakdown；reconciliation；exceptions；drilldown。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：财务/订单页面以中性色和深蓝为主；金色不用于金额普遍高亮。
- **关键尺寸 / 密度**：摘要卡 220×104px；对账卡 280px+；图表 220px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把 APT 数量称收入；不隐藏冻结/待确认。
- **读取数据**：LedgerAccount summary、ReconciliationStatus。
- **主要交互**：打开账户/流水/异常。
- **跳转/返回**：→A-LEDGER-002/003/004。
- **必须画出的状态**：数据延迟；对账异常；权限不足。
- **权限/限制**：财务字段权限。
- **接口参考**：`GET /admin/ledger/summary`
- **页面验收**：冻结/待确认/已销毁分别展示，不能一锅算“余额”。
- **人话备注**：这是资产健康页，不是收入页。

### `A-LEDGER-002｜APT 账户与流水` · P0
- **页面目标**：查用户/平台 APT 数量账和每笔 entry。
- **高保真布局**：account search；ledger table；filter；detail drawer；related objects；reversal chain。
- **I18N Copy Contract**：`page.a_ledger_002.title` / `page.a_ledger_002.description` / `page.a_ledger_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`账本表格`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：搜索/筛选 + append-only 流水表 + 详情 Drawer + reversal chain。
- **首屏视觉结构**：沿用本页业务布局——account search；ledger table；filter；detail drawer；related objects；reversal chain。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：财务/订单页面以中性色和深蓝为主；金色不用于金额普遍高亮。
- **关键尺寸 / 密度**：表格行 48px；数字右对齐；Drawer 640px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：禁止内联编辑余额/流水；冲正不能覆盖原记录。
- **读取数据**：LedgerAccount、JournalBatch、LedgerEntry。
- **主要交互**：查看；标异常；创建更正 proposal。
- **跳转/返回**：→A-LEDGER-004 / related objects。
- **必须画出的状态**：Empty；query fail；entry posting pending。
- **权限/限制**：禁止直接 edit balance。
- **接口参考**：`GET /users/{id}/asset-ledger；GET /admin/ledger`
- **页面验收**：append-only；更正只能走 reversal proposal。
- **人话备注**：后台再有权限，也不能拿文本框直接改余额。

### `A-LEDGER-003｜池子与对账` · P0
- **页面目标**：对账 AI/Prediction/OTC 等账户和批次。
- **高保真布局**：account tree；batch；diff；reconciliation state；evidence；task links。
  - **资金池展示**：页面需体现 OTC 结算储备和运营与风险预算的隔离视图。
- **I18N Copy Contract**：`page.a_ledger_003.title` / `page.a_ledger_003.description` / `page.a_ledger_003.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`账本表格`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：搜索/筛选 + append-only 流水表 + 详情 Drawer + reversal chain。
- **首屏视觉结构**：沿用本页业务布局——account tree；batch；diff；reconciliation state；evidence；task links。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：财务/订单页面以中性色和深蓝为主；金色不用于金额普遍高亮。
- **关键尺寸 / 密度**：表格行 48px；数字右对齐；Drawer 640px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：禁止内联编辑余额/流水；冲正不能覆盖原记录。
- **读取数据**：JournalBatch、Reconciliation、Reserve positions。
  - **补充数据源**：OTC 储备（已批准额度、已承诺/占用、可用量、对账状态、证据、更新时间）；运营预算（已批准额度、已支出、剩余、对账状态）。
- **主要交互**：重新对账；建异常任务；查看 batch。
- **跳转/返回**：→Approval/Audit。
- **必须画出的状态**：diff != 0；async running；failed。
  - OTC 储备可用 / 不足 / 警告；运营预算正常 / 警告 / 超标。
- **权限/限制**：财务/账本角色。
  - 页面不得从页面直接调拨、修改或补造记录。
  - 调拨、预算变更、异常处置必须走 proposal/case、审批、执行和审计。
  - 不得把储备金额表现为平台固定回购能力。
  - 与 APT 数量账、AI Reward Budget、Prediction 资金保持隔离展示。
- **接口参考**：`GET /admin/reconciliations；GET /async-jobs/{id}`
- **页面验收**：差异不为0的批次不能假装 closed。
  - 储备金额不与回购、保价或无条件兑现能力混淆。
- **人话备注**：对账页的目标是解释差异，不是把差异藏掉。

### `A-LEDGER-004｜更正 / 冲正申请` · P0
- **页面目标**：创建受控的 ledger correction，不直接改账。
- **高保真布局**：source entries；reason；impact preview；reversal/new entry plan；evidence；approval route。
- **I18N Copy Contract**：`page.a_ledger_004.title` / `page.a_ledger_004.description` / `page.a_ledger_004.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`高风险申请`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：来源对象 + Impact Preview + Evidence + Approval Route；底部固定提交区。
- **首屏视觉结构**：沿用本页业务布局——source entries；reason；impact preview；reversal/new entry plan；evidence；approval route。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：财务/订单页面以中性色和深蓝为主；金色不用于金额普遍高亮。
- **关键尺寸 / 密度**：内容宽 960px；Diff 行 44px；Sticky Action 64px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：草案不得表现成已执行；不允许申请人直接越过审批。
- **读取数据**：LedgerCorrectionProposal。
- **主要交互**：创建草案；提交审批；取消草案。
- **跳转/返回**：→A-APPROVAL-001。
- **必须画出的状态**：invalid source；already reversed；approval rejected。
- **权限/限制**：申请人与审批人分离。
- **接口参考**：`POST /ledger/journal-batches/{id}/reversal（approval execution）`
- **页面验收**：草案无资金效果；执行后原记录仍存在。
- **人话备注**：这里是“申请修账”，不是“修账按钮”。

### `A-ROBOT-001｜Robot 列表` · P0
- **页面目标**：查用户 Robot、等级、状态、异常和当前规则版本。
- **高保真布局**：filters；table；level/status；eligibility；reward alert；rule version。
- **I18N Copy Contract**：`page.a_robot_001.title` / `page.a_robot_001.description` / `page.a_robot_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象列表`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Page Header + Filter + Table + Drawer/Detail，状态字段固定列。
- **首屏视觉结构**：沿用本页业务布局——filters；table；level/status；eligibility；reward alert；rule version。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：深蓝 Sidebar 固定；Robot 页面可用品牌蓝，金色只标 Level / Reward。
- **关键尺寸 / 密度**：表格行 48px；Header 64px；筛选 56px；分页 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不在列表直接放高风险“万能修改”按钮。
- **读取数据**：Robot[]、Eligibility、RewardSummary。
- **主要交互**：打开详情；创建限制/复核 case。
- **跳转/返回**：→A-ROBOT-002。
- **必须画出的状态**：Empty；service fail。
- **权限/限制**：Ops read；限制动作需 case/approval。
- **接口参考**：`GET /admin/robots（read model）`
- **页面验收**：不能从列表直接改 level。
- **人话备注**：Robot 管理首先是查对象。

### `A-ROBOT-002｜Robot 详情` · P0
- **页面目标**：看一个 Robot 的状态时间线、升级、Reward、Power、Ledger 与版本。
- **高保真布局**：object header；timeline；Upgrade tab；Reward tab；Power tab；Ledger tab；Audit。
- **I18N Copy Contract**：`page.a_robot_002.title` / `page.a_robot_002.description` / `page.a_robot_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：对象头 + 状态 + Tabs + 关联对象/时间线；高风险动作集中在 Action 区。
- **首屏视觉结构**：沿用本页业务布局——object header；timeline；Upgrade tab；Reward tab；Power tab；Ledger tab；Audit。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：深蓝 Sidebar 固定；Robot 页面可用品牌蓝，金色只标 Level / Reward。
- **关键尺寸 / 密度**：对象头 88–104px；Tabs 44px；详情宽 960–1280px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **读取数据**：Robot、UpgradeOrder、Reward、Power、Ledger。
- **主要交互**：创建 pause/review proposal；跳参数；看用户360。
- **跳转/返回**：→A-CONFIG-001 / User360。
- **必须画出的状态**：data partial；rule version unavailable。
- **权限/限制**：高风险 action 通过 allowed_actions。
- **接口参考**：`GET AI admin read models；POST /admin/cases`
- **页面验收**：状态历史不能编辑。
- **人话备注**：Robot 详情是事实页，参数改动去参数中心。

### `A-ROBOT-003｜Reward / Claim 运营` · P0
- **页面目标**：查 Reward candidate/held/pending/claimed/expired/reversed 和 Claim 异常。
- **高保真布局**：batch filters；user/reward rows；budget snapshot；claim status；ledger refs；case action。
- **I18N Copy Contract**：`page.a_robot_003.title` / `page.a_robot_003.description` / `page.a_robot_003.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：对象头 + 状态 + Tabs + 关联对象/时间线；高风险动作集中在 Action 区。
- **首屏视觉结构**：沿用本页业务布局——batch filters；user/reward rows；budget snapshot；claim status；ledger refs；case action。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：深蓝 Sidebar 固定；Robot 页面可用品牌蓝，金色只标 Level / Reward。
- **关键尺寸 / 密度**：对象头 88–104px；Tabs 44px；详情宽 960–1280px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **读取数据**：AIReward、Claim、RewardBatch、Journal refs。
- **主要交互**：查看；创建 review/clawback case；打开 ledger。
- **跳转/返回**：→Approval/Ledger。
- **必须画出的状态**：claim unknown；posting mismatch；budget closed。
- **权限/限制**：不能直接把 candidate 改 claimed。
- **接口参考**：`GET /ai/users/{id}/rewards；POST /reward-clawbacks（approved）`
- **页面验收**：Reward status 和 ledger posting 必须一致。
- **人话备注**：运营可以查和发起案件，不能手工“补一个已领取”。

### `A-OTC-001｜OTC 订单列表` · P0
- **页面目标**：查所有 OTC 订单、状态、风险和撮合进度。
- **高保真布局**：filters；side/status/risk；order table；partial fill；capacity summary。
- **I18N Copy Contract**：`page.a_otc_001.title` / `page.a_otc_001.description` / `page.a_otc_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象列表`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Page Header + Filter + Table + Drawer/Detail，状态字段固定列。
- **首屏视觉结构**：沿用本页业务布局——filters；side/status/risk；order table；partial fill；capacity summary。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：财务/订单页面以中性色和深蓝为主；金色不用于金额普遍高亮。
- **关键尺寸 / 密度**：表格行 48px；Header 64px；筛选 56px；分页 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不在列表直接放高风险“万能修改”按钮。
- **读取数据**：OTCOrder[]、RiskSummary。
- **主要交互**：打开详情；保存视图。
- **跳转/返回**：→A-OTC-002。
- **必须画出的状态**：Empty；service unavailable。
- **权限/限制**：按角色脱敏对手方。
- **接口参考**：`GET /admin/otc/orders（read model）`
- **页面验收**：SUBMITTED/MATCHING/PARTIAL 绝不能显示 Completed。
- **人话备注**：列表重点是订单状态，而不是制造交易量大屏。

### `A-OTC-002｜OTC 订单详情 / 审核` · P0
- **页面目标**：处理 review/dispute 并看冻结、Power、Trade、Ledger 全链路。
- **高保真布局**：order；user eligibility；risk evidence；asset freeze；power；trades；timeline；decision panel。
- **I18N Copy Contract**：`page.a_otc_002.title` / `page.a_otc_002.description` / `page.a_otc_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：对象头 + 状态 + Tabs + 关联对象/时间线；高风险动作集中在 Action 区。
- **首屏视觉结构**：沿用本页业务布局——order；user eligibility；risk evidence；asset freeze；power；trades；timeline；decision panel。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：财务/订单页面以中性色和深蓝为主；金色不用于金额普遍高亮。
- **关键尺寸 / 密度**：对象头 88–104px；Tabs 44px；详情宽 960–1280px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **读取数据**：OTCOrder、Trade、Freeze、Power、RiskCase。
- **主要交互**：approve/reject/needs_info；取消/强制处置只能建 proposal；添加内部 note。
- **跳转/返回**：→Approval/User360/Ledger。
- **必须画出的状态**：evidence unavailable；state conflict；partial fill。
- **权限/限制**：风险决定与高危处置分权。
- **接口参考**：`GET /otc/orders/{id}；POST /admin/cases；POST /approval-tasks/{id}/decisions`
- **页面验收**：决定必须写 reason；资产影响预览。
- **人话备注**：审核的是“订单能否继续”，不是随便改用户余额。

### `A-POWER-001｜Power 账户与流水` · P0
- **页面目标**：查 Power 的 available/frozen/consumed/released 和 OTC 关联。
- **高保真布局**：user search；position；ledger；related OTC order；rule version。
- **I18N Copy Contract**：`page.a_power_001.title` / `page.a_power_001.description` / `page.a_power_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象列表`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Page Header + Filter + Table + Drawer/Detail，状态字段固定列。
- **首屏视觉结构**：沿用本页业务布局——user search；position；ledger；related OTC order；rule version。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：财务/订单页面以中性色和深蓝为主；金色不用于金额普遍高亮。
- **关键尺寸 / 密度**：表格行 48px；Header 64px；筛选 56px；分页 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不在列表直接放高风险“万能修改”按钮。
- **读取数据**：PowerPosition、PowerLedger。
- **主要交互**：查看；标异常；去 OTC。
- **跳转/返回**：→OTC detail / User360。
- **必须画出的状态**：data unavailable；mismatch。
- **权限/限制**：只读为主。
- **接口参考**：`GET /ai/users/{id}/computing-power；GET power ledger`
- **页面验收**：Power 不可直接手改。
- **人话备注**：把 Power 当资源账来管。

### `A-PREDICT-001｜Market / Event 列表` · P0
- **页面目标**：管理 P0 Football Pre-match 1X2 市场生命周期。
- **高保真布局**：market filters；event/source；state axes；lock time；liquidity；risk；publish status。
- **I18N Copy Contract**：`page.a_predict_001.title` / `page.a_predict_001.description` / `page.a_predict_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象列表`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Page Header + Filter + Table + Drawer/Detail，状态字段固定列。
- **首屏视觉结构**：沿用本页业务布局——market filters；event/source；state axes；lock time；liquidity；risk；publish status。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：深蓝 Sidebar + 白色主区；Prediction 用品牌蓝/Cyan，禁止博彩盘口视觉。
- **关键尺寸 / 密度**：表格行 48px；Header 64px；筛选 56px；分页 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不在列表直接放高风险“万能修改”按钮。
- **读取数据**：MarketTemplate、Market、Selection、LockEvaluation。
- **主要交互**：建 draft；提交 review；打开详情；pause proposal。
- **跳转/返回**：→A-PREDICT-002。
- **必须画出的状态**：policy/param missing→closed；source unavailable。
- **权限/限制**：Market publish 需要相应权限/审批。
- **接口参考**：`GET /markets；POST /markets；POST /markets/{id}/publish`
- **页面验收**：P0 template 只允许 Football pre-match 1X2。
- **人话备注**：后台可以有通用模板架构，但不要让运营随便上线新玩法。

### `A-PREDICT-002｜Market 详情` · P0
- **页面目标**：看三方向、订单结构、流动性、关联账户、锁定评估和快照。
- **高保真布局**：event header；Home/Draw/Away pools；liquidity；cluster summary；orders；snapshots；allowed actions。
- **I18N Copy Contract**：`page.a_predict_002.title` / `page.a_predict_002.description` / `page.a_predict_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：对象头 + 状态 + Tabs + 关联对象/时间线；高风险动作集中在 Action 区。
- **首屏视觉结构**：沿用本页业务布局——event header；Home/Draw/Away pools；liquidity；cluster summary；orders；snapshots；allowed actions。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：深蓝 Sidebar + 白色主区；Prediction 用品牌蓝/Cyan，禁止博彩盘口视觉。
- **关键尺寸 / 密度**：对象头 88–104px；Tabs 44px；详情宽 960–1280px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **读取数据**：Market、Selection、LockEvaluation、Risk summary。
- **主要交互**：运行 lock evaluation；pause；打开 result/settlement。
- **跳转/返回**：→A-PREDICT-003/004。
- **必须画出的状态**：low liquidity；cluster review；source issue；locked。
- **权限/限制**：反作弊阈值/完整图谱不对普通运营公开。
- **接口参考**：`POST /markets/{id}/lock-evaluations`
- **页面验收**：锁定失败要有明确 reason 和后续 refund 路径。
- **人话备注**：三方向结构一定可见，但内部风控算法不要暴露。

### `A-PREDICT-003｜Result / Settlement` · P0
- **页面目标**：接收/复核 Result，计算 Settlement，确认 posting 和 reconciliation。
- **高保真布局**：result source；primary/secondary evidence；result status；settlement batch；T/W/L/F/R；journal；reconcile。
- **I18N Copy Contract**：`page.a_predict_003.title` / `page.a_predict_003.description` / `page.a_predict_003.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：对象头 + 状态 + Tabs + 关联对象/时间线；高风险动作集中在 Action 区。
- **首屏视觉结构**：沿用本页业务布局——result source；primary/secondary evidence；result status；settlement batch；T/W/L/F/R；journal；reconcile。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：深蓝 Sidebar + 白色主区；Prediction 用品牌蓝/Cyan，禁止博彩盘口视觉。
- **关键尺寸 / 密度**：对象头 88–104px；Tabs 44px；详情宽 960–1280px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **读取数据**：Result、SettlementBatch、JournalBatch。
- **主要交互**：receive/review result；calculate sandbox；submit settlement approval。
- **跳转/返回**：→Approval/Ledger。
- **必须画出的状态**：source conflict→HELD；calculation fail；reconcile diff。
- **权限/限制**：Result confirmer 和 settlement approver 分离。
- **接口参考**：`POST /markets/{id}/results；POST /settlement-batches`
- **页面验收**：未 reconciliation=0 不得关闭 batch。
- **人话备注**：“赛果确认”和“钱已结算”是两件事。

### `A-PREDICT-004｜Refund / Correction` · P0
- **页面目标**：处理低流动性、取消、无人赢家、赛果更正等特殊路径。
- **高保真布局**：reason；affected orders；principal/fee impact；old/new result；reversal plan；approval；timeline。
- **I18N Copy Contract**：`page.a_predict_004.title` / `page.a_predict_004.description` / `page.a_predict_004.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：对象头 + 状态 + Tabs + 关联对象/时间线；高风险动作集中在 Action 区。
- **首屏视觉结构**：沿用本页业务布局——reason；affected orders；principal/fee impact；old/new result；reversal plan；approval；timeline。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：深蓝 Sidebar + 白色主区；Prediction 用品牌蓝/Cyan，禁止博彩盘口视觉。
- **关键尺寸 / 密度**：对象头 88–104px；Tabs 44px；详情宽 960–1280px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **读取数据**：RefundCase、CorrectionCase。
- **主要交互**：建 refund/correction；提交审批；查看 execution。
- **跳转/返回**：→Approval/Ledger/Audit。
- **必须画出的状态**：partial failure；appeal open；dependency fail。
- **权限/限制**：高危；必须证据和审批。
- **接口参考**：`POST /refunds；POST /corrections；POST /results/{id}/corrections`
- **页面验收**：更正不覆盖 old snapshot；refund 保留原订单。
- **人话备注**：这页是救火页，要把影响范围和回滚写得特别清楚。

### `A-RISK-001｜Risk Case` · P0
- **页面目标**：集中处理用户/订单/市场风险案件。
- **高保真布局**：queue；safe summary；evidence categories；related objects；analyst decision；approver step；timeline。
- **I18N Copy Contract**：`page.a_risk_001.title` / `page.a_risk_001.description` / `page.a_risk_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`工作队列`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：筛选栏 + 高密度但可读表格 + 右侧轻详情。
- **首屏视觉结构**：沿用本页业务布局——queue；safe summary；evidence categories；related objects；analyst decision；approver step；timeline。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：高风险页面仍以白/深蓝为主，Warning/Danger 只标真实风险和不可逆动作。
- **关键尺寸 / 密度**：筛选栏 56px；表格行 48px；Drawer 480/640px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不使用卡片瀑布替代表格；优先级不能只靠颜色。
- **读取数据**：RiskCase、ParticipantCluster safe view、LocationEvidence。
- **主要交互**：review；hold request；recommend approve/reject；escalate。
- **跳转/返回**：→User/OTC/Prediction/Approval。
- **必须画出的状态**：evidence unavailable；policy conflict。
- **权限/限制**：analyst/approver separation；不暴露模型权重。
- **接口参考**：`POST /admin/cases；GET risk read models`
- **页面验收**：用户可见 reason 与内部 reason 分离。
- **人话备注**：内部知道得更多，不等于可以把模型细节展示给用户。

### `A-APPROVAL-001｜审批中心` · P0
- **页面目标**：统一处理高风险参数、账本、风险、结算、更正和发布审批。
- **高保真布局**：inbox；type；risk；requester；impact diff；evidence；discussion；decision；execution state。
- **I18N Copy Contract**：`page.a_approval_001.title` / `page.a_approval_001.description` / `page.a_approval_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`审批详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Inbox + Impact Diff + Evidence + Discussion + Decision；决策状态最醒目。
- **首屏视觉结构**：沿用本页业务布局——inbox；type；risk；requester；impact diff；evidence；discussion；decision；execution state。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：高风险页面仍以白/深蓝为主，Warning/Danger 只标真实风险和不可逆动作。
- **关键尺寸 / 密度**：列表行 48px；详情 960–1200px；Decision bar 72px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：Approved 不等于 Executed；不隐藏执行失败。
- **读取数据**：ApprovalTask、ExecutionTask。
- **主要交互**：approve/reject/request changes；MFA。
- **跳转/返回**：执行后跳关联对象。
- **必须画出的状态**：state changed；self-approval blocked；execution failed。
- **权限/限制**：SoD；申请人不可审批自己的申请。
- **接口参考**：`POST /approval-tasks/{id}/decisions`
- **页面验收**：approved ≠ executed；执行失败必须显示 failed。
- **人话备注**：审批通过只是允许执行，不代表后续任务一定成功。

### `A-CONFIG-001｜Parameter Center · Definition/Candidate` · P0
- **页面目标**：查看参数定义、创建候选值和仿真，不直接生效。
- **高保真布局**：namespace tree；Definition；current release；candidate；scope；validation；simulation。
- **I18N Copy Contract**：`page.a_config_001.title` / `page.a_config_001.description` / `page.a_config_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`参数编辑器`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Namespace/Definition 左侧，Candidate/Validation/Simulation 主区，版本信息右侧。
- **首屏视觉结构**：沿用本页业务布局——namespace tree；Definition；current release；candidate；scope；validation；simulation。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：Sidebar 280px；Editor min 640px；Diff 行 44px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不做“保存即上线”；TBC/null 必须显式。
- **读取数据**：ParameterDefinition、Candidate。
- **主要交互**：新建 candidate；编辑草案；simulate；submit release。
- **跳转/返回**：→A-CONFIG-002。
- **必须画出的状态**：TBC/null；validation fail；scope conflict。
- **权限/限制**：Editor 可编辑 candidate；不能 activate。
- **接口参考**：`GET /parameter-definitions；POST /parameter-candidates`
- **页面验收**：保存不生效；TBC 生产值必须 null。
- **人话备注**：这是“起草参数”，不是“改线上值”。

### `A-CONFIG-002｜Parameter Release / Snapshot` · P0
- **页面目标**：审批、排期、激活、暂停、回滚不可变参数发布。
- **高保真布局**：release diff；scope；approvals；effective time；snapshots；gray scope；pause/rollback。
- **I18N Copy Contract**：`page.a_config_002.title` / `page.a_config_002.description` / `page.a_config_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`参数发布`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Release Diff + Scope + Approval + Effective Time + Snapshot；不可变版本视觉明确。
- **首屏视觉结构**：沿用本页业务布局——release diff；scope；approvals；effective time；snapshots；gray scope；pause/rollback。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：详情 1040px；Timeline 行 48px；Action bar 64px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不能直接编辑 Active release；Rollback 不覆盖历史。
- **读取数据**：ParameterRelease、ParameterSnapshot。
- **主要交互**：submit approval；activate（授权角色）；pause；rollback。
- **跳转/返回**：→Approval/Audit。
- **必须画出的状态**：dependency invalid；scope conflict；rollback running。
- **权限/限制**：Editor/Approver/Release Operator 分离。
- **接口参考**：`POST /parameter-releases；POST /parameter-releases/{id}/activate；GET snapshots`
- **页面验收**：Release immutable；新值用新 release，不改旧 release。
- **人话备注**：线上到底用了哪个值，必须能查到具体 Release 和 Snapshot。

### `A-POLICY-001｜地区 / KYC / 保护策略` · P0
- **页面目标**：查看地区、渠道、年龄、KYC、限额、冷静期、自我排除的策略决策。
- **高保真布局**：policy matrix；evidence；decision；version；user preview；protection rules。
- **I18N Copy Contract**：`page.a_policy_001.title` / `page.a_policy_001.description` / `page.a_policy_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`策略矩阵`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：地区/渠道/能力矩阵 + 证据 + 版本 + Preview；只在必要处用状态色。
- **首屏视觉结构**：沿用本页业务布局——policy matrix；evidence；decision；version；user preview；protection rules。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：矩阵行 44–48px；Sticky first column；Drawer 640px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不做“全球开放”单一开关；无证据不能显示 ALLOW。
- **读取数据**：PolicyDecision、KYC/Age policy、CoolingOff、SelfExclusion。
- **主要交互**：创建策略候选/案件；查看评估；不能手选更宽松结果。
- **跳转/返回**：→Approval/User360。
- **必须画出的状态**：evidence stale；timeout；conflict→fail closed。
- **权限/限制**：Policy roles；默认 deny。
- **接口参考**：`POST /policy/evaluations；GET /policy/evaluations/{id}`
- **页面验收**：无证据不能 ALLOW；用户保护跨渠道。
- **人话备注**：后台不是“点一下就让某国开放”的开关。

### `A-SUPPORT-001｜工单队列` · P0
- **页面目标**：按 SLA、类别、风险和负责人处理用户工单。
- **高保真布局**：filters；queue；priority；SLA；user/object；assignee。
- **I18N Copy Contract**：`page.a_support_001.title` / `page.a_support_001.description` / `page.a_support_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`工作队列`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：筛选栏 + 高密度但可读表格 + 右侧轻详情。
- **首屏视觉结构**：沿用本页业务布局——filters；queue；priority；SLA；user/object；assignee。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：筛选栏 56px；表格行 48px；Drawer 480/640px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不使用卡片瀑布替代表格；优先级不能只靠颜色。
- **读取数据**：Ticket[]、WorkItem。
- **主要交互**：领取；转派；打开；批量仅低风险动作。
- **跳转/返回**：→A-SUPPORT-002。
- **必须画出的状态**：Empty；queue fail；assignment conflict。
- **权限/限制**：客服只见需要的信息。
- **接口参考**：`GET /admin/tickets`
- **页面验收**：相同对象/问题可关联已有 case。
- **人话备注**：客服队列要帮助处理，而不是把审计和风控全部塞进去。

### `A-SUPPORT-002｜工单详情` · P0
- **页面目标**：处理回复、补件、升级风控、结论和关联对象。
- **高保真布局**：conversation；user-visible/internal note；attachments；related objects；timeline；case links。
- **I18N Copy Contract**：`page.a_support_002.title` / `page.a_support_002.description` / `page.a_support_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象详情`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：对象头 + 状态 + Tabs + 关联对象/时间线；高风险动作集中在 Action 区。
- **首屏视觉结构**：沿用本页业务布局——conversation；user-visible/internal note；attachments；related objects；timeline；case links。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：对象头 88–104px；Tabs 44px；详情宽 960–1280px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **读取数据**：Ticket、Message、Evidence。
- **主要交互**：回复；补件；升级风险；关闭。
- **跳转/返回**：→User/Order/Ledger/Risk。
- **必须画出的状态**：attachment fail；permission；state conflict。
- **权限/限制**：客服不能改业务终态，只能调用授权 workflow/case。
- **接口参考**：`GET/POST /admin/tickets/{id}/*`
- **页面验收**：关闭前需要结论；内部 note 与用户回复严格区分。
- **人话备注**：客服能解释和推进，不能绕过业务系统直接“帮用户改好”。

### `A-AUDIT-001｜审计日志` · P0
- **页面目标**：按对象、人员、动作、时间追踪关键变化。
- **高保真布局**：filters；event table；before/after；request/case/approval；export task。
- **I18N Copy Contract**：`page.a_audit_001.title` / `page.a_audit_001.description` / `page.a_audit_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象列表`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Page Header + Filter + Table + Drawer/Detail，状态字段固定列。
- **首屏视觉结构**：沿用本页业务布局——filters；event table；before/after；request/case/approval；export task。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：表格行 48px；Header 64px；筛选 56px；分页 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不在列表直接放高风险“万能修改”按钮。
- **读取数据**：AuditLog。
- **主要交互**：查询；打开事件；发起受控导出。
- **跳转/返回**：→关联对象。
- **必须画出的状态**：No permission；export pending。
- **权限/限制**：Auditor scope；敏感字段脱敏。
- **接口参考**：`GET /audit-log；POST /export-tasks`
- **页面验收**：审计日志不可编辑/删除。
- **人话备注**：任何“谁改了什么”都应该在这里回答。

### `A-OPS-001｜异步任务 / 对账 / 系统状态` · P0
- **页面目标**：看 async job、outbox/webhook、reconciliation 和关键依赖状态。
- **高保真布局**：environment；jobs；DLQ；reconcile；dependency health；retry/case links。
- **I18N Copy Contract**：`page.a_ops_001.title` / `page.a_ops_001.description` / `page.a_ops_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`运维控制台`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：健康摘要 + Jobs/DLQ/Reconcile 表格；错误和重试分清。
- **首屏视觉结构**：沿用本页业务布局——environment；jobs；DLQ；reconcile；dependency health；retry/case links。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：摘要卡 220×96px；日志行 40px；Drawer 640px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不能把重试设计成“重做用户订单”；资金任务需额外确认。
- **读取数据**：AsyncJob、Outbox status、Reconciliation。
- **主要交互**：重试允许的任务；建 case；查看证据。
- **跳转/返回**：→Audit/Approval。
- **必须画出的状态**：failed/DLQ/dependency unavailable。
- **权限/限制**：资金效果任务默认不能随便 replay。
- **接口参考**：`GET /async-jobs/{id}；admin ops read models`
- **页面验收**：重试必须防重复业务效果。
- **人话备注**：运维重试的是任务，不是重做一次用户订单。

### `A-REPORT-001｜运营报表` · P1
- **页面目标**：查看留存、业务量、异常、退款等分析。
- **高保真布局**：report filters；charts；definition link；export。
- **I18N Copy Contract**：`page.a_report_001.title` / `page.a_report_001.description` / `page.a_report_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`报表页`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：筛选 + 图表 + 指标说明 + 导出；报表和账本视觉区分。
- **首屏视觉结构**：沿用本页业务布局——report filters；charts；definition link；export。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：图表高 280px；筛选 56px；数据卡 220×104px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不把报表值当权威账本或收入。
- **读取数据**：MetricDefinition/Snapshot。
- **主要交互**：筛选；导出。
- **跳转/返回**：独立页面。
- **必须画出的状态**：data delayed。
- **权限/限制**：按数据权限。
- **接口参考**：`/admin/reports`
- **页面验收**：报表不是账本/收入权威。
- **人话备注**：P1，不阻塞 P0。

### `A-GROWTH-001｜Referral / Team 运营` · P1
- **页面目标**：查看关系、候选/HELD/PAID 与反作弊。
- **高保真布局**：relationship；reward state；campaign；risk。
- **I18N Copy Contract**：`page.a_growth_001.title` / `page.a_growth_001.description` / `page.a_growth_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象列表`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Page Header + Filter + Table + Drawer/Detail，状态字段固定列。
- **首屏视觉结构**：沿用本页业务布局——relationship；reward state；campaign；risk。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：表格行 48px；Header 64px；筛选 56px；分页 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不在列表直接放高风险“万能修改”按钮。
- **读取数据**：Referral/Reward/KOL。
- **主要交互**：查看；建 case/approval。
- **跳转/返回**：→Risk/User360。
- **必须画出的状态**：budget closed。
- **权限/限制**：不能直接补发。
- **接口参考**：`Reward APIs`
- **页面验收**：不鼓励把增长做成拉人头看板。
- **人话备注**：P1。

### `A-MIGRATION-001｜APT Migration` · Future/CLOSED
- **页面目标**：未来管理 APT-I→APT-C 请求、finality 和防双花。
- **高保真布局**：requests；wallet；broadcast/finality；journal；exceptions。
- **I18N Copy Contract**：`page.a_migration_001.title` / `page.a_migration_001.description` / `page.a_migration_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`对象列表`。
- **画布 / 容器**：基准 `1440×900`；Sidebar 240px / 收起 72px；Header 64px；内容边距 24px。
- **视觉主任务**：Page Header + Filter + Table + Drawer/Detail，状态字段固定列。
- **首屏视觉结构**：沿用本页业务布局——requests；wallet；broadcast/finality；journal；exceptions。；Page Header 只放标题、状态和真正需要的主操作。
- **品牌应用**：统一深蓝 Sidebar + 白色内容区 + 品牌蓝主操作；页面自身不另造主题色。
- **关键尺寸 / 密度**：表格行 48px；Header 64px；筛选 56px；分页 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **宽屏规则**：1280/1440/1920 同一 IA；内容最大宽 1600px；复杂详情优先独立页，轻详情用 480/640px Drawer。
- **无障碍**：表格、筛选、Tab 和 Action 支持键盘 Focus；状态必须有文字；图标按钮有 tooltip/label。
- **视觉禁止**：不在列表直接放高风险“万能修改”按钮。
- **读取数据**：MigrationRequest。
- **主要交互**：查看；case/approval。
- **跳转/返回**：→Ledger/Approval。
- **必须画出的状态**：CLOSED by default。
- **权限/限制**：高风险。
- **接口参考**：`POST /migration-requests；GET finality`
- **页面验收**：P0 不显示可执行 create 按钮。
- **人话备注**：Future 功能，先保留对象契约。

### `A-EMERGENCY-001｜紧急操作控制` · P0

> 紧急操作只允许预授权角色执行。具体角色和阈值参数进入 06。

- **页面目标**：在紧急情况下安全执行受限操作，确保可追溯和可恢复。
- **高保真布局**：紧急操作列表 → 操作类型 → 影响范围 → 理由 → 双人确认 → 审计摘要 → 事后复核状态。
- **I18N Copy Contract**：`page.a_emergency_001.title` / `page.a_emergency_001.description` / `page.a_emergency_001.primary_action`。
- **视觉模板**：`安全/操作控制台`。
- **必须画出的状态**：可执行 / 需双人授权 / 执行中 / 已执行 / 事后补审待处理 / 补审超时升级。
- **权限/限制**：
  - 紧急操作只能由预授权角色执行。
  - 影响资产、账本、资格、参数或结算的紧急操作默认仍需双人授权。
  - 必须有 `case_id`、理由、影响范围、执行人、时间、审计记录和恢复方案。
  - "先执行后补审"的场景必须被明确列举，不能作为万能绕审批通道。
  - 事后补审失败或超时必须升级异常任务。
  - 超级管理员仍不能绕过审计和职责分离。
- **读取数据**：EmergencyAction[]、CaseID、ApprovalStatus、AuditLog。
- **主要交互**：查看可执行操作；发起紧急操作；双人确认；查看审计；事后复核。
- **页面验收**：每笔操作有完整审计链；事后补审有明确期限；超时自动升级。


## 4. Admin 原型主流程必须可点击

### Flow A · KYC
`待办 → KYC Queue → User360 → 决定/补件 → Audit`

### Flow B · Robot 异常
`Robot List → Robot Detail → Create Risk/Admin Case → Approval → Execution → Robot Timeline`

### Flow C · OTC Review
`OTC List → Order Detail → Risk Review → Decision / Approval → Asset & Power → Audit`

### Flow D · Prediction Settlement
`Market → Market Detail → Result → Settlement Batch → Approval → Journal/Reconciliation`

### Flow E · Refund/Correction
`Market/Order → Refund or Correction Proposal → Approval → Reversal/New Posting → User Notice`

### Flow F · Parameter Release
`Definition → Candidate → Simulation → Release → Approval → Activate → Snapshot → Pause/Rollback`

## 5. Admin 原型验收

- [ ] 8 个一级导航固定，不回退旧 12 菜单视觉。
- [ ] 所有 P0 页面可点击进入核心详情/流程。
- [ ] 所有高风险动作经过 case/proposal + approval。
- [ ] 没有任何“直接改余额/等级/资格/地区 ALLOW/Active 参数”的万能按钮。
- [ ] 业务状态、账本状态、审批状态、异步执行状态分开显示。
- [ ] No Permission/TBC/Closed/Dependency Unavailable 都有页面状态。
- [ ] 大屏不把表格拉成超长空洞页面；核心内容区有最大宽度和信息密度控制。
