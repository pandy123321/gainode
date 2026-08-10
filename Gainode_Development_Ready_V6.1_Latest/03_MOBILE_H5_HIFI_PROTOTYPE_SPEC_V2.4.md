# 03 · Gainode Mobile / H5 高保真原型逐页规范

> 版本：V2.4 · Latest Project Decision Closure
> 目标：可以直接交给可视化原型 Agent / 前端实现
> 重要：**全部页面重新生成；不采用旧大 Figma；不复用旧 Flutter 视觉。**
> 视觉依赖：全局颜色、Logo、字体、间距、圆角、状态、响应式与无障碍统一遵守 `08_VISUAL_DESIGN_SYSTEM_V2.4.md`。

> 文档权威：本文件是该端逐页 HIFI Page Execution Spec。页面级布局、状态、交互与组件以本文件为准；全局视觉与 I18N/L10N 读取 `08_VISUAL_DESIGN_SYSTEM_V2.4.md`；业务字段、状态、权限、API 与参数继续读取 `05/06`。
> 合并说明：已确认的全量视觉/交互策划内容只作为合并来源，不再作为并行开发基线。

## 1. 原型必须做到什么

- 固定底部导航：Home / Robot / Prediction / Me。
- H5 与 Mobile 使用同一信息架构；窄屏不出现桌面式多栏挤压。
- 每一页至少做：Default / Loading / Empty / Error / Restricted；有写操作的页面再做 Submitting / Processing / Success / Failed。
- 每一页只有一个最主要 CTA；次要动作降级为 link / secondary button。
- 所有业务数字都带：名称、单位、状态、更新时间或快照版本。
- 前端不自己重算资格、Reward、OTC capacity、Prediction settlement。
- 高风险操作必须：摘要 → 主动确认 → 提交中 → 结果。
- 新视觉方向：可信、克制、AI sports analytics；不要高刺激收益看板、赌场风、币价拉盘感。
- 最新审美：Western / Premium / Sports-Tech / Operational；优先减少模板化卡片堆叠。
- 设计稿交付：每个页面独立画板/独立图，不加手机设备边框；不得四页拼成一张展示图。

## 2. 全局响应式

| 区域 | Mobile | H5 大屏 |
|---|---|---|
| 内容宽度 | 100%，左右安全边距 16px | 表单/确认最大 520–560px；列表/详情最大 720px，居中 |
| Bottom Nav | 固定 | 保留在 H5 App Shell 底部；不另外发明桌面导航 |
| Sheet | Bottom Sheet | 居中 Modal / Side Sheet 均可 |
| 表格 | 禁止横向堆复杂表格 | 仍优先卡片/列表，必要时横向滚动 |
| 固定 CTA | 底部 safe area | 可在内容底部或右侧 sticky action |


## 2.1 V6 全局体验覆盖规则

> 本节是 V6 对 Mobile/H5 的正式覆盖规则；与本文件旧描述冲突时，以本节及对应页面的 V6 页面规格为准。业务状态、经济公式、权限和 API 的事实仍以 `01/02/05/06` 为准。

### 2.1.1 四个一级导航不变

Bottom Navigation 固定为 4 个：

- `Home` → 中文正式显示「首页」
- `Robot` → 中文正式显示 `Robot`
- `Prediction` → 中文正式显示「竞猜」
- `Me` → 中文正式显示「我的」

不得增加第 5 个 Bottom Tab。APT、Power、OTC 继续作为「我的」和 Home 快捷入口中的二级能力。

### 2.1.2 Root 页面固定操作区与安全区

- `BottomNav Height = 64px + device safe-area`
- Robot / Prediction / OTC Root 可拥有自己的 Fixed / Sticky Action Bar。
- `Floating Action Bar Height = 64–72px`
- Floating Action Bar 与 Bottom Nav 的视觉间隔为 `8–12px`。
- Root 页正文底部 padding 必须至少等于：`BottomNav + FloatingActionBar + gap + safe-area + 16px`。
- 键盘弹出时：表单页 CTA 可跟随键盘；Root 页 Floating Action Bar 应隐藏或上移，不能与键盘、Bottom Nav 三层重叠。
- Fixed CTA 不得遮挡正文、列表尾部、Toast 或系统手势区域。

### 2.1.3 正式运营态 Fixture 规则

原型仍使用 Mock / Fixture 驱动多状态，但**正式用户界面不得出现**：

`Demo / Mock / Sandbox / 模拟数据 / 模拟环境 / 演示环境`

这些标记只允许存在于 Prototype Developer Panel、QA Scenario Switcher 或 Fixture Metadata。

用户看到的页面必须像正常运营状态。任何 Fixture 数值仍不得被写进 `01/02/05/06` 充当生产参数。

推荐原型数据密度：

- Home：多条通知、排行榜、热门竞猜、Robot/Reward 状态；
- Robot：今日数据、最近 7 日趋势、完整 Activity；
- 竞猜：至少 12–24 场；
- AI Signal：至少 20 条；
- APT Ledger：至少 30 条；
- OTC：至少 12–20 条；
- Support：至少 8–12 条；
- Security：多个 Device / Session / Security Event。

### 2.1.4 用户文案必须说人话

- 中文用户可见 `Prediction` 统一优先写「竞猜」。
- 禁止用户界面出现：下注、投注、博彩、赔率、盘口、押注、买方向。
- 不直接显示系统 enum。
- 不使用 PRD/AI 营销腔，例如「数据驱动决策，AI 助力胜算」。
- 文案优先回答：现在是什么情况、我能做什么、下一步是什么。

### 2.1.5 Robot Reward 展示边界

V6 允许清晰展示：今日累计 Reward、昨日 Reward、近 7 日趋势、当前可领取、今日已领取、累计已领取、生成/领取时间及对应 Robot Level / Rule Version。

仍然禁止：

`APR / APY / Guaranteed Income / Guaranteed Return / 固定收益率 / 保本 / 稳赚 / 回本周期`

Reward 公式和资格仍读取 `02/05/06`；前端不得自行发明 production coefficient、claim cycle 或收益参数。

### 2.1.6 Prediction 与 Power 边界

P0 竞猜仍为 Football Pre-match `1X2`，Home / Draw / Away 三方向同级。

当前 `02/05/06` 只正式定义 Power 与 OTC Sell 的冻结/消耗/释放关系，因此：

- P0 竞猜确认页**默认不展示 Power 消耗**；
- 只有未来 `02/05/06` 正式增加 Prediction-Power 规则后，03 才能显示对应 Power Impact；
- 不得把 OTC 的 Power 规则套用到竞猜。


### 2.1.7 Latest Visual / Delivery Override

以下为 2026-08-10 项目最新确认规则，与旧描述冲突时优先：

- Home 成长/升级榜继续保留，但移动到 Home 内容区最底部，位于 AI Data Summary 之后、BottomNav 之前。
- Robot 不再使用 `UpgradeProgress` 进度条表达成长；Robot Root 用 `PowerMeter · Battery Variant` 展示真实可变资源。
- Robot Upgrade 继续展示 Current Level / Target Level / Capability Diff / Power Cap Diff，但不显示“升级完成百分比”。
- Prediction Root 必须一眼看出“同时存在很多场竞猜”；正常运营 Fixture 至少 12–24 场，不允许一个巨大单场 Hero 让产品看起来只有一场。
- Root 页面最新视觉锚点是项目内已经确认的 Home / Robot / Prediction / Me 新视觉方向；旧大 Figma 仍不是基线。
- HIFI 设计稿输出采用**每页一张图**，直接输出页面画板；不加手机设备边框，不做四页拼图。
- 视觉默认优先 English UI 作为审美校验语言，但所有正式页面必须接入 `/i18n`。
- 视觉关键词追加：`Western / Premium / Sports-Tech / Operational`。
- 降低“Generic Card Feel”：增加真实运营数据量不能等于增加巨大卡片数量；优先用轻列表、时间分组、Segment、紧凑数据行和适量留白。
- 375 / 390 / 430 三种 Mobile 宽度必须逐页回归，重点检查换行、CTA 掉位、Bottom Sheet、Fixed Action Bar 和 BottomNav。


## 3. 页面规格

### `M-AUTH-001｜登录` · P0
- **页面目标**：已有账号安全登录并获得 session。
- **高保真布局**：品牌区；账号；密码；登录按钮；注册/忘记密码；条款与帮助。
- **I18N Copy Contract**：`page.m_auth_001.title` / `page.m_auth_001.description` / `page.m_auth_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`表单页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：浅色页面，单列任务，首屏只突出表单和一个主 CTA。
- **首屏视觉结构**：沿用本页业务布局——品牌区；账号；密码；登录按钮；注册/忘记密码；条款与帮助。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：输入框 48px；表单项间距 16px；CTA 48px；表单卡圆角 16px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `520px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不做营销 Banner；不使用金色主按钮。
- **读取数据**：登录策略、是否需要 MFA、频控/锁定状态。
- **主要交互**：输入账号/密码；登录；去注册；忘记密码。
- **跳转/返回**：成功→M-AUTH-005 或 M-KYC-001 / M-HOME-001；忘记密码→M-AUTH-004。
- **必须画出的状态**：Loading；凭据错误；频控；账户安全锁定；依赖不可用。
- **权限/限制**：未登录可访问；不泄露账号是否存在等敏感判断。
- **接口参考**：`POST /api/v1/auth/login；GET /api/v1/auth/login-policy`
- **页面验收**：登录中禁止重复点；失败保留账号但清密码；成功必须由服务端给 next_step。
- **人话备注**：不要把登录和注册塞在一个复杂 Tab 里；页面任务只做“登录”。

### `M-AUTH-002｜注册` · P0
- **页面目标**：创建账号并完成基础协议确认。
- **高保真布局**：账号类型；手机号/邮箱；密码；确认密码；条款勾选；注册按钮。
- **I18N Copy Contract**：`page.m_auth_002.title` / `page.m_auth_002.description` / `page.m_auth_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`表单页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：浅色页面，单列任务，首屏只突出表单和一个主 CTA。
- **首屏视觉结构**：沿用本页业务布局——账号类型；手机号/邮箱；密码；确认密码；条款勾选；注册按钮。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：输入框 48px；表单项间距 16px；CTA 48px；表单卡圆角 16px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `520px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不做营销 Banner；不使用金色主按钮。
- **读取数据**：注册策略、条款版本、验证码发送规则。
- **主要交互**：填写；主动同意条款；提交注册。
- **跳转/返回**：成功→M-AUTH-003。
- **必须画出的状态**：字段错误；账号已存在；发送受限；服务不可用。
- **权限/限制**：游客可访问；条款不能默认勾选。
- **接口参考**：`POST /api/v1/auth/register`
- **页面验收**：注册成功必须返回 verification_challenge_id；重复请求幂等。
- **人话备注**：人话：注册只是建账号，不代表已经获得 Prediction/OTC 权限。

