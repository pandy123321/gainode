# Gainode 全量页面视觉与交互策划 V1.1 · 多语言版

> 基于 `Gainode Development Baseline V2.1`、Gainode Logo，以及已确认的四个一级主菜单视觉方向。
> 交付范围：Mobile / H5 44 页 + Admin 31 页，共 75 个页面；本版只做文档策划，不生成页面图。

## 0. 结论

四个一级主菜单的视觉方向可以作为全站视觉锚点：**浅色、克制、体育数据感、品牌蓝为主、Logo 负责识别、UI 负责清晰。** 但 AI 概念图只作为视觉参考，不能覆盖开发基线中的业务规则。

需要立刻纠正四张概念图里可能误导后续设计的内容：
- `我的`：删除“今日收益 / 累计收益 / 未经批准的 USD 估值 / Premium”等未被当前基线确认的信息。APT 首先是数量账。
- `预测`：删除默认高亮的“AI 推荐某方向”表达；Home / Draw / Away 三方向必须同级展示，不能暗示必赢。
- `Robot`：Reward 数字只可作为显式 Demo/Sandbox Fixture；正式界面必须同时展示状态、周期、快照、资格与动态系数。
- `Home`：保留任务分流结构，但不能演变为收益大盘。

## 1. 全站视觉 DNA

- **背景**：Mobile/H5 使用 `#F8FAFC`；Admin 也是浅灰主内容区。
- **品牌色**：Navy `#071226/#05285D`，Blue `#024EC2/#057CF1`，Cyan `#06A9FE`；Gold `#F4D016` 仅用于 Level/Reward/升级关键变化，<=5%。
- **卡片**：白底、16px Mobile 圆角、12–14px Admin 圆角；弱边框 + 轻阴影，避免玻璃拟态泛滥。
- **排版**：数字清楚但克制；所有业务数值必须带单位、状态、更新时间/快照/规则版本。
- **图标**：统一线性/半填充几何图标；足球队徽仅作为赛事识别，不把体育元素铺成背景。
- **Logo**：Mobile 内部默认只用 Symbol；登录/品牌页可用完整 Logo；Admin 深蓝侧栏用 Mono Light。
- **动效**：180–280ms 的页面/Sheet/Drawer 转场；成功动效 <600ms；不做闪烁、金币雨、老虎机、数字翻滚。

### 1.1 四个一级导航的角色

| 一级菜单 | 视觉角色 | 首屏任务 | 主色策略 | 禁止 |
|---|---|---|---|---|
| 首页 Home | 状态 + 下一步分流 | 看懂准入、Robot、精选赛事、APT/通知 | 允许一个深蓝 Hero，其余白卡 | 收益大屏 / K线 |
| Robot | 能力 + 等级 + 运行状态 | 看懂 Level、Capability、Status、下一步 | 蓝 + 极少金 | ROI / APR / Today Profit |
| Prediction | 体育数据 + 参与决策 | 看懂赛事、三方向、状态、锁定、风险 | 白 + 蓝/Cyan | 博彩盘口 / 默认推荐必赢 |
| 我的 Me | 账户 + 资源 + 安全 | 进入 APT/Power/OTC/KYC/Support | 白 + 深蓝/蓝 | 交易所钱包 / 收益榜 |

## 2. 全局交互规则

1. **Root / Detail**：四个一级菜单 Root 保留 Bottom Nav；高风险全屏流程、Auth/KYC 表单可隐藏 Bottom Nav 以减少误触。返回必须恢复原筛选、滚动和 Tab。
2. **高风险动作**：Input → Validation → Review Summary → Risk/Impact → Explicit Confirm → Submitting → Processing/Review → Success/Failed/Unknown → Record。
3. **Unknown Result**：不允许“再试一次创建”；必须显示原 `request_id / object_id` 并用原 Idempotency-Key 查询。
4. **权限**：按钮只读服务端 `allowed_actions / next_action`，不在前端根据等级、余额、KYC 自行推断。
5. **局部失败**：卡片、Tab、列表尾部可以独立 Error；不要用全屏错误覆盖仍然可用的部分。
6. **Empty / Restricted**：Empty 说明“还没有什么 + 如何开始”；Restricted 说明“不能做什么 + 为什么 + 下一步”。
7. **表单**：Label 常驻；错误绑定字段；上传失败单项重试；提交中按钮保持原宽。
8. **H5**：<=767px 完全复用 Mobile；768–1023px 只增宽容器，不改一级 IA。

## 3. 网络参考基准

- **R1**：Gainode Logo + 四个一级主菜单概念图（内部视觉锚点）
- **R2**：Apple Human Interface Guidelines（Tab Bar、Search、Sheet、Alert、可访问性与系统型交互）
- **R3**：Apple Sports（快速扫读、赛事卡、低视觉噪声）
- **R4**：FotMob（赛事列表、比赛详情、数据层级）
- **R5**：Sofascore（多维体育数据、历史状态与统计可视化）
- **R6**：Stripe Dashboard（对象列表/详情、筛选、报表、退款/争议/状态闭环）
- **R7**：Stripe Identity（KYC/Verification Session、审核工作流、敏感资料访问边界）
- **R8**：Ant Design（Table、Drawer、Modal、Steps、Timeline、Result、Empty、Skeleton、Tabs 等组件模式）

参考原则：只借鉴信息层级、组件行为和数据呈现，不照抄品牌、配色、文案或业务模型。

## 4. Mobile / H5 页面总表

| Page ID | 页面 | 优先级 | 模块 |
|---|---|---|---|
| M-AUTH-001 | 登录 | P0 | A. Auth / 身份入口 |
| M-AUTH-002 | 注册 | P0 | A. Auth / 身份入口 |
| M-AUTH-003 | OTP 验证 | P0 | A. Auth / 身份入口 |
| M-AUTH-004 | 找回 / 重置密码 | P0 | A. Auth / 身份入口 |
| M-AUTH-005 | MFA 二次验证 | P0 | A. Auth / 身份入口 |
| M-KYC-001 | KYC 与功能准入概览 | P0 | B. KYC / 准入 |
| M-KYC-002 | KYC 资料提交 / 补件 | P0 | B. KYC / 准入 |
| M-KYC-003 | KYC 状态 / 结果 | P0 | B. KYC / 准入 |
| M-HOME-001 | 首页 | P0 | C. Home / 通知 |
| M-NOTICE-001 | 消息中心 | P0 | C. Home / 通知 |
| M-ROBOT-001 | Robot 概览 | P0 | D. Robot |
| M-ROBOT-002 | Robot 启动 / 停止确认 | P0 | D. Robot |
| M-ROBOT-003 | Robot 升级 | P0 | D. Robot |
| M-ROBOT-004 | 升级结果 | P0 | D. Robot |
| M-ROBOT-005 | 56 级等级地图 | P0 | D. Robot |
| M-ROBOT-006 | Rewards & Claim | P0 | D. Robot |
| M-ROBOT-007 | Robot 活动与记录 | P0 | D. Robot |
| M-PREDICT-001 | Prediction 赛事列表 | P0 | E. Prediction |
| M-PREDICT-002 | 赛事详情 · Football 1X2 | P0 | E. Prediction |
| M-PREDICT-003 | Prediction 确认 | P0 | E. Prediction |
| M-PREDICT-004 | 我的 Prediction | P0 | E. Prediction |
| M-PREDICT-005 | Prediction 订单详情 | P0 | E. Prediction |
| M-PREDICT-006 | 异常 / 退款 / 更正详情 | P0 | E. Prediction |
| M-ME-001 | 我的 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-ASSET-001 | APT 资产 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-ASSET-002 | APT 流水列表 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-ASSET-003 | APT 流水详情 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-POWER-001 | Power | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-OTC-001 | OTC 市场 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-OTC-002 | OTC 下单输入 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-OTC-003 | OTC 订单确认 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-OTC-004 | OTC 提交结果 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-OTC-005 | 我的 OTC 订单 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-OTC-006 | OTC 订单详情 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-SEC-001 | 安全中心 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-SEC-002 | MFA / 设备 / Session 管理 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-SUPPORT-001 | 帮助中心 / 工单列表 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-SUPPORT-002 | 创建工单 / 申诉 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-SUPPORT-003 | 工单详情 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-SETTINGS-001 | 设置 | P0 | F. Me / 资产 / OTC / 安全 / Support |
| M-AI-001 | AI 数据 / Signal 详情 | P1 | G. P1 / Future |
| M-GROWTH-001 | Referral / Team | P1 | G. P1 / Future |
| M-PREDICT-FREE-001 | 免费 YES/NO | P1/Sandbox | E. Prediction |
| M-MIGRATION-001 | APT-I → APT-C Migration | Future/CLOSED | G. P1 / Future |

## 5. Mobile / H5 逐页视觉与交互策划

### A. Auth / 身份入口

#### M-AUTH-001｜登录 · P0

- **页面目标**：已有账号安全登录并获得 session。
- **视觉结构（基线）**：品牌区；账号；密码；登录按钮；注册/忘记密码；条款与帮助。
- **视觉策划补充**：顶部只保留完整 Logo、标题和一句安全说明；表单置于白色卡片/无边框容器中，账号与密码层级清晰，主按钮使用品牌蓝。底部“注册 / 找回密码 / 帮助”用文本按钮，不出现营销 Banner。
- **关键交互（基线）**：输入账号/密码；登录；去注册；忘记密码。
- **交互策划补充**：登录后完全听从服务端 next_step；MFA/KYC/首页跳转不由前端猜。输入错误就地显示，账号保留、密码清空；提交期间锁定主按钮并保留原尺寸。
- **推荐组件**：BrandHeader、LabeledInput、PasswordInput、PrimaryButton、InlineError、SecurityNotice。
- **状态覆盖**：Loading；凭据错误；频控；账户安全锁定；依赖不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：未登录可访问；不泄露账号是否存在等敏感判断。
- **路由 / 上下文**：成功→M-AUTH-005 或 M-KYC-001 / M-HOME-001；忘记密码→M-AUTH-004。
- **关键禁止**：不做营销 Banner；不使用金色主按钮。
- **验收重点**：登录中禁止重复点；失败保留账号但清密码；成功必须由服务端给 next_step。
- **参考模式**：R1 / R2 / R8

#### M-AUTH-002｜注册 · P0

- **页面目标**：创建账号并完成基础协议确认。
- **视觉结构（基线）**：账号类型；手机号/邮箱；密码；确认密码；条款勾选；注册按钮。
- **视觉策划补充**：延续登录页视觉；账号类型用 SegmentedControl，条款放在提交按钮上方并用可打开全文的链接。密码规则以实时 checklist 展示，但不泄露内部风控阈值。
- **关键交互（基线）**：填写；主动同意条款；提交注册。
- **交互策划补充**：条款必须主动勾选；注册成功后直接进入 OTP，不在成功页停留。若账号已存在只给安全的人话提示，不透露过多账户信息。
- **推荐组件**：SegmentedControl、LabeledInput、PasswordRuleList、CheckboxConsent、PrimaryButton。
- **状态覆盖**：字段错误；账号已存在；发送受限；服务不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：游客可访问；条款不能默认勾选。
- **路由 / 上下文**：成功→M-AUTH-003。
- **关键禁止**：不做营销 Banner；不使用金色主按钮。
- **验收重点**：注册成功必须返回 verification_challenge_id；重复请求幂等。
- **参考模式**：R1 / R2 / R8

#### M-AUTH-003｜OTP 验证 · P0

- **页面目标**：验证注册/登录/找回操作的一次性验证码。
- **视觉结构（基线）**：验证码输入；倒计时；重发；当前账号脱敏展示。
- **视觉策划补充**：以 6 位验证码格为视觉中心，标题区域弱化；脱敏账号、倒计时和重发入口放在验证码下方。错误提示紧贴验证码，不用全屏 Error。
- **关键交互（基线）**：输入验证码；验证；重发。
- **交互策划补充**：支持自动聚焦、粘贴整段验证码、删除回退；倒计时以服务端时间为准。过期后显式提示“重新获取”，不自动发送。
- **推荐组件**：OtpInput、MaskedAccount、Countdown、TextButton、InlineError。
- **状态覆盖**：验证码错误/过期；尝试过多；重发频控。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只能操作当前 challenge。
- **路由 / 上下文**：成功按 challenge purpose 去下一步。
- **关键禁止**：不显示内部安全规则、风控阈值或“账号是否存在”。
- **验收重点**：倒计时以服务端为准；过期不静默自动重发。
- **参考模式**：R1 / R2 / R8

#### M-AUTH-004｜找回 / 重置密码 · P0

- **页面目标**：安全恢复账号凭据。
- **视觉结构（基线）**：账号；OTP/验证步骤；新密码；确认；完成页。
- **视觉策划补充**：使用 3 步流程：确认账号 → 验证身份 → 设置新密码；顶部 Stepper 简洁，不把所有字段塞在一屏。
- **关键交互（基线）**：发起找回；验证 OTP；设置新密码。
- **交互策划补充**：每一步保留上一阶段上下文；新密码提交前再次校验规则。成功页只告诉用户密码已更新及旧会话处理结果，并回登录。
- **推荐组件**：Stepper、LabeledInput、OtpInput、PasswordRuleList、ResultState。
- **状态覆盖**：账号恢复受限；challenge 过期；密码不合规。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：不能通过页面暴露账号是否注册；高风险可转人工安全流程。
- **路由 / 上下文**：完成→M-AUTH-001。
- **关键禁止**：不做营销 Banner；不使用金色主按钮。
- **验收重点**：成功后旧 session 按策略失效；必须记录安全事件。
- **参考模式**：R1 / R2 / R8

#### M-AUTH-005｜MFA 二次验证 · P0

- **页面目标**：在高风险登录或敏感动作前完成二次验证。
- **视觉结构（基线）**：验证方式；验证码；倒计时/恢复方式；安全提示。
- **视觉策划补充**：安全验证采用验证页，不重复完整业务信息；但必须展示“正在确认的操作”摘要，例如 Robot 升级 / OTC 提交，避免用户不知道为何验证。
- **关键交互（基线）**：验证；切换允许的方法；安全帮助。
- **交互策划补充**：MFA 成功后回到原动作并复用原 request/idempotency context；切换验证方式使用 Bottom Sheet。
- **推荐组件**：OperationContextCard、OtpInput、MethodPickerSheet、Countdown、SecurityNotice。
- **状态覆盖**：错误；过期；次数过多；恢复模式。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：challenge 绑定原 request/context。
- **路由 / 上下文**：成功回原操作，不另造重复业务请求。
- **关键禁止**：不显示内部安全规则、风控阈值或“账号是否存在”。
- **验收重点**：MFA 成功后必须继续原流程并保留原 idempotency context。
- **参考模式**：R1 / R2 / R8

### B. KYC / 准入

#### M-KYC-001｜KYC 与功能准入概览 · P0

- **页面目标**：告诉用户验证进度，以及哪些功能可用/不可用。
- **视觉结构（基线）**：KYC 进度；功能能力清单；限制原因；开始/继续/补件/申诉按钮。
- **视觉策划补充**：顶部状态卡显示 KYC 当前阶段，下面用“功能准入清单”逐项显示 Robot / Prediction / OTC 等 allowed、原因和下一步。不要只用一个巨大通过/拒绝图标。
- **关键交互（基线）**：开始 KYC；继续；补件；看原因；申诉。
- **交互策划补充**：点击每个受限能力直接执行 next_action；补件、申诉、继续认证均返回对应对象状态，不创建重复 KYC Case。
- **推荐组件**：KycStatusHero、CapabilityList、StatusBadge、ReasonBlock、PrimaryCTA。
- **状态覆盖**：not_started/pending/needs_info/approved/rejected/review；依赖异常。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：历史可看；新功能写操作由 allowed 决定。
- **路由 / 上下文**：填写→M-KYC-002；结果→M-KYC-003；申诉→M-SUPPORT-002。
- **关键禁止**：不只显示一个“通过/未通过”大图标。
- **验收重点**：功能列表每项要有 allowed、reason、next_action。
- **参考模式**：R1 / R2 / R7 / R8

#### M-KYC-002｜KYC 资料提交 / 补件 · P0

- **页面目标**：提交当前策略要求的身份资料。
- **视觉结构（基线）**：分步表单；资料字段；文件上传；Consent；保存草稿；提交。
- **视觉策划补充**：分步表单 + 上传卡；每一步只处理同一类信息。上传结果用缩略信息卡展示文件名、状态、重试，不展示敏感原图大预览。
- **关键交互（基线）**：填写；上传；保存；提交。
- **交互策划补充**：支持保存草稿；单个文件上传失败可单项重试；策略版本变化时弹出说明并要求重新确认，不清空已填数据。
- **推荐组件**：Stepper、FormSection、UploadCard、ConsentBlock、StickyCTA。
- **状态覆盖**：字段/文件错误；上传失败；重复提交；策略变更。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：仅本人；敏感字段不写入日志/埋点。
- **路由 / 上下文**：提交成功→M-KYC-003。
- **关键禁止**：不把所有步骤塞进一屏；上传失败不能清空整页。
- **验收重点**：字段错误保留已填内容；上传失败可单项重试；策略版本变化时重新确认。
- **参考模式**：R1 / R2 / R7 / R8