### `M-AUTH-003｜OTP 验证` · P0
- **页面目标**：验证注册/登录/找回操作的一次性验证码。
- **高保真布局**：验证码输入；倒计时；重发；当前账号脱敏展示。
- **I18N Copy Contract**：`page.m_auth_003.title` / `page.m_auth_003.description` / `page.m_auth_003.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`验证页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：Logo/标题弱化，验证码或验证方式成为唯一视觉中心。
- **首屏视觉结构**：沿用本页业务布局——验证码输入；倒计时；重发；当前账号脱敏展示。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：验证码格 48–52px；主 CTA 48px；倒计时使用 Meta 字号。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `480px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不显示内部安全规则、风控阈值或“账号是否存在”。
- **读取数据**：challenge_id、expires_at、retry_after、remaining_attempts。
- **主要交互**：输入验证码；验证；重发。
- **跳转/返回**：成功按 challenge purpose 去下一步。
- **必须画出的状态**：验证码错误/过期；尝试过多；重发频控。
- **权限/限制**：只能操作当前 challenge。
- **接口参考**：`POST /api/v1/auth/otp/verify；POST /api/v1/auth/otp/resend`
- **页面验收**：倒计时以服务端为准；过期不静默自动重发。
- **人话备注**：不要只写“验证码错误”，要告诉用户还能做什么。

### `M-AUTH-004｜找回 / 重置密码` · P0
- **页面目标**：安全恢复账号凭据。
- **高保真布局**：账号；OTP/验证步骤；新密码；确认；完成页。
- **I18N Copy Contract**：`page.m_auth_004.title` / `page.m_auth_004.description` / `page.m_auth_004.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`表单页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：浅色页面，单列任务，首屏只突出表单和一个主 CTA。
- **首屏视觉结构**：沿用本页业务布局——账号；OTP/验证步骤；新密码；确认；完成页。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：输入框 48px；表单项间距 16px；CTA 48px；表单卡圆角 16px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `520px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不做营销 Banner；不使用金色主按钮。
- **读取数据**：RecoveryChallenge、安全策略。
- **主要交互**：发起找回；验证 OTP；设置新密码。
- **跳转/返回**：完成→M-AUTH-001。
- **必须画出的状态**：账号恢复受限；challenge 过期；密码不合规。
- **权限/限制**：不能通过页面暴露账号是否注册；高风险可转人工安全流程。
- **接口参考**：`POST /api/v1/auth/recovery；POST /api/v1/auth/recovery/verify；POST /api/v1/auth/password/reset`
- **页面验收**：成功后旧 session 按策略失效；必须记录安全事件。
- **人话备注**：找回密码是安全流程，不是简单改字段。

### `M-AUTH-005｜MFA 二次验证` · P0
- **页面目标**：在高风险登录或敏感动作前完成二次验证。
- **高保真布局**：验证方式；验证码；倒计时/恢复方式；安全提示。
- **I18N Copy Contract**：`page.m_auth_005.title` / `page.m_auth_005.description` / `page.m_auth_005.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`验证页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：Logo/标题弱化，验证码或验证方式成为唯一视觉中心。
- **首屏视觉结构**：沿用本页业务布局——验证方式；验证码；倒计时/恢复方式；安全提示。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：验证码格 48–52px；主 CTA 48px；倒计时使用 Meta 字号。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `480px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不显示内部安全规则、风控阈值或“账号是否存在”。
- **读取数据**：challenge、allowed_methods、expires_at。
- **主要交互**：验证；切换允许的方法；安全帮助。
- **跳转/返回**：成功回原操作，不另造重复业务请求。
- **必须画出的状态**：错误；过期；次数过多；恢复模式。
- **权限/限制**：challenge 绑定原 request/context。
- **接口参考**：`POST /api/v1/auth/mfa/verify`
- **页面验收**：MFA 成功后必须继续原流程并保留原 idempotency context。
- **人话备注**：用户不应该完成 MFA 后发现原来的升级/订单内容丢了。

### `M-KYC-001｜KYC 与功能准入概览` · P0
- **页面目标**：告诉用户验证进度，以及哪些功能可用/不可用。
- **高保真布局**：KYC 进度；功能能力清单；限制原因；开始/继续/补件/申诉按钮。
- **I18N Copy Contract**：`page.m_kyc_001.title` / `page.m_kyc_001.description` / `page.m_kyc_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`状态总览页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：顶部状态卡 + 功能能力清单；状态和下一步动作优先。
- **首屏视觉结构**：沿用本页业务布局——KYC 进度；功能能力清单；限制原因；开始/继续/补件/申诉按钮。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：状态卡 112–144px；能力行最小 56px；CTA 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `640px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不只显示一个“通过/未通过”大图标。
- **读取数据**：KycCase、FeatureEntitlement、PolicyDecision 安全摘要。
- **主要交互**：开始 KYC；继续；补件；看原因；申诉。
- **跳转/返回**：填写→M-KYC-002；结果→M-KYC-003；申诉→M-SUPPORT-002。
- **必须画出的状态**：not_started/pending/needs_info/approved/rejected/review；依赖异常。
- **权限/限制**：历史可看；新功能写操作由 allowed 决定。
- **接口参考**：`GET /api/v1/me/admission；GET /api/v1/me/kyc`
- **页面验收**：功能列表每项要有 allowed、reason、next_action。
- **人话备注**：不要只显示一个“KYC通过/不通过”，用户真正关心“我现在能用什么”。

### `M-KYC-002｜KYC 资料提交 / 补件` · P0
- **页面目标**：提交当前策略要求的身份资料。
- **高保真布局**：分步表单；资料字段；文件上传；Consent；保存草稿；提交。
- **I18N Copy Contract**：`page.m_kyc_002.title` / `page.m_kyc_002.description` / `page.m_kyc_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`分步流程页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：顶部 Stepper + 当前步骤表单 + 固定 CTA，复杂内容分段。
- **首屏视觉结构**：沿用本页业务布局——分步表单；资料字段；文件上传；Consent；保存草稿；提交。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：Stepper 40–48px；输入 48px；上传卡最小 96px；CTA 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `560px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不把所有步骤塞进一屏；上传失败不能清空整页。
- **读取数据**：required_fields、file_rules、consent_version、case 状态。
- **主要交互**：填写；上传；保存；提交。
- **跳转/返回**：提交成功→M-KYC-003。
- **必须画出的状态**：字段/文件错误；上传失败；重复提交；策略变更。
- **权限/限制**：仅本人；敏感字段不写入日志/埋点。
- **接口参考**：`POST /api/v1/me/kyc/submissions；POST /api/v1/uploads`
- **页面验收**：字段错误保留已填内容；上传失败可单项重试；策略版本变化时重新确认。
- **人话备注**：不要让一个附件失败导致所有资料清空。

### `M-KYC-003｜KYC 状态 / 结果` · P0
- **页面目标**：显示审核中、补件、通过、拒绝和下一步。
- **高保真布局**：状态卡；时间线；缺失项；开放能力；申诉/支持。
- **I18N Copy Contract**：`page.m_kyc_003.title` / `page.m_kyc_003.description` / `page.m_kyc_003.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`结果 / 时间线页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：结果卡先回答现在是什么状态，再展示时间线、影响和下一步。
- **首屏视觉结构**：沿用本页业务布局——状态卡；时间线；缺失项；开放能力；申诉/支持。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：状态图标 48–56px；时间线行 56px+；CTA 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `640px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不把“处理中”画成成功；不使用庆祝动画掩盖未完成状态。
- **读取数据**：KycCase timeline、FeatureEntitlement。
- **主要交互**：补件；查看功能；申诉；回首页。
- **跳转/返回**：补件→M-KYC-002。
- **必须画出的状态**：审核中；needs_info；rejected；approved；service unavailable。
- **权限/限制**：拒绝只展示安全原因，不泄露内部风险模型。
- **接口参考**：`GET /api/v1/me/kyc/{case_id}`
- **页面验收**：每个状态必须有“下一步”；通过后重新拉取 capabilities。
- **人话备注**：“审核中”不是死页面，要告诉用户是否还需要做什么。

#### KycCase 领域状态展示映射

> 本表是页面展示映射，不新增领域状态。Canonical enum 以 05 为准。

KycCase 的六个 canonical 状态：

| canonical code | 用户显示名 | 说明 |
|---|---|---|
| `not_started` | 未开始 | 尚未提交 KYC 资料 |
| `pending` | 审核中 | 资料已提交，等待审核 |
| `needs_info` | 需补件 | 审核人要求补充资料 |
| `approved` | 已通过 | KYC 审核通过 |
| `rejected` | 未通过 | 本次验证未通过；可查看可见原因 |
| `review` | 复核中 | 需要进行二次人工复核 |

区分说明：
- 草稿未提交（M-KYC-002）：属于 KycSubmission 本地草稿状态，不是 KycCase 状态。
- 已限制：属于 User/FeatureEntitlement/Risk，不是 KYC 状态。
- 已暂停：属于 User suspended 或功能暂停，不是 KYC 状态。
- 账户复核中：只有确实是 KYC case review 时才可显示为 KYC `review`；普通账户复核不能冒充 KYC 状态。

#### M-KYC-001 补充：账户限制、功能限制与 KYC 状态的展示边界

- KYC 状态只反映身份验证流程的进展，不反映账户整体可用性。
- 账户限制（User.restricted/suspended）通过 Admission 或 FeatureEntitlement 展示。
- FeatureEntitlement 的 `reason_code` 可能引用 KYC 尚未完成，但展示时必须区分“KYC 未完成”和“功能因其他原因限制”。
- 03 中不得把 FeatureEntitlement.reason_code 直接当作 KYC 状态展示。

### `M-HOME-001｜首页` · P0
- **页面目标**：让用户每天打开 Gainode 后立刻看懂账户是否可用、Robot 是否运行、今天是否有 Reward 可领取、有哪些热门竞猜，以及现在最值得做什么。
- **V6 高保真布局**：固定顺序：`Header → Hero/今日状态 → Banner → NoticeTicker → DailyClaimCard/Robot 回访 → 热门竞猜 → APT/Power/OTC 快捷入口 → AI 数据摘要 → UpgradeLeaderboard → BottomNav`。Hero 同时承载 Admission、Robot 状态、Claimable Reward 与最重要 CTA，但不把 Reward 做成整页最大数字。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：HomeHeader、TodayHero、BannerCarousel、NoticeTicker、DailyClaimCard、FeaturedPredictionList、OTCQuickAction、AISummaryCard、UpgradeLeaderboard、BottomNav
- **关键尺寸 / Safe Area**：Hero 建议 168–208px；NoticeTicker 40–44px；快捷入口 44px+；BottomNav 64px + safe-area。
- **I18N Copy Contract**：`page.m_home_001.title` / `page.m_home_001.description` / `page.m_home_001.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。中文：`首页 / 竞猜 / 今日有收益待领取 / 今天有什么值得关注`，不显示 `Prediction`。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Admission、RobotSummary、FeaturedMarkets、AptSummary、NoticeSummary。
- **主要交互**：进入 KYC；启动/查看 Robot；领取今日 Reward；打开热门竞猜；查看榜单；打开 APT/Power/OTC；查看 Notice 与 AI 数据。首页 CTA 只做导航/进入明确流程，不在 Home 内直接完成高风险写操作。
- **主 CTA**：领取今日收益 / 启动或查看 Robot / 参与竞猜（三者按当前状态只突出一个主 CTA）
- **跳转 / 返回**：底部导航固定 Home/Robot/Prediction/Me。
- **必须画出的状态**：Admission Restricted；Robot 未运行/运行中/异常；无可领取/可领取/已领取；热门竞猜为空；榜单无数据；卡片级网络失败。
- **权限 / 业务边界**：不同模块独立权限；单卡失败不能拖死整页。；首页排行榜固定放在 AI 数据摘要之后、BottomNav 之前，只允许社区成长/活跃维度：排名、脱敏昵称、Robot Level、本周升级/活跃度、我的排名、距下一名差距。禁止资产余额榜、财富榜、Reward 金额榜。
- **接口参考**：`GET /api/v1/me/home-summary`
- **页面验收**：首屏必须在正常运营态同时看见 Robot 状态、可领取提示（如有）和至少一个热门竞猜入口；Notice 不得占大卡；排行榜不得包含资产/财富指标；任一卡失败不拖死整页。
- **人话备注**：首页不是后台状态面板。用户打开后应该觉得“今天有事可看、有事可做”，但不能靠夸张收益刺激。

### `M-NOTICE-001｜消息中心` · P0
- **页面目标**：集中查看从首页单行 NoticeTicker 进入的通知，并能回到对应业务对象。
- **V6 高保真布局**：首页只保留单行 `NoticeTicker`；本页采用「未读 / 全部」+ 时间分组列表。每条包含 icon、单行标题、最多两行摘要、时间、对象类型；高优先级只用小状态标识。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：NoticeTicker、SegmentedTabs、NoticeRow、UnreadDot、ObjectDeepLink、EmptyState
- **关键尺寸 / Safe Area**：首页 NoticeTicker 高 40–44px；本页列表行最小 64px。
- **I18N Copy Contract**：`page.m_notice_001.title` / `page.m_notice_001.description` / `page.m_notice_001.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。中文用「消息 / 通知」，不要出现系统事件 enum。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Notice[]、read_state、related_object。
- **主要交互**：标记已读；全部已读；打开关联对象；返回首页后恢复原滚动/Tab。
- **主 CTA**：查看通知 / 打开关联对象
- **跳转 / 返回**：根据 object_type 深链跳转。
- **必须画出的状态**：Empty；分页失败；目标对象已变更/失效；高优先级；已读/未读；通知投递失败提示（不影响业务状态）；关联对象已失效但正文仍可读。
- **权限 / 业务边界**：只展示本人通知；敏感原因做安全映射。；不得把 NoticeTicker 做成强打断弹窗；普通通知不抢占 Home 首屏。
- **I18N Copy Contract 补充**：通知类型（notice_type）事件映射进入 I18N：ROBOT_UPGRADE / KYC_UPDATE / OTC_ORDER / MARKET_SETTLEMENT / RISK_ACTION / SYSTEM_ANNOUNCEMENT。
- **安全展示边界**：KYC、Robot、OTC、Prediction、风险限制的通知正文使用安全 reason mapping，不暴露内部 reason_code 或风控规则。
- **接口参考**：`GET /api/v1/me/notices；POST /api/v1/me/notices/{id}/read`
- **页面验收**：首页 NoticeTicker 与本页同一数据源；点击后正确标已读并深链；目标失效仍能查看通知正文。
- **人话备注**：首页只需要一行提醒，真正需要看的细节都放这里。

### `M-ROBOT-001｜Robot 控制中心` · P0
- **页面目标**：把 Robot 从静态状态页升级为持续运行中的个人控制中心：看状态、运行时长、能力、今日 Reward、运行过程和下一步。
- **V6 高保真布局**：`RobotStatusHero → Power Battery → 今日 Reward（RewardTrendCard）→ RobotRuntimeTimeline → Level/Capability → 最近 Activity → RobotFloatingActionBar → BottomNav`。Status Hero 显示 Current Level、UI Runtime Stage、Running Duration、Capability、Health、Last Update。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：RobotStatusHero、PowerMeter(Battery)、RewardTrendCard、RobotRuntimeTimeline、LevelBadge、CapabilityMetrics、ActivityPreview、RobotFloatingActionBar、BottomNav
- **关键尺寸 / Safe Area**：RobotFloatingActionBar 64–72px；与 BottomNav 间隔 8–12px；正文 bottom padding 按 V6 全局公式预留。
- **I18N Copy Contract**：`page.m_robot_001.title` / `page.m_robot_001.description` / `page.m_robot_001.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。enum 只用于工程；用户必须看到自然文案，如「准备中 / 启动中 / 运行中 / 可领取 / 已领取 / 已暂停 / 需要处理」。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Robot、Eligibility、Capacity、RewardSummary、allowed_actions。
- **主要交互**：启动/停止；查看运行状态；升级；查看等级地图；查看 Reward；Claim；查看 Activity。所有动作先读 allowed_actions。
- **主 CTA**：Floating Action Bar 按状态：启动运行 / 查看运行状态 / 领取今日收益 / 查看异常
- **跳转 / 返回**：Start/Stop→M-ROBOT-002；Upgrade→003；Level→005；Reward→006；History→007。
- **必须画出的状态**：PREPARING / STARTING / RUNNING / OUTPUT_READY / CLAIMABLE / CLAIMED / PAUSING / PAUSED / UPGRADING / RESTRICTED / ERROR；以及 05 的 inactive/active/cooling/review/restricted/paused 业务状态映射。
- **权限 / 业务边界**：所有按钮以 allowed_actions 为准。；V6 Runtime Stage 是 UI 呈现层，不替代 05 的 Robot / Reward 业务状态，也不得被前端用来推断 Eligibility、Reward 或 Ledger。生产环境应由服务端返回可展示 stage/activity；原型可用 fixture。
- **接口参考**：`GET /ai/users/{id}/summary；GET /ai/users/{id}/capacity`
- **页面验收**：Floating Action Bar 必须始终位于 BottomNav 上方且不遮挡正文；运行中时能看见 Power Battery、运行过程和今日 Reward；状态变化后刷新；历史始终可访问。
- **人话备注**：这一页打开后用户第一眼应该知道：“我的 Robot 现在在做什么，今天有没有可领取，下一步按哪里。”

#### Robot 领域状态展示映射

> 本表是页面展示映射，不新增领域状态。Canonical enum、状态轴和 allowed_actions 以 05 为准。实际用户字符串使用 I18N key。

| canonical code | 用户显示名 | 用户看见什么 | 可执行操作 | 不可执行与提示 | 下一步 |
|---|---|---|---|---|---|
| `inactive` | 未启用 | Robot 尚未启动 | 启动（如有 ROBOT_START allowed） | 不可领取 Reward、不可运行 | 启动 Robot |
| `active` | 运行中 | Robot 正在运行，产出 Reward | 查看运行、领取、暂停、升级 | 不可删除 | 领取或查看 |
| `review` | 审核中 | Robot 处于审核状态，同时显示关联 Action/UpgradeOrder | 查看详情 | 审核期间部分操作暂停 | 等待审核 |
| `cooling` | 冷却中 | Robot 当前不可操作 | 查看详情和恢复时间 | 不可启动、升级 | 等待冷却结束 |
| `restricted` | 受限 | 当前 Robot 功能暂时不能使用 | 查看原因和支持入口 | 不可操作 | 按引导处理 |
| `paused` | 已暂停 | Robot 已暂停运行 | 恢复（如有 allowed） | 暂停期间 Reward 停止累积 | 恢复或查看 |

说明：
- Robot 最终表现由 `Robot.status + allowed_actions + current action/runtime stage` 共同决定。
- `PREPARING / STARTING / RUNNING / OUTPUT_READY / ...` 仍是 UI Runtime Stage。
- Runtime Stage 不得替代 05 的 Robot/Reward 状态。
- 升级确认中（M-ROBOT-003 页面流程）和 Upgrade Action 不是 Robot 状态。

### `M-ROBOT-002｜Robot 启动 / 停止确认` · P0
- **页面目标**：在启动、暂停或恢复前，把当前状态、目标状态、影响和可能的 Review/Cooling 说明白。
- **V6 高保真布局**：当前状态卡 → 目标状态 → 影响摘要 → 资格/安全提示 → 确认 CTA。运行中停止时同时说明本轮未完成状态如何处理；启动时展示服务端返回的下一步。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：ConfirmSummary、RobotStateTransition、ImpactList、RiskNotice、StickyCTA、ProcessingState
- **关键尺寸 / Safe Area**：固定 CTA 48px；长影响说明可滚动，CTA 不被 BottomNav/键盘遮挡。
- **I18N Copy Contract**：`page.m_robot_002.title` / `page.m_robot_002.description` / `page.m_robot_002.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。禁止把 `STARTING/PAUSING` 原样展示。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Robot action quote、allowed、reason、verification_required。
- **主要交互**：确认启动、停止或恢复；取消；进入 MFA（如需要）；Processing 后使用原 action_id 查询。
- **主 CTA**：确认启动 / 确认暂停 / 确认恢复
- **跳转 / 返回**：成功回 M-ROBOT-001；review 显示处理中结果。
- **必须画出的状态**：可操作 / 不可操作 / STARTING / PAUSING / Review / Failed / Unknown Result。
- **权限 / 业务边界**：服务端决定是否可启动/停止。；不能因为 UI 显示 PREPARING/PAUSED 就自行判断可否操作；一切由 allowed_actions 决定。
- **接口参考**：`POST /api/v1/ai/robots/{id}/actions；GET action status`
- **页面验收**：提交后不可重复创建 action；Unknown Result 只能查询原请求；成功/Review 返回 Root 后正确反映新状态。
- **人话备注**：这是状态切换确认，不是一个“开关”。用户必须知道点下去会发生什么。