#### M-KYC-003｜KYC 状态 / 结果 · P0

- **页面目标**：显示审核中、补件、通过、拒绝和下一步。
- **视觉结构（基线）**：状态卡；时间线；缺失项；开放能力；申诉/支持。
- **视觉策划补充**：结果卡 + 时间线 + 影响功能清单。审核中使用蓝色 Processing，不使用绿色成功；needs_info 用黄橙提示具体缺失项。
- **关键交互（基线）**：补件；查看功能；申诉；回首页。
- **交互策划补充**：每个状态必须有下一步：等待、补件、申诉、回首页或查看已开放能力。通过后主动刷新 FeatureEntitlement。
- **推荐组件**：ResultHero、Timeline、CapabilityList、ActionPanel。
- **状态覆盖**：审核中；needs_info；rejected；approved；service unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：拒绝只展示安全原因，不泄露内部风险模型。
- **路由 / 上下文**：补件→M-KYC-002。
- **关键禁止**：不把“处理中”画成成功；不使用庆祝动画掩盖未完成状态。
- **验收重点**：每个状态必须有“下一步”；通过后重新拉取 capabilities。
- **参考模式**：R1 / R2 / R7 / R8

### C. Home / 通知

#### M-HOME-001｜首页 · P0

- **页面目标**：一次浏览知道账户状态，并进入最重要下一步。
- **视觉结构（基线）**：顶部品牌/通知；准入条；Robot 摘要；Featured Prediction；APT 摘要；可选 AI data card。
- **视觉策划补充**：以已确认的 Home 主菜单概念图为视觉锚点：轻背景、一个深蓝 Hero、Robot/精选赛事/APT/通知分层白卡。首屏只让用户回答“我现在能做什么”；不做收益大屏。
- **关键交互（基线）**：进入 KYC/Robot/Prediction/APT/通知。
- **交互策划补充**：卡片整块可点但主操作区域必须明确；通知入口保留未读状态。首页只做分流，不在卡片内完成高风险写操作。
- **推荐组件**：AppBar、AdmissionHero、RobotSummaryCard、FeaturedMatchCard、AptSummaryCard、NoticeCard、BottomNav。
- **状态覆盖**：卡片级 Loading/Error/Empty；全局 Restricted banner。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：不同模块独立权限；单卡失败不能拖死整页。
- **路由 / 上下文**：底部导航固定 Home/Robot/Prediction/Me。
- **关键禁止**：不做收益大盘、K 线背景、金币动画；首页不堆超过 6 个主区块。
- **验收重点**：所有卡片可独立重试；不在首页显示固定收益/回本。
- **参考模式**：R1 / R2 / R3

#### M-NOTICE-001｜消息中心 · P0

- **页面目标**：查看状态、风控、订单、结算和工单通知。
- **视觉结构（基线）**：未读筛选；通知列表；详情；关联对象按钮。
- **视觉策划补充**：按“未读 / 全部”或时间分组展示通知，列表项含图标、标题、摘要、时间和对象类型；高优先级只用小型状态标识，不做红色大 Banner。
- **关键交互（基线）**：标已读；全部已读；打开关联对象。
- **交互策划补充**：点击通知后标记已读并打开关联对象；返回时保持滚动位置与筛选。失效对象显示“状态已更新”，仍可查看通知正文。
- **推荐组件**：SearchField、SegmentedTabs、NoticeRow、UnreadDot、EmptyState。
- **状态覆盖**：Empty；分页失败；通知目标已失效。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只展示本人通知；敏感原因做安全映射。
- **路由 / 上下文**：根据 object_type 深链跳转。
- **关键禁止**：不为每一行加厚重阴影；不做超长横向表格。
- **验收重点**：点击通知后返回仍保留列表位置/筛选。
- **参考模式**：R1 / R2 / R3

### D. Robot

#### M-ROBOT-001｜Robot 概览 · P0

- **页面目标**：看懂当前等级、状态、能力、Reward 摘要和下一步。
- **视觉结构（基线）**：Robot 卡；运行状态；等级/能力；Reward 摘要；主 CTA；等级地图/记录入口。
- **视觉策划补充**：以 Robot 主菜单概念图为锚点，但把“能力/运行状态”放在 Reward 前面。机器人插画只作为一个 Hero 装饰，不在全站重复；金色仅用于等级和 Reward 资格。
- **关键交互（基线）**：启动/停止；升级；看等级；看 Reward；看记录。
- **交互策划补充**：Start/Stop、升级、Claim 都先读取 allowed_actions；点击后进入独立确认流程。概览页不通过本地 Switch 直接改状态。
- **推荐组件**：RobotHero、LevelBadge、CapabilityMetrics、RewardSummary、LevelMapTeaser、ActivityPreview、BottomNav。
- **状态覆盖**：inactive/active/cooling/review/restricted/paused；数据失败。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：所有按钮以 allowed_actions 为准。
- **路由 / 上下文**：Start/Stop→M-ROBOT-002；Upgrade→003；Level→005；Reward→006；History→007。
- **关键禁止**：不做固定收益仪表盘；不把 Pending APT 做成首屏最大数字。
- **验收重点**：状态变化后自动刷新；历史记录始终可进。
- **参考模式**：R1 / R2 / R8

#### M-ROBOT-002｜Robot 启动 / 停止确认 · P0

- **页面目标**：让用户明确这次状态切换的影响。
- **视觉结构（基线）**：当前状态；目标状态；影响摘要；资格；风险；确认按钮。
- **视觉策划补充**：使用全屏高风险确认页或大 Bottom Sheet：顶部显示当前状态与目标状态，中间说明影响、预计生效方式、冷却/Review 可能性，底部单一确认 CTA。
- **关键交互（基线）**：确认启动/停止；取消。
- **交互策划补充**：提交后进入 Processing/Review 状态，禁止重复操作；Success/Failed/Unknown 均回到同一 action_id 查询结果。
- **推荐组件**：ConfirmSummary、ImpactList、RiskNotice、StickyCTA、ProcessingState。
- **状态覆盖**：quote 过期；操作不允许；MFA required；unknown result。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：服务端决定是否可启动/停止。
- **路由 / 上下文**：成功回 M-ROBOT-001；review 显示处理中结果。
- **关键禁止**：不省略影响说明；不把确认做成一个小弹窗里塞满长文本。
- **验收重点**：提交后禁重复；未知结果用原 idempotency_key 查询。
- **参考模式**：R1 / R2 / R8

#### M-ROBOT-003｜Robot 升级 · P0

- **页面目标**：选择目标等级并看成本、能力变化、冷却和资格。
- **视觉结构（基线）**：当前/目标等级；能力 diff；APT cost；Power limit diff；cooldown；资格；主动确认。
- **视觉策划补充**：升级页用“当前等级 → 目标等级”对比卡，突出新增能力、standard_capacity 变化和规则版本；费用/所需资源是信息，不做购买刺激。
- **关键交互（基线）**：选择允许目标；刷新报价；确认升级。
- **交互策划补充**：先选择目标/可升级项，再获取 quote；quote 有过期倒计时。进入确认前再次刷新 eligibility。
- **推荐组件**：LevelCompareCard、CapabilityDiff、QuoteCard、EligibilityNotice、StickyCTA。
- **状态覆盖**：APT不足；冷却；资格不足；quote过期；受限。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：前端不算 apt_cost。
- **路由 / 上下文**：提交→M-ROBOT-004。
- **关键禁止**：不把升级做成充值促销页；金色只点缀等级/提升。
- **验收重点**：确认页必须显示 quote_expires_at；过期重新拉报价。
- **参考模式**：R1 / R2 / R8

#### M-ROBOT-004｜升级结果 · P0

- **页面目标**：给出升级的最终/处理中结果和记录编号。
- **视觉结构（基线）**：状态图标；新等级/处理中；APT 影响；cooldown；order_id；记录入口。
- **视觉策划补充**：结果页不只给 Success 图标；顶部结果状态，下面显示等级变化、账本影响、规则版本、action/order ID 和时间线。Review/Cooling 各有独立视觉。
- **关键交互（基线）**：返回 Robot；看流水；看订单记录。
- **交互策划补充**：可进入 Robot、流水、活动记录；Unknown/Review 状态提供“查询进度”而不是再次提交。
- **推荐组件**：ResultHero、BeforeAfterDiff、Timeline、ReferenceBlock、ActionGroup。
- **状态覆盖**：completed/review/failed/no_effect/unknown。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：历史可查看。
- **路由 / 上下文**：→M-ROBOT-001 / M-ASSET-003 / M-ROBOT-007。
- **关键禁止**：不使用金币雨/烟花；“已提交”不得画成“已完成”。
- **验收重点**：失败必须明确本次是否产生 APT 效果。
- **参考模式**：R1 / R2 / R8

#### M-ROBOT-005｜56 级等级地图 · P0

- **页面目标**：浏览 1–56 级能力与当前/下一等级。
- **视觉结构（基线）**：6 个 UI 分组；等级节点；能力详情；当前/已解锁/未解锁；升级入口。
- **视觉策划补充**：56 级采用分段路径：每 8 或 10 级为一组，当前级居中；Locked / Current / Available / Restricted 用形状+文案区分。避免 56 张大卡平铺。
- **关键交互（基线）**：切分组；看等级；可升级时去升级。
- **交互策划补充**：点击等级打开 Bottom Sheet 查看该级能力和条件；只有 allowed 的目标级出现“去升级”。支持快速跳到当前等级。
- **推荐组件**：LevelSegmentTabs、LevelPath、LevelNode、LevelDetailSheet、Legend。
- **状态覆盖**：配置不可用；目标级别不可升级。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只读；参数/规则来源服务端。
- **路由 / 上下文**：→M-ROBOT-003。
- **关键禁止**：不一屏平铺 56 张卡；不写死成本/能力参数。
- **验收重点**：不能用本地表写死正式能力/成本；显示 rule_version。
- **参考模式**：R1 / R2 / R8

#### M-ROBOT-006｜Rewards & Claim · P0

- **页面目标**：查看 Reward 的候选、待领取、已领取、审核、过期和冲正。
- **视觉结构（基线）**：Reward summary；状态 tabs；pending APT；capacity×coefficient 解释；Claim CTA；记录详情。
- **视觉策划补充**：Reward 页以资格和状态为主：Candidate/Held/Pending Claim/Claimed 分组，数量配单位、周期、快照和系数说明。系数为 0 时用解释卡，不显示“0 收益失败”。
- **关键交互（基线）**：筛选；看详情；Claim；查看流水。
- **交互策划补充**：Claim 前二次确认可领取数量与相关快照；提交后处理状态与 ledger_entry_id 可追踪。Claim disabled 时展示 reason/next_action。
- **推荐组件**：RewardStatusTabs、RewardCard、FormulaExplanation、ClaimCTA、ResultSheet。
- **状态覆盖**：coefficient=0；claim disabled；review；expired；unknown result。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只有 claim_allowed=true 才能提交。
- **路由 / 上下文**：Claim 结果留在本页/结果 sheet；流水→M-ASSET-003。
- **关键禁止**：不使用“今日收益”“提现”“稳赚”等视觉或文案；金色面积 <=5%。
- **验收重点**：0 系数显示“今日系数为 0”，不是 Error；Claim 幂等。
- **参考模式**：R1 / R2 / R8

#### M-ROBOT-007｜Robot 活动与记录 · P0

- **页面目标**：追溯状态、升级、Reward、限制和版本变化。
- **视觉结构（基线）**：筛选；时间线/列表；关联对象；规则/参数版本；支持入口。
- **视觉策划补充**：按时间线/记录列表展示启动、停止、升级、Reward、异常和规则版本事件；不同事件用统一图标体系，不用彩色气泡堆叠。
- **关键交互（基线）**：筛选；打开记录；跳关联流水/工单。
- **交互策划补充**：支持类型与日期筛选；点击事件打开详情或关联对象；分页失败只影响列表尾部。
- **推荐组件**：FilterChips、TimelineList、EventRow、ReferenceMeta、EmptyState。
- **状态覆盖**：Empty；分页失败；关联对象不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：历史只读。
- **路由 / 上下文**：深链到 Asset/Support。
- **关键禁止**：不把历史记录画成实时行情；不覆盖旧版本信息。
- **验收重点**：历史状态不被当前规则覆盖。
- **参考模式**：R1 / R2 / R8

### E. Prediction

#### M-PREDICT-001｜Prediction 赛事列表 · P0

- **页面目标**：发现 P0 可参与的 Football Pre-match 1X2 市场。
- **视觉结构（基线）**：日期/联赛筛选；Featured；赛事卡；锁定时间；可参与状态；我的预测入口。
- **视觉策划补充**：以 Prediction 主菜单概念图为视觉锚点，但去掉默认“AI 推荐某方向”的表达。参考 Apple Sports/FotMob：日期条、联赛分组、赛事卡、锁定时间、市场状态和就绪度快速扫读。
- **关键交互（基线）**：筛选；打开市场；进入我的预测。
- **交互策划补充**：筛选日期/联赛后保持状态；赛事卡点击进入详情。受限赛事仍可看公开信息，但 CTA 显示限制原因。
- **推荐组件**：DateStrip、LeagueSection、MatchCard、MarketStatusBadge、FilterSheet、BottomNav。
- **状态覆盖**：Loading/Empty/Error/ViewOnly/Restricted。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：未准入也可按策略浏览公开信息；不能提交。
- **路由 / 上下文**：→M-PREDICT-002 / M-PREDICT-004。
- **关键禁止**：不做博彩盘口矩阵、红绿赔率闪烁或深色高频交易界面。
- **验收重点**：P0 只展示 enabled Football Pre-match 1X2；不混入未开放玩法。
- **参考模式**：R1 / R2 / R3 / R4 / R5

#### M-PREDICT-002｜赛事详情 · Football 1X2 · P0

- **页面目标**：理解赛事、三方向、当前池、流动性、规则并输入数量。
- **视觉结构（基线）**：赛事头；Home/Draw/Away 三方向固定展示；池/预计信息；流动性；数量；可用APT；规则；CTA。
- **视觉策划补充**：参考 FotMob/Sofascore 的比赛详情层级：赛事头部 → 1X2 三方向 → AI 数据/关键数据 → 流动性/规则/风险 → CTA。主胜/平局/客胜始终同时可见且无默认高亮。
- **关键交互（基线）**：选方向；输入数量；原方向追加入口；看规则；继续。
- **交互策划补充**：选方向后才激活数量输入；数据变更时保留用户输入但提示重新确认。锁定或资格变化即时禁用继续。
- **推荐组件**：MatchHero、ThreeWaySelector、AmountInput、DataTabs、LiquidityCard、RuleMeta、StickyCTA。
- **状态覆盖**：Market closing/locked；数据源异常；资格不足；数量非法。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：三方向不能隐藏；服务端最终校验。
- **路由 / 上下文**：→M-PREDICT-003。
- **关键禁止**：不隐藏 Draw；不把倍数/估算做成保证性大数字。
- **验收重点**：预计倍数必须标 Not guaranteed；锁定前会变化。
- **参考模式**：R1 / R2 / R3 / R4 / R5

#### M-PREDICT-003｜Prediction 确认 · P0

- **页面目标**：在产生资产效果前主动确认所有关键事实。
- **视觉结构（基线）**：赛事；方向；数量；服务费规则；锁定；不可撤销；低流动性；退款/更正；Consent checkbox；提交。
- **视觉策划补充**：高风险确认页坚持浅色：赛事、方向、数量、费用规则、锁定、不可撤销、低流动性、退款/更正规则分层呈现。Consent 不默认勾选。
- **关键交互（基线）**：主动勾选；确认提交；返回修改。
- **交互策划补充**：提交前刷新市场、余额、policy/parameter 版本；变化则展示 Diff 并要求重新确认。Unknown Result 进入查询状态，不允许重复提交。
- **推荐组件**：ConfirmSummary、VersionDiffNotice、ConsentCheckbox、RiskNotice、StickyCTA。
- **状态覆盖**：Consent mismatch；Market locked；余额变化；policy changed；unknown result。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：Consent 不能默认勾选。
- **路由 / 上下文**：成功→M-PREDICT-005；unknown→处理中。
- **关键禁止**：不省略影响说明；不把确认做成一个小弹窗里塞满长文本。
- **验收重点**：必须先创建有效 ConsentReceipt；订单用 Idempotency-Key。
- **参考模式**：R1 / R2 / R3 / R4 / R5

#### M-PREDICT-004｜我的 Prediction · P0