### `M-ROBOT-003｜Robot 升级` · P0
- **页面目标**：选择目标等级，清楚比较当前与升级后的能力、APT cost、Power limit、冷却和资格。
- **V6 高保真布局**：当前 Level → 目标 Level → Capability Diff / Power Cap Diff → Quote/资源 → Eligibility → 冷却/不可逆说明 → 确认。升级页不把费用做成促销主角。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：LevelCompareCard、CapabilityDiff、PowerCapDiff、QuoteCard、EligibilityNotice、StickyCTA
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_robot_003.title` / `page.m_robot_003.description` / `page.m_robot_003.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。中文使用「升级 / 当前等级 / 目标等级 / 能力变化」，不得出现「回本」。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：UpgradeEligibility、UpgradeQuote、rule/parameter versions。
- **主要交互**：选择允许目标；获取/刷新 Quote；确认升级；需要时进入 MFA；提交后进入 M-ROBOT-004。
- **主 CTA**：确认升级
- **跳转 / 返回**：提交→M-ROBOT-004。
- **必须画出的状态**：可升级 / 条件不足 / 冷却 / Quote 过期 / Review / Upgrading / Failed。
- **权限 / 业务边界**：前端不算 apt_cost。；apt_cost、能力差异、冷却和 eligibility 全由服务端/参数版本返回；前端不自算。
- **接口参考**：`GET /ai/users/{id}/upgrade-eligibility；POST /ai/users/{id}/upgrade-orders`
- **页面验收**：Quote 过期自动要求刷新；页面必须显示 Rule/Parameter Version；不使用“回本/收益升级”文案。
- **人话备注**：升级卖点是能力与 Power Cap 的变化，不是“升级百分比”，也不是“花多少换多少收益”。

### `M-ROBOT-004｜升级结果` · P0
- **页面目标**：明确告诉用户升级是成功、处理中、Review、Cooling、失败还是未知，并给出可追踪记录。
- **V6 高保真布局**：Result Hero → Level Before/After → APT/Ledger 影响 → Cooldown/可用时间 → Order/Action ID → Timeline → 下一步。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：ResultHero、BeforeAfterDiff、LedgerImpact、Timeline、ReferenceBlock、ActionGroup
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_robot_004.title` / `page.m_robot_004.description` / `page.m_robot_004.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：AIUpgradeOrder、Ledger refs。
- **主要交互**：返回 Robot；查看 APT 流水；查看 Activity；查询处理中状态。
- **主 CTA**：返回 Robot / 查看进度
- **跳转 / 返回**：→M-ROBOT-001 / M-ASSET-003 / M-ROBOT-007。
- **必须画出的状态**：Success / UPGRADING / Review / Cooling / Failed-No-Effect / Unknown Result。
- **权限 / 业务边界**：历史可查看。；“已提交”不等于“已升级”；Failed 必须说明本次是否产生 APT/Ledger 影响。
- **接口参考**：`GET /ai/users/{id}/upgrade-orders/{upgrade_order_id}`
- **页面验收**：所有非最终状态都有查询入口；Unknown 不允许再次提交；成功后 Root Level 正确刷新。
- **人话备注**：结果页不是庆祝页，最重要的是用户知道结果和账本影响。

### `M-ROBOT-005｜56 级等级地图` · P0
- **页面目标**：让用户理解 56 级成长路径、当前等级、已解锁能力和下一等级，而不是平铺 56 张销售卡。
- **V6 高保真布局**：等级分段导航 → 当前 Level 锚点 → 路径/节点 → 点击节点 Bottom Sheet → 能力差异 → 可升级时进入 M-ROBOT-003。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：LevelBandTabs、LevelPath、CurrentLevelMarker、LevelDetailSheet、CapabilityList、UpgradeEntry
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_robot_005.title` / `page.m_robot_005.description` / `page.m_robot_005.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：RobotLevelRuleSnapshot[]。
- **主要交互**：切换分段；查看 Level 详情；跳转升级；返回 Root 保留当前位置。
- **主 CTA**：查看升级条件 / 去升级
- **跳转 / 返回**：→M-ROBOT-003。
- **必须画出的状态**：Locked / Current / Available / Restricted / Rule unavailable。
- **权限 / 业务边界**：只读；参数/规则来源服务端。；等级规则、standard_capacity、能力和条件全部读服务端；不得根据历史截图/旧 Figma 填参数。
- **接口参考**：`GET /ai/robots；GET /ai/robots/{robot_id}`
- **页面验收**：Current 必须显著；56 级完整可达；不把 Lv56 表述为保证未来权益。
- **人话备注**：等级地图像成长路径，不像商品价目表。

### `M-ROBOT-006｜Rewards & Claim` · P0
- **页面目标**：把动态 Reward 的生成、待领取、领取和历史说明清楚，并形成真实的 Daily Claim Loop。
- **V6 高保真布局**：今日 Reward Summary（今日累计 / 当前可领取 / 今日已领取 / 昨日）→ 7 日 RewardTrendCard → Claim Card → 下一次可领取周期（仅服务端有值时）→ Reward Records → Rule/Version 说明。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：DailyClaimCard、RewardTrendCard、RewardStateBadge、ClaimButton、RewardHistoryList、RuleMeta
- **关键尺寸 / Safe Area**：Claim CTA 48px；趋势图 148–180px 高；记录行 64px+。
- **I18N Copy Contract**：`page.m_robot_006.title` / `page.m_robot_006.description` / `page.m_robot_006.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。中文优先用「今日收益 / 可领取 / 已领取」，风险说明仍说明动态系数可变化且可能为 0。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：AIReward[]、capacity、coefficient、eligibility、claim_allowed。
- **主要交互**：查看趋势与来源；Claim；查询 Claim；查看流水/记录。Claim 成功后回显「今日已领取」，并提示下一次周期（若服务端提供）。
- **主 CTA**：领取今日收益
- **跳转 / 返回**：Claim 结果留在本页/结果 sheet；流水→M-ASSET-003。
- **必须画出的状态**：candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed；无可领取 / 可领取 / 领取中 / 已领取 / Unknown。
- **权限 / 业务边界**：只有 claim_allowed=true 才能提交。；Daily Claim Loop 只是把真实可领取 Reward 形成回访动作；不得签到造币、不得制造不存在的 Reward、不得承诺每天固定产生。
- **接口参考**：`GET /ai/users/{id}/rewards；POST /ai/users/{id}/reward-claims；GET claim status`
- **页面验收**：可以清楚展示今日/昨日/7日/累计已领取，但必须带状态、周期、生成时间、Level/Rule Version；Claim 幂等；动态系数可能为 0；禁止 APR/APY/固定收益/回本。
- **人话备注**：这里可以让用户看见“收益”，但要让人同时看懂它是动态 Reward，不是固定利息。

### `M-ROBOT-007｜Robot 活动与记录` · P0
- **页面目标**：用一条可追溯时间线解释 Robot 启动、运行、Reward、升级、异常和恢复。
- **V6 高保真布局**：Filter Chips → 日期分组 Activity Timeline → 事件详情 → 关联 Reward/APT/Upgrade/Support。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：ActivityFilter、RobotActivityTimeline、EventIcon、RelatedObjectLinks、StatusBadge
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_robot_007.title` / `page.m_robot_007.description` / `page.m_robot_007.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：RobotEvent[]、UpgradeOrder[]、Reward[]。
- **主要交互**：筛选事件；展开详情；深链 Reward、APT 流水、升级结果或 Support；返回保持位置。
- **主 CTA**：查看关联记录
- **跳转 / 返回**：深链到 Asset/Support。
- **必须画出的状态**：启动 / 暂停 / 恢复 / 运行产出 / Reward 生成 / Reward 领取 / 升级 / 资格变化 / 异常 / 恢复；分页/局部失败。
- **权限 / 业务边界**：历史只读。；用户事件文案不得直接显示后台 event enum；敏感 eligibility/risk 原因使用安全映射。
- **接口参考**：`GET /api/v1/ai/users/{id}/activity`
- **页面验收**：10 类事件在原型中都有示例；每条有时间、状态、来源对象/Rule Version；关联对象可追溯。
- **人话备注**：用户不需要看日志代码，只需要知道“什么时候发生了什么，和我有什么关系”。

### `M-PREDICT-001｜竞猜广场 / 赛事竞猜` · P0
- **页面目标**：用会员竞猜广场的方式发现比赛，而不是像 Sportsbook/盘口列表。
- **V6 高保真布局**：顶部「竞猜」+ 我的竞猜 → Hot/Closing Soon/My Active 分区 → League/Time Filter → 12–24 场赛事卡列表。每卡显示赛事、双方、开赛/截止时间、状态、参与人数、Home/Draw/Away 参与分布、Readiness 和必要风险状态。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：PredictionSectionTabs、LeagueFilter、TimeFilter、PredictionMatchCard、PredictionParticipationBar、ReadinessBadge、BottomNav
- **关键尺寸 / Safe Area**：赛事卡最小 128px；三方向分布区 36–44px；Filter 横滑但核心选项可读。
- **I18N Copy Contract**：`page.m_predict_001.title` / `page.m_predict_001.description` / `page.m_predict_001.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。中文一级导航和本页用「竞猜 / 竞猜广场 / 我的竞猜」。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Market[]、policy/eligibility safe summary。
- **主要交互**：切换热门/即将截止/我的进行中；筛选联赛/时间；打开赛事；进入我的竞猜。
- **主 CTA**：查看竞猜
- **跳转 / 返回**：→M-PREDICT-002 / M-PREDICT-004。
- **必须画出的状态**：Open / Paused / Locked / Result Pending / Settlement / Void / Restricted / Empty。
- **权限 / 业务边界**：未准入也可按策略浏览公开信息；不能提交。；产品是会员与会员之间的竞猜参与系统；不得出现 Sportsbook、Betting、Casino、Odds、盘口式红绿涨跌视觉。
- **接口参考**：`GET /markets`
- **页面验收**：三方向分布同级；卡片有参与人数/热度；临近截止可感知；受限用户仍可按策略浏览公开赛事。
- **人话备注**：像体育赛事产品，不像投注站。用户先看比赛和参与热度，再决定是否进入详情。