- **页面目标**：按状态查看所有历史与进行中订单。
- **视觉结构（基线）**：Tabs/筛选；订单卡；状态；赛事；方向；数量；更新时间。
- **视觉策划补充**：订单列表使用状态 Segmented Tabs + 卡片/紧凑列表，赛事信息是主标题，方向和数量为次级，状态与更新时间始终可见。
- **关键交互（基线）**：筛选；打开订单；回赛事。
- **交互策划补充**：筛选条件持久化；点击订单进入详情；账户受限时历史仍可查看。
- **推荐组件**：StatusTabs、OrderCard、FilterSheet、PaginationState。
- **状态覆盖**：Empty；分页失败；账户受限仍可看历史。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人数据；历史始终可读。
- **路由 / 上下文**：→M-PREDICT-005。
- **关键禁止**：不把 Submitted/Matching/Partial 视觉上伪装为 Completed。
- **验收重点**：列表状态与详情状态同一枚举来源。
- **参考模式**：R1 / R2 / R3 / R4 / R5

#### M-PREDICT-005｜Prediction 订单详情 · P0

- **页面目标**：完整追踪提交、锁定、结果、结算与资金效果。
- **视觉结构（基线）**：订单摘要；多轴状态；时间线；Consent；snapshot versions；关联流水；申诉。
- **视觉策划补充**：订单详情按多轴状态展示：订单、资产、风险、结果、结算分开，不用一个总状态。顶部摘要，下面时间线、Consent/快照、关联流水与申诉入口。
- **关键交互（基线）**：看规则快照；看流水；申诉；原方向追加（若 allowed）。
- **交互策划补充**：所有可操作按钮来自 allowed_actions；若允许原方向追加，使用明确的“追加同方向”入口并回到赛事上下文。
- **推荐组件**：OrderHeader、MultiAxisStatus、Timeline、SnapshotPanel、RelatedObjects、ActionPanel。
- **状态覆盖**：submitted/locked/awaiting_result/settling/settled/refunding/correcting。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：allowed_actions 服务端返回。
- **路由 / 上下文**：异常→M-PREDICT-006；流水→M-ASSET-003；申诉→M-SUPPORT-002。
- **关键禁止**：不把内部风控理由直接展示；异常状态必须保留记录入口。
- **验收重点**：不能把 Result official 和 Settlement paid 混成一个“已完成”。
- **参考模式**：R1 / R2 / R3 / R4 / R5

#### M-PREDICT-006｜异常 / 退款 / 更正详情 · P0

- **页面目标**：解释异常原因、资产影响、处理进度和最终更正。
- **视觉结构（基线）**：异常 banner；reason；result/settlement/principal/reward axes；时间线；退款/冲正流水；申诉。
- **视觉策划补充**：异常页顶部用醒目的但克制的异常说明，随后明确本金、费用、Reward、Result/Settlement 各自状态；更正前后版本使用对比区而非覆盖旧值。
- **关键交互（基线）**：查看证据摘要；看流水；申诉。
- **交互策划补充**：用户可查看退款/冲正流水和申诉；Processing/Correcting 期间只查询，不再提交。
- **推荐组件**：ExceptionBanner、StatusMatrix、BeforeAfterResult、LedgerLinks、AppealCTA。
- **状态覆盖**：review/refunding/refunded/correcting/corrected/dependency unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：不能泄露反作弊算法或他人信息。
- **路由 / 上下文**：→M-ASSET-003 / M-SUPPORT-002。
- **关键禁止**：不用一整屏红色；不删除原结果/原订单视觉记录。
- **验收重点**：更正必须保留 old/new 版本；退款结果可追溯。
- **参考模式**：R1 / R2 / R3 / R4 / R5

### F. Me / 资产 / OTC / 安全 / Support

#### M-ME-001｜我的 · P0

- **页面目标**：集中进入资产、Power、OTC、安全、工单与设置。
- **视觉结构（基线）**：用户摘要；KYC/资格；APT；Power；OTC；Security；Support；Settings。
- **视觉策划补充**：以“我的”主菜单概念图为视觉锚点，但必须删除概念图中的“今日收益/累计收益、未经批准 USD 估值、Premium 等未在基线确认的内容”。保留个人资料、KYC/准入、APT、Power、OTC、安全、工单、设置的干净分组。
- **关键交互（基线）**：进入各模块。
- **交互策划补充**：每行进入独立功能页；局部卡片失败不阻断整个个人中心。受限用户仍可进入历史、合法退款、Support 与安全页。
- **推荐组件**：ProfileHeader、AdmissionBadge、MenuGroup、ResourceSummaryRow、BottomNav。
- **状态覆盖**：局部卡片错误；受限提示。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人。
- **路由 / 上下文**：→各 Me 子页。
- **关键禁止**：不把资产数字做成用户中心第一视觉焦点。
- **验收重点**：不要在这里放复杂业务操作，只做入口和摘要。
- **参考模式**：R1 / R2

#### M-ASSET-001｜APT 资产 · P0

- **页面目标**：清楚展示 APT-I 可用、冻结、待确认和更新时间。
- **视觉结构（基线）**：总览；状态拆分；最近流水；OTC/Power入口；规则说明。
- **视觉策划补充**：APT 是“数量账”，顶部突出可用/冻结/待确认数量和更新时间；参考估值若存在必须弱化并标“参考/估算”。最近流水和 OTC/Power 入口放在下方。
- **关键交互（基线）**：看流水；进 OTC；进 Power。
- **交互策划补充**：点数量状态可查看解释；点最近流水进详情；OTC/Power 入口先检查 entitlement。
- **推荐组件**：AssetQuantityHero、StatusBreakdown、RecentLedgerList、RuleNotice。
- **状态覆盖**：Loading/Empty/Error/ViewOnly。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：资产可见与交易权限分开。
- **路由 / 上下文**：→M-ASSET-002 / M-OTC-001 / M-POWER-001。
- **关键禁止**：不使用币价涨跌色或资产暴涨视觉。
- **验收重点**：每个数量有单位/状态；不默认显示美元“收入”。
- **参考模式**：R1 / R2 / R6 / R8

#### M-ASSET-002｜APT 流水列表 · P0

- **页面目标**：按类型/状态/日期查每笔 APT 变化。
- **视觉结构（基线）**：筛选；流水列表；方向；数量；状态；来源对象。
- **视觉策划补充**：流水列表采用日期分组 + 方向图标 + 数量 + 状态 + 来源对象，避免币圈式红绿涨跌。筛选器放 Bottom Sheet。
- **关键交互（基线）**：筛选；打开详情。
- **交互策划补充**：按类型/状态/时间筛选；返回后保留位置；点击来源对象可从详情继续深链。
- **推荐组件**：FilterChips、LedgerRow、DateSection、StatusBadge。
- **状态覆盖**：Empty；分页失败。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人只读。
- **路由 / 上下文**：→M-ASSET-003。
- **关键禁止**：不隐藏负向/冲正记录；不要用纯颜色表示正负。
- **验收重点**：cursor pagination；历史不可消失。
- **参考模式**：R1 / R2 / R6 / R8

#### M-ASSET-003｜APT 流水详情 · P0

- **页面目标**：解释一笔 APT 变化的来源、状态和关联对象。
- **视觉结构（基线）**：entry_id；数量；方向；状态；source；rule/snapshot；时间；关联对象；reversal。
- **视觉策划补充**：详情采用“对象事实页”：entry_id、数量、方向、状态、来源、rule/snapshot、时间、reversal chain。ID 和版本用 Meta 样式，不抢主内容。
- **关键交互（基线）**：打开关联 Robot/Prediction/OTC；争议。
- **交互策划补充**：关联对象用可点击 chips/links；disputed/reversed 显示完整时间线与原始 entry 关系。
- **推荐组件**：ObjectHeader、Descriptions、StatusTimeline、RelatedObjectLinks。
- **状态覆盖**：pending/posted/reversed/disputed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人数据；证据安全摘要。
- **路由 / 上下文**：按 related_object 跳转。
- **关键禁止**：不把参考估值当已实现收入。
- **验收重点**：reversed 必须显示原 entry 和反向 entry。
- **参考模式**：R1 / R2 / R6 / R8

#### M-POWER-001｜Power · P0

- **页面目标**：看可用/冻结/消耗/释放，并理解 OTC 卖出影响。
- **视觉结构（基线）**：Power summary；上限；冻结；流水；规则；OTC CTA。
- **视觉策划补充**：Power 用资源仪表而非金币/资产卡：Available / Frozen / Limit / Consumed 分区，Sell 规则说明紧随其后；用蓝色比例条，不用收益视觉。
- **关键交互（基线）**：看流水；去 OTC。
- **交互策划补充**：点击 Frozen 展开关联 OTC 订单；去 OTC 前先刷新可用 Power 和资格。
- **推荐组件**：ResourceMeter、PowerBreakdown、FrozenList、RuleNotice、PrimaryCTA。
- **状态覆盖**：0 可用；冻结中；服务不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只读；消费由业务流程产生。
- **路由 / 上下文**：→M-OTC-001。
- **关键禁止**：不使用币价涨跌色或资产暴涨视觉。
- **验收重点**：前端不能自己按数量算最终 Power。
- **参考模式**：R1 / R2 / R6 / R8

#### M-OTC-001｜OTC 市场 · P0

- **页面目标**：看资格、市场、参考信息并进入挂买/挂卖。
- **视觉结构（基线）**：资格/额度/Power卡；Buy/Sell order book；我的订单；风险提示；创建按钮。
- **视觉策划补充**：OTC 是受控撮合，不做交易所 K 线。顶部资格/额度/Power 状态卡，主体可用简化 Buy/Sell 订单簿、当前参考信息、我的订单和风险说明。
- **关键交互（基线）**：挂买；挂卖；看订单；看规则。
- **交互策划补充**：挂买/挂卖先选择 side 再进入输入页；市场依赖异常时隐藏创建 CTA 但保留历史订单入口。
- **推荐组件**：EligibilityCard、SideTabs、OrderBookList、MyOrdersPreview、RiskNotice。
- **状态覆盖**：Empty market；restricted；market dependency unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：功能权限服务端决定。
- **路由 / 上下文**：→M-OTC-002 / M-OTC-005。
- **关键禁止**：不做博彩盘口矩阵、红绿赔率闪烁或深色高频交易界面。
- **验收重点**：明确“参考价和流动性不保证”。
- **参考模式**：R1 / R2 / R6 / R8

#### M-OTC-002｜OTC 下单输入 · P0

- **页面目标**：输入 side、数量和允许的价格/结算字段。
- **视觉结构（基线）**：Buy/Sell toggle；price；quantity；available/limit/power；settlement method；Next。
- **视觉策划补充**：表单按 side→价格→数量→可用/额度/Power→结算方式组织，实时预览冻结影响。Max 是辅助按钮，不自动替用户填满。
- **关键交互（基线）**：输入；Max；下一步。
- **交互策划补充**：每次关键字段变化重新获取/失效 quote；Power 不足时直接给 next_action，不在前端自行估算通过。
- **推荐组件**：SideToggle、PriceInput、QuantityInput、ResourcePreview、SettlementMethodPicker、StickyCTA。
- **状态覆盖**：字段错误；超额度；Power不足；结算方式无效。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：客户端校验只做体验，最终服务端。
- **路由 / 上下文**：→M-OTC-003。
- **关键禁止**：不做营销 Banner；不使用金色主按钮。
- **验收重点**：修改 price/quantity 必须重新 quote。
- **参考模式**：R1 / R2 / R6 / R8

#### M-OTC-003｜OTC 订单确认 · P0

- **页面目标**：提交前确认价格、数量、费用、冻结、Power 和取消规则。
- **视觉结构（基线）**：Quote summary；fee；freeze；power；cancel rule；risk；Consent；submit。
- **视觉策划补充**：确认页显示 quote 到期时间、价格/数量/fee、APT 冻结、Power 冻结、取消规则、Review 可能性。提交按钮和风险说明之间留足空间。
- **关键交互（基线）**：返回修改；主动确认；提交。
- **交互策划补充**：quote 过期自动禁止提交并给“重新获取报价”；提交前刷新 eligibility；Unknown 结果按原 idempotency 查询。
- **推荐组件**：QuoteSummary、FreezeImpact、CountdownBadge、ConsentBlock、StickyCTA。
- **状态覆盖**：quote expired；eligibility changed；review required；unknown result。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：高风险可能 MFA。
- **路由 / 上下文**：→M-OTC-004。
- **关键禁止**：不省略影响说明；不把确认做成一个小弹窗里塞满长文本。
- **验收重点**：Idempotency-Key；不能用按钮 success 代替 order status。
- **参考模式**：R1 / R2 / R6 / R8

#### M-OTC-004｜OTC 提交结果 · P0

- **页面目标**：展示订单是否已创建、是否审核、是否进入撮合。
- **视觉结构（基线）**：结果状态；order_id；冻结影响；Power；下一步；查看订单。
- **视觉策划补充**：提交结果明确“已提交 ≠ 已成交”。顶部状态应是 Review / Matching / Rejected / Unknown 等，下面显示 order_id、APT/Power 冻结影响和下一步。
- **关键交互（基线）**：查看详情；返回市场。
- **交互策划补充**：只提供“查看订单详情 / 返回市场”；Unknown 状态不出现重新下单按钮。
- **推荐组件**：ResultHero、OrderReference、FreezeSummary、ActionGroup。
- **状态覆盖**：review/matching/rejected/unknown/no_effect。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人。
- **路由 / 上下文**：→M-OTC-006 / M-OTC-001。
- **关键禁止**：不使用金币雨/烟花；“已提交”不得画成“已完成”。
- **验收重点**：失败必须明确未冻结或已释放。
- **参考模式**：R1 / R2 / R6 / R8

#### M-OTC-005｜我的 OTC 订单 · P0

- **页面目标**：按状态查看自己的 OTC 订单。
- **视觉结构（基线）**：status tabs；order list；side/price/qty/filled/remaining。
- **视觉策划补充**：订单列表按 Buy/Sell 与状态筛选，列表行展示价格、原数量、已成交、剩余、更新时间；Partial 用进度条辅助。
- **关键交互（基线）**：筛选；打开详情。
- **交互策划补充**：保留筛选；点击进入详情；分页/刷新失败不影响已有数据。
- **推荐组件**：SegmentedTabs、OrderRow、FillProgress、FilterSheet。
- **状态覆盖**：Empty；分页失败。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人。
- **路由 / 上下文**：→M-OTC-006。
- **关键禁止**：不把 Submitted/Matching/Partial 视觉上伪装为 Completed。
- **验收重点**：Partial 必须显式显示 filled/remaining。
- **参考模式**：R1 / R2 / R6 / R8

#### M-OTC-006｜OTC 订单详情 · P0

- **页面目标**：追踪审核、撮合、部分成交、完成、取消、争议与资产影响。
- **视觉结构（基线）**：订单摘要；状态时间线；trade list；APT ledger；Power impact；cancel/appeal。
- **视觉策划补充**：订单详情顶部展示 side/status，核心区域显示原数量/已成交/剩余；下面分别是 Trade、APT Ledger、Power Impact、Timeline。Partial 是主视觉状态之一。
- **关键交互（基线）**：取消（若可）；看流水；申诉。
- **交互策划补充**：取消只在 allowed 时出现；取消确认明确释放多少 APT/Power。Disputed 提供 Support/申诉入口。
- **推荐组件**：OrderHeader、FillSummary、TradeList、PowerImpact、Timeline、ActionPanel。
- **状态覆盖**：review/matching/partial/completed/cancelled/rejected/disputed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：取消按钮由服务端 allowed_actions。
- **路由 / 上下文**：→M-ASSET-003 / M-SUPPORT-002。
- **关键禁止**：不把内部风控理由直接展示；异常状态必须保留记录入口。
- **验收重点**：取消只释放未成交部分；已成交不可回滚成未成交。
- **参考模式**：R1 / R2 / R6 / R8

#### M-SEC-001｜安全中心 · P0

- **页面目标**：集中看 MFA、设备、Session、登录记录和密码安全。
- **视觉结构（基线）**：security summary；MFA；devices；sessions；login audit；password。
- **视觉策划补充**：安全中心用“已启用/需处理”的可信结构，不做夸张安全分数。MFA、设备、Session、登录记录、密码分别成组。
- **关键交互（基线）**：绑定/管理 MFA；设备；改密码；撤销 session。
- **交互策划补充**：高风险安全操作要求 MFA；撤销 Session 用 destructive confirm；当前会话与其他会话明确区分。
- **推荐组件**：SecuritySummary、SecuritySettingRow、DevicePreview、LoginActivity、ActionSheet。
- **状态覆盖**：风险限制；操作失败；依赖不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：敏感操作二次验证。
- **路由 / 上下文**：→M-SEC-002 / M-AUTH-004。
- **关键禁止**：不靠大面积红色制造恐慌；敏感信息必须脱敏。
- **验收重点**：不能展示完整 IP/敏感设备指纹。
- **参考模式**：R1 / R2 / R8

#### M-SEC-002｜MFA / 设备 / Session 管理 · P0