### `M-PREDICT-002｜竞猜详情 · Football 1X2` · P0
- **页面目标**：在一个页面看懂比赛、会员参与情况、AI 数据参考、三方向、规则和风险，然后决定是否参与。
- **V6 高保真布局**：Match Hero → 参与人数/热度 → `Home / Draw / Away` 三方向同级 `PredictionThreeWayCard` → AI Data Reference（球队状态/历史/阵容/伤停/数据质量）→ Lock/Result/Refund/Correction Rules → CTA。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：MatchHero、PredictionParticipationBar、PredictionThreeWayCard、AIReferenceSection、RuleAccordion、RiskNotice、StickyCTA
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_predict_002.title` / `page.m_predict_002.description` / `page.m_predict_002.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。`Home/Draw/Away` 按 locale 正常本地化；内部 1X2 canonical 不变。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Market、Selection、LiquiditySummary、Eligibility、AptBalance、Disclosure preview。
- **主要交互**：选择 Home/Draw/Away；查看 AI 数据来源；查看规则；进入确认。
- **主 CTA**：参与竞猜
- **跳转 / 返回**：→M-PREDICT-003。
- **必须画出的状态**：Open / Low Readiness / Paused / Locked / Restricted / Data delayed / Result pending。
- **权限 / 业务边界**：三方向不能隐藏；服务端最终校验。；AI 数据只提供参考，不允许默认高亮“推荐方向”、必赢或高胜率下注暗示；Home/Draw/Away 视觉权重一致。
- **接口参考**：`GET /markets/{id}；GET /markets/{id}/disclosure`
- **页面验收**：三方向同时存在且同级；Readiness/截止时间清楚；风险/退款/更正规则在 CTA 前可读；任何方向都没有默认推荐光效。
- **人话备注**：用户看到的是“这场比赛大家怎么参与、数据怎么样”，不是“平台叫我买哪边”。

### `M-PREDICT-003｜竞猜确认` · P0
- **页面目标**：在提交前确认竞猜场次、我的选择、参与数量、所需资源、截止时间、结果/退款/修正规则和 Consent。
- **V6 高保真布局**：赛事摘要 → 我的选择 → 数量/资源摘要 → 截止时间 → Result/Lock/Refund/Correction → 风险/Consent → 固定 CTA。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：ConfirmSummary、SelectionSummary、ResourceSummary、RuleSummary、ConsentBlock、StickyCTA
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_predict_003.title` / `page.m_predict_003.description` / `page.m_predict_003.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。中文使用「竞猜确认 / 我的选择 / 确认参与」，禁止「下注 / 投注 / 押注」。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Disclosure、Quote/Snapshot、Consent template。
- **主要交互**：返回修改；主动勾选 Consent；确认参与；如需 MFA 保持原 request context。
- **主 CTA**：确认参与
- **跳转 / 返回**：成功→M-PREDICT-005；unknown→处理中。
- **必须画出的状态**：可确认 / Quote或Snapshot过期 / 已锁定 / Restricted / Submitting / Unknown Result。
- **权限 / 业务边界**：Consent 不能默认勾选。；当前 P0 不默认显示 Power Impact：`02/05/06` 尚未定义竞猜消耗 Power。只有未来权威规则正式新增后才能显示，不得套用 OTC Sell Power 逻辑。
- **接口参考**：`GET /markets/{id}/disclosure；POST /consent-receipts；POST /orders`
- **页面验收**：Consent 不默认勾选；Snapshot/Rule Version 可追溯；提交后有 order id；Unknown 只能查原请求。
- **人话备注**：这是竞猜确认，不是“下注确认”。用户必须清楚自己选了什么、用了多少、什么时候锁定。

### `M-PREDICT-004｜我的竞猜` · P0
- **页面目标**：按用户真正关心的阶段管理所有竞猜记录。
- **V6 高保真布局**：固定 Tabs：`进行中 / 等待结果 / 已完成 / 异常处理`。列表项显示场次、我的选择、参与数量、比赛状态、Result Status、Settlement Status、Reward / Refund / Correction 摘要。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：PredictionStatusTabs、MyPredictionCard、ResultBadge、SettlementBadge、ExceptionIndicator
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_predict_004.title` / `page.m_predict_004.description` / `page.m_predict_004.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。中文页名固定「我的竞猜」。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：PredictionOrder[]。
- **主要交互**：切 Tab；打开订单详情；从异常项进入 M-PREDICT-006；返回保持 Tab/滚动。
- **主 CTA**：查看详情
- **跳转 / 返回**：→M-PREDICT-005。
- **必须画出的状态**：四 Tabs Empty；进行中；等待结果；已完成；Refund/Correction/Review；分页失败。
- **权限 / 业务边界**：本人数据；历史始终可读。；历史竞猜始终可读；新参与权限变化不能隐藏历史。
- **接口参考**：`GET /api/v1/me/prediction-orders`
- **页面验收**：四 Tabs 与状态映射准确；Result 与 Settlement 分开显示；异常处理入口明确。
- **人话备注**：用户要按“现在进行到哪一步”找记录，不需要理解后台状态机。

### `M-PREDICT-005｜竞猜订单详情` · P0
- **页面目标**：追踪一个竞猜订单从参与、锁定、比赛结果到结算的全过程。
- **V6 高保真布局**：订单状态 Hero → Match/Selection/Quantity → Result Status 与 Settlement Status 双轨 → Timeline → Reward/Refund → Ledger Links → Rule/Snapshot → Appeal。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：OrderStatusHero、MatchSummary、ResultSettlementStatus、Timeline、LedgerLinks、RuleMeta、ActionPanel
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_predict_005.title` / `page.m_predict_005.description` / `page.m_predict_005.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Order、Result、Settlement、Principal/Reward status、Ledger refs。
- **主要交互**：查看结果；查看结算；查看 APT 流水；异常进入 M-PREDICT-006；允许时发起申诉。
- **主 CTA**：查看处理进度 / 查看流水
- **跳转 / 返回**：异常→M-PREDICT-006；流水→M-ASSET-003；申诉→M-SUPPORT-002。
- **必须画出的状态**：Submitted / Locked / Awaiting Result / Settling / Settled / Refunding / Refunded / Correcting / Corrected / Review。
- **权限 / 业务边界**：allowed_actions 服务端返回。；Result status 不等于 Settlement final；不得在 official result 出现时提前画成资金已完成。
- **接口参考**：`GET /orders/{id}/receipt；GET /settlements/{id}`
- **页面验收**：结果与结算双状态清楚；已更正保留原记录；关联流水可达。
- **人话备注**：比赛出结果不代表钱已经结算完，这一页必须把两件事分清。

### `M-PREDICT-006｜竞猜异常 / 退款 / 更正` · P0
- **页面目标**：解释暂停、Void、Refund、Result Correction 或 Settlement Correction 对用户的真实影响。
- **V6 高保真布局**：Exception Hero → 原因安全摘要 → 处理 Timeline → Principal/Reward 状态 → 原结果/新结果对比（Correction）→ Reversal/New Ledger Links → Next Update → Support/Appeal。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：ExceptionHero、CorrectionCompare、RefundProgress、Timeline、LedgerLinks、SupportCTA
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_predict_006.title` / `page.m_predict_006.description` / `page.m_predict_006.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：RefundCase、CorrectionCase、ResultSnapshot history。
- **主要交互**：查看退款/更正进度；查看原/新记录；进入流水；申诉/工单。
- **主 CTA**：查看进度 / 联系支持
- **跳转 / 返回**：→M-ASSET-003 / M-SUPPORT-002。
- **必须画出的状态**：Under Review / Refund Processing / Refunded / Correction Processing / Corrected / Failed / Dependency unavailable。
- **权限 / 业务边界**：不能泄露反作弊算法或他人信息。；不覆盖历史；Correction 使用 reversal + new record；不得泄露反作弊模型或其他会员信息。
- **接口参考**：`GET /refunds/{id}；GET /corrections/{id}；GET /appeals/{id}`
- **页面验收**：用户能明确知道“为什么、当前到哪、APT 有没有变化、下一步什么时候”；原记录与更正记录都可追溯。
- **人话备注**：异常页不是一句“系统异常”。必须把处理过程说清楚。

### `M-ME-001｜我的` · P0
- **页面目标**：集中进入资产、Power、OTC、安全、工单与设置。
- **高保真布局**：用户摘要；KYC/资格；APT；Power；OTC；Security；Support；Settings。
- **I18N Copy Contract**：`page.m_me_001.title` / `page.m_me_001.description` / `page.m_me_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`个人中心`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：个人摘要卡 + KYC/安全状态 + 功能分组列表；比首页更轻。
- **首屏视觉结构**：沿用本页业务布局——用户摘要；KYC/资格；APT；Power；OTC；Security；Support；Settings。；第一屏只保留一个最主要 CTA。
- **品牌应用**：通用：白/浅灰为主，品牌蓝作 CTA 和链接；金色默认不使用。
- **关键尺寸**：Profile card 128px；菜单行 56px；分组间距 20px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `560px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不把资产数字做成用户中心第一视觉焦点。
- **读取数据**：User summary、Admission、Asset/Power summary。
- **主要交互**：进入各模块。
- **跳转/返回**：→各 Me 子页。
- **必须画出的状态**：局部卡片错误；受限提示。
- **权限/限制**：本人。
- **接口参考**：`GET /api/v1/me/summary`
- **页面验收**：不要在这里放复杂业务操作，只做入口和摘要。
- **人话备注**：Me 是入口页，不是第二个首页。

### `M-ASSET-001｜APT 资产` · P0
- **页面目标**：看清 APT Quantity 的可用/冻结/待确认状态，并快速进入流水、Power、OTC 挂买/挂卖。
- **V6 高保真布局**：APT Quantity Summary → 状态拆分 → 最近流水 → `OTCQuickAction（挂买 / 挂卖）` → Power 入口 → Reference Valuation（若展示必须标来源/时间/未实现）→ 风险说明。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：AptQuantityCard、AssetBreakdown、LedgerPreview、OTCQuickAction、PowerEntry、ReferenceValuationNotice
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_asset_001.title` / `page.m_asset_001.description` / `page.m_asset_001.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。`APT` 锁定不翻译；「挂买 / 挂卖」各 locale 自然本地化。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：AptAccount、recent ledger。
- **主要交互**：查看流水；进入 Power；首屏快捷挂买/挂卖；查看 OTC 市场。
- **主 CTA**：挂买 / 挂卖
- **跳转 / 返回**：→M-ASSET-002 / M-OTC-001 / M-POWER-001。
- **必须画出的状态**：正常 / Frozen / Pending / ledger partial error / OTC restricted。
- **权限 / 业务边界**：资产可见与交易权限分开。；APT Quantity、Reference Valuation、Platform Realized Revenue、Robot Reward 必须继续分开；不得把 Reference Valuation 称为收入或兑付价。
- **接口参考**：`GET /users/{id}/asset-ledger（summary variant）`
- **页面验收**：挂买/挂卖首屏可见但不新增 Bottom Tab；资产数字均有单位、状态、更新时间/快照。
- **人话备注**：APT 页先讲“我有多少、什么状态”，再给 OTC 动作；不是币价钱包。

### `M-ASSET-002｜APT 流水列表` · P0
- **页面目标**：按类型/状态/日期查每笔 APT 变化。
- **高保真布局**：筛选；流水列表；方向；数量；状态；来源对象。
- **I18N Copy Contract**：`page.m_asset_002.title` / `page.m_asset_002.description` / `page.m_asset_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`流水列表页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：筛选 + 按日期分组流水；类型、数量、状态、来源、时间一眼可读。
- **首屏视觉结构**：沿用本页业务布局——筛选；流水列表；方向；数量；状态；来源对象。；第一屏只保留一个最主要 CTA。
- **品牌应用**：资产/OTC：白底 + Navy/Blue；不使用涨跌红绿作为主要视觉语言。
- **关键尺寸**：流水行 68px+；日期 header 32px；筛选 40px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `720px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不隐藏负向/冲正记录；不要用纯颜色表示正负。
- **读取数据**：AptLedgerEntry[]。
- **主要交互**：筛选；打开详情。
- **跳转/返回**：→M-ASSET-003。
- **必须画出的状态**：Empty；分页失败。
- **权限/限制**：本人只读。
- **接口参考**：`GET /users/{id}/asset-ledger`
- **页面验收**：cursor pagination；历史不可消失。
- **人话备注**：账本列表不是通知列表，要支持真正查记录。

### `M-ASSET-003｜APT 流水详情` · P0
- **页面目标**：解释一笔 APT 变化的来源、状态和关联对象。
- **高保真布局**：entry_id；数量；方向；状态；source；rule/snapshot；时间；关联对象；reversal。
- **I18N Copy Contract**：`page.m_asset_003.title` / `page.m_asset_003.description` / `page.m_asset_003.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`流水详情页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：金额/数量不是唯一主角；对象、来源、状态、批次、版本和关联记录完整展示。
- **首屏视觉结构**：沿用本页业务布局——entry_id；数量；方向；状态；source；rule/snapshot；时间；关联对象；reversal。；第一屏只保留一个最主要 CTA。
- **品牌应用**：资产/OTC：白底 + Navy/Blue；不使用涨跌红绿作为主要视觉语言。
- **关键尺寸**：详情卡 120px+；Key-value 行 44px+；关联入口 44px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `640px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不把参考估值当已实现收入。
- **读取数据**：AptLedgerEntry。
- **主要交互**：打开关联 Robot/Prediction/OTC；争议。
- **跳转/返回**：按 related_object 跳转。
- **必须画出的状态**：pending/posted/reversed/disputed。
- **权限/限制**：本人数据；证据安全摘要。
- **接口参考**：`GET /users/{id}/asset-ledger/{entry_id}`
- **页面验收**：reversed 必须显示原 entry 和反向 entry。
- **人话备注**：余额争议最终都要能落到具体一笔流水。

### `M-POWER-001｜Power` · P0
- **页面目标**：把 Power 做成用户真正能理解的“可消耗、可恢复操作资源”，看懂当前容量、使用情况、恢复状态，以及哪些动作会产生 Power 影响。
- **V6.1 高保真布局**：`PowerMeter(Battery) → Available/Frozen/Consumed/Released/Recovering → Power Cap 与 Robot Level 来源 → 最近 7 日变化 → Power 使用场景 → PowerImpactSummary → Frozen/Related Actions → OTCQuickAction → Rule Meta`。
- **V6.1 视觉主任务**：Power Battery 是首屏主视觉，但不要做成游戏体力条；明确“当前有多少、上限多少、正在恢复多少、为什么变化”。
- **推荐组件**：PowerMeter(Battery)、PowerBreakdown、PowerTrend、PowerImpactSummary、PowerUsageList、RelatedActionList、OTCQuickAction
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；Power Battery 视觉高度建议 20–28px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器。
- **I18N Copy Contract**：`page.m_power_001.*` + `power.available / power.frozen / power.consumed / power.released / power.recovering / power.cap / power.usage.*`。`Power` 锁定不翻译。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；涉及 Action Preview 时增加 `Confirm / Processing / Success / Failed / Unknown Result`。
- **读取数据**：PowerPosition、Power Ledger、Robot Level / power_cap_source、next_restore_at、related_actions、allowed_actions。
- **主要交互**：查看 7 日变化；查看 Power 为什么被冻结/消耗/释放；查看 Robot Level 对 Power Cap 的影响；进入相关 OTC 订单；进入挂买/挂卖；刷新 Power。
- **主 CTA**：挂买 / 挂卖；其他需要 Power 的动作从对应业务页面进入，不在 Power Root 直接发起。
- **跳转 / 返回**：→M-OTC-001 / 关联业务对象。
- **必须画出的状态**：Available 正常 / Power 低 / 0 Available / Frozen / Recovering / Service unavailable / Related action restricted。
- **权限 / 业务边界**：
  - Robot 成长可以提升 Power Cap，但具体每级数值来自 Active Robot Rule / Parameter，前端不得计算。
  - OTC Sell 固定遵循 `freeze → filled portion consumes → unfilled stays frozen → cancel/expiry releases`。
  - Withdrawal 与 Robot Start / Auto-execution Activation 会使用 Power，但具体门槛、数量和扣减时点由服务端 `PowerImpactPreview` + Active Parameter 返回；原型不得硬编码生产值。
  - Prediction P0 默认不消耗 Power。
- **接口参考**：`GET /ai/users/{id}/computing-power；GET /ai/users/{id}/computing-power-ledger`；所有需要 Power 的写操作由各自业务 Action API 同时返回 `power_impact`。
- **页面验收**：用户能解释 Power 的 Available/Frozen/Consumed/Released/Recovering；能看懂 Power Cap 与 Robot Level 的关系；OTC Partial Fill/Cancel 能对上 Power Ledger；Withdrawal/Robot Start 如果展示 Power 影响，必须来自 Preview，不得前端猜值。
- **人话备注**：Power 是“重要操作会用到、之后还能按规则恢复的操作额度”。不是手续费，也不是收益数字。

### `M-OTC-001｜OTC 市场` · P0
- **页面目标**：把挂买/挂卖作为首屏高频动作，同时维持 Controlled Matching，而不是做交易所终端。
- **V6 高保真布局**：Header → 首屏 `OTCQuickAction：挂买 / 挂卖` 双 CTA → Capacity/Power Summary → 市场参考信息 → 订单列表/状态 → Liquidity Risk → 我的订单入口。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：OTCQuickAction、OTCCapacityCard、PowerImpactSummary、ReferencePriceMeta、OTCOrderCard、LiquidityNotice
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_otc_001.title` / `page.m_otc_001.description` / `page.m_otc_001.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。`OTC` 锁定不翻译；动作词按 locale 本地化。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：OTC eligibility、order book、capacity、power。
- **主要交互**：首屏进入挂买或挂卖；查看市场订单；看 Capacity/Power；进入我的订单。
- **主 CTA**：挂买 / 挂卖
- **跳转 / 返回**：→M-OTC-002 / M-OTC-005。
- **必须画出的状态**：Available / Restricted / Empty / Matching / Partial-heavy / Service unavailable。
- **权限 / 业务边界**：功能权限服务端决定。；OTC = Controlled Matching；禁止 K-Line、Order Book Trading Terminal、红绿 Buy/Sell 博彩感、Guaranteed Fill、Guaranteed Redemption。
- **接口参考**：`GET /otc/order-book；GET /api/v1/me/otc-capacity`
- **页面验收**：挂买与挂卖两个动作无需滚动即可看见；不把一个藏到“更多”；参考价必须带来源/时点且不是官方兑付价。
- **人话备注**：用户来 OTC 最常做的就是挂买或挂卖，不应该找三层菜单。

### `M-OTC-002｜OTC 挂单输入` · P0
- **页面目标**：输入挂买或挂卖条件，并在输入阶段就看清 Capacity、Fee 以及挂卖的 Power 影响。
- **V6 高保真布局**：Buy/Sell Mode Header → 数量/价格等输入 → Capacity → Fee → `PowerImpactSummary（Sell only）` → Settlement Method → Rule/Limits → 下一步。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：OTCModeHeader、LabeledInput、CapacitySummary、FeeSummary、PowerImpactSummary、SettlementMethodCard、StickyCTA
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_otc_002.title` / `page.m_otc_002.description` / `page.m_otc_002.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。「挂买 / 挂卖 / 预计冻结」必须自然本地化。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：Capacity、SettlementMethod、parameter constraints。
- **主要交互**：输入；切换挂买/挂卖（保留兼容字段）；请求 Quote；进入确认。
- **主 CTA**：下一步
- **跳转 / 返回**：→M-OTC-003。
- **必须画出的状态**：Buy / Sell；field invalid；Capacity insufficient；Power insufficient；Restricted；Quote unavailable。
- **权限 / 业务边界**：客户端校验只做体验，最终服务端。；Buy 不消费 Sell Power；Sell 才按规则计算 expected freeze。页面只显示服务端 Quote，不由客户端用数量自算最终 Power。
- **接口参考**：`POST /api/v1/otc/quotes（开发新增统一 quote contract）`
- **页面验收**：挂卖时确认前能看见“可用 Power / 本次预计冻结”；挂买时不显示误导性的 Power 消耗。
- **人话备注**：用户在点下一步之前就要知道这单会占用什么。

### `M-OTC-003｜OTC 订单确认` · P0
- **页面目标**：在最终提交前，把订单、Fee、Power Freeze、匹配规则和取消/释放规则一次讲清楚。
- **V6 高保真布局**：Order Summary → Buy/Sell → Quantity/Price/Reference → Fee → Power Impact（Sell）→ Settlement → Matching/Partial/Cancel Rules → Consent/Risk → Fixed CTA。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：ConfirmSummary、PowerImpactSummary、MatchingRuleNotice、ConsentBlock、StickyCTA
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_otc_003.title` / `page.m_otc_003.description` / `page.m_otc_003.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：OtcQuote、Eligibility snapshot。
- **主要交互**：返回修改；确认挂单；需要时 MFA；提交后进入 M-OTC-004。
- **主 CTA**：确认挂买 / 确认挂卖
- **跳转 / 返回**：→M-OTC-004。
- **必须画出的状态**：Ready / Quote expired / Restricted / MFA required / Submitting / Unknown Result。
- **权限 / 业务边界**：高风险可能 MFA。；Submitted ≠ Completed；Sell Power 只是先冻结，只有 filled portion 消耗；未成交部分按取消/到期规则释放。
- **接口参考**：`POST /otc/orders`
- **页面验收**：确认页必须明确区分“提交订单”和“成交”；Sell 的 Power Freeze 数值来自服务端 Quote。
- **人话备注**：这是挂单确认，不是成交确认。