- **页面目标**：绑定验证器、查看并撤销其他会话。
- **视觉结构（基线）**：MFA enrollment；二维码/密钥安全流程；设备列表；revoke。
- **视觉策划补充**：MFA enrollment、设备、Session 用分组设置列表；二维码/密钥只在绑定流程短时显示，Session 行含设备、地区粗粒度、最近活动和状态。
- **关键交互（基线）**：绑定；验证；撤销 session/device。
- **交互策划补充**：撤销后必须等待服务端确认再移除行；不能撤销关键当前会话时说明原因。二维码页阻止截图提示可选但不强依赖。
- **推荐组件**：MfaSetupFlow、DeviceRow、SessionRow、ConfirmDialog、ResultState。
- **状态覆盖**：验证失败；不能撤销关键当前会话；risk held。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人 + MFA。
- **路由 / 上下文**：成功回 Security。
- **关键禁止**：不在设置页塞业务营销内容。
- **验收重点**：撤销成功后服务端立即失效对应 session。
- **参考模式**：R1 / R2 / R8

#### M-SUPPORT-001｜帮助中心 / 工单列表 · P0

- **页面目标**：找帮助并查看自己的工单。
- **视觉结构（基线）**：FAQ；分类；创建工单；ticket list/status。
- **视觉策划补充**：顶部搜索 + “我的工单”优先，FAQ 放次级；工单行展示标题、关联对象、状态、最后更新。整体像轻量帮助中心，不像客服后台。
- **关键交互（基线）**：搜索帮助；创建；打开工单。
- **交互策划补充**：若同对象已有未关闭工单，创建前提示继续原工单；搜索 FAQ 无结果时给创建入口。
- **推荐组件**：SearchField、TicketRow、CategoryChips、PrimaryCTA、EmptyState。
- **状态覆盖**：Empty；列表失败。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人；FAQ 可公开部分。
- **路由 / 上下文**：→M-SUPPORT-002 / 003。
- **关键禁止**：不做复杂客服工作台样式。
- **验收重点**：相同问题已有工单时提示继续原工单。
- **参考模式**：R1 / R2 / R8

#### M-SUPPORT-002｜创建工单 / 申诉 · P0

- **页面目标**：提交可处理的问题并绑定具体对象。
- **视觉结构（基线）**：category；related object；description；attachments；contact；submit。
- **视觉策划补充**：related object 选择放在最前，让工单天然绑定订单/流水/Robot；描述、附件、联系方式按表单分组。附件以可重试卡片展示。
- **关键交互（基线）**：填写；上传；提交。
- **交互策划补充**：保存草稿（本地/服务端按实现）；附件失败不阻断其他字段；duplicate case 直接引导原工单。
- **推荐组件**：ObjectPicker、TextArea、UploadCard、ContactField、StickyCTA。
- **状态覆盖**：上传失败；字段错误；duplicate case。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只能关联本人对象。
- **路由 / 上下文**：成功→M-SUPPORT-003。
- **关键禁止**：不做营销 Banner；不使用金色主按钮。
- **验收重点**：失败保留草稿；附件单项可重试。
- **参考模式**：R1 / R2 / R8

#### M-SUPPORT-003｜工单详情 · P0

- **页面目标**：看处理进度、回复、补件和最终结论。
- **视觉结构（基线）**：status；SLA（若批准）；timeline；messages；attachments；related objects；reply。
- **视觉策划补充**：顶部工单状态和关联对象，主体是对话 + 系统时间线，内部状态变化用系统消息区分普通回复；底部回复框固定。
- **关键交互（基线）**：回复；补件；看关联对象。
- **交互策划补充**：waiting_user 时突出待补充项；resolved/closed 前显示结论摘要。附件和对象链接都可独立打开。
- **推荐组件**：TicketHeader、ConversationThread、SystemTimeline、AttachmentCard、ReplyComposer。
- **状态覆盖**：submitted/in_progress/waiting_user/under_review/resolved/closed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人。
- **路由 / 上下文**：深链到相关业务页。
- **关键禁止**：不隐藏系统状态变化；附件不能和普通文本混为一行。
- **验收重点**：resolved/closed 前必须有用户可见结论。
- **参考模式**：R1 / R2 / R8

#### M-SETTINGS-001｜设置 · P0

- **页面目标**：管理语言、时区、通知偏好和基础应用设置。
- **视觉结构（基线）**：language；timezone；notifications；legal/help；logout。
- **视觉策划补充**：标准系统分组列表：语言/时区、通知、法律与帮助、退出。避免把资产、安全高风险操作或营销内容塞到设置。
- **关键交互（基线）**：修改偏好；退出。
- **交互策划补充**：偏好即时或保存式提交均需明确状态；离线时保留本地选择但标记未同步。退出需确认并清理 session。
- **推荐组件**：SettingGroup、SettingRow、Switch、PickerSheet、LogoutButton。
- **状态覆盖**：保存失败；离线。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：本人。
- **路由 / 上下文**：Logout→M-AUTH-001。
- **关键禁止**：不在设置页塞业务营销内容。
- **验收重点**：语言变化不能改变业务数值/规则语义。
- **参考模式**：R1 / R2

### G. P1 / Future

#### M-AI-001｜AI 数据 / Signal 详情 · P1

- **页面目标**：解释 AI 数据与信号来源、时间和非保证属性。
- **视觉结构（基线）**：signal summary；source/time；historical context；explanation。
- **视觉策划补充**：参考 Sofascore 数据可视化思路，但强调数据来源/更新时间/延迟属性。Signal Summary、历史上下文、解释和图表分层，不做上涨箭头或“必胜”视觉。
- **关键交互（基线）**：筛选；查看说明。
- **交互策划补充**：筛选时间/数据类型，查看数据口径说明；延迟/不可用时保留最后快照并显式标记。
- **推荐组件**：SignalSummary、ChartCard、SourceMeta、ExplanationBlock、DelayBadge。
- **状态覆盖**：data delayed/unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只读。
- **路由 / 上下文**：返回 Home。
- **关键禁止**：不使用保证性箭头、暴涨曲线或“必胜”视觉。
- **验收重点**：必须标实时/延迟/估算。
- **参考模式**：R1 / R4 / R5

#### M-GROWTH-001｜Referral / Team · P1

- **页面目标**：查看邀请关系和符合条件的候选/已结算奖励。
- **视觉结构（基线）**：invite；relationship；candidate/held/payable/paid；rules。
- **视觉策划补充**：邀请关系、候选/held/payable/paid 奖励分区，避免树状金字塔和层级夸张。分享入口是辅助动作。
- **关键交互（基线）**：分享；看记录。
- **交互策划补充**：奖励状态不可由前端推导；budget closed 时保留历史并隐藏新活动 CTA。
- **推荐组件**：InviteCard、RelationshipList、RewardStatusTabs、RuleNotice。
- **状态覆盖**：资格不足；budget closed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：由服务端资格决定。
- **路由 / 上下文**：Me/Robot 子入口。
- **关键禁止**：不做层级金字塔、拉人头树形收益图。
- **验收重点**：不能承诺永久佣金或“拉人头收益”。
- **参考模式**：R1 / R2 / R8

### E. Prediction

#### M-PREDICT-FREE-001｜免费 YES/NO · P1/Sandbox

- **页面目标**：提供不含真实价值的学习/互动预测。
- **视觉结构（基线）**：question；yes/no；free points；result/learning。
- **视觉策划补充**：必须显著显示 Sandbox/Free Points；问题、YES/NO、学习结果简单直观，不与真实 1X2 或 APT 订单视觉混淆。
- **关键交互（基线）**：选 YES/NO；提交。
- **交互策划补充**：提交只影响不可兑付 points；结束后给学习/结果反馈，不出现现金价值。
- **推荐组件**：SandboxBadge、BinarySelector、PointsSummary、LearningResult。
- **状态覆盖**：closed/ended。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：必须是不可兑付 points。
- **路由 / 上下文**：Prediction 子入口。
- **关键禁止**：不隐藏 Draw；不把倍数/估算做成保证性大数字。
- **验收重点**：不得与真实 APT 或收入混淆。
- **参考模式**：R1 / R2 / R3 / R4 / R5

### G. P1 / Future

#### M-MIGRATION-001｜APT-I → APT-C Migration · Future/CLOSED

- **页面目标**：未来满足 Gate 后处理数量迁移。
- **视觉结构（基线）**：eligibility；quantity；wallet；confirmation；finality timeline。
- **视觉策划补充**：Future/CLOSED 默认展示关闭说明，而不是可操作表单；只有在未来 Gate 开放的 Sandbox 才显示 eligibility→quantity→wallet→confirm→finality Stepper。
- **关键交互（基线）**：创建迁移；查 finality。
- **交互策划补充**：P0 入口隐藏/禁用；未来提交后以 finality timeline 查询，不允许重复广播。
- **推荐组件**：ClosedState、MigrationStepper、WalletField、FinalityTimeline。
- **状态覆盖**：closed/review/broadcast/finality/failed/reversed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：P0 必须 hidden/disabled。
- **路由 / 上下文**：Me/Asset 子入口（默认隐藏）。
- **关键禁止**：不把所有步骤塞进一屏；上传失败不能清空整页。
- **验收重点**：未正式开启时不能用 mock 开关放出真实入口。
- **参考模式**：R1 / R6 / R8

## 6. Admin 页面总表

| Page ID | 页面 | 优先级 | 一级导航 |
|---|---|---|---|
| A-WORK-001 | 运营总览 | P0 | 01 工作台 |
| A-WORK-002 | 今日待办 | P0 | 01 工作台 |
| A-USER-001 | 用户列表 | P0 | 02 用户与准入 |
| A-USER-002 | 用户 360 | P0 | 02 用户与准入 |
| A-KYC-001 | KYC 审核队列 | P0 | 02 用户与准入 |
| A-LEDGER-001 | 资产总览 | P0 | 03 资产与账本 |
| A-LEDGER-002 | APT 账户与流水 | P0 | 03 资产与账本 |
| A-LEDGER-003 | 池子与对账 | P0 | 03 资产与账本 |
| A-LEDGER-004 | 更正 / 冲正申请 | P0 | 03 资产与账本 |
| A-ROBOT-001 | Robot 列表 | P0 | 04 机器人与权益 |
| A-ROBOT-002 | Robot 详情 | P0 | 04 机器人与权益 |
| A-ROBOT-003 | Reward / Claim 运营 | P0 | 04 机器人与权益 |
| A-OTC-001 | OTC 订单列表 | P0 | 05 OTC 与 Power |
| A-OTC-002 | OTC 订单详情 / 审核 | P0 | 05 OTC 与 Power |
| A-POWER-001 | Power 账户与流水 | P0 | 05 OTC 与 Power |
| A-PREDICT-001 | Market / Event 列表 | P0 | 06 赛事预测 |
| A-PREDICT-002 | Market 详情 | P0 | 06 赛事预测 |
| A-PREDICT-003 | Result / Settlement | P0 | 06 赛事预测 |
| A-PREDICT-004 | Refund / Correction | P0 | 06 赛事预测 |
| A-RISK-001 | Risk Case | P0 | 07 风控 / 审批 / 参数 / 策略 |
| A-APPROVAL-001 | 审批中心 | P0 | 07 风控 / 审批 / 参数 / 策略 |
| A-CONFIG-001 | Parameter Center · Definition/Candidate | P0 | 07 风控 / 审批 / 参数 / 策略 |
| A-CONFIG-002 | Parameter Release / Snapshot | P0 | 07 风控 / 审批 / 参数 / 策略 |
| A-POLICY-001 | 地区 / KYC / 保护策略 | P0 | 07 风控 / 审批 / 参数 / 策略 |
| A-SUPPORT-001 | 工单队列 | P0 | 08 客服 / 审计 / 运维 |
| A-SUPPORT-002 | 工单详情 | P0 | 08 客服 / 审计 / 运维 |
| A-AUDIT-001 | 审计日志 | P0 | 08 客服 / 审计 / 运维 |
| A-OPS-001 | 异步任务 / 对账 / 系统状态 | P0 | 08 客服 / 审计 / 运维 |
| A-REPORT-001 | 运营报表 | P1 | 08 客服 / 审计 / 运维 |
| A-GROWTH-001 | Referral / Team 运营 | P1 | 08 客服 / 审计 / 运维 |
| A-MIGRATION-001 | APT Migration | Future/CLOSED | 08 客服 / 审计 / 运维 |

## 7. Admin 全局视觉与交互框架

- **Shell**：240px 深蓝 Sidebar / 72px 收起态；64px 白 Header；Gray-50 主背景；内容最大宽 1600px。
- **对象模型**：高频对象用独立 List + Detail；中频预览用 480/640px Drawer；低频历史放 Detail Tab。
- **表格**：48px 默认行高；Audit/Log 才允许 40px；表头吸顶、筛选器与列设置固定模式复用。
- **高风险变更**：Create Proposal → Review → Approval → Execution → Result → Audit，后台禁止直接改余额、账本、结算终态或历史版本。
- **视觉语气**：像专业 SaaS 对象管理系统，不像黑色监控盘、交易终端或大屏。

## 8. Admin 逐页视觉与交互策划

### 01 工作台

#### A-WORK-001｜运营总览 · P0

- **页面目标**：看平台健康、异常、对账和待办入口。
- **视觉结构（基线）**：环境标识；KPI摘要；异常；待办；对账；系统状态；快捷入口。
- **视觉策划补充**：沿用前面概念板中的 Stripe-like Admin 方向：深蓝 Sidebar + 白 Header + Gray-50 内容区。首屏 KPI 只保留真正能驱动运营动作的 4–6 项，下面是异常/待办、对账、系统状态与近期动作。
- **关键交互（基线）**：点异常/待办/对账详情；保存视图。
- **交互策划补充**：所有 KPI/异常卡可下钻到对象列表并自动带筛选；局部卡失败独立重试，不阻断整页。
- **推荐组件**：AdminShell、KpiCard、TrendCard、WorkItemPanel、HealthPanel、RecentActivity。
- **状态覆盖**：局部加载失败；无异常；数据延迟。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：按角色隐藏敏感财务字段。
- **路由 / 上下文**：→A-WORK-002 / 对象详情。
- **关键禁止**：不做黑色收益大屏或全屏数字墙。
- **验收重点**：任何指标都能点到口径/对象；不能把失败数据显示为0。
- **参考模式**：R1 / R6 / R8

#### A-WORK-002｜今日待办 · P0

- **页面目标**：统一处理审核、补件、异常和到期任务。
- **视觉结构（基线）**：筛选；优先级；SLA；object type；assignee；table；drawer preview。
- **视觉策划补充**：标准工作队列：顶部筛选/保存视图，中间高密度表格，右侧 Drawer 预览对象；优先级/SLA 只用 Badge，不用彩色整行。
- **关键交互（基线）**：领取；转派；打开对象；补件；建 case。
- **交互策划补充**：领取/转派采用乐观提示但最终以服务端冲突结果为准；打开对象后返回保留队列筛选。
- **推荐组件**：FilterBar、DataTable、PriorityBadge、AssigneeControl、PreviewDrawer。
- **状态覆盖**：Empty；加载失败；任务被他人领取。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只能转派到允许队列。
- **路由 / 上下文**：→对应对象页。
- **关键禁止**：不使用卡片瀑布替代表格；优先级不能只靠颜色。
- **验收重点**：并发领取用版本控制，不能两个人同时处理成已领取。
- **参考模式**：R1 / R6 / R8

### 02 用户与准入

#### A-USER-001｜用户列表 · P0

- **页面目标**：查用户并进入 User360/KYC/风险。
- **视觉结构（基线）**：搜索；KYC/状态/风险/资格筛选；表格；保存筛选。
- **视觉策划补充**：参考 Stripe Dashboard 对象列表：搜索为第一入口，KYC/状态/风险/资格为可组合筛选；表格列保持可扫描，ID/时间弱化。
- **关键交互（基线）**：打开 User360；去 KYC；创建限制 case。
- **交互策划补充**：点击用户行进入 User360；批量操作只允许低风险标签/导出类，不做批量改资格。
- **推荐组件**：SearchBar、FilterBar、DataTable、ColumnSettings、SavedView。
- **状态覆盖**：Empty；搜索失败；字段无权限。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：按字段脱敏。
- **路由 / 上下文**：→A-USER-002 / A-KYC-001。
- **关键禁止**：不在列表直接放高风险“万能修改”按钮。
- **验收重点**：列表不能直接改余额/资格。
- **参考模式**：R1 / R6 / R8

#### A-USER-002｜用户 360 · P0