### `M-OTC-004｜OTC 提交结果` · P0
- **页面目标**：明确告诉用户订单只是已提交、审核中、撮合中还是创建失败，并给出可追踪入口。
- **V6 高保真布局**：Result Hero → Order ID → Submitted/Review/Matching Status → 初始冻结（Sell）→ 下一步 → 我的订单 / 详情。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：ResultHero、OrderReference、PowerImpactSummary、NextStepCard、ActionGroup
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_otc_004.title` / `page.m_otc_004.description` / `page.m_otc_004.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：OtcOrderCreateResult。
- **主要交互**：查看订单详情；去我的订单；继续市场；Unknown 查询原请求。
- **主 CTA**：查看订单
- **跳转 / 返回**：→M-OTC-006 / M-OTC-001。
- **必须画出的状态**：Submitted / Review / Matching / Failed-No-Effect / Unknown Result。
- **权限 / 业务边界**：本人。；绝不能把 Submitted 显示成 Completed；Unknown 禁止重复提交。
- **接口参考**：`GET /otc/orders/{id}`
- **页面验收**：每个结果都显示是否产生 APT/Power 冻结；失败说明无影响或已恢复。
- **人话备注**：订单“收到了”不等于“成交了”。

### `M-OTC-005｜我的 OTC 订单` · P0
- **页面目标**：快速找到审核、撮合、Partial、Completed、Cancelled、Expired、Disputed 的订单。
- **V6 高保真布局**：Status Tabs/Filters → 订单卡列表 → `OTCPartialProgress` → Sell Power 状态 → 更新时间 → 详情。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：OrderStatusTabs、OTCOrderCard、OTCPartialProgress、PowerStatusMini、FilterSheet
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_otc_005.title` / `page.m_otc_005.description` / `page.m_otc_005.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：OtcOrder[]。
- **主要交互**：筛选；打开详情；允许时快捷取消进入确认；返回保留筛选/滚动。
- **主 CTA**：查看详情
- **跳转 / 返回**：→M-OTC-006。
- **必须画出的状态**：Review / Matching / Partial / Completed / Cancelled / Expired / Rejected / Disputed / Empty / Pagination error。
- **过期订单**：Expired 后未成交 APT/Power 按规则释放；Expired 不等于用户主动 Cancelled；Partial+Expired 只释放 remaining 部分。
- **权限 / 业务边界**：本人。；Partial 必须显示成交比例与剩余冻结；Completed 才是最终成交。
- **接口参考**：`GET /otc/users/{id}/orders`
- **页面验收**：Partial 订单一眼能看出 filled / remaining；取消/到期后的 Released 状态更新。
- **人话备注**：订单列表首先帮用户回答“这单现在到哪了”。

### `M-OTC-006｜OTC 订单详情` · P0
- **页面目标**：完整追踪一笔 OTC 从 Submitted 到 Matching/Partial/Completed/Cancelled/Disputed 的订单、成交、APT 和 Power 变化。
- **V6 高保真布局**：Order Status Hero → Order Facts → `OTCPartialProgress` → Trades → Power Flow Timeline → APT Ledger Links → Settlement → Actions/Dispute。
- **V6 视觉主任务**：首屏先回答“现在是什么状态、最重要的下一步是什么”；信息可以丰富，但不能变成财富/博彩/交易终端大屏。
- **推荐组件**：OrderStatusHero、OTCPartialProgress、TradeList、PowerImpactSummary、PowerFlowTimeline、LedgerLinks、ActionPanel
- **关键尺寸 / Safe Area**：基准 390×844；左右 16px；主要触控目标 >=44×44px；H5 >=768px 只增宽容器，不改变一级 IA。
- **I18N Copy Contract**：`page.m_otc_006.title` / `page.m_otc_006.description` / `page.m_otc_006.primary_action`；V6 专项文案从 `/i18n/*.json` 读取。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态必须另出 Variant，不允许只改一行文字。
- **读取数据**：OtcOrder、OtcTrade、Ledger、Power ledger、allowed_actions。
- **主要交互**：取消允许订单；查看 trades；查看 APT/Power 流水；发起争议；查询状态。
- **主 CTA**：取消剩余 / 查看流水 / 提交争议（按 allowed_actions）
- **跳转 / 返回**：→M-ASSET-003 / M-SUPPORT-002。
- **必须画出的状态**：Review / Matching / Partial / Completed / Cancelled / Rejected / Disputed / Cancel processing / Unknown。
- **权限 / 业务边界**：取消按钮由服务端 allowed_actions。；Power Flow 必须遵循：提交 Sell 冻结 → 每笔 fill 按对应部分消耗 → 未成交继续冻结 → 取消/到期释放未成交部分。不得直接编辑终态。
- **接口参考**：`GET /otc/orders/{id}；POST /otc/orders/{id}/cancel；GET /otc/trades`
- **页面验收**：Partial/Cancel 例子能在 Timeline 对上 Power ledger；每个状态都有时间和关联记录。
- **人话备注**：用户要能把“成交了多少”和“Power 为什么少了/释放了”对起来。

### `M-SEC-001｜安全中心` · P0
- **页面目标**：集中看 MFA、设备、Session、登录记录和密码安全。
- **高保真布局**：security summary；MFA；devices；sessions；login audit；password。
- **I18N Copy Contract**：`page.m_sec_001.title` / `page.m_sec_001.description` / `page.m_sec_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`安全中心`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：安全状态总览 + MFA/Device/Session/Password 分组；风险提示克制。
- **首屏视觉结构**：沿用本页业务布局——security summary；MFA；devices；sessions；login audit；password。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：安全卡 120px；菜单行 56px；警示块内边距 16px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `560px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不靠大面积红色制造恐慌；敏感信息必须脱敏。
- **读取数据**：SecurityProfile、SessionDevice[]、LoginAudit[]。
- **主要交互**：绑定/管理 MFA；设备；改密码；撤销 session。
- **跳转/返回**：→M-SEC-002 / M-AUTH-004。
- **必须画出的状态**：风险限制；操作失败；依赖不可用。
- **权限/限制**：敏感操作二次验证。
- **接口参考**：`GET /api/v1/me/security`
- **页面验收**：不能展示完整 IP/敏感设备指纹。
- **人话备注**：安全页只说用户能理解的信息，不暴露内部风控模型。

### `M-SEC-002｜MFA / 设备 / Session 管理` · P0
- **页面目标**：绑定验证器、查看并撤销其他会话。
- **高保真布局**：MFA enrollment；二维码/密钥安全流程；设备列表；revoke。
- **I18N Copy Contract**：`page.m_sec_002.title` / `page.m_sec_002.description` / `page.m_sec_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`设置列表页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：使用系统型分组列表；开关、进入箭头和状态清楚。
- **首屏视觉结构**：沿用本页业务布局——MFA enrollment；二维码/密钥安全流程；设备列表；revoke。；第一屏只保留一个最主要 CTA。
- **品牌应用**：身份/安全：浅色为主，品牌蓝建立可信感；状态色只用于真实状态。
- **关键尺寸**：行高 56px；Section gap 24px；Switch 44px touch target。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `560px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不在设置页塞业务营销内容。
- **读取数据**：MfaEnrollment、SessionDevice。
- **主要交互**：绑定；验证；撤销 session/device。
- **跳转/返回**：成功回 Security。
- **必须画出的状态**：验证失败；不能撤销关键当前会话；risk held。
- **权限/限制**：本人 + MFA。
- **接口参考**：`POST /api/v1/me/mfa；GET /api/v1/me/sessions；POST /api/v1/me/sessions/{id}/revoke`
- **页面验收**：撤销成功后服务端立即失效对应 session。
- **人话备注**：别让“删除设备”只是前端删一行。

### `M-SUPPORT-001｜帮助中心 / 工单列表` · P0
- **页面目标**：找帮助并查看自己的工单。
- **高保真布局**：FAQ；分类；创建工单；ticket list/status。
- **I18N Copy Contract**：`page.m_support_001.title` / `page.m_support_001.description` / `page.m_support_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`帮助 / 工单中心`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：搜索/常见问题弱化，当前工单和创建入口优先。
- **首屏视觉结构**：沿用本页业务布局——FAQ；分类；创建工单；ticket list/status。；第一屏只保留一个最主要 CTA。
- **品牌应用**：通用：白/浅灰为主，品牌蓝作 CTA 和链接；金色默认不使用。
- **关键尺寸**：搜索 48px；工单行 72px+；主 CTA 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `640px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不做复杂客服工作台样式。
- **读取数据**：FAQ config、Ticket[]。
- **主要交互**：搜索帮助；创建；打开工单。
- **跳转/返回**：→M-SUPPORT-002 / 003。
- **必须画出的状态**：Empty；列表失败。
- **权限/限制**：本人；FAQ 可公开部分。
- **接口参考**：`GET /api/v1/help；GET /api/v1/me/tickets`
- **页面验收**：相同问题已有工单时提示继续原工单。
- **人话备注**：先帮用户自助，再进入人工工单。

### `M-SUPPORT-002｜创建工单 / 申诉` · P0
- **页面目标**：提交可处理的问题并绑定具体对象。
- **高保真布局**：category；related object；description；attachments；contact；submit。
- **I18N Copy Contract**：`page.m_support_002.title` / `page.m_support_002.description` / `page.m_support_002.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`表单页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：浅色页面，单列任务，首屏只突出表单和一个主 CTA。
- **首屏视觉结构**：沿用本页业务布局——category；related object；description；attachments；contact；submit。；第一屏只保留一个最主要 CTA。
- **品牌应用**：通用：白/浅灰为主，品牌蓝作 CTA 和链接；金色默认不使用。
- **关键尺寸**：输入框 48px；表单项间距 16px；CTA 48px；表单卡圆角 16px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `520px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不做营销 Banner；不使用金色主按钮。
- **读取数据**：allowed categories、accessible objects、upload policy。
- **主要交互**：填写；上传；提交。
- **跳转/返回**：成功→M-SUPPORT-003。
- **必须画出的状态**：上传失败；字段错误；duplicate case。
- **权限/限制**：只能关联本人对象。
- **接口参考**：`POST /api/v1/me/tickets；POST /api/v1/uploads`
- **页面验收**：失败保留草稿；附件单项可重试。
- **人话备注**：工单最好绑定具体 order/ledger/robot，不要只让用户写一大段话。