- **页面目标**：一个页面看用户身份、准入、Robot、APT、OTC、Prediction、Power、Risk、Ticket、Audit。
- **视觉结构（基线）**：header summary；tabs：Admission/Robot/APT/Power/OTC/Prediction/Risk/Support/Audit。
- **视觉策划补充**：对象详情页：顶部 User Header（ID、状态、准入、风险摘要），下方固定 Tabs：Admission/Robot/APT/Power/OTC/Prediction/Risk/Support/Audit。
- **关键交互（基线）**：打开关联对象；创建 case/approval；只读资格。
- **交互策划补充**：Tab 深链可复制 URL；每个 Tab 独立加载/权限；高风险动作从对象页发起 case/proposal，不直接改最终状态。
- **推荐组件**：ObjectHeader、Tabs、Descriptions、RelatedObjectTable、CaseAction。
- **状态覆盖**：Tab级 Loading/NoPermission/Empty。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：三字段 global_p/AI eligibility/Prediction eligibility 独立展示；不能直接编辑。
- **路由 / 上下文**：深链到各模块详情。
- **关键禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **验收重点**：每个 Tab 的数字可回到原始对象。
- **参考模式**：R1 / R6 / R8

#### A-KYC-001｜KYC 审核队列 · P0

- **页面目标**：处理 submitted/needs_info 的 KYC case。
- **视觉结构（基线）**：queue；case preview；资料；证据；decision；reason template；history。
- **视觉策划补充**：参考 Stripe Identity review tools：队列 + 资料/证据详情 + 决策区。敏感图像默认缩略/受控查看；Insights/系统判断与人工决策分区。
- **关键交互（基线）**：approve/reject/needs_info；转派；建 appeal/admin case。
- **交互策划补充**：Approve/Reject/Needs Info 必须选择 reason；并发冲突时刷新 case，不覆盖他人决定。
- **推荐组件**：WorkQueue、EvidencePanel、SensitivePreview、DecisionPanel、HistoryTimeline。
- **状态覆盖**：资料缺失；证据服务不可用；并发决定冲突。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：KYC reviewer 权限；敏感资料严格最小化。
- **路由 / 上下文**：→User360。
- **关键禁止**：不使用卡片瀑布替代表格；优先级不能只靠颜色。
- **验收重点**：决定必须有 reason_code 和 decision_version；不可覆盖旧决定。
- **参考模式**：R1 / R6 / R8 / R7

### 03 资产与账本

#### A-LEDGER-001｜资产总览 · P0

- **页面目标**：看 APT 总量、状态拆分、异常和对账。
- **视觉结构（基线）**：summary cards；status breakdown；reconciliation；exceptions；drilldown。
- **视觉策划补充**：像 Stripe Balance/Reports：资产数量摘要、状态拆分、对账状态和异常入口，不用财务大屏。所有数字带单位、快照、更新时间。
- **关键交互（基线）**：打开账户/流水/异常。
- **交互策划补充**：点击任何摘要直接带条件进入账户/流水/对账页。
- **推荐组件**：SummaryCard、ReconcileStatus、ExceptionList、DrilldownLink。
- **状态覆盖**：数据延迟；对账异常；权限不足。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：财务字段权限。
- **路由 / 上下文**：→A-LEDGER-002/003/004。
- **关键禁止**：不把 APT 数量称收入；不隐藏冻结/待确认。
- **验收重点**：冻结/待确认/已销毁分别展示，不能一锅算“余额”。
- **参考模式**：R1 / R6 / R8

#### A-LEDGER-002｜APT 账户与流水 · P0

- **页面目标**：查用户/平台 APT 数量账和每笔 entry。
- **视觉结构（基线）**：account search；ledger table；filter；detail drawer；related objects；reversal chain。
- **视觉策划补充**：账本表格作为主角：账户搜索、流水筛选、方向/类型/状态/来源对象/批次；Drawer 显示 entry 详情和 reversal chain。
- **关键交互（基线）**：查看；标异常；创建更正 proposal。
- **交互策划补充**：不能编辑 entry；“更正”只创建 proposal。Drawer 内可继续打开关联对象。
- **推荐组件**：LedgerTable、FilterBar、EntryDrawer、ReversalChain、ProposalButton。
- **状态覆盖**：Empty；query fail；entry posting pending。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：禁止直接 edit balance。
- **路由 / 上下文**：→A-LEDGER-004 / related objects。
- **关键禁止**：禁止内联编辑余额/流水；冲正不能覆盖原记录。
- **验收重点**：append-only；更正只能走 reversal proposal。
- **参考模式**：R1 / R6 / R8

#### A-LEDGER-003｜池子与对账 · P0

- **页面目标**：对账 AI/Prediction/OTC 等账户和批次。
- **视觉结构（基线）**：account tree；batch；diff；reconciliation state；evidence；task links。
- **视觉策划补充**：对账页分三列/区：账户树或批次导航、对账差异表、证据与任务；diff != 0 用明确警示但不铺满红色。
- **关键交互（基线）**：重新对账；建异常任务；查看 batch。
- **交互策划补充**：重新对账创建 async job；失败进入 job/case，不在浏览器计算最终差异。
- **推荐组件**：AccountTree、ReconcileTable、DiffBadge、EvidenceDrawer、JobStatus。
- **状态覆盖**：diff != 0；async running；failed。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：财务/账本角色。
- **路由 / 上下文**：→Approval/Audit。
- **关键禁止**：禁止内联编辑余额/流水；冲正不能覆盖原记录。
- **验收重点**：差异不为0的批次不能假装 closed。
- **参考模式**：R1 / R6 / R8

#### A-LEDGER-004｜更正 / 冲正申请 · P0

- **页面目标**：创建受控的 ledger correction，不直接改账。
- **视觉结构（基线）**：source entries；reason；impact preview；reversal/new entry plan；evidence；approval route。
- **视觉策划补充**：高风险申请页用 source entry → 影响预览 → reversal/new entry plan → evidence → approval route 的纵向步骤。
- **关键交互（基线）**：创建草案；提交审批；取消草案。
- **交互策划补充**：草案可编辑；提交审批后只读；already reversed 等冲突在提交前阻止。
- **推荐组件**：SourceEntryCard、ImpactDiff、EvidenceUploader、ApprovalRoute、StickyAction。
- **状态覆盖**：invalid source；already reversed；approval rejected。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：申请人与审批人分离。
- **路由 / 上下文**：→A-APPROVAL-001。
- **关键禁止**：草案不得表现成已执行；不允许申请人直接越过审批。
- **验收重点**：草案无资金效果；执行后原记录仍存在。
- **参考模式**：R1 / R6 / R8

### 04 机器人与权益

#### A-ROBOT-001｜Robot 列表 · P0

- **页面目标**：查用户 Robot、等级、状态、异常和当前规则版本。
- **视觉结构（基线）**：filters；table；level/status；eligibility；reward alert；rule version。
- **视觉策划补充**：对象列表展示用户/Robot ID、Level、Status、Eligibility、Reward Alert、Rule Version；Level 允许少量金色，但表格仍以中性色为主。
- **关键交互（基线）**：打开详情；创建限制/复核 case。
- **交互策划补充**：行点击详情；异常操作只创建 Risk/Admin Case。
- **推荐组件**：FilterBar、DataTable、LevelBadge、StatusBadge、AlertChip。
- **状态覆盖**：Empty；service fail。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：Ops read；限制动作需 case/approval。
- **路由 / 上下文**：→A-ROBOT-002。
- **关键禁止**：不在列表直接放高风险“万能修改”按钮。
- **验收重点**：不能从列表直接改 level。
- **参考模式**：R1 / R6 / R8

#### A-ROBOT-002｜Robot 详情 · P0

- **页面目标**：看一个 Robot 的状态时间线、升级、Reward、Power、Ledger 与版本。
- **视觉结构（基线）**：object header；timeline；Upgrade tab；Reward tab；Power tab；Ledger tab；Audit。
- **视觉策划补充**：顶部 Robot 对象头，下面 Timeline + Upgrade/Reward/Power/Ledger/Audit Tabs；当前规则/参数版本固定显示在右上 Meta 区。
- **关键交互（基线）**：创建 pause/review proposal；跳参数；看用户360。
- **交互策划补充**：Pause/Review 通过 proposal；跳参数中心时带当前 rule/definition。
- **推荐组件**：ObjectHeader、Tabs、Timeline、VersionMeta、ProposalPanel。
- **状态覆盖**：data partial；rule version unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：高风险 action 通过 allowed_actions。
- **路由 / 上下文**：→A-CONFIG-001 / User360。
- **关键禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **验收重点**：状态历史不能编辑。
- **参考模式**：R1 / R6 / R8

#### A-ROBOT-003｜Reward / Claim 运营 · P0

- **页面目标**：查 Reward candidate/held/pending/claimed/expired/reversed 和 Claim 异常。
- **视觉结构（基线）**：batch filters；user/reward rows；budget snapshot；claim status；ledger refs；case action。
- **视觉策划补充**：Reward 运营以批次/用户/状态表格为主，上方 budget snapshot 与异常摘要；Claim 和 Ledger Ref 都可下钻。
- **关键交互（基线）**：查看；创建 review/clawback case；打开 ledger。
- **交互策划补充**：clawback/review 只建 case；claim unknown 与 posting mismatch 自动引导调查路径。
- **推荐组件**：BatchFilter、RewardTable、BudgetSnapshot、MismatchAlert、CaseAction。
- **状态覆盖**：claim unknown；posting mismatch；budget closed。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：不能直接把 candidate 改 claimed。
- **路由 / 上下文**：→Approval/Ledger。
- **关键禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **验收重点**：Reward status 和 ledger posting 必须一致。
- **参考模式**：R1 / R6 / R8

### 05 OTC 与 Power

#### A-OTC-001｜OTC 订单列表 · P0

- **页面目标**：查所有 OTC 订单、状态、风险和撮合进度。
- **视觉结构（基线）**：filters；side/status/risk；order table；partial fill；capacity summary。
- **视觉策划补充**：列表页而非交易终端：side/status/risk/filter + order table，Partial 显示 filled/remaining；可加小型 capacity summary，不画 K 线。
- **关键交互（基线）**：打开详情；保存视图。
- **交互策划补充**：行点击详情；保存视图；服务不可用保留最后列表并标 stale。
- **推荐组件**：FilterBar、DataTable、FillProgress、RiskBadge、SavedView。
- **状态覆盖**：Empty；service unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：按角色脱敏对手方。
- **路由 / 上下文**：→A-OTC-002。
- **关键禁止**：不在列表直接放高风险“万能修改”按钮。
- **验收重点**：SUBMITTED/MATCHING/PARTIAL 绝不能显示 Completed。
- **参考模式**：R1 / R6 / R8

#### A-OTC-002｜OTC 订单详情 / 审核 · P0

- **页面目标**：处理 review/dispute 并看冻结、Power、Trade、Ledger 全链路。
- **视觉结构（基线）**：order；user eligibility；risk evidence；asset freeze；power；trades；timeline；decision panel。
- **视觉策划补充**：对象详情拆成 Order Summary、User Eligibility、Risk Evidence、APT Freeze、Power、Trades、Timeline、Decision Panel；重要冻结影响可用固定侧栏摘要。
- **关键交互（基线）**：approve/reject/needs_info；取消/强制处置只能建 proposal；添加内部 note。
- **交互策划补充**：Approve/Reject/Needs Info 依据权限；强制取消/处置必须创建 proposal。Partial 状态下任何决策先刷新对象版本。
- **推荐组件**：ObjectHeader、EvidencePanel、FreezeSummary、TradeTable、DecisionPanel、Timeline。
- **状态覆盖**：evidence unavailable；state conflict；partial fill。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：风险决定与高危处置分权。
- **路由 / 上下文**：→Approval/User360/Ledger。
- **关键禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **验收重点**：决定必须写 reason；资产影响预览。
- **参考模式**：R1 / R6 / R8

#### A-POWER-001｜Power 账户与流水 · P0

- **页面目标**：查 Power 的 available/frozen/consumed/released 和 OTC 关联。
- **视觉结构（基线）**：user search；position；ledger；related OTC order；rule version。
- **视觉策划补充**：用户/Power Position 列表 + 流水，重点展示 available/frozen/limit 与 related OTC；不做价格或收益视觉。
- **关键交互（基线）**：查看；标异常；去 OTC。
- **交互策划补充**：异常只标记/建 case；点击关联订单跳 OTC detail。
- **推荐组件**：SearchBar、PositionTable、LedgerDrawer、RelatedOrderLink。
- **状态覆盖**：data unavailable；mismatch。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：只读为主。
- **路由 / 上下文**：→OTC detail / User360。
- **关键禁止**：不在列表直接放高风险“万能修改”按钮。
- **验收重点**：Power 不可直接手改。
- **参考模式**：R1 / R6 / R8

### 06 赛事预测

#### A-PREDICT-001｜Market / Event 列表 · P0

- **页面目标**：管理 P0 Football Pre-match 1X2 市场生命周期。
- **视觉结构（基线）**：market filters；event/source；state axes；lock time；liquidity；risk；publish status。
- **视觉策划补充**：Market/Event 列表参考 sports data 产品的信息层级：Event/League、Template、State Axes、Lock、Liquidity、Risk、Publish Status。不要呈现博彩赔率列表。
- **关键交互（基线）**：建 draft；提交 review；打开详情；pause proposal。
- **交互策划补充**：建 Draft/提交 Review/Pause Proposal 分权；缺参数/策略时状态直接 CLOSED/Restricted。
- **推荐组件**：FilterBar、MarketTable、StateAxisBadges、LiquidityBadge、ActionMenu。
- **状态覆盖**：policy/param missing→closed；source unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：Market publish 需要相应权限/审批。
- **路由 / 上下文**：→A-PREDICT-002。
- **关键禁止**：不在列表直接放高风险“万能修改”按钮。
- **验收重点**：P0 template 只允许 Football pre-match 1X2。
- **参考模式**：R1 / R6 / R8 / R4 / R5

#### A-PREDICT-002｜Market 详情 · P0

- **页面目标**：看三方向、订单结构、流动性、关联账户、锁定评估和快照。
- **视觉结构（基线）**：event header；Home/Draw/Away pools；liquidity；cluster summary；orders；snapshots；allowed actions。
- **视觉策划补充**：对象头展示赛事与 Home/Draw/Away；下方分 Tab：Liquidity、Orders、Snapshots、Risk、Audit。三方向池用中性横向对比，不用红绿投注盘。
- **关键交互（基线）**：运行 lock evaluation；pause；打开 result/settlement。
- **交互策划补充**：Lock evaluation、pause、进入 Result/Settlement 都基于 allowed_actions；低流动性时顶部明确风险和阻断。
- **推荐组件**：MarketHeader、ThreeWayPool、Tabs、LiquidityPanel、SnapshotTable、ActionPanel。
- **状态覆盖**：low liquidity；cluster review；source issue；locked。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：反作弊阈值/完整图谱不对普通运营公开。
- **路由 / 上下文**：→A-PREDICT-003/004。
- **关键禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **验收重点**：锁定失败要有明确 reason 和后续 refund 路径。
- **参考模式**：R1 / R6 / R8 / R4 / R5

#### A-PREDICT-003｜Result / Settlement · P0

- **页面目标**：接收/复核 Result，计算 Settlement，确认 posting 和 reconciliation。
- **视觉结构（基线）**：result source；primary/secondary evidence；result status；settlement batch；T/W/L/F/R；journal；reconcile。
- **视觉策划补充**：Result Source / Evidence / Result Status / Settlement Batch / T-W-L-F-R / Journal / Reconcile 顺序展示，参考 Stripe dispute/refund 的事实与处理分区。
- **关键交互（基线）**：receive/review result；calculate sandbox；submit settlement approval。
- **交互策划补充**：Source conflict 进入 HELD；Sandbox calculation 与正式 approval 分开；结算执行后再看 journal/reconcile。
- **推荐组件**：EvidenceComparison、ResultStatus、SettlementBatchPanel、JournalTable、ReconcileSummary。
- **状态覆盖**：source conflict→HELD；calculation fail；reconcile diff。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：Result confirmer 和 settlement approver 分离。
- **路由 / 上下文**：→Approval/Ledger。
- **关键禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **验收重点**：未 reconciliation=0 不得关闭 batch。
- **参考模式**：R1 / R6 / R8 / R4 / R5

#### A-PREDICT-004｜Refund / Correction · P0

- **页面目标**：处理低流动性、取消、无人赢家、赛果更正等特殊路径。
- **视觉结构（基线）**：reason；affected orders；principal/fee impact；old/new result；reversal plan；approval；timeline。
- **视觉策划补充**：Refund/Correction 用“旧事实 → 原因 → 影响订单 → 新方案 → Reversal Plan → Approval → Execution Timeline”的审计型布局。
- **关键交互（基线）**：建 refund/correction；提交审批；查看 execution。
- **交互策划补充**：任何 correction 都保留旧 snapshot；partial failure 进入 case，不允许手工改成完成。
- **推荐组件**：BeforeAfterDiff、AffectedOrdersTable、ReversalPlan、ApprovalStatus、Timeline。
- **状态覆盖**：partial failure；appeal open；dependency fail。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：高危；必须证据和审批。
- **路由 / 上下文**：→Approval/Ledger/Audit。
- **关键禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **验收重点**：更正不覆盖 old snapshot；refund 保留原订单。
- **参考模式**：R1 / R6 / R8 / R4 / R5

### 07 风控 / 审批 / 参数 / 策略

#### A-RISK-001｜Risk Case · P0

- **页面目标**：集中处理用户/订单/市场风险案件。
- **视觉结构（基线）**：queue；safe summary；evidence categories；related objects；analyst decision；approver step；timeline。
- **视觉策划补充**：工作队列 + Risk Case 详情；Evidence 按类别折叠，关联对象与策略版本固定可见。风险颜色只做 Badge/边栏，不整页红。
- **关键交互（基线）**：review；hold request；recommend approve/reject；escalate。
- **交互策划补充**：Analyst 推荐与 Approver 决策分离；证据不可用或策略冲突时 fail closed。
- **推荐组件**：RiskQueue、EvidenceAccordion、PolicyMeta、RecommendationPanel、ApprovalLink。
- **状态覆盖**：evidence unavailable；policy conflict。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：analyst/approver separation；不暴露模型权重。
- **路由 / 上下文**：→User/OTC/Prediction/Approval。
- **关键禁止**：不使用卡片瀑布替代表格；优先级不能只靠颜色。
- **验收重点**：用户可见 reason 与内部 reason 分离。
- **参考模式**：R1 / R6 / R8

#### A-APPROVAL-001｜审批中心 · P0

- **页面目标**：统一处理高风险参数、账本、风险、结算、更正和发布审批。
- **视觉结构（基线）**：inbox；type；risk；requester；impact diff；evidence；discussion；decision；execution state。
- **视觉策划补充**：审批中心采用 Inbox + Detail：请求类型/发起人/影响 Diff/证据/讨论/决策/执行状态。自审禁止状态要明确。
- **关键交互（基线）**：approve/reject/request changes；MFA。
- **交互策划补充**：Approve/Reject/Request Changes 前必须检查对象版本；高风险决策触发 MFA；批准后执行状态继续可追踪。
- **推荐组件**：ApprovalInbox、ImpactDiff、EvidencePanel、Discussion、DecisionBar、ExecutionTimeline。
- **状态覆盖**：state changed；self-approval blocked；execution failed。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：SoD；申请人不可审批自己的申请。
- **路由 / 上下文**：执行后跳关联对象。
- **关键禁止**：Approved 不等于 Executed；不隐藏执行失败。
- **验收重点**：approved ≠ executed；执行失败必须显示 failed。
- **参考模式**：R1 / R6 / R8

#### A-CONFIG-001｜Parameter Center · Definition/Candidate · P0

- **页面目标**：查看参数定义、创建候选值和仿真，不直接生效。
- **视觉结构（基线）**：namespace tree；Definition；current release；candidate；scope；validation；simulation。
- **视觉策划补充**：参数中心像配置 IDE，但不做代码编辑器：左侧 namespace tree，中间 Definition/Candidate 表单，右侧 current release/validation/simulation 摘要。
- **关键交互（基线）**：新建 candidate；编辑草案；simulate；submit release。
- **交互策划补充**：保存 Candidate 不生效；simulate 后才能 submit release；null/TBC/closed 用专属 Badge。
- **推荐组件**：NamespaceTree、DefinitionForm、CandidateEditor、ValidationPanel、SimulationPanel。
- **状态覆盖**：TBC/null；validation fail；scope conflict。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：Editor 可编辑 candidate；不能 activate。
- **路由 / 上下文**：→A-CONFIG-002。
- **关键禁止**：不做“保存即上线”；TBC/null 必须显式。
- **验收重点**：保存不生效；TBC 生产值必须 null。
- **参考模式**：R1 / R6 / R8

#### A-CONFIG-002｜Parameter Release / Snapshot · P0

- **页面目标**：审批、排期、激活、暂停、回滚不可变参数发布。
- **视觉结构（基线）**：release diff；scope；approvals；effective time；snapshots；gray scope；pause/rollback。
- **视觉策划补充**：Release Detail 强调版本 Diff、Scope、Approvals、Effective Time、Snapshots、Gray Scope、Rollback。Active/Paused/Rolled Back 显著但克制。
- **关键交互（基线）**：submit approval；activate（授权角色）；pause；rollback。
- **交互策划补充**：Activate/Pause/Rollback 依据角色，均有二次确认和 execution job；回滚也是新事件不是覆盖历史。
- **推荐组件**：ReleaseHeader、DiffViewer、ScopePanel、ApprovalTimeline、ActivationBar、SnapshotList。
- **状态覆盖**：dependency invalid；scope conflict；rollback running。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：Editor/Approver/Release Operator 分离。
- **路由 / 上下文**：→Approval/Audit。
- **关键禁止**：不能直接编辑 Active release；Rollback 不覆盖历史。
- **验收重点**：Release immutable；新值用新 release，不改旧 release。
- **参考模式**：R1 / R6 / R8

#### A-POLICY-001｜地区 / KYC / 保护策略 · P0

- **页面目标**：查看地区、渠道、年龄、KYC、限额、冷静期、自我排除的策略决策。
- **视觉结构（基线）**：policy matrix；evidence；decision；version；user preview；protection rules。
- **视觉策划补充**：策略矩阵展示 Region × KYC × Feature/Protection，不使用大段规则文本。下方显示 evidence、版本和 user preview。
- **关键交互（基线）**：创建策略候选/案件；查看评估；不能手选更宽松结果。
- **交互策划补充**：创建 Candidate/Case；不能在界面直接手选更宽松的最终评估；evidence stale 时 fail closed。
- **推荐组件**：PolicyMatrix、EvidenceMeta、UserPreview、CandidateAction。
- **状态覆盖**：evidence stale；timeout；conflict→fail closed。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：Policy roles；默认 deny。
- **路由 / 上下文**：→Approval/User360。
- **关键禁止**：不做“全球开放”单一开关；无证据不能显示 ALLOW。
- **验收重点**：无证据不能 ALLOW；用户保护跨渠道。
- **参考模式**：R1 / R6 / R8

### 08 客服 / 审计 / 运维

#### A-SUPPORT-001｜工单队列 · P0

- **页面目标**：按 SLA、类别、风险和负责人处理用户工单。
- **视觉结构（基线）**：filters；queue；priority；SLA；user/object；assignee。
- **视觉策划补充**：客服工作队列：筛选、优先级/SLA、用户、关联对象、assignee；中频预览用 Drawer。
- **关键交互（基线）**：领取；转派；打开；批量仅低风险动作。
- **交互策划补充**：领取/转派解决并发；批量动作只限低风险，例如转派/标签，不批量结案高风险申诉。
- **推荐组件**：FilterBar、TicketTable、SlaBadge、AssigneeControl、PreviewDrawer。
- **状态覆盖**：Empty；queue fail；assignment conflict。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：客服只见需要的信息。
- **路由 / 上下文**：→A-SUPPORT-002。
- **关键禁止**：不使用卡片瀑布替代表格；优先级不能只靠颜色。
- **验收重点**：相同对象/问题可关联已有 case。
- **参考模式**：R1 / R6 / R8 / R2

#### A-SUPPORT-002｜工单详情 · P0

- **页面目标**：处理回复、补件、升级风控、结论和关联对象。
- **视觉结构（基线）**：conversation；user-visible/internal note；attachments；related objects；timeline；case links。
- **视觉策划补充**：详情以 Conversation 为主，右侧/上方放用户与关联对象摘要；Internal Note 和 User-visible Reply 视觉明确区分。
- **关键交互（基线）**：回复；补件；升级风险；关闭。
- **交互策划补充**：回复、补件、升级风险、关闭都有状态约束；关闭前必须有用户可见结论。
- **推荐组件**：TicketHeader、ConversationThread、InternalNoteComposer、RelatedObjects、Timeline。
- **状态覆盖**：attachment fail；permission；state conflict。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：客服不能改业务终态，只能调用授权 workflow/case。
- **路由 / 上下文**：→User/Order/Ledger/Risk。
- **关键禁止**：不把所有字段塞一张无限长表；历史字段不可编辑。
- **验收重点**：关闭前需要结论；内部 note 与用户回复严格区分。
- **参考模式**：R1 / R6 / R8 / R2

#### A-AUDIT-001｜审计日志 · P0

- **页面目标**：按对象、人员、动作、时间追踪关键变化。
- **视觉结构（基线）**：filters；event table；before/after；request/case/approval；export task。
- **视觉策划补充**：紧凑日志表格：时间、actor、action、object、request/case/approval、result；before/after 在 Drawer 中查看。
- **关键交互（基线）**：查询；打开事件；发起受控导出。
- **交互策划补充**：查询与受控导出；导出是异步任务。无权限字段显示掩码/不可见，不显示空白假数据。
- **推荐组件**：CompactTable、AdvancedFilter、EventDrawer、BeforeAfterDiff、ExportTask。
- **状态覆盖**：No permission；export pending。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：Auditor scope；敏感字段脱敏。
- **路由 / 上下文**：→关联对象。
- **关键禁止**：不在列表直接放高风险“万能修改”按钮。
- **验收重点**：审计日志不可编辑/删除。
- **参考模式**：R1 / R6 / R8

#### A-OPS-001｜异步任务 / 对账 / 系统状态 · P0