### `M-SUPPORT-003｜工单详情` · P0
- **页面目标**：看处理进度、回复、补件和最终结论。
- **高保真布局**：status；SLA（若批准）；timeline；messages；attachments；related objects；reply。
- **I18N Copy Contract**：`page.m_support_003.title` / `page.m_support_003.description` / `page.m_support_003.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`工单对话详情`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：顶部工单状态 + 对话/时间线 + 附件 + 底部回复框。
- **首屏视觉结构**：沿用本页业务布局——status；SLA（若批准）；timeline；messages；attachments；related objects；reply。；第一屏只保留一个最主要 CTA。
- **品牌应用**：通用：白/浅灰为主，品牌蓝作 CTA 和链接；金色默认不使用。
- **关键尺寸**：状态区 80px；消息块最大宽 85%；回复区 56–72px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `720px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不隐藏系统状态变化；附件不能和普通文本混为一行。
- **读取数据**：Ticket、TicketMessage[]。
- **主要交互**：回复；补件；看关联对象。
- **跳转/返回**：深链到相关业务页。
- **必须画出的状态**：submitted/in_progress/waiting_user/under_review/resolved/closed。
- **权限/限制**：本人。
- **接口参考**：`GET /api/v1/me/tickets/{id}；POST /api/v1/me/tickets/{id}/messages`
- **页面验收**：resolved/closed 前必须有用户可见结论。
- **人话备注**：“处理中”要有阶段和更新时间，不要永远一个状态。

### `M-SETTINGS-001｜设置` · P0
- **页面目标**：管理语言、时区、通知偏好和基础应用设置。
- **高保真布局**：language；timezone；notifications；legal/help；logout。
- **I18N Copy Contract**：`page.m_settings_001.title` / `page.m_settings_001.description` / `page.m_settings_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`设置列表页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：使用系统型分组列表；开关、进入箭头和状态清楚。
- **首屏视觉结构**：沿用本页业务布局——language；timezone；notifications；legal/help；logout。；第一屏只保留一个最主要 CTA。
- **品牌应用**：通用：白/浅灰为主，品牌蓝作 CTA 和链接；金色默认不使用。
- **关键尺寸**：行高 56px；Section gap 24px；Switch 44px touch target。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `560px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不在设置页塞业务营销内容。
- **读取数据**：UserPreference。
- **主要交互**：修改偏好；退出。
- **跳转/返回**：Logout→M-AUTH-001。
- **必须画出的状态**：保存失败；离线。
- **权限/限制**：本人。
- **接口参考**：`GET/PUT /api/v1/me/preferences；POST /api/v1/auth/logout`
- **页面验收**：语言变化不能改变业务数值/规则语义。
- **人话备注**：设置页不要混资产和安全高风险操作。

### `M-AI-001｜AI 数据 / Signal 详情` · P1
- **页面目标**：解释 AI 数据与信号来源、时间和非保证属性。
- **高保真布局**：signal summary；source/time；historical context；explanation。
- **I18N Copy Contract**：`page.m_ai_001.title` / `page.m_ai_001.description` / `page.m_ai_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`AI 数据详情`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：卡片 + 图表 + 数据口径；视觉强调更新时间和数据类型，而非预测必然性。
- **首屏视觉结构**：沿用本页业务布局——signal summary；source/time；historical context；explanation。；第一屏只保留一个最主要 CTA。
- **品牌应用**：通用：白/浅灰为主，品牌蓝作 CTA 和链接；金色默认不使用。
- **关键尺寸**：图表高 220–280px；指标卡 96px+；说明块 16px padding。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `720px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不使用保证性箭头、暴涨曲线或“必胜”视觉。
- **读取数据**：SignalSummary。
- **主要交互**：筛选；查看说明。
- **跳转/返回**：返回 Home。
- **必须画出的状态**：data delayed/unavailable。
- **权限/限制**：只读。
- **接口参考**：`GET /api/v1/ai/signals`
- **页面验收**：必须标实时/延迟/估算。
- **人话备注**：P1，不阻塞 P0。

### `M-GROWTH-001｜Referral / Team` · P1
- **页面目标**：查看邀请关系和符合条件的候选/已结算奖励。
- **高保真布局**：invite；relationship；candidate/held/payable/paid；rules。
- **I18N Copy Contract**：`page.m_growth_001.title` / `page.m_growth_001.description` / `page.m_growth_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`Growth 二级页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：关系/活动/候选奖励分块，品牌色轻量使用。
- **首屏视觉结构**：沿用本页业务布局——invite；relationship；candidate/held/payable/paid；rules。；第一屏只保留一个最主要 CTA。
- **品牌应用**：通用：白/浅灰为主，品牌蓝作 CTA 和链接；金色默认不使用。
- **关键尺寸**：摘要卡 104px；关系行 72px+；CTA 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `720px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不做层级金字塔、拉人头树形收益图。
- **读取数据**：Referral/Reward objects。
- **主要交互**：分享；看记录。
- **跳转/返回**：Me/Robot 子入口。
- **必须画出的状态**：资格不足；budget closed。
- **权限/限制**：由服务端资格决定。
- **接口参考**：`GET /api/v1/me/referrals`
- **页面验收**：不能承诺永久佣金或“拉人头收益”。
- **人话备注**：P1，不放一级导航。

### `M-PREDICT-FREE-001｜免费 YES/NO` · P1/Sandbox
- **页面目标**：提供不含真实价值的学习/互动预测。
- **高保真布局**：question；yes/no；free points；result/learning。
- **I18N Copy Contract**：`page.m_predict_free_001.title` / `page.m_predict_free_001.description` / `page.m_predict_free_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`赛事详情页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：赛事头部 + 三方向 / 选项 + 数量/规则 + CTA；信息顺序服务决策。
- **首屏视觉结构**：沿用本页业务布局——question；yes/no；free points；result/learning。；第一屏只保留一个最主要 CTA。
- **品牌应用**：Prediction：白底 + 品牌蓝/Cyan；足球只作为赛事识别元素，不做博彩视觉。
- **关键尺寸**：赛事 Hero 132–156px；1X2 按钮高 56px；输入 48px；CTA 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `720px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不隐藏 Draw；不把倍数/估算做成保证性大数字。
- **读取数据**：FreePredictionMarket。
- **主要交互**：选 YES/NO；提交。
- **跳转/返回**：Prediction 子入口。
- **必须画出的状态**：closed/ended。
- **权限/限制**：必须是不可兑付 points。
- **接口参考**：`/api/v1/free-predictions/*`
- **页面验收**：不得与真实 APT 或收入混淆。
- **人话备注**：只在 Sandbox/P1 启用。

#### Prediction 多状态轴展示补充

> 本表是页面 Variant 和聚合展示状态，不新增领域状态。Canonical Market、Result、Settlement、PredictionOrder 状态以 05 为准。

各 Prediction 页面必须画出的状态按以下对象分别定义，不得用一个“赛事状态”覆盖所有状态轴：

- **Market 状态**：draft / open / closing / locked / awaiting_result / settlement / settled / void / exception
- **Result 状态**：provisional / official / disputed / corrected
- **Settlement 状态**：queued / calculating / review / payable / paid / failed
- **PredictionOrder 状态**：submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected

关键区分：
- Result official 不等于 Settlement 完成。
- Market settled、Order settled、Settlement paid 必须按对象分别解释。
- 03 中列出的状态是页面 Variant，canonical 以 05 为准。

### `M-MIGRATION-001｜APT-I → APT-C Migration` · Future/CLOSED
- **页面目标**：未来满足 Gate 后处理数量迁移。
- **高保真布局**：eligibility；quantity；wallet；confirmation；finality timeline。
- **I18N Copy Contract**：`page.m_migration_001.title` / `page.m_migration_001.description` / `page.m_migration_001.primary_action`；通用 action/state 和敏感域文案读取 `/i18n/*.json` 与 `ui-copy-manifest.json`，不得直接显示 raw enum。
- **视觉模板**：`分步流程页`。
- **画布 / 容器**：基准 `390×844`；左右边距 16px；Mobile 卡片圆角 16px；H5 同 IA。
- **视觉主任务**：顶部 Stepper + 当前步骤表单 + 固定 CTA，复杂内容分段。
- **首屏视觉结构**：沿用本页业务布局——eligibility；quantity；wallet；confirmation；finality timeline。；第一屏只保留一个最主要 CTA。
- **品牌应用**：通用：白/浅灰为主，品牌蓝作 CTA 和链接；金色默认不使用。
- **关键尺寸**：Stepper 40–48px；输入 48px；上传卡最小 96px；CTA 48px。
- **原型 Frame**：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **H5 响应式**：内容最大宽度 `560px`；>=768px 只居中增宽，不改变 4 个一级 IA；固定 CTA 保持可见。
- **无障碍**：主要按钮与图标点击区域 >=44×44px；状态必须有文字；Focus/错误提示不能只靠颜色。
- **视觉禁止**：不把所有步骤塞进一屏；上传失败不能清空整页。
- **读取数据**：MigrationEligibility、MigrationRequest。
- **主要交互**：创建迁移；查 finality。
- **跳转/返回**：Me/Asset 子入口（默认隐藏）。
- **必须画出的状态**：closed/review/broadcast/finality/failed/reversed。
- **权限/限制**：P0 必须 hidden/disabled。
- **接口参考**：`GET /apt/migration-eligibility；POST /apt/migration-requests`
- **页面验收**：未正式开启时不能用 mock 开关放出真实入口。
- **人话备注**：Future 页面可以做设计稿，但生产入口必须关闭。


## 4. Mobile/H5 原型主流程必须可点击

### Flow A · 新用户

```text
注册 → OTP → KYC → KYC状态 → Home
```

### Flow B · Robot

```text
Home → Robot → 启动 → Robot
Robot → 升级 → 升级结果 → 流水
Robot → Rewards → Claim → 流水
```

### Flow C · Prediction

```text
Prediction List → 1X2 Detail → Confirm/Consent → Order Detail
→ Settled
或 → Refund/Correction → Ledger / Appeal
```

### Flow D · OTC

```text
APT → OTC Market → Input → Confirm → Submitted/Review/Matching
→ Partial/Completed/Expired
或 → Cancel/Dispute → Ledger/Support

Expired 等于订单到期自然结束，不等同于 Cancel。
```

### Flow E · Support

```text
任意对象 → Create Ticket → Ticket Detail → Waiting User/Review → Resolved/Closed
```

## 5. 前端原型最终验收

- [ ] 本文所有 P0 page_id 都有独立 Frame/Route。
- [ ] 所有 P0 写操作都有提交中、unknown result、失败无资产影响或需复核状态。
- [ ] 所有深链能返回原列表并恢复筛选/滚动位置。
- [ ] 订单/流水/Reward/Robot 状态使用统一名称。
- [ ] Restricted 页面仍允许用户看历史、退款、工单等合法路径。
- [ ] Prototype 不使用旧 Figma 的页面结构或视觉作为模板。
- [ ] Mock 值显式属于 prototype fixture，不得作为正式参数。