- **页面目标**：看 async job、outbox/webhook、reconciliation 和关键依赖状态。
- **视觉结构（基线）**：environment；jobs；DLQ；reconcile；dependency health；retry/case links。
- **视觉策划补充**：运维控制台仍保持白底：Environment、Jobs、DLQ、Reconcile、Dependency Health 分卡/表。不要黑色 NOC 大屏。
- **关键交互（基线）**：重试允许的任务；建 case；查看证据。
- **交互策划补充**：Retry 只对允许的任务开放；高风险失败创建 case/approval；dependency 状态可下钻证据。
- **推荐组件**：EnvironmentBanner、JobTable、DlqTable、HealthPanel、RetryAction、CaseLink。
- **状态覆盖**：failed/DLQ/dependency unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable / Invalid / Confirm / Submitting / Processing / Success / Failed / State Changed`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：资金效果任务默认不能随便 replay。
- **路由 / 上下文**：→Audit/Approval。
- **关键禁止**：不能把重试设计成“重做用户订单”；资金任务需额外确认。
- **验收重点**：重试必须防重复业务效果。
- **参考模式**：R1 / R6 / R8

#### A-REPORT-001｜运营报表 · P1

- **页面目标**：查看留存、业务量、异常、退款等分析。
- **视觉结构（基线）**：report filters；charts；definition link；export。
- **视觉策划补充**：P1 报表页：顶部定义/时间/筛选，中部少量高价值图表，下方数据表与导出；每个图表标口径、时间和更新时间。
- **关键交互（基线）**：筛选；导出。
- **交互策划补充**：筛选、保存视图、异步导出；data delayed 显示 last refresh。
- **推荐组件**：ReportFilter、ChartCard、DefinitionLink、DataTable、ExportTask。
- **状态覆盖**：data delayed。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：按数据权限。
- **路由 / 上下文**：独立页面。
- **关键禁止**：不把报表值当权威账本或收入。
- **验收重点**：报表不是账本/收入权威。
- **参考模式**：R1 / R6 / R8

#### A-GROWTH-001｜Referral / Team 运营 · P1

- **页面目标**：查看关系、候选/HELD/PAID 与反作弊。
- **视觉结构（基线）**：relationship；reward state；campaign；risk。
- **视觉策划补充**：Referral/Team 运营仍按对象列表：关系、Reward State、Campaign、Risk，不做金字塔树。
- **关键交互（基线）**：查看；建 case/approval。
- **交互策划补充**：异常关系/奖励进入 case/approval；budget closed 时只读历史。
- **推荐组件**：RelationshipTable、RewardBadge、CampaignFilter、RiskAction。
- **状态覆盖**：budget closed。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：不能直接补发。
- **路由 / 上下文**：→Risk/User360。
- **关键禁止**：不在列表直接放高风险“万能修改”按钮。
- **验收重点**：不鼓励把增长做成拉人头看板。
- **参考模式**：R1 / R6 / R8

#### A-MIGRATION-001｜APT Migration · Future/CLOSED

- **页面目标**：未来管理 APT-I→APT-C 请求、finality 和防双花。
- **视觉结构（基线）**：requests；wallet；broadcast/finality；journal；exceptions。
- **视觉策划补充**：Future/CLOSED 默认就是关闭对象页：显示 Gate 状态和历史预留字段；只有 sandbox 才展示 request/wallet/broadcast/finality/journal 表格。
- **关键交互（基线）**：查看；case/approval。
- **交互策划补充**：正式入口保持关闭；任何异常通过 Ledger/Approval 流程，不提供直接改 finality。
- **推荐组件**：ClosedState、MigrationTable、FinalityBadge、JournalLink。
- **状态覆盖**：CLOSED by default。 原型通用 Frame：`Default / Loading / Empty / Error / No Permission / Dependency Unavailable`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限 / 业务边界**：高风险。
- **路由 / 上下文**：→Ledger/Approval。
- **关键禁止**：不在列表直接放高风险“万能修改”按钮。
- **验收重点**：P0 不显示可执行 create 按钮。
- **参考模式**：R1 / R6 / R8

## 9. 统一组件库规划

### 9.1 Mobile / H5

`GainodeAppBar`、`BottomNav`、`AdmissionHero`、`ObjectSummaryCard`、`StatusBadge`、`RiskNotice`、`InlineNotice`、`LabeledInput`、`OtpInput`、`Stepper`、`UploadCard`、`SegmentedControl`、`FilterSheet`、`MatchCard`、`ThreeWaySelector`、`DataTabs`、`ConfirmSummary`、`ProcessingState`、`ResultHero`、`Timeline`、`OrderCard`、`LedgerRow`、`ResourceMeter`、`TicketThread`、`EmptyState`、`ErrorState`、`RestrictedState`、`Skeleton`。

### 9.2 Admin

`AdminShell`、`Sidebar`、`PageHeader`、`KpiCard`、`FilterBar`、`SearchBar`、`SavedView`、`DataTable`、`CompactAuditTable`、`ObjectHeader`、`Descriptions`、`Tabs`、`StatusBadge`、`RiskBadge`、`PreviewDrawer`、`DecisionPanel`、`EvidencePanel`、`ImpactDiff`、`DiffViewer`、`Timeline`、`ApprovalRoute`、`ApprovalInbox`、`JobStatus`、`HealthPanel`、`ExportTask`、`Empty/Loading/Error/NoPermission`。

## 10. 主流程串联

### Mobile

- **Flow A 新用户**：注册 → OTP → KYC → KYC Status → Home
- **Flow B Robot**：Home → Robot → Start/Stop；Robot → Upgrade → Result → Ledger/Activity；Robot → Rewards → Claim → Ledger
- **Flow C Prediction**：List → 1X2 Detail → Confirm → Order Detail → Settled；异常 → Refund/Correction → Ledger → Appeal
- **Flow D OTC**：APT → OTC Market → Input → Confirm → Submitted → Review/Matching → Partial/Completed；异常 → Cancel/Dispute → Ledger/Support
- **Flow E Support**：任何对象 → Create Ticket → Ticket Detail → Waiting User/Review → Resolved → Closed

### Admin

- **Flow A KYC**：Workbench → KYC Queue → User360 → Review → Approve/Reject/Needs Info → Audit
- **Flow B Robot 异常**：Robot List → Robot Detail → Risk/Admin Case → Approval → Execution → Timeline
- **Flow C OTC Review**：OTC List → Detail → Risk Review → Decision/Approval → Asset & Power → Audit
- **Flow D Prediction Settlement**：Market → Detail → Result → Settlement Batch → Approval → Journal/Reconciliation
- **Flow E Refund/Correction**：Market/Order → Proposal → Approval → Reversal/New Posting → User Notice
- **Flow F Parameter Release**：Definition → Candidate → Simulation → Release Proposal → Approval → Activate → Snapshot → Pause/Rollback

## 11. 原型制作顺序建议

1. 先固化 Design Tokens + Logo Mapping + 组件库。
2. 用四个一级主菜单作为视觉回归基准，先完成所有 Root 页面。
3. 完成 Mobile 的 Auth/KYC、Robot、Prediction、APT/OTC、Security/Support 全 P0。
4. 完成 Admin Shell 与 List/Detail/Drawer/Approval 等核心模板，再逐页落地 31 页。
5. 最后补齐所有状态 Frame、Unknown Result、No Permission、P1/Future Closed。

## 12. 最终视觉验收红线

- 75 个 Page ID 全部有独立 Route/Frame。
- P0 页面不出现“其它页面同理”的占位设计。
- Prediction 三方向并列；Draw 不缺失；无默认必赢推荐。
- Robot 不出现 APR/APY/固定回报/回本周期。
- APT 不等同现金收益；参考估值必须明确为参考/估算。
- OTC Submitted 不等于 Completed；Partial 的资产与 Power 变化必须可看懂。
- Error / Restricted / Unknown Result 三种视觉和文案必须不同。
- Admin 高风险动作全部经过 Proposal/Approval/Audit，不提供直接改最终结果的按钮。
- 所有 Mock 数值必须标 Demo/Sandbox/Fixture，不能伪装成生产参数。

# 14. 多语言与国际化基线（I18N / L10N）
支持语言：`zh-CN / en-US / ja-JP / ko-KR / th-TH / de-DE / fr-FR`。七种语言覆盖 Mobile/H5 44 页 + Admin 31 页，共 75 页。
## 14.1 全局原则
- 用户显式选择 > 账号语言 > 设备/浏览器语言 > en-US fallback。
- 七种语言共用同一 Page ID / Route / object_id，语言不改变业务状态、权限、参数或 API。
- 语言切换保留 Route、Tab、Filter、Scroll、表单输入；高风险 Confirm/Consent 重新渲染。
- ID / code / version / hash / parameter_key / audit_event_code 不翻译。
- zh-CN 为源文案，所有显示文案通过 i18n key 管理。

## 14.2 核心 UI 七语字典
| Key | 中文 | English | 日本語 | 한국어 | ไทย | Deutsch | Français |
|---|---|---|---|---|---|---|---|
| nav.home | 首页 | Home | ホーム | 홈 | หน้าแรก | Start | Accueil |
| nav.robot | Robot | Robot | Robot | Robot | Robot | Robot | Robot |
| nav.prediction | 预测 | Prediction | 予測 | 예측 | การคาดการณ์ | Prognose | Prédictions |
| nav.me | 我的 | Me | マイページ | 내 정보 | บัญชี | Konto | Compte |
| action.login | 登录 | Log in | ログイン | 로그인 | เข้าสู่ระบบ | Anmelden | Se connecter |
| action.signup | 注册 | Sign up | 登録 | 회원가입 | สมัครสมาชิก | Registrieren | S’inscrire |
| action.continue | 继续 | Continue | 続ける | 계속 | ดำเนินการต่อ | Weiter | Continuer |
| action.confirm | 确认 | Confirm | 確認 | 확인 | ยืนยัน | Bestätigen | Confirmer |
| action.cancel | 取消 | Cancel | キャンセル | 취소 | ยกเลิก | Abbrechen | Annuler |
| action.back | 返回 | Back | 戻る | 뒤로 | กลับ | Zurück | Retour |
| action.retry | 重试 | Retry | 再試行 | 다시 시도 | ลองอีกครั้ง | Erneut versuchen | Réessayer |
| action.view_details | 查看详情 | View details | 詳細を見る | 상세 보기 | ดูรายละเอียด | Details anzeigen | Voir les détails |
| status.open | 开放 | Open | オープン | 오픈 | เปิด | Offen | Ouvert |
| status.paused | 已暂停 | Paused | 一時停止 | 일시 중지 | หยุดชั่วคราว | Pausiert | En pause |
| status.locked | 已锁定 | Locked | ロック済み | 잠김 | ล็อกแล้ว | Gesperrt | Verrouillé |
| status.review | 审核中 | Under review | 審査中 | 검토 중 | อยู่ระหว่างตรวจสอบ | In Prüfung | En cours d’examen |
| status.processing | 处理中 | Processing | 処理中 | 처리 중 | กำลังดำเนินการ | Wird verarbeitet | Traitement en cours |
| status.success | 成功 | Success | 完了 | 성공 | สำเร็จ | Erfolgreich | Réussi |
| status.failed | 失败 | Failed | 失敗 | 실패 | ล้มเหลว | Fehlgeschlagen | Échec |
| status.restricted | 受限 | Restricted | 利用制限 | 제한됨 | ถูกจำกัด | Eingeschränkt | Restreint |
| prediction.home_win | 主胜 | Home Win | ホーム勝利 | 홈 승 | เจ้าบ้านชนะ | Heimsieg | Victoire domicile |
| prediction.draw | 平局 | Draw | 引き分け | 무승부 | เสมอ | Unentschieden | Match nul |
| prediction.away_win | 客胜 | Away Win | アウェイ勝利 | 원정 승 | ทีมเยือนชนะ | Auswärtssieg | Victoire extérieur |
| common.loading | 加载中 | Loading | 読み込み中 | 불러오는 중 | กำลังโหลด | Wird geladen | Chargement |
| common.no_data | 暂无数据 | No data yet | データはありません | 데이터 없음 | ยังไม่มีข้อมูล | Noch keine Daten | Aucune donnée |
| common.unknown_result | 系统正在确认本次操作结果，请不要重复提交 | We are confirming the result. Do not submit again. | 処理結果を確認中です。再送信しないでください。 | 처리 결과를 확인 중입니다. 다시 제출하지 마세요. | กำลังตรวจสอบผลลัพธ์ กรุณาอย่าส่งซ้ำ | Das Ergebnis wird geprüft. Bitte nicht erneut senden. | Nous vérifions le résultat. Ne soumettez pas à nouveau. |

## 14.3 75 个页面标题七语映射
| Page ID | 中文 | English | 日本語 | 한국어 | ไทย | Deutsch | Français |
|---|---|---|---|---|---|---|---|
| M-AUTH-001 | 登录 | Login | ログイン | 로그인 | เข้าสู่ระบบ | Anmeldung | Connexion |
| M-AUTH-002 | 注册 | Sign Up | 登録 | 회원가입 | สมัครสมาชิก | Registrierung | Inscription |
| M-AUTH-003 | OTP 验证 | OTP Verification | OTP認証 | OTP 인증 | ยืนยัน OTP | OTP-Verifizierung | Vérification OTP |
| M-AUTH-004 | 找回 / 重置密码 | Forgot / Reset Password | パスワード再設定 | 비밀번호 찾기 / 재설정 | ลืม / รีเซ็ตรหัสผ่าน | Passwort vergessen / zurücksetzen | Mot de passe oublié / réinitialisation |
| M-AUTH-005 | MFA 二次验证 | MFA Verification | MFA追加認証 | MFA 추가 인증 | การยืนยัน MFA | MFA-Zweitprüfung | Vérification MFA |
| M-KYC-001 | KYC 与功能准入概览 | KYC & Access Overview | KYC・利用資格概要 | KYC 및 기능 이용 개요 | ภาพรวม KYC และสิทธิ์การใช้งาน | KYC- & Zugangsübersicht | Aperçu KYC et accès |
| M-KYC-002 | KYC 资料提交 / 补件 | KYC Submission / Additional Documents | KYC提出 / 追加書類 | KYC 제출 / 추가 서류 | ส่ง KYC / เอกสารเพิ่มเติม | KYC-Einreichung / Nachreichung | Soumission KYC / pièces complémentaires |
| M-KYC-003 | KYC 状态 / 结果 | KYC Status / Result | KYCステータス / 結果 | KYC 상태 / 결과 | สถานะ / ผล KYC | KYC-Status / Ergebnis | Statut / résultat KYC |
| M-HOME-001 | 首页 | Home | ホーム | 홈 | หน้าแรก | Start | Accueil |
| M-NOTICE-001 | 消息中心 | Notifications | 通知センター | 알림 센터 | ศูนย์การแจ้งเตือน | Mitteilungen | Notifications |
| M-ROBOT-001 | Robot 概览 | Robot Overview | Robot 概要 | Robot 개요 | ภาพรวม Robot | Robot-Übersicht | Vue d’ensemble Robot |
| M-ROBOT-002 | Robot 启动 / 停止确认 | Robot Start / Stop Confirmation | Robot 開始 / 停止確認 | Robot 시작 / 중지 확인 | ยืนยันเริ่ม / หยุด Robot | Robot Start / Stopp bestätigen | Confirmation démarrage / arrêt Robot |
| M-ROBOT-003 | Robot 升级 | Robot Upgrade | Robot アップグレード | Robot 업그레이드 | อัปเกรด Robot | Robot-Upgrade | Mise à niveau Robot |
| M-ROBOT-004 | 升级结果 | Upgrade Result | アップグレード結果 | 업그레이드 결과 | ผลการอัปเกรด | Upgrade-Ergebnis | Résultat de la mise à niveau |
| M-ROBOT-005 | 56 级等级地图 | 56-Level Map | 56レベルマップ | 56레벨 맵 | แผนที่ 56 ระดับ | 56-Level-Karte | Carte des 56 niveaux |
| M-ROBOT-006 | Rewards & Claim | Rewards & Claim | 報酬・受取 | 보상 및 수령 | รางวัลและการรับ | Belohnungen & Abholung | Récompenses & Réclamation |
| M-ROBOT-007 | Robot 活动与记录 | Robot Activity & History | Robot アクティビティ・履歴 | Robot 활동 및 기록 | กิจกรรมและประวัติ Robot | Robot-Aktivität & Verlauf | Activité & historique Robot |
| M-PREDICT-001 | Prediction 赛事列表 | Prediction Match List | Prediction 試合一覧 | Prediction 경기 목록 | รายการแข่งขัน Prediction | Prediction-Spielübersicht | Liste des matchs Prediction |
| M-PREDICT-002 | 赛事详情 · Football 1X2 | Match Detail · Football 1X2 | 試合詳細 · Football 1X2 | 경기 상세 · Football 1X2 | รายละเอียดการแข่งขัน · Football 1X2 | Spieldetails · Football 1X2 | Détail du match · Football 1X2 |
| M-PREDICT-003 | Prediction 确认 | Prediction Confirmation | Prediction 確認 | Prediction 확인 | ยืนยัน Prediction | Prediction bestätigen | Confirmation Prediction |
| M-PREDICT-004 | 我的 Prediction | My Predictions | マイ Prediction | 내 Prediction | Prediction ของฉัน | Meine Predictions | Mes Predictions |
| M-PREDICT-005 | Prediction 订单详情 | Prediction Order Detail | Prediction 注文詳細 | Prediction 주문 상세 | รายละเอียดคำสั่ง Prediction | Prediction-Auftragsdetails | Détail de l’ordre Prediction |
| M-PREDICT-006 | 异常 / 退款 / 更正详情 | Exception / Refund / Correction Detail | 例外 / 返金 / 訂正詳細 | 예외 / 환불 / 정정 상세 | รายละเอียดข้อผิดปกติ / คืนเงิน / แก้ไข | Ausnahme / Erstattung / Korrektur | Exception / remboursement / correction |
| M-ME-001 | 我的 | Me | マイページ | 내 정보 | บัญชี | Konto | Compte |
| M-ASSET-001 | APT 资产 | APT Assets | APT 資産 | APT 자산 | สินทรัพย์ APT | APT-Vermögen | Actifs APT |
| M-ASSET-002 | APT 流水列表 | APT Ledger List | APT 台帳一覧 | APT 원장 목록 | รายการบัญชี APT | APT-Buchungsliste | Liste du grand livre APT |
| M-ASSET-003 | APT 流水详情 | APT Ledger Detail | APT 台帳詳細 | APT 원장 상세 | รายละเอียดบัญชี APT | APT-Buchungsdetails | Détail du grand livre APT |
| M-POWER-001 | Power | Power | Power | Power | Power | Power | Power |
| M-OTC-001 | OTC 市场 | OTC Market | OTC マーケット | OTC 마켓 | ตลาด OTC | OTC-Markt | Marché OTC |
| M-OTC-002 | OTC 下单输入 | OTC Order Entry | OTC 注文入力 | OTC 주문 입력 | กรอกคำสั่ง OTC | OTC-Auftragseingabe | Saisie d’ordre OTC |
| M-OTC-003 | OTC 订单确认 | OTC Order Confirmation | OTC 注文確認 | OTC 주문 확인 | ยืนยันคำสั่ง OTC | OTC-Auftrag bestätigen | Confirmation d’ordre OTC |
| M-OTC-004 | OTC 提交结果 | OTC Submission Result | OTC 送信結果 | OTC 제출 결과 | ผลการส่ง OTC | OTC-Übermittlungsergebnis | Résultat de soumission OTC |
| M-OTC-005 | 我的 OTC 订单 | My OTC Orders | マイ OTC 注文 | 내 OTC 주문 | คำสั่ง OTC ของฉัน | Meine OTC-Aufträge | Mes ordres OTC |
| M-OTC-006 | OTC 订单详情 | OTC Order Detail | OTC 注文詳細 | OTC 주문 상세 | รายละเอียดคำสั่ง OTC | OTC-Auftragsdetails | Détail d’ordre OTC |
| M-SEC-001 | 安全中心 | Security Center | セキュリティセンター | 보안 센터 | ศูนย์ความปลอดภัย | Sicherheitscenter | Centre de sécurité |
| M-SEC-002 | MFA / 设备 / Session 管理 | MFA / Device / Session Management | MFA / デバイス / セッション管理 | MFA / 기기 / 세션 관리 | จัดการ MFA / อุปกรณ์ / Session | MFA / Geräte / Sitzungen | Gestion MFA / appareils / sessions |
| M-SUPPORT-001 | 帮助中心 / 工单列表 | Help Center / Tickets | ヘルプセンター / チケット | 도움말 센터 / 티켓 | ศูนย์ช่วยเหลือ / ทิกเก็ต | Hilfezentrum / Tickets | Centre d’aide / tickets |
| M-SUPPORT-002 | 创建工单 / 申诉 | Create Ticket / Appeal | チケット作成 / 異議申立て | 티켓 생성 / 이의 제기 | สร้างทิกเก็ต / อุทธรณ์ | Ticket erstellen / Einspruch | Créer un ticket / recours |
| M-SUPPORT-003 | 工单详情 | Ticket Detail | チケット詳細 | 티켓 상세 | รายละเอียดทิกเก็ต | Ticketdetails | Détail du ticket |
| M-SETTINGS-001 | 设置 | Settings | 設定 | 설정 | การตั้งค่า | Einstellungen | Paramètres |
| M-AI-001 | AI 数据 / Signal 详情 | AI Data / Signal Detail | AI データ / Signal 詳細 | AI 데이터 / Signal 상세 | รายละเอียดข้อมูล AI / Signal | AI-Daten / Signal-Details | Données AI / détail Signal |
| M-GROWTH-001 | Referral / Team | Referral / Team | Referral / Team | Referral / Team | Referral / Team | Referral / Team | Referral / Team |
| M-PREDICT-FREE-001 | 免费 YES/NO | Free YES/NO | 無料 YES/NO | 무료 YES/NO | YES/NO ฟรี | Kostenloses YES/NO | YES/NO gratuit |
| M-MIGRATION-001 | APT-I → APT-C Migration | APT-I → APT-C Migration | APT-I → APT-C Migration | APT-I → APT-C Migration | APT-I → APT-C Migration | APT-I → APT-C Migration | Migration APT-I → APT-C |
| A-WORK-001 | 运营总览 | Operations Overview | 運用概要 | 운영 개요 | ภาพรวมการดำเนินงาน | Betriebsübersicht | Vue d’ensemble des opérations |
| A-WORK-002 | 今日待办 | Today’s Tasks | 本日のタスク | 오늘 할 일 | งานวันนี้ | Heutige Aufgaben | Tâches du jour |
| A-USER-001 | 用户列表 | User List | ユーザー一覧 | 사용자 목록 | รายชื่อผู้ใช้ | Benutzerliste | Liste des utilisateurs |
| A-USER-002 | 用户 360 | User 360 | ユーザー 360 | 사용자 360 | ผู้ใช้ 360 | Benutzer 360 | Utilisateur 360 |
| A-KYC-001 | KYC 审核队列 | KYC Review Queue | KYC 審査キュー | KYC 검토 대기열 | คิวตรวจสอบ KYC | KYC-Prüfwarteschlange | File de revue KYC |
| A-LEDGER-001 | 资产总览 | Asset Overview | 資産概要 | 자산 개요 | ภาพรวมสินทรัพย์ | Vermögensübersicht | Vue d’ensemble des actifs |
| A-LEDGER-002 | APT 账户与流水 | APT Accounts & Ledger | APT 口座・台帳 | APT 계정 및 원장 | บัญชีและบัญชีแยกประเภท APT | APT-Konten & Buchungen | Comptes & grand livre APT |
| A-LEDGER-003 | 池子与对账 | Pools & Reconciliation | プール・照合 | 풀 및 조정 | พูลและการกระทบยอด | Pools & Abstimmung | Pools & rapprochement |
| A-LEDGER-004 | 更正 / 冲正申请 | Correction / Reversal Request | 訂正 / 取消申請 | 정정 / 역분개 요청 | คำขอแก้ไข / กลับรายการ | Korrektur- / Stornoantrag | Demande de correction / contre-passation |
| A-ROBOT-001 | Robot 列表 | Robot List | Robot 一覧 | Robot 목록 | รายการ Robot | Robot-Liste | Liste Robot |
| A-ROBOT-002 | Robot 详情 | Robot Detail | Robot 詳細 | Robot 상세 | รายละเอียด Robot | Robot-Details | Détail Robot |
| A-ROBOT-003 | Reward / Claim 运营 | Reward / Claim Operations | Reward / Claim 運用 | Reward / Claim 운영 | การดำเนินงาน Reward / Claim | Reward / Claim Betrieb | Opérations Reward / Claim |
| A-OTC-001 | OTC 订单列表 | OTC Order List | OTC 注文一覧 | OTC 주문 목록 | รายการคำสั่ง OTC | OTC-Auftragsliste | Liste des ordres OTC |
| A-OTC-002 | OTC 订单详情 / 审核 | OTC Order Detail / Review | OTC 注文詳細 / 審査 | OTC 주문 상세 / 검토 | รายละเอียด / ตรวจสอบคำสั่ง OTC | OTC-Auftrag / Prüfung | Détail / revue d’ordre OTC |
| A-POWER-001 | Power 账户与流水 | Power Accounts & Ledger | Power 口座・台帳 | Power 계정 및 원장 | บัญชีและบัญชีแยกประเภท Power | Power-Konten & Buchungen | Comptes & grand livre Power |
| A-PREDICT-001 | Market / Event 列表 | Market / Event List | Market / Event 一覧 | Market / Event 목록 | รายการ Market / Event | Market- / Event-Liste | Liste Market / Event |
| A-PREDICT-002 | Market 详情 | Market Detail | Market 詳細 | Market 상세 | รายละเอียด Market | Market-Details | Détail Market |
| A-PREDICT-003 | Result / Settlement | Result / Settlement | Result / Settlement | Result / Settlement | Result / Settlement | Result / Settlement | Result / Settlement |
| A-PREDICT-004 | Refund / Correction | Refund / Correction | Refund / Correction | Refund / Correction | Refund / Correction | Refund / Correction | Refund / Correction |
| A-RISK-001 | Risk Case | Risk Case | Risk Case | Risk Case | Risk Case | Risk Case | Risk Case |
| A-APPROVAL-001 | 审批中心 | Approval Center | 承認センター | 승인 센터 | ศูนย์อนุมัติ | Freigabecenter | Centre d’approbation |
| A-CONFIG-001 | Parameter Center · Definition/Candidate | Parameter Center · Definition/Candidate | Parameter Center · 定義/Candidate | Parameter Center · 정의/Candidate | Parameter Center · Definition/Candidate | Parameter Center · Definition/Candidate | Parameter Center · Definition/Candidate |
| A-CONFIG-002 | Parameter Release / Snapshot | Parameter Release / Snapshot | Parameter Release / Snapshot | Parameter Release / Snapshot | Parameter Release / Snapshot | Parameter Release / Snapshot | Parameter Release / Snapshot |
| A-POLICY-001 | 地区 / KYC / 保护策略 | Region / KYC / Protection Policy | 地域 / KYC / 保護ポリシー | 지역 / KYC / 보호 정책 | นโยบายภูมิภาค / KYC / การป้องกัน | Region / KYC / Schutzrichtlinie | Politique région / KYC / protection |
| A-SUPPORT-001 | 工单队列 | Ticket Queue | チケットキュー | 티켓 대기열 | คิวทิกเก็ต | Ticket-Warteschlange | File de tickets |
| A-SUPPORT-002 | 工单详情 | Ticket Detail | チケット詳細 | 티켓 상세 | รายละเอียดทิกเก็ต | Ticketdetails | Détail du ticket |
| A-AUDIT-001 | 审计日志 | Audit Log | 監査ログ | 감사 로그 | บันทึกการตรวจสอบ | Audit-Log | Journal d’audit |
| A-OPS-001 | 异步任务 / 对账 / 系统状态 | Async Jobs / Reconciliation / System Status | 非同期ジョブ / 照合 / システム状態 | 비동기 작업 / 조정 / 시스템 상태 | งานอะซิงก์ / กระทบยอด / สถานะระบบ | Async-Jobs / Abstimmung / Systemstatus | Tâches asynchrones / rapprochement / état système |
| A-REPORT-001 | 运营报表 | Operations Reports | 運用レポート | 운영 보고서 | รายงานการดำเนินงาน | Betriebsberichte | Rapports d’exploitation |
| A-GROWTH-001 | Referral / Team 运营 | Referral / Team Operations | Referral / Team 運用 | Referral / Team 운영 | การดำเนินงาน Referral / Team | Referral / Team Betrieb | Opérations Referral / Team |
| A-MIGRATION-001 | APT Migration | APT Migration | APT Migration | APT Migration | APT Migration | APT-Migration | Migration APT |

## 14.4 逐页多语言适配矩阵
| Page ID | 页面 | 本地化内容 | 交互验收 |
|---|---|---|---|
| M-AUTH-001 | 登录 | 表单 Label/错误/CTA 全量本地化；国家/地区与电话区号按 locale 展示；OTP 数字保持拉丁数字。 | 切换语言保留已输入内容与倒计时；密码/MFA 错误码映射本地文案。 |
| M-AUTH-002 | 注册 | 表单 Label/错误/CTA 全量本地化；国家/地区与电话区号按 locale 展示；OTP 数字保持拉丁数字。 | 切换语言保留已输入内容与倒计时；密码/MFA 错误码映射本地文案。 |
| M-AUTH-003 | OTP 验证 | 表单 Label/错误/CTA 全量本地化；国家/地区与电话区号按 locale 展示；OTP 数字保持拉丁数字。 | 切换语言保留已输入内容与倒计时；密码/MFA 错误码映射本地文案。 |
| M-AUTH-004 | 找回 / 重置密码 | 表单 Label/错误/CTA 全量本地化；国家/地区与电话区号按 locale 展示；OTP 数字保持拉丁数字。 | 切换语言保留已输入内容与倒计时；密码/MFA 错误码映射本地文案。 |
| M-AUTH-005 | MFA 二次验证 | 表单 Label/错误/CTA 全量本地化；国家/地区与电话区号按 locale 展示；OTP 数字保持拉丁数字。 | 切换语言保留已输入内容与倒计时；密码/MFA 错误码映射本地文案。 |
| M-KYC-001 | KYC 与功能准入概览 | KYC 文案、证件类型、补件原因、Consent 必须人工校对；姓名与证件号保留原文，不擅自翻译。 | 语言切换不得改变审核状态；如法律 Consent 文本版本变化，需重新确认。 |
| M-KYC-002 | KYC 资料提交 / 补件 | KYC 文案、证件类型、补件原因、Consent 必须人工校对；姓名与证件号保留原文，不擅自翻译。 | 语言切换不得改变审核状态；如法律 Consent 文本版本变化，需重新确认。 |
| M-KYC-003 | KYC 状态 / 结果 | KYC 文案、证件类型、补件原因、Consent 必须人工校对；姓名与证件号保留原文，不擅自翻译。 | 语言切换不得改变审核状态；如法律 Consent 文本版本变化，需重新确认。 |
| M-HOME-001 | 首页 | 卡片标题、状态、通知、CTA 全量本地化；球队/联赛优先官方本地名，缺失回退英文。 | 通知正文按 locale 拉取；切换语言保持卡片状态、滚动与深链。 |
| M-NOTICE-001 | 消息中心 | 卡片标题、状态、通知、CTA 全量本地化；球队/联赛优先官方本地名，缺失回退英文。 | 通知正文按 locale 拉取；切换语言保持卡片状态、滚动与深链。 |
| M-ROBOT-001 | Robot 概览 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| M-ROBOT-002 | Robot 启动 / 停止确认 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| M-ROBOT-003 | Robot 升级 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| M-ROBOT-004 | 升级结果 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| M-ROBOT-005 | 56 级等级地图 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| M-ROBOT-006 | Rewards & Claim | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| M-ROBOT-007 | Robot 活动与记录 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| M-PREDICT-001 | Prediction 赛事列表 | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| M-PREDICT-002 | 赛事详情 · Football 1X2 | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| M-PREDICT-003 | Prediction 确认 | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| M-PREDICT-004 | 我的 Prediction | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| M-PREDICT-005 | Prediction 订单详情 | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| M-PREDICT-006 | 异常 / 退款 / 更正详情 | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| M-ME-001 | 我的 | 账户资源、KYC、安全、Support、设置等菜单本地化；去除未批准收益/美元估值表达。 | 语言入口放 Settings；切换语言后返回本页并保留账户状态。 |
| M-ASSET-001 | APT 资产 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-ASSET-002 | APT 流水列表 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-ASSET-003 | APT 流水详情 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-POWER-001 | Power | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-OTC-001 | OTC 市场 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-OTC-002 | OTC 下单输入 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-OTC-003 | OTC 订单确认 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-OTC-004 | OTC 提交结果 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-OTC-005 | 我的 OTC 订单 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-OTC-006 | OTC 订单详情 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| M-SEC-001 | 安全中心 | 设备/浏览器/系统名称保留原名；动作、风险原因、Session 状态本地化。 | 切换语言不结束 Session；安全确认弹窗必须重新以新语言渲染。 |
| M-SEC-002 | MFA / 设备 / Session 管理 | 设备/浏览器/系统名称保留原名；动作、风险原因、Session 状态本地化。 | 切换语言不结束 Session；安全确认弹窗必须重新以新语言渲染。 |
| M-SUPPORT-001 | 帮助中心 / 工单列表 | 系统 UI 与预设分类本地化；用户自己提交的原始消息不静默机器翻译，必要时标注译文来源。 | 客服侧展示用户首选语言；模板回复按目标语言选择，附件名不强制翻译。 |
| M-SUPPORT-002 | 创建工单 / 申诉 | 系统 UI 与预设分类本地化；用户自己提交的原始消息不静默机器翻译，必要时标注译文来源。 | 客服侧展示用户首选语言；模板回复按目标语言选择，附件名不强制翻译。 |
| M-SUPPORT-003 | 工单详情 | 系统 UI 与预设分类本地化；用户自己提交的原始消息不静默机器翻译，必要时标注译文来源。 | 客服侧展示用户首选语言；模板回复按目标语言选择，附件名不强制翻译。 |
| M-SETTINGS-001 | 设置 | 语言设置必须列出 7 种语言的本地名称：简体中文 / English / 日本語 / 한국어 / ไทย / Deutsch / Français。 | 选中即即时预览，保存到账号偏好；未登录前保存在本地设备。 |
| M-AI-001 | AI 数据 / Signal 详情 | P1/Future/Closed 文案也要 7 语完整覆盖；内部术语与版本号保持 canonical。 | 关闭态/Coming Later 必须本地化，不能因语言缺失误显示为已开放。 |
| M-GROWTH-001 | Referral / Team | P1/Future/Closed 文案也要 7 语完整覆盖；内部术语与版本号保持 canonical。 | 关闭态/Coming Later 必须本地化，不能因语言缺失误显示为已开放。 |
| M-PREDICT-FREE-001 | 免费 YES/NO | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| M-MIGRATION-001 | APT-I → APT-C Migration | P1/Future/Closed 文案也要 7 语完整覆盖；内部术语与版本号保持 canonical。 | 关闭态/Coming Later 必须本地化，不能因语言缺失误显示为已开放。 |
| A-WORK-001 | 运营总览 | Admin 导航、筛选、状态、操作名称 7 语；用户名、邮箱、ID、原始实名保留原文。 | 语言切换保留 Filter/Sort/Page/Tab；日期与数字可按操作员 locale 展示。 |
| A-WORK-002 | 今日待办 | Admin 导航、筛选、状态、操作名称 7 语；用户名、邮箱、ID、原始实名保留原文。 | 语言切换保留 Filter/Sort/Page/Tab；日期与数字可按操作员 locale 展示。 |
| A-USER-001 | 用户列表 | Admin 导航、筛选、状态、操作名称 7 语；用户名、邮箱、ID、原始实名保留原文。 | 语言切换保留 Filter/Sort/Page/Tab；日期与数字可按操作员 locale 展示。 |
| A-USER-002 | 用户 360 | Admin 导航、筛选、状态、操作名称 7 语；用户名、邮箱、ID、原始实名保留原文。 | 语言切换保留 Filter/Sort/Page/Tab；日期与数字可按操作员 locale 展示。 |
| A-KYC-001 | KYC 审核队列 | KYC 文案、证件类型、补件原因、Consent 必须人工校对；姓名与证件号保留原文，不擅自翻译。 | 语言切换不得改变审核状态；如法律 Consent 文本版本变化，需重新确认。 |
| A-LEDGER-001 | 资产总览 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| A-LEDGER-002 | APT 账户与流水 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| A-LEDGER-003 | 池子与对账 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| A-LEDGER-004 | 更正 / 冲正申请 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| A-ROBOT-001 | Robot 列表 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| A-ROBOT-002 | Robot 详情 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| A-ROBOT-003 | Reward / Claim 运营 | Robot/Level/APT 等产品词保持 canonical；能力、资格、Reward 周期、状态本地化。 | 数字按 locale 格式化但不改业务值；规则版本、snapshot_id 不翻译。 |
| A-OTC-001 | OTC 订单列表 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| A-OTC-002 | OTC 订单详情 / 审核 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| A-POWER-001 | Power 账户与流水 | APT/Power/OTC 术语保持 canonical；金额/数量/小数按 locale 展示；不把 APT 数量自动加货币符号。 | 流水类型/订单状态用翻译映射；账本 ID、order_id、tx/reference 不翻译。 |
| A-PREDICT-001 | Market / Event 列表 | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| A-PREDICT-002 | Market 详情 | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| A-PREDICT-003 | Result / Settlement | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| A-PREDICT-004 | Refund / Correction | 赛事/联赛/球队名称使用本地化字典；主胜/平局/客胜必须等权翻译；风险提示全量人工校对。 | 时间显示用户本地时区并标明；赛事 ID、Market ID、Rule Version 保持 canonical。 |
| A-RISK-001 | Risk Case | 风险原因、审批动作、影响说明、拒绝理由必须高质量人工翻译；Case/Approval ID 保持原值。 | 切换语言后确认内容刷新；已签署/已确认内容显示签署当时语言与版本。 |
| A-APPROVAL-001 | 审批中心 | 风险原因、审批动作、影响说明、拒绝理由必须高质量人工翻译；Case/Approval ID 保持原值。 | 切换语言后确认内容刷新；已签署/已确认内容显示签署当时语言与版本。 |
| A-CONFIG-001 | Parameter Center · Definition/Candidate | Parameter Key/enum/value 不翻译；display_name、description、policy reason 本地化。 | 发布/回滚确认必须同时显示 canonical key + 本地说明，避免误操作。 |
| A-CONFIG-002 | Parameter Release / Snapshot | Parameter Key/enum/value 不翻译；display_name、description、policy reason 本地化。 | 发布/回滚确认必须同时显示 canonical key + 本地说明，避免误操作。 |
| A-POLICY-001 | 地区 / KYC / 保护策略 | Parameter Key/enum/value 不翻译；display_name、description、policy reason 本地化。 | 发布/回滚确认必须同时显示 canonical key + 本地说明，避免误操作。 |
| A-SUPPORT-001 | 工单队列 | 系统 UI 与预设分类本地化；用户自己提交的原始消息不静默机器翻译，必要时标注译文来源。 | 客服侧展示用户首选语言；模板回复按目标语言选择，附件名不强制翻译。 |
| A-SUPPORT-002 | 工单详情 | 系统 UI 与预设分类本地化；用户自己提交的原始消息不静默机器翻译，必要时标注译文来源。 | 客服侧展示用户首选语言；模板回复按目标语言选择，附件名不强制翻译。 |
| A-AUDIT-001 | 审计日志 | audit_event_code 原样显示/可复制；显示层 action label 本地化；导出可选 canonical 或 localized。 | 日志时间支持操作员 locale，但原始 UTC 时间戳可展开查看。 |
| A-OPS-001 | 异步任务 / 对账 / 系统状态 | 系统状态、任务类型、报表标题本地化；Job ID/指标 key 保持 canonical。 | 报表导出明确 locale、时区、数字格式；CSV 原始字段名可固定英文 canonical。 |
| A-REPORT-001 | 运营报表 | 系统状态、任务类型、报表标题本地化；Job ID/指标 key 保持 canonical。 | 报表导出明确 locale、时区、数字格式；CSV 原始字段名可固定英文 canonical。 |
| A-GROWTH-001 | Referral / Team 运营 | P1/Future/Closed 文案也要 7 语完整覆盖；内部术语与版本号保持 canonical。 | 关闭态/Coming Later 必须本地化，不能因语言缺失误显示为已开放。 |
| A-MIGRATION-001 | APT Migration | P1/Future/Closed 文案也要 7 语完整覆盖；内部术语与版本号保持 canonical。 | 关闭态/Coming Later 必须本地化，不能因语言缺失误显示为已开放。 |

## 14.5 多语言 Gate
- `LANGUAGE_COUNT=7`
- `MISSING_I18N_KEY=0`
- `NAVIGATION_PERSISTENCE=PASS`
- `RISK_COPY_FULL=PASS`
- `TEXT_EXPANSION_DE_TH=PASS`
- `DATE_NUMBER_LOCALE=PASS`
- `CANONICAL_FIELDS_UNCHANGED=PASS`
- `SPORTS_NAME_FALLBACK=PASS`
