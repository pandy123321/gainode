# Gainode HIFI Prototype Design System V1.2 · Final

> **DOCUMENT_ROLE = DESIGN_EXECUTION_COMPANION**  
> **VERSION = V1.2 Final**  
> **CURRENT_BASELINE = Gainode Development Ready V6.1 Latest**  
> **SOURCE_OF_TRUTH = 01–08 + /i18n + /assets/logo**  
> **CURRENT_03_VERSION = V2.4**  
> **CURRENT_04_VERSION = V2.2**  
> **CURRENT_05_VERSION = V2.1**  
> **CURRENT_06_VERSION = V2.1**  
> **CURRENT_07_VERSION = V2.1**  
> **CURRENT_08_VERSION = V2.4**  
> **DUPLICATE_DESIGN_SYSTEM = NO**  
> **Gainode_Mobile_H5_Design_System_V1.1.md = MERGED / ARCHIVED**


> **DOCUMENT_STATUS = DESIGN_EXECUTION_COMPANION**  
> **TARGET = Gainode App / Mobile HIFI**  
> **SCOPE = 44 Mobile/H5 Page IDs + Root Visual Anchors + Global Components + Overlays + Interaction Flows**  
> **NOT_A_NEW_SOURCE_OF_TRUTH = YES**  
> 本文档用于把已确认的四个一级页面视觉、Logo、交互结论与 03 V2.4 / 08 V2.4 的规范整合成更易于 UI/原型/Flutter/Vue 执行的“高保真设计伴随文档”。发生冲突时，仍以 01–08 + /i18n + Logo 资产为准。

## 0. 最终收口结论

本版是对 `Gainode_HIFI_Prototype_Design_System_V1.1_Optimized.md` 的 **Micro Revision + Consolidation**。它不新增产品能力、不重新定义 44 个 Page ID、不修改经济模型/API/参数，只把当前权威基线和已经确认的 Mobile/H5 设计系统增量收拢成 AI / Designer / Prototype Agent 可稳定执行的一份伴随文档。

本轮完成：

- 当前权威引用统一更新为 `03 V2.4 / 04 V2.2 / 05 V2.1 / 06 V2.1 / 07 V2.1 / 08 V2.4`。
- 修正 `M-ME-001` 为 `P0 Root`，四个 Root 均为 P0。
- Home 移除 Post Buy / Post Sell / OTC Buy-Sell Quick Action，正式引入 `MyPredictionCard` 与 `TodayTaskCard`。
- 合并 `Gainode_Mobile_H5_Design_System_V1.1.md` 中有效增量，并将其状态改为 `MERGED / ARCHIVED`。
- Fixture 规则改为“内部可标识、正式用户 UI 不显示 Demo/Mock/Sandbox/Fixture”。
- 对齐 Power V6.1、RobotFloatingActionBar、运行阶段一致性、Prediction 正常运营密度、Member Level/XP Guardrail、中文语言污染规则和 AI 单页出图 Hard Gate。
- 增加 `44 / 44 PAGE EXECUTION MATRIX`，作为逐页高保真生成前的执行检查表。

> 本版只是 `DESIGN_EXECUTION_COMPANION`。任何事实冲突时，必须回到 01–08、`/i18n` 与 Logo Assets，而不是反向用本文件覆盖权威基线。

## 1. 权威关系与使用方式

```text
01 = Product Truth
02 = Economic / Business Rule Truth
03 V2.4 = Mobile/H5 Page Execution Truth
04 V2.2 = Admin Page Execution Truth
05 V2.1 = Data / State / Permission / API Truth
06 V2.1 = Parameter Truth
07 V2.1 = Development / Acceptance Truth
08 V2.4 = Global Visual / Interaction / I18N Truth
/i18n = User-visible String Assets
/assets/logo = Official Logo Assets

本文件 V1.2 = DESIGN_EXECUTION_COMPANION
```

### 1.1 冲突处理顺序

1. Page ID、页面目标、布局、状态、交互、Route：读取 `03_MOBILE_H5_HIFI_PROTOTYPE_SPEC_V2.4.md`。
2. Admin 页面：读取 `04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md`。
3. Logo、Color、Typography、Spacing、Radius、Shadow、Motion、Breakpoints、I18N/L10N：读取 `08_VISUAL_DESIGN_SYSTEM_V2.4.md`。
4. 业务字段、对象、状态、权限、`allowed_actions / next_action`、API：读取 `05 V2.1`。
5. Power / Robot / OTC / Prediction 等参数值与 Active Release：读取 `06 V2.1`。
6. 开发与验收：读取 `07 V2.1`。
7. 四张已确认 Root 页面只作为 `VISUAL_ANCHOR`，不能覆盖 01–08 中的业务事实。

### 1.2 本文件禁止成为第二权威源

本文件不得自行定义或修改：

- Economic Rules；
- API / State Machine；
- Parameter / Threshold；
- Power Formula / Power Cap 数值；
- Robot Reward Formula / Coefficient；
- Member Level / XP Formula；
- Page ID / Priority；
- Production Legal / Financial / Risk Copy。

### 1.3 Duplicate Design System 收口

`Gainode_Mobile_H5_Design_System_V1.1.md` 中已确认且与当前基线一致的内容已经并入本 V1.2，主要包括：

`MyPredictionCard / TodayTaskCard / PowerBattery / RobotFloatingActionBar / Root Responsibility Matrix / AI Image Generation Guardrails / Responsive Rules / I18N Layout Rules`

其后续状态：

```text
Gainode_Mobile_H5_Design_System_V1.1.md = MERGED / ARCHIVED
PARALLEL_MAINTENANCE = FORBIDDEN
DUPLICATE_DESIGN_SYSTEM = NO
```

## 2. 已确认四个 Root 页面：Visual Anchor DNA

四个 Root：`M-HOME-001 / M-ROBOT-001 / M-PREDICT-001 / M-ME-001` 是当前全站视觉锚点。它们共同遵守 `Light App Shell + White/Gray-50 Body + Gainode Blue/Navy + 少量 Cyan/Gold`，但各自承担不同的首屏任务。

### 2.1 Root Responsibility Matrix

| Root | 核心职责 | 首屏要回答 | 不应承担 |
|---|---|---|---|
| Home | 今日状态、今日行动、Robot Reward 提醒、我的竞猜状态、热门竞猜、AI Data、Growth/Activity | 今天发生了什么？还有什么值得我处理？ | 完整资产管理、Post Buy/Post Sell、OTC 操作面板、大面积 APT/Power 操作 |
| Robot | Robot Status、Robot Level、Power、Runtime、Reward、Claim、Activity | Robot 是否运行？Power 是否够？Reward 是否可领取？ | Crypto Trading、Arbitrage、APR/APY、财富大盘 |
| Prediction | 竞猜发现、参与、Community Picks、My Predictions、Result | 有哪些赛事？何时截止？我参与的结果如何？ | Sportsbook、Odds、Bookmaker、Stake UI、默认必赢推荐 |
| Me | APT、Power、OTC、Security、KYC、Account、Support、Settings | 我的账户/资源/安全状态是什么？去哪里管理？ | 首页运营推荐、财富榜、收益大屏 |

### 2.2 Shared Visual DNA

- 同一 AppShell / Header / BottomNav / Card Radius / Typography / Icon / CTA / Sheet / Modal。
- 页面背景为浅色；Robot 允许 `Dark Robot Hero + Light Body`，但不得整页改成暗色终端。
- 真实运营数据增加时优先使用紧凑行、Segment、时间分组、轻列表、折叠和留白，不增加巨大卡片数量。
- Gold 只在 Reward/Level/Milestone 等极少场景出现，不能变成财富主题。
- 四张 Root 设计图中与业务基线冲突的内容一律纠偏；视觉风格可以继承，错误业务文案/数字不能继承。

### 2.3 Visual Anchor 业务纠偏

- **Home**：不做收益/资产大盘，不放 Post Buy / Post Sell；最终结构见 §10 M-HOME-001。
- **Robot**：不展示 APR/APY/固定收益；Reward 必须带周期、状态、时间、Rule/Snapshot。
- **Prediction**：Home / Draw / Away 三方向同级；百分比只能表示 `COMMUNITY_PICK_DISTRIBUTION`。
- **Me**：概念图中的未经授权 USD 收益、Premium、Member XP 等只可视为视觉占位；没有产品权威依据时不得进入正式 UI。

## 3. Logo 与品牌资产

| Asset | App 用途 |
|---|---|
| `logo_source_01_primary_light.png` | 登录/注册/品牌入口浅色背景 |
| `logo_source_02_symbol_transparent.png` | App 内小型品牌位、头像、Loading |
| `logo_source_03_app_icon_dark.png` | App/PWA Icon；禁止当普通 UI 图标 |
| `logo_source_04_vertical_transparent.png` | Splash/品牌介绍 |
| `logo_source_05_horizontal_transparent.png` | H5 Header；必要时 Root 视觉锚点追踪 |
| `logo_source_06_mono_light.png` | 深蓝背景/Sidebar |
| `logo_source_07_dark_splash.png` | App Splash |
| `logo_source_08_mono_dark.png` | 白底低彩/打印场景 |

Logo 硬规则：完整 Logo 四周安全空间 >= symbol 宽度 25%；Mobile 完整字标最小宽度 104px；单独 Symbol 最小 24×24px，常用 28/32/40px。Root 视觉稿中 Logo 的较大呈现属于已确认视觉参考，**不要把 Logo 缩成普通 20px 导航图标**；但也不要在每个二级页面重复大型完整字标。

## 4. Design Tokens · MIRROR_OF_08_V2.4

> 本节所有 Color / Radius / Spacing / Typography / Shadow / Motion / Breakpoints 都只是 `MIRROR_OF_08_V2.4` 的 AI-Friendly 摘要。`08_VISUAL_DESIGN_SYSTEM_V2.4.md` 是唯一 Token Source of Truth；本节不得独立演进或覆盖 08。


### 4.1 Brand
```css
--brand-navy-950:#071226;
--brand-navy-900:#05285D;
--brand-blue-800:#024EC2;
--brand-blue-600:#057CF1;
--brand-cyan-500:#06A9FE;
--brand-cyan-300:#3ACFFD;
--brand-gold-500:#F4D016;
--brand-gold-300:#FFE27A;
```

### 4.2 Neutral / Status
```css
--gray-950:#0F172A; --gray-800:#1E293B; --gray-700:#334155;
--gray-600:#475569; --gray-500:#64748B; --gray-400:#94A3B8;
--gray-300:#CBD5E1; --gray-200:#E2E8F0; --gray-100:#F1F5F9;
--gray-50:#F8FAFC; --white:#FFFFFF;
--success-600:#059669; --success-100:#D1FAE5;
--warning-600:#D97706; --warning-100:#FEF3C7;
--danger-600:#DC2626; --danger-100:#FEE2E2;
--info-600:#0284C7; --info-100:#E0F2FE;
```

用色比例：白/浅灰 70–80%；Navy/Blue 15–25%；Gold <=5%。

### 4.3 Typography

| Token | Size/Line | Weight | Usage |
|---|---:|---:|---|
| Display | 28/36 | 700 | 少量 Hero |
| H1 | 24/32 | 700 | 页面标题 |
| H2 | 20/28 | 650 | 核心卡标题 |
| H3 | 17/24 | 600 | 分组标题 |
| Body | 15/22 | 400 | 正文 |
| Body Strong | 15/22 | 600 | 强调正文 |
| Meta | 13/18 | 400 | 时间/版本/ID |
| Caption | 12/16 | 400 | 最小辅助文字 |
| Data L | 28/34 | 700 | 少量关键数量 |
| Data M | 20/28 | 650 | 卡片指标 |

字体栈：`Inter, PingFang SC, HarmonyOS Sans SC, Noto Sans SC, system-ui, sans-serif`；数字使用 `tabular-nums`。

### 4.4 Spacing / Radius / Shadow

```css
--space-1:4px; --space-2:8px; --space-3:12px; --space-4:16px;
--space-5:20px; --space-6:24px; --space-8:32px; --space-10:40px; --space-12:48px;
--radius-sm:8px; --radius-md:12px; --radius-lg:16px; --radius-xl:20px;
--border-default:1px solid #E2E8F0;
--shadow-card:0 4px 16px rgba(15,23,42,.06);
--shadow-float:0 12px 32px rgba(15,23,42,.12);
```

## 5. Mobile Layout / Responsive · MIRROR_OF_08_V2.4

必须回归：

```text
375 × 812
390 × 844
430 × 932
768px
1024px
```

规则：

- `375 / 390 / 430` 使用同一 Mobile IA；主要差异只允许是安全边距、文本换行与容器舒展度。
- `<=767px` 完全复用 Mobile；`768–1023px` 只居中增宽，不改成桌面后台 IA。
- BottomNav、Floating CTA、Sticky Action、Toast、Keyboard、Safe Area 不能互相遮挡。
- 表单/确认页 H5 最大宽度以 08/03 当前 Token 为准；详情/列表使用 08/03 当前最大内容宽度。
- 任何触控目标不得小于 44×44 CSS px。
- 德语检查 1.35–1.6× 文案膨胀；法语检查长按钮；泰语检查上下附标与无空格换行；日/韩检查异常断字。
- Bottom Sheet / Modal / Drawer / Sticky Action 在所有目标尺寸下都必须可关闭、可滚动、主 CTA 不掉位。
- Root 页面正文底部 padding 必须覆盖 BottomNav + FloatingActionBar + gap + safe-area + 16px。

## 6. Global Components

### 6.1 Foundation / Navigation

`GainodeAppBar / BottomNav / PrimaryButton / SecondaryButton / TextAction / StatusBadge / InlineNotice / RiskNotice / SegmentedControl / Tabs / EmptyState / LoadingState / ErrorState / RestrictedState / Skeleton`

### 6.2 Home

#### `MyPredictionCard`

目标：回答“**我之前参与的竞猜现在怎么样了？**”。

推荐结构：

```text
我的竞猜
2 场进行中 · 1 场等待结果

曼城 vs 阿森纳
我的选择：主胜
今晚 22:00
即将截止
```

点击：`→ M-PREDICT-004 我的竞猜`

必须支持：`DEFAULT / EMPTY / CLOSING_SOON / WAITING_RESULT / RESULT_READY / RESTRICTED / ERROR`

禁止展示：`Odds / Stake / Bet Amount / 预计赢钱 / 预计收益 / 博彩式红绿 / 资产金额`。

#### `TodayTaskCard`

目标：回答“**今天还有什么值得我处理？**”。

推荐结构：

```text
今日任务
今日已完成 2 / 4
[Progress]

✓ 领取今日 Robot 奖励
● 查看等待结果的竞猜
○ 检查即将截止的竞猜
```

硬规则：

- `STATE_DRIVEN = YES`
- 任务来源只允许真实用户状态 + `allowed_actions` + `next_action`。
- 前端不得通过等级/余额/时间自行猜任务。
- 不得自行定义任务获得 Power、XP、签到金币、现金奖励、Reward Multiplier、固定成长值；只有未来 01/02/05/06 正式建立规则后才能展示。

### 6.3 Robot

#### `PowerBattery / PowerMeter(Battery)`

必须表达：

`Current Available / Current Cap / Recovery State / Frozen / Usage Impact`

Power 核心状态：

`Available / Frozen / Consumed / Released / Recovering / Cap`

视觉是“可消耗、可恢复操作资源”，不是游戏体力、币价、现金余额。

#### `RobotFloatingActionBar`

固定在 Bottom Navigation 上方，滚动始终可见且不得遮挡正文。

状态驱动：

| Robot State | 主动作 |
|---|---|
| STOPPED | 启动 Robot |
| RUNNING | 显示当前运行状态 / 进入状态详情 |
| CLAIMABLE | **领取奖励优先** |
| ERROR | 查看问题 |
| RESTRICTED | 查看限制原因 |

### 6.4 Prediction

`CompactMatchRow / FeaturedMatchCard / ThreeWaySelector / CommunityPickDistribution / PredictionStatus / PredictionInsightCard / MyPredictionCard / OrderCard / ResultStatus`

`ThreeWaySelector` 的 Home / Draw / Away 必须等权，不得默认高亮；百分比是 Community Picks，不是 Odds。

### 6.5 Asset / OTC / Support / Security

`APTSummary / LedgerRow / PowerBattery / PowerImpactSummary / OTCOrderCard / OTCPartialProgress / TicketThread / DeviceRow / SessionRow / SecurityEventRow`

所有组件一次定义，多页复用 Variant，不允许每页重新设计同义组件。

## 7. Overlay / Popup System

| Type | 使用场景 | 结构 | 禁止 |
|---|---|---|---|
| Bottom Sheet | 选择、筛选、说明、轻量详情 | Drag handle → Title → Content → Action | 塞入超长高风险确认 |
| Picker Sheet | 语言、时区、验证方式、证件类型、联赛/日期 | 单选列表 + 当前项 + Done/即时生效 | 额外造新页面 |
| Action Sheet | 上传来源、OTC 快捷动作等短动作 | 2–5 个动作 + Cancel | 高风险多步骤 |
| Fullscreen Confirm | Robot/Prediction/OTC 等高风险最终确认 | Object Summary → Impact → Risk → Consent → CTA | 小弹窗塞长文 |
| Result Page/Sheet | Success/Failed/Review/Unknown | Result Hero → Impact → Reference → Next Action | 只 Toast `Success` |
| Info Sheet | 规则、来源、状态原因、公式解释 | 标题 → 说明 → Meta/Version | 泄露内部风控阈值 |
| Filter Sheet | 列表日期/类型/状态/联赛 | 当前条件 → 选择 → Reset/Apply | 切页后丢条件 |

### 7.1 Unknown Result
必须显示：正在确认结果、原 `request_id/object_id`、原 Idempotency-Key 或可追踪 reference、查询原请求、历史/Support 入口。**禁止“再试一次创建”。**

## 8. 全局状态矩阵

每个 P0 页面至少：`Default / Loading / Empty / Error / Restricted`。

有写操作页面再增加：`Invalid / Confirm / Submitting / Processing / Review / Success / Failed / Unknown Result`。

- Loading：Skeleton 跟真实布局一致；局部卡片可独立加载。
- Empty：说明“还没有什么 + 如何开始”。
- Error：说明“发生了什么 + 有没有产生业务影响 + 现在能做什么”。
- Restricted：说明“当前不能做什么 + 原因分类 + 恢复/补件/申诉/等待”。
- Failed：明确本次资产/状态是否生效。
- Review：不冒充成功，持续可查询。

## 9. App Information Architecture · 44 Page IDs

| Family | Page IDs |
|---|---|
| Auth / 身份入口 | M-AUTH-001 登录<br>M-AUTH-002 注册<br>M-AUTH-003 OTP 验证<br>M-AUTH-004 找回 / 重置密码<br>M-AUTH-005 MFA 二次验证 |
| KYC / 准入 | M-KYC-001 KYC 与功能准入概览<br>M-KYC-002 KYC 资料提交 / 补件<br>M-KYC-003 KYC 状态 / 结果 |
| Home / 通知 | M-HOME-001 首页<br>M-NOTICE-001 消息中心 |
| Robot | M-ROBOT-001 Robot 概览<br>M-ROBOT-002 Robot 启动 / 停止确认<br>M-ROBOT-003 Robot 升级<br>M-ROBOT-004 升级结果<br>M-ROBOT-005 56 级等级地图<br>M-ROBOT-006 Rewards & Claim<br>M-ROBOT-007 Robot 活动与记录 |
| Prediction | M-PREDICT-001 竞猜赛事列表<br>M-PREDICT-002 赛事详情<br>M-PREDICT-003 竞猜确认<br>M-PREDICT-004 我的竞猜<br>M-PREDICT-005 竞猜订单详情<br>M-PREDICT-006 异常 / 退款 / 更正详情 |
| Me Root | M-ME-001 我的 |
| P1 / Future | M-AI-001 AI 数据 / Signal 详情<br>M-GROWTH-001 Referral / Team<br>M-PREDICT-FREE-001 免费 YES/NO（P1/Sandbox，Sandbox 仅内部分类，不在正式用户 UI 显示）<br>M-MIGRATION-001 APT-I → APT-C Migration（Future/CLOSED） |
| APT / Power / OTC | M-ASSET-001 APT 资产<br>M-ASSET-002 APT 流水列表<br>M-ASSET-003 APT 流水详情<br>M-POWER-001 Power<br>M-OTC-001 OTC 市场<br>M-OTC-002 OTC 下单输入<br>M-OTC-003 OTC 订单确认<br>M-OTC-004 OTC 提交结果<br>M-OTC-005 我的 OTC 订单<br>M-OTC-006 OTC 订单详情 |
| Security | M-SEC-001 安全中心<br>M-SEC-002 MFA / 设备 / Session 管理 |
| Support | M-SUPPORT-001 帮助中心 / 工单列表<br>M-SUPPORT-002 创建工单 / 申诉<br>M-SUPPORT-003 工单详情 |
| Settings | M-SETTINGS-001 设置 |

## 10. 44 页逐页 HIFI + Interaction Spec

### 10.1 Auth / 身份入口

> 视觉家族：大量留白、完整 Logo 仅品牌入口使用；表单单列；蓝色主按钮；无营销 Banner。

#### `M-AUTH-001｜登录` · P0

- **页面目标**：已有账号安全登录并获得 session。
- **视觉结构**：品牌区；账号；密码；登录按钮；注册/忘记密码；条款与帮助。
- **UI 视觉延展**：顶部只保留完整 Logo、标题和一句安全说明；表单置于白色卡片/无边框容器中，账号与密码层级清晰，主按钮使用品牌蓝。底部“注册 / 找回密码 / 帮助”用文本按钮，不出现营销 Banner。
- **主 CTA**：登录。
- **次级按钮 / 可点区域**：注册、找回密码、帮助。
- **关键交互细节**：密码可见切换；错误就地反馈；登录中锁定重复提交。
- **基线交互补充**：登录后完全听从服务端 next_step；MFA/KYC/首页跳转不由前端猜。输入错误就地显示，账号保留、密码清空；提交期间锁定主按钮并保留原尺寸。
- **弹出 / Overlay**：无；必要安全锁定使用 Inline/Full state，不弹营销弹窗。
- **推荐组件**：BrandHeader、LabeledInput、PasswordInput、PrimaryButton、InlineError、SecurityNotice。
- **状态覆盖**：Loading；凭据错误；频控；账户安全锁定；依赖不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：未登录可访问；不泄露账号是否存在等敏感判断。
- **路由 / 返回上下文**：成功→M-AUTH-005 或 M-KYC-001 / M-HOME-001；忘记密码→M-AUTH-004。
- **禁止 / 验收**：不做营销 Banner；不使用金色主按钮。；登录中禁止重复点；失败保留账号但清密码；成功必须由服务端给 next_step。

#### `M-AUTH-002｜注册` · P0

- **页面目标**：创建账号并完成基础协议确认。
- **视觉结构**：账号类型；手机号/邮箱；密码；确认密码；条款勾选；注册按钮。
- **UI 视觉延展**：延续登录页视觉；账号类型用 SegmentedControl，条款放在提交按钮上方并用可打开全文的链接。密码规则以实时 checklist 展示，但不泄露内部风控阈值。
- **主 CTA**：创建账号。
- **次级按钮 / 可点区域**：切换手机号/邮箱、查看条款、去登录。
- **关键交互细节**：字段实时校验；条款主动勾选；提交成功直接进入 OTP。
- **基线交互补充**：条款必须主动勾选；注册成功后直接进入 OTP，不在成功页停留。若账号已存在只给安全的人话提示，不透露过多账户信息。
- **弹出 / Overlay**：条款全文 Bottom Sheet / WebView；账号类型 SegmentedControl。
- **推荐组件**：SegmentedControl、LabeledInput、PasswordRuleList、CheckboxConsent、PrimaryButton。
- **状态覆盖**：字段错误；账号已存在；发送受限；服务不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：游客可访问；条款不能默认勾选。
- **路由 / 返回上下文**：成功→M-AUTH-003。
- **禁止 / 验收**：不做营销 Banner；不使用金色主按钮。；注册成功必须返回 verification_challenge_id；重复请求幂等。

#### `M-AUTH-003｜OTP 验证` · P0

- **页面目标**：验证注册/登录/找回操作的一次性验证码。
- **视觉结构**：验证码输入；倒计时；重发；当前账号脱敏展示。
- **UI 视觉延展**：以 6 位验证码格为视觉中心，标题区域弱化；脱敏账号、倒计时和重发入口放在验证码下方。错误提示紧贴验证码，不用全屏 Error。
- **主 CTA**：验证。
- **次级按钮 / 可点区域**：重新发送、修改账号。
- **关键交互细节**：6 位 OTP 自动前进/回退/整段粘贴；倒计时以服务端为准。
- **基线交互补充**：支持自动聚焦、粘贴整段验证码、删除回退；倒计时以服务端时间为准。过期后显式提示“重新获取”，不自动发送。
- **弹出 / Overlay**：验证码过期 Inline State；重发成功轻 Toast。
- **推荐组件**：OtpInput、MaskedAccount、Countdown、TextButton、InlineError。
- **状态覆盖**：验证码错误/过期；尝试过多；重发频控。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：只能操作当前 challenge。
- **路由 / 返回上下文**：成功按 challenge purpose 去下一步。
- **禁止 / 验收**：不显示内部安全规则、风控阈值或“账号是否存在”。；倒计时以服务端为准；过期不静默自动重发。

#### `M-AUTH-004｜找回 / 重置密码` · P0

- **页面目标**：安全恢复账号凭据。
- **视觉结构**：账号；OTP/验证步骤；新密码；确认；完成页。
- **UI 视觉延展**：使用 3 步流程：确认账号 → 验证身份 → 设置新密码；顶部 Stepper 简洁，不把所有字段塞在一屏。
- **主 CTA**：继续 / 重置密码。
- **次级按钮 / 可点区域**：返回上一步、回登录。
- **关键交互细节**：三步 Stepper；各步保留上下文；完成后旧 Session 按策略处理。
- **基线交互补充**：每一步保留上一阶段上下文；新密码提交前再次校验规则。成功页只告诉用户密码已更新及旧会话处理结果，并回登录。
- **弹出 / Overlay**：完成 Result Page；不使用普通 Toast 代替结果。
- **推荐组件**：Stepper、LabeledInput、OtpInput、PasswordRuleList、ResultState。
- **状态覆盖**：账号恢复受限；challenge 过期；密码不合规。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：不能通过页面暴露账号是否注册；高风险可转人工安全流程。
- **路由 / 返回上下文**：完成→M-AUTH-001。
- **禁止 / 验收**：不做营销 Banner；不使用金色主按钮。；成功后旧 session 按策略失效；必须记录安全事件。

#### `M-AUTH-005｜MFA 二次验证` · P0

- **页面目标**：在高风险登录或敏感动作前完成二次验证。
- **视觉结构**：验证方式；验证码；倒计时/恢复方式；安全提示。
- **UI 视觉延展**：安全验证采用验证页，不重复完整业务信息；但必须展示“正在确认的操作”摘要，例如 Robot 升级 / OTC 提交，避免用户不知道为何验证。
- **主 CTA**：确认验证。
- **次级按钮 / 可点区域**：切换验证方式、安全帮助。
- **关键交互细节**：显示原操作 Context；MFA 成功后回原业务 request，不创建重复请求。
- **基线交互补充**：MFA 成功后回到原动作并复用原 request/idempotency context；切换验证方式使用 Bottom Sheet。
- **弹出 / Overlay**：验证方式 Bottom Sheet；恢复方式 Sheet。
- **推荐组件**：OperationContextCard、OtpInput、MethodPickerSheet、Countdown、SecurityNotice。
- **状态覆盖**：错误；过期；次数过多；恢复模式。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：challenge 绑定原 request/context。
- **路由 / 返回上下文**：成功回原操作，不另造重复业务请求。
- **禁止 / 验收**：不显示内部安全规则、风控阈值或“账号是否存在”。；MFA 成功后必须继续原流程并保留原 idempotency context。

### 10.2 KYC / 准入

> 视觉家族：可信、安全、分步；状态卡 + Capability List；Processing 用蓝，Needs Info 用 Warning；敏感图像最小化。

#### `M-KYC-001｜KYC 与功能准入概览` · P0

- **页面目标**：告诉用户验证进度，以及哪些功能可用/不可用。
- **视觉结构**：KYC 进度；功能能力清单；限制原因；开始/继续/补件/申诉按钮。
- **UI 视觉延展**：顶部状态卡显示 KYC 当前阶段，下面用“功能准入清单”逐项显示 Robot / Prediction / OTC 等 allowed、原因和下一步。不要只用一个巨大通过/拒绝图标。
- **主 CTA**：开始 / 继续认证。
- **次级按钮 / 可点区域**：查看受限能力、补件、申诉。
- **关键交互细节**：Capability 行读取 allowed/reason/next_action；点击受限项执行对应 next_action。
- **基线交互补充**：点击每个受限能力直接执行 next_action；补件、申诉、继续认证均返回对应对象状态，不创建重复 KYC Case。
- **弹出 / Overlay**：原因说明 Bottom Sheet；申诉跳 Support，不弹通用 Permission。
- **推荐组件**：KycStatusHero、CapabilityList、StatusBadge、ReasonBlock、PrimaryCTA。
- **状态覆盖**：not_started/pending/needs_info/approved/rejected/review；依赖异常。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：历史可看；新功能写操作由 allowed 决定。
- **路由 / 返回上下文**：填写→M-KYC-002；结果→M-KYC-003；申诉→M-SUPPORT-002。
- **禁止 / 验收**：不只显示一个“通过/未通过”大图标。；功能列表每项要有 allowed、reason、next_action。

#### `M-KYC-002｜KYC 资料提交 / 补件` · P0

- **页面目标**：提交当前策略要求的身份资料。
- **视觉结构**：分步表单；资料字段；文件上传；Consent；保存草稿；提交。
- **UI 视觉延展**：分步表单 + 上传卡；每一步只处理同一类信息。上传结果用缩略信息卡展示文件名、状态、重试，不展示敏感原图大预览。
- **主 CTA**：提交资料。
- **次级按钮 / 可点区域**：保存草稿、上一步。
- **关键交互细节**：分步表单；单文件上传失败单项重试；策略版本变化不清空数据。
- **基线交互补充**：支持保存草稿；单个文件上传失败可单项重试；策略版本变化时弹出说明并要求重新确认，不清空已填数据。
- **弹出 / Overlay**：证件类型 Picker Sheet；Consent Full Sheet；上传源 Action Sheet。
- **推荐组件**：Stepper、FormSection、UploadCard、ConsentBlock、StickyCTA。
- **状态覆盖**：字段/文件错误；上传失败；重复提交；策略变更。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：仅本人；敏感字段不写入日志/埋点。
- **路由 / 返回上下文**：提交成功→M-KYC-003。
- **禁止 / 验收**：不把所有步骤塞进一屏；上传失败不能清空整页。；字段错误保留已填内容；上传失败可单项重试；策略版本变化时重新确认。

#### `M-KYC-003｜KYC 状态 / 结果` · P0

- **页面目标**：显示审核中、补件、通过、拒绝和下一步。
- **视觉结构**：状态卡；时间线；缺失项；开放能力；申诉/支持。
- **UI 视觉延展**：结果卡 + 时间线 + 影响功能清单。审核中使用蓝色 Processing，不使用绿色成功；needs_info 用黄橙提示具体缺失项。
- **主 CTA**：下一步动作。
- **次级按钮 / 可点区域**：回首页、查看能力、申诉。
- **关键交互细节**：Processing/Needs Info/Approved/Rejected 各自独立 Result 结构；通过后刷新 entitlement。
- **基线交互补充**：每个状态必须有下一步：等待、补件、申诉、回首页或查看已开放能力。通过后主动刷新 FeatureEntitlement。
- **弹出 / Overlay**：补件原因 Sheet；申诉跳 Support。
- **推荐组件**：ResultHero、Timeline、CapabilityList、ActionPanel。
- **状态覆盖**：审核中；needs_info；rejected；approved；service unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：拒绝只展示安全原因，不泄露内部风险模型。
- **路由 / 返回上下文**：补件→M-KYC-002。
- **禁止 / 验收**：不把“处理中”画成成功；不使用庆祝动画掩盖未完成状态。；每个状态必须有“下一步”；通过后重新拉取 capabilities。

### 10.3 Home / 通知

> 视觉家族：四张确认视觉稿之一；浅灰背景、白卡、一个深蓝/蓝 Hero；信息层级快扫；Root 保留 Bottom Nav。

#### `M-HOME-001｜首页` · P0

- **页面角色**：P0 Root / 今日状态与行动分流。
- **页面目标**：用户进入 Gainode 后快速知道 Robot 是否运行、是否有 Reward 可领取、自己的竞猜现在怎样、今天还有什么值得处理。
- **FINAL STRUCTURE**：
  1. Gainode Header
  2. Greeting / Today Context
  3. HomeRobotHero
  4. NoticeTicker
  5. Operational Quick Cards
     - `MyPredictionCard`
     - `TodayTaskCard`
  6. Featured Predictions
  7. AI Data Insights
  8. Weekly Growth / Activity Leaderboard
  9. Bottom Navigation
- **主 CTA**：由真实状态与 `next_action` 决定；首屏始终只允许一个最主要动作。
- **关键交互**：
  - HomeRobotHero → M-ROBOT-001 / Reward 状态；
  - NoticeTicker → M-NOTICE-001；
  - MyPredictionCard → M-PREDICT-004；
  - TodayTaskCard → 对应真实业务对象；
  - Featured Prediction → M-PREDICT-002；
  - AI Data → M-AI-001（若当前 entitlement/阶段允许）；
  - Leaderboard → 对应 Growth/Activity 只读详情或保持 Home 展开。
- **局部失败**：Notice / Robot / Prediction / AI / Leaderboard 各自独立 Loading/Error，不用一个全屏 Error 覆盖可用模块。
- **Overlay**：Notice preview / filter 可使用轻量 Bottom Sheet；高风险动作必须进入独立业务流程。
- **Required States**：`Default / Loading / Empty / Error / Restricted` + 组件局部状态。
- **明确移除**：`Home Post Buy / Home Post Sell / OTC Buy-Sell Quick Action / 大面积 APT 快捷操作 / 大面积 Power 操作 / OTC Operation Cards`。
- **禁止**：收益大盘、资产大数字抢占首屏、K线/涨跌、财富排行榜。
- **验收**：Home 与 Me 职责不重复；首页仍有运营回访价值，但不成为第二个资产/OTC 页面。

#### `M-NOTICE-001｜消息中心` · P0

- **页面目标**：查看状态、风控、订单、结算和工单通知。
- **视觉结构**：未读筛选；通知列表；详情；关联对象按钮。
- **UI 视觉延展**：按“未读 / 全部”或时间分组展示通知，列表项含图标、标题、摘要、时间和对象类型；高优先级只用小型状态标识，不做红色大 Banner。
- **主 CTA**：无固定主 CTA。
- **次级按钮 / 可点区域**：全部已读、筛选、打开关联对象。
- **关键交互细节**：按未读/全部与日期分组；点击后标已读并深链；返回保留滚动与筛选。
- **基线交互补充**：点击通知后标记已读并打开关联对象；返回时保持滚动位置与筛选。失效对象显示“状态已更新”，仍可查看通知正文。
- **弹出 / Overlay**：通知正文可 Bottom Sheet；失效对象显示状态更新。
- **推荐组件**：SearchField、SegmentedTabs、NoticeRow、UnreadDot、EmptyState。
- **状态覆盖**：Empty；分页失败；通知目标已失效。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：只展示本人通知；敏感原因做安全映射。
- **路由 / 返回上下文**：根据 object_type 深链跳转。
- **禁止 / 验收**：不为每一行加厚重阴影；不做超长横向表格。；点击通知后返回仍保留列表位置/筛选。

### 10.4 Robot

> 视觉家族：四张确认视觉稿之一；机器人插画只在 Root Hero；蓝为主、金色只点 Level/Reward；浮动 Action Bar 不遮 Bottom Nav。

#### `M-ROBOT-001｜Robot 概览` · P0

- **页面角色**：P0 Root / Robot Control Center。
- **固定视觉结构**：`Light App Shell + Dark Robot Hero + Light Body`。
- **核心模块**：Robot Status → Robot Level → PowerBattery → Runtime Process → Today's Reward / Claim → Recent Activity → RobotFloatingActionBar → BottomNav。
- **主 CTA**：完全状态驱动：STOPPED=启动 Robot；RUNNING=查看运行状态；CLAIMABLE=领取奖励优先；ERROR=查看问题；RESTRICTED=查看限制原因。
- **Power**：只展示服务端当前 Position/Preview；不得本地硬编码启动消耗、恢复量、阈值或每级 Cap。
- **Runtime Consistency Hard Gate**：`RUNNING_STAGE + PROCESS_STEPPER + RECENT_ACTIVITY` 必须一致。若 Stepper 显示 Processing，则 Data Sync / Analysis 的完成状态和 Activity 时间顺序必须可相互验证。
- **Reward**：至少明确一个统计周期（Today / 7D / This Month / Total Claimed），并显示状态、生成/领取时间、Robot Level、Rule/Snapshot；禁止 APR/APY/固定回报。
- **Overlay / Route**：Start/Stop → M-ROBOT-002；Upgrade → 003；Level → 005/LevelDetailSheet；Reward → 006；Activity → 007。
- **Required States**：`Default / Loading / Empty / Error / Restricted` + inactive/active/cooling/review/paused/claimable。
- **禁止**：Crypto Arbitrage UI、Upgrade Progress Bar、收益仪表盘、整页暗色交易终端。
- **验收**：Floating Action Bar 不遮挡正文；状态变化刷新服务端事实；Activity 与 Process Stepper 无时间/阶段矛盾。

#### `M-ROBOT-002｜Robot 启动 / 停止确认` · P0

- **页面目标**：让用户明确这次状态切换的影响。
- **视觉结构**：当前状态；目标状态；影响摘要；资格；风险；确认按钮。
- **UI 视觉延展**：使用全屏高风险确认页或大 Bottom Sheet：顶部显示当前状态与目标状态，中间说明影响、预计生效方式、冷却/Review 可能性，底部单一确认 CTA。
- **主 CTA**：确认启动 / 确认停止。
- **次级按钮 / 可点区域**：取消。
- **关键交互细节**：当前状态→目标状态→影响→风险→确认；提交后 Processing/Review，禁止重复。
- **基线交互补充**：提交后进入 Processing/Review 状态，禁止重复操作；Success/Failed/Unknown 均回到同一 action_id 查询结果。
- **弹出 / Overlay**：高风险全屏确认优先；简单状态切换可大型 Bottom Sheet。
- **推荐组件**：ConfirmSummary、ImpactList、RiskNotice、StickyCTA、ProcessingState。
- **状态覆盖**：quote 过期；操作不允许；MFA required；unknown result。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：服务端决定是否可启动/停止。
- **路由 / 返回上下文**：成功回 M-ROBOT-001；review 显示处理中结果。
- **禁止 / 验收**：不省略影响说明；不把确认做成一个小弹窗里塞满长文本。；提交后禁重复；未知结果用原 idempotency_key 查询。

#### `M-ROBOT-003｜Robot 升级` · P0

- **页面目标**：选择目标等级并看成本、能力变化、冷却和资格。
- **视觉结构**：当前/目标等级；能力 diff；APT cost；Power limit diff；cooldown；资格；主动确认。
- **UI 视觉延展**：升级页用“当前等级 → 目标等级”对比卡，突出新增能力、standard_capacity 变化和规则版本；费用/所需资源是信息，不做购买刺激。
- **主 CTA**：确认升级。
- **次级按钮 / 可点区域**：选择目标等级、刷新报价、返回。
- **关键交互细节**：当前→目标能力 Diff；quote 倒计时；确认前重新拉 eligibility。
- **基线交互补充**：先选择目标/可升级项，再获取 quote；quote 有过期倒计时。进入确认前再次刷新 eligibility。
- **弹出 / Overlay**：目标等级 Picker / Level Sheet；Quote Expired Sheet。
- **推荐组件**：LevelCompareCard、CapabilityDiff、QuoteCard、EligibilityNotice、StickyCTA。
- **状态覆盖**：APT不足；冷却；资格不足；quote过期；受限。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：前端不算 apt_cost。
- **路由 / 返回上下文**：提交→M-ROBOT-004。
- **禁止 / 验收**：不把升级做成充值促销页；金色只点缀等级/提升。；确认页必须显示 quote_expires_at；过期重新拉报价。

#### `M-ROBOT-004｜升级结果` · P0

- **页面目标**：给出升级的最终/处理中结果和记录编号。
- **视觉结构**：状态图标；新等级/处理中；APT 影响；cooldown；order_id；记录入口。
- **UI 视觉延展**：结果页不只给 Success 图标；顶部结果状态，下面显示等级变化、账本影响、规则版本、action/order ID 和时间线。Review/Cooling 各有独立视觉。
- **主 CTA**：返回 Robot。
- **次级按钮 / 可点区域**：查看流水、查看 Activity。
- **关键交互细节**：Completed/Review/Failed/Unknown 独立结果；显示 action/order ID、版本与资产影响。
- **基线交互补充**：可进入 Robot、流水、活动记录；Unknown/Review 状态提供“查询进度”而不是再次提交。
- **弹出 / Overlay**：Result Page；Unknown 不出现重试创建。
- **推荐组件**：ResultHero、BeforeAfterDiff、Timeline、ReferenceBlock、ActionGroup。
- **状态覆盖**：completed/review/failed/no_effect/unknown。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：历史可查看。
- **路由 / 返回上下文**：→M-ROBOT-001 / M-ASSET-003 / M-ROBOT-007。
- **禁止 / 验收**：不使用金币雨/烟花；“已提交”不得画成“已完成”。；失败必须明确本次是否产生 APT 效果。

#### `M-ROBOT-005｜56 级等级地图` · P0

- **页面目标**：浏览 1–56 级能力与当前/下一等级。
- **视觉结构**：6 个 UI 分组；等级节点；能力详情；当前/已解锁/未解锁；升级入口。
- **UI 视觉延展**：56 级采用分段路径：每 8 或 10 级为一组，当前级居中；Locked / Current / Available / Restricted 用形状+文案区分。避免 56 张大卡平铺。
- **主 CTA**：去升级（仅 allowed）。
- **次级按钮 / 可点区域**：切分组、跳当前等级。
- **关键交互细节**：56 级分段路径；节点用 Locked/Current/Available/Restricted 形状+文案。
- **基线交互补充**：点击等级打开 Bottom Sheet 查看该级能力和条件；只有 allowed 的目标级出现“去升级”。支持快速跳到当前等级。
- **弹出 / Overlay**：Level Detail Bottom Sheet。
- **推荐组件**：LevelSegmentTabs、LevelPath、LevelNode、LevelDetailSheet、Legend。
- **状态覆盖**：配置不可用；目标级别不可升级。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：只读；参数/规则来源服务端。
- **路由 / 返回上下文**：→M-ROBOT-003。
- **禁止 / 验收**：不一屏平铺 56 张卡；不写死成本/能力参数。；不能用本地表写死正式能力/成本；显示 rule_version。

#### `M-ROBOT-006｜Rewards & Claim` · P0

- **页面目标**：查看 Reward 的候选、待领取、已领取、审核、过期和冲正。
- **视觉结构**：Reward summary；状态 tabs；pending APT；capacity×coefficient 解释；Claim CTA；记录详情。
- **UI 视觉延展**：Reward 页以资格和状态为主：Candidate/Held/Pending Claim/Claimed 分组，数量配单位、周期、快照和系数说明。系数为 0 时用解释卡，不显示“0 收益失败”。
- **主 CTA**：领取。
- **次级按钮 / 可点区域**：切状态 Tab、查看流水。
- **关键交互细节**：Candidate/Held/Pending Claim/Claimed 分组；0 系数是解释状态不是 Error。
- **基线交互补充**：Claim 前二次确认可领取数量与相关快照；提交后处理状态与 ledger_entry_id 可追踪。Claim disabled 时展示 reason/next_action。
- **弹出 / Overlay**：Claim Confirm Sheet；Claim Result Sheet。
- **推荐组件**：RewardStatusTabs、RewardCard、FormulaExplanation、ClaimCTA、ResultSheet。
- **状态覆盖**：coefficient=0；claim disabled；review；expired；unknown result。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：只有 claim_allowed=true 才能提交。
- **路由 / 返回上下文**：Claim 结果留在本页/结果 sheet；流水→M-ASSET-003。
- **禁止 / 验收**：不使用“今日收益”“提现”“稳赚”等视觉或文案；金色面积 <=5%。；0 系数显示“今日系数为 0”，不是 Error；Claim 幂等。

#### `M-ROBOT-007｜Robot 活动与记录` · P0

- **页面目标**：追溯状态、升级、Reward、限制和版本变化。
- **视觉结构**：筛选；时间线/列表；关联对象；规则/参数版本；支持入口。
- **UI 视觉延展**：按时间线/记录列表展示启动、停止、升级、Reward、异常和规则版本事件；不同事件用统一图标体系，不用彩色气泡堆叠。
- **主 CTA**：无固定主 CTA。
- **次级按钮 / 可点区域**：类型/日期筛选、打开关联对象。
- **关键交互细节**：运行/产出/领取/升级/限制/异常/恢复时间线；历史规则版本不可被当前规则覆盖。
- **基线交互补充**：支持类型与日期筛选；点击事件打开详情或关联对象；分页失败只影响列表尾部。
- **弹出 / Overlay**：Filter Sheet；Event Detail Sheet。
- **推荐组件**：FilterChips、TimelineList、EventRow、ReferenceMeta、EmptyState。
- **状态覆盖**：Empty；分页失败；关联对象不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：历史只读。
- **路由 / 返回上下文**：深链到 Asset/Support。
- **禁止 / 验收**：不把历史记录画成实时行情；不覆盖旧版本信息。；历史状态不被当前规则覆盖。

### 10.5 Prediction

> 视觉家族：四张确认视觉稿之一；赛事信息层级参考体育数据产品；主/平/客三方向等权；不出现博彩赔率盘。

#### `M-PREDICT-001｜竞猜赛事列表` · P0

- **页面角色**：P0 Root / 会员与会员之间的竞猜社区。
- **定位**：`Participation / Community Prediction`，不是 Sportsbook / Betting / Bookmaker。
- **首屏结构**：Header → Date/League Controls → Featured / Closing Soon → Compact Match Rows → MyPrediction Entry → BottomNav。
- **三方向**：Home / Draw / Away 完全同级、同尺寸、同视觉权重、无默认高亮。
- **百分比语义**：只允许 `COMMUNITY_PICK_DISTRIBUTION`，不得表现为 Odds/Return。
- **运营密度**：正常运行至少 12 场，推荐 Fixture 24 场以上；Featured/Closing Soon 可用完整卡，普通赛事必须 Compact Match Row。
- **赛事状态**：可参与 / 即将截止 / 已截止 / 等待结果 / 已完成。
- **时间**：必须同时区分 `Match Time` 与 `Prediction Close Time`。
- **主 CTA**：打开赛事 / 参与竞猜（仅 allowed）。
- **Overlay / Route**：日期/联赛 Filter Sheet；赛事 → M-PREDICT-002；我的竞猜 → M-PREDICT-004。
- **Required States**：`Default / Loading / Empty / Error / Restricted` + open/closing/closed/waiting_result/completed。
- **Power 边界**：P0 竞猜默认不展示 Power Impact；除非未来 01/02/05/06 正式加入规则。
- **禁止**：Odds、Bet、Stake、Bookmaker、盘口、博彩式红绿、AI 默认推荐、保证胜率。

#### `M-PREDICT-002｜赛事详情` · Football 1X2 · P0

- **页面目标**：理解赛事、三方向、当前池、流动性、规则并输入数量。
- **视觉结构**：赛事头；Home/Draw/Away 三方向固定展示；池/预计信息；流动性；数量；可用APT；规则；CTA。
- **UI 视觉延展**：参考 FotMob/Sofascore 的比赛详情层级：赛事头部 → 1X2 三方向 → AI 数据/关键数据 → 流动性/规则/风险 → CTA。主胜/平局/客胜始终同时可见且无默认高亮。
- **主 CTA**：参与竞猜 / 继续。
- **次级按钮 / 可点区域**：选主胜/平局/客胜、看 AI 数据、规则。
- **关键交互细节**：三方向永远同级；选方向后激活数量；数据变化保留输入但要求重新确认。
- **基线交互补充**：选方向后才激活数量输入；数据变更时保留用户输入但提示重新确认。锁定或资格变化即时禁用继续。
- **弹出 / Overlay**：规则/数据 Bottom Sheet 或 Tab；不得默认高亮推荐。
- **推荐组件**：MatchHero、ThreeWaySelector、AmountInput、DataTabs、LiquidityCard、RuleMeta、StickyCTA。
- **状态覆盖**：Market closing/locked；数据源异常；资格不足；数量非法。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：三方向不能隐藏；服务端最终校验。
- **路由 / 返回上下文**：→M-PREDICT-003。
- **禁止 / 验收**：不隐藏 Draw；不把倍数/估算做成保证性大数字。；预计倍数必须标 Not guaranteed；锁定前会变化。

#### `M-PREDICT-003｜Prediction 确认` · P0

- **页面目标**：在产生资产效果前主动确认所有关键事实。
- **视觉结构**：赛事；方向；数量；服务费规则；锁定；不可撤销；低流动性；退款/更正；Consent checkbox；提交。
- **UI 视觉延展**：高风险确认页坚持浅色：赛事、方向、数量、费用规则、锁定、不可撤销、低流动性、退款/更正规则分层呈现。Consent 不默认勾选。
- **主 CTA**：确认参与。
- **次级按钮 / 可点区域**：返回修改。
- **关键交互细节**：提交前刷新市场/余额/版本；差异出现 Version Diff；Unknown 禁止重复提交。
- **基线交互补充**：提交前刷新市场、余额、policy/parameter 版本；变化则展示 Diff 并要求重新确认。Unknown Result 进入查询状态，不允许重复提交。
- **弹出 / Overlay**：Consent Full Sheet；提交结果进入 Order Detail/Processing。
- **推荐组件**：ConfirmSummary、VersionDiffNotice、ConsentCheckbox、RiskNotice、StickyCTA。
- **状态覆盖**：Consent mismatch；Market locked；余额变化；policy changed；unknown result。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：Consent 不能默认勾选。
- **路由 / 返回上下文**：成功→M-PREDICT-005；unknown→处理中。
- **禁止 / 验收**：不省略影响说明；不把确认做成一个小弹窗里塞满长文本。；必须先创建有效 ConsentReceipt；订单用 Idempotency-Key。

#### `M-PREDICT-004｜我的 Prediction` · P0

- **页面目标**：按状态查看所有历史与进行中订单。
- **视觉结构**：Tabs/筛选；订单卡；状态；赛事；方向；数量；更新时间。
- **UI 视觉延展**：订单列表使用状态 Segmented Tabs + 卡片/紧凑列表，赛事信息是主标题，方向和数量为次级，状态与更新时间始终可见。
- **主 CTA**：打开订单。
- **次级按钮 / 可点区域**：状态筛选、回赛事。
- **关键交互细节**：进行中/等待结果/已完成/异常处理；列表状态与详情枚举同源。
- **基线交互补充**：筛选条件持久化；点击订单进入详情；账户受限时历史仍可查看。
- **弹出 / Overlay**：Status Filter Sheet。
- **推荐组件**：StatusTabs、OrderCard、FilterSheet、PaginationState。
- **状态覆盖**：Empty；分页失败；账户受限仍可看历史。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人数据；历史始终可读。
- **路由 / 返回上下文**：→M-PREDICT-005。
- **禁止 / 验收**：不把 Submitted/Matching/Partial 视觉上伪装为 Completed。；列表状态与详情状态同一枚举来源。

#### `M-PREDICT-005｜Prediction 订单详情` · P0

- **页面目标**：完整追踪提交、锁定、结果、结算与资金效果。
- **视觉结构**：订单摘要；多轴状态；时间线；Consent；snapshot versions；关联流水；申诉。
- **UI 视觉延展**：订单详情按多轴状态展示：订单、资产、风险、结果、结算分开，不用一个总状态。顶部摘要，下面时间线、Consent/快照、关联流水与申诉入口。
- **主 CTA**：按 allowed_actions 显示唯一主动作。
- **次级按钮 / 可点区域**：规则快照、流水、申诉、追加同方向。
- **关键交互细节**：Order/Asset/Result/Settlement 多轴状态；Result official ≠ Settlement paid；对象关系可深链。
- **基线交互补充**：所有可操作按钮来自 allowed_actions；若允许原方向追加，使用明确的“追加同方向”入口并回到赛事上下文。
- **弹出 / Overlay**：Snapshot Sheet；追加方向 Confirm；申诉跳 Support。
- **推荐组件**：OrderHeader、MultiAxisStatus、Timeline、SnapshotPanel、RelatedObjects、ActionPanel。
- **状态覆盖**：submitted/locked/awaiting_result/settling/settled/refunding/correcting。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：allowed_actions 服务端返回。
- **路由 / 返回上下文**：异常→M-PREDICT-006；流水→M-ASSET-003；申诉→M-SUPPORT-002。
- **禁止 / 验收**：不把内部风控理由直接展示；异常状态必须保留记录入口。；不能把 Result official 和 Settlement paid 混成一个“已完成”。

#### `M-PREDICT-006｜异常 / 退款 / 更正详情` · P0

- **页面目标**：解释异常原因、资产影响、处理进度和最终更正。
- **视觉结构**：异常 banner；reason；result/settlement/principal/reward axes；时间线；退款/冲正流水；申诉。
- **UI 视觉延展**：异常页顶部用醒目的但克制的异常说明，随后明确本金、费用、Reward、Result/Settlement 各自状态；更正前后版本使用对比区而非覆盖旧值。
- **主 CTA**：查看处理进度 / 申诉。
- **次级按钮 / 可点区域**：查看退款/冲正流水、证据摘要。
- **关键交互细节**：旧结果→新结果对比；本金/费用/Reward/Settlement 分轴；Processing 只查询不再提交。
- **基线交互补充**：用户可查看退款/冲正流水和申诉；Processing/Correcting 期间只查询，不再提交。
- **弹出 / Overlay**：Evidence Summary Sheet；Before/After Diff Sheet。
- **推荐组件**：ExceptionBanner、StatusMatrix、BeforeAfterResult、LedgerLinks、AppealCTA。
- **状态覆盖**：review/refunding/refunded/correcting/corrected/dependency unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：不能泄露反作弊算法或他人信息。
- **路由 / 返回上下文**：→M-ASSET-003 / M-SUPPORT-002。
- **禁止 / 验收**：不用一整屏红色；不删除原结果/原订单视觉记录。；更正必须保留 old/new 版本；退款结果可追溯。

### 10.6 Me Root

> `M-ME-001` 是 P0 Root；视觉继续承接已确认 Me Anchor，但业务内容必须以 01–08 为准。

#### `M-ME-001｜我的` · P0 Root

- **页面角色**：四个 P0 Root 之一；账户、资源、安全与设置入口。
- **固定职责**：APT / APT Ledger / Power / OTC / Security / KYC / Account / Help & Tickets / Settings。
- **首屏建议**：Profile/Admission → APT Quantity Summary → Power Summary → OTC Quick Entry（挂买/挂卖）→ Security/KYC → Support → Settings → BottomNav。
- **APT**：先展示 Quantity；若存在 Reference Valuation，必须同时显示 `Reference / Estimated / Snapshot / Timestamp`，不得包装成现金余额或收入。
- **Power**：只做状态摘要和进入 Power 详情/OTC 的入口。
- **OTC**：挂买/挂卖强入口保留在 Me / Power / OTC；Home 不复制。
- **Reward Summary**：如 Me 展示 Robot Reward 摘要，必须明确统计周期（Today / 7D / This Month / Total Claimed 中至少一项）。
- **Member Level / XP**：概念图出现的账户等级/成长视觉只有在 01/02/05/06 有正式对象与规则时才能展示；否则标记 `CONDITIONAL_PRODUCT_FEATURE` 并从正式 HIFI 隐藏。Robot Level 与 Member Level 必须分离。
- **Overlay**：账户信息 Sheet；语言/安全等进入对应独立页；不提供收益/Premium 弹层。
- **Required States**：`Default / Loading / Empty / Error / Restricted` + 局部卡片失败。
- **禁止**：今日收益/累计收益、未经批准 USD 估值、Premium 假权益、财富榜、把资产作为首页级财富焦点。
- **验收**：`M-ME-001_PRIORITY=P0`；受限用户仍能进入历史、安全、合法退款与 Support。

### 10.7 APT / Power / OTC

> 视觉家族：白底 + Navy/Blue；APT=数量账，Power=资源仪表，OTC=受控订单；不做交易所/币价/K线。

#### `M-ASSET-001｜APT 资产` · P0

- **页面目标**：清楚展示 APT-I 可用、冻结、待确认和更新时间。
- **视觉结构**：总览；状态拆分；最近流水；OTC/Power入口；规则说明。
- **UI 视觉延展**：APT 是“数量账”，顶部突出可用/冻结/待确认数量和更新时间；参考估值若存在必须弱化并标“参考/估算”。最近流水和 OTC/Power 入口放在下方。
- **主 CTA**：查看流水 / OTC 快捷动作（视业务优先级）。
- **次级按钮 / 可点区域**：Power、状态解释。
- **关键交互细节**：APT 作为数量账；Available/Frozen/Pending；最近流水可深链；参考估值如有必须弱化。
- **基线交互补充**：点数量状态可查看解释；点最近流水进详情；OTC/Power 入口先检查 entitlement。
- **弹出 / Overlay**：数量状态 Explanation Sheet；OTC Quick Action Sheet。
- **推荐组件**：AssetQuantityHero、StatusBreakdown、RecentLedgerList、RuleNotice。
- **状态覆盖**：Loading/Empty/Error/ViewOnly。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：资产可见与交易权限分开。
- **路由 / 返回上下文**：→M-ASSET-002 / M-OTC-001 / M-POWER-001。
- **禁止 / 验收**：不使用币价涨跌色或资产暴涨视觉。；每个数量有单位/状态；不默认显示美元“收入”。

#### `M-ASSET-002｜APT 流水列表` · P0

- **页面目标**：按类型/状态/日期查每笔 APT 变化。
- **视觉结构**：筛选；流水列表；方向；数量；状态；来源对象。
- **UI 视觉延展**：流水列表采用日期分组 + 方向图标 + 数量 + 状态 + 来源对象，避免币圈式红绿涨跌。筛选器放 Bottom Sheet。
- **主 CTA**：打开流水详情。
- **次级按钮 / 可点区域**：类型/状态/日期筛选。
- **关键交互细节**：日期分组；正负方向用图标+文字而非红绿涨跌；返回保留筛选/滚动。
- **基线交互补充**：按类型/状态/时间筛选；返回后保留位置；点击来源对象可从详情继续深链。
- **弹出 / Overlay**：Filter Sheet。
- **推荐组件**：FilterChips、LedgerRow、DateSection、StatusBadge。
- **状态覆盖**：Empty；分页失败。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人只读。
- **路由 / 返回上下文**：→M-ASSET-003。
- **禁止 / 验收**：不隐藏负向/冲正记录；不要用纯颜色表示正负。；cursor pagination；历史不可消失。

#### `M-ASSET-003｜APT 流水详情` · P0

- **页面目标**：解释一笔 APT 变化的来源、状态和关联对象。
- **视觉结构**：entry_id；数量；方向；状态；source；rule/snapshot；时间；关联对象；reversal。
- **UI 视觉延展**：详情采用“对象事实页”：entry_id、数量、方向、状态、来源、rule/snapshot、时间、reversal chain。ID 和版本用 Meta 样式，不抢主内容。
- **主 CTA**：打开来源对象。
- **次级按钮 / 可点区域**：复制 ID、发起争议。
- **关键交互细节**：事实页：entry_id、数量、状态、source、snapshot、reversal chain；关联对象可深链。
- **基线交互补充**：关联对象用可点击 chips/links；disputed/reversed 显示完整时间线与原始 entry 关系。
- **弹出 / Overlay**：Copy Toast；Dispute → Support Create。
- **推荐组件**：ObjectHeader、Descriptions、StatusTimeline、RelatedObjectLinks。
- **状态覆盖**：pending/posted/reversed/disputed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人数据；证据安全摘要。
- **路由 / 返回上下文**：按 related_object 跳转。
- **禁止 / 验收**：不把参考估值当已实现收入。；reversed 必须显示原 entry 和反向 entry。

#### `M-POWER-001｜Power` · P0

- **页面目标**：把 Power 表达为可消耗、可恢复操作资源，解释当前容量、冻结、恢复与业务影响。
- **V6.1 STRUCTURE**：`PowerBattery → Available/Frozen/Consumed/Released/Recovering/Cap → Cap Source / Robot Level → 7D Trend → Usage Scenarios → PowerImpactSummary → Related Actions → OTC Quick Action → Rule Meta`。
- **PowerBattery 必须表达**：Current Available / Current Cap / Recovery State / Frozen / Usage Impact。
- **Power 状态**：Available / Frozen / Consumed / Released / Recovering / Cap。
- **使用场景**：OTC Sell、Withdrawal、Robot Start；具体数量、恢复量、周期、阈值、扣减时点、每级 Cap 全部来自 Server + Active Rule + Parameter Version + Preview。
- **Power 高风险交互统一流程**：`Input → Preview → Power Impact → Review Summary → Explicit Confirm → Processing → Result → Record`。
- **OTC Sell Impact**：展示 Available Power / Estimated Freeze / Filled Portion Consumed / Remaining Frozen / Cancel Remaining / Released Power。
- **Robot Start / Withdrawal**：只展示服务端 PowerImpactPreview，禁止设计稿硬编码生产值。
- **主 CTA**：挂买 / 挂卖；Robot Start/Withdrawal 从各自业务页面发起，不在 Power Root 模拟。
- **Overlay**：Power Rule Sheet / Related Action Sheet；高风险确认使用对应业务 Fullscreen Confirm。
- **Required States**：`Default / Loading / Empty / Error / Restricted` + Low/Zero Available/Frozen/Recovering/Service Unavailable。
- **禁止**：币价涨跌、金币体力条、前端自行计算最终 Power、把 Prediction 的 Power 规则套入 P0 竞猜。

#### `M-OTC-001｜OTC 市场` · P0

- **页面目标**：看资格、市场、参考信息并进入挂买/挂卖。
- **视觉结构**：资格/额度/Power卡；Buy/Sell order book；我的订单；风险提示；创建按钮。
- **UI 视觉延展**：OTC 是受控撮合，不做交易所 K 线。顶部资格/额度/Power 状态卡，主体可用简化 Buy/Sell 订单簿、当前参考信息、我的订单和风险说明。
- **主 CTA**：挂买 / 挂卖（首屏可见双动作）。
- **次级按钮 / 可点区域**：我的订单、规则、筛选。
- **关键交互细节**：受控撮合；资格/额度/Power 在顶部；不使用 K 线或交易终端视觉。
- **基线交互补充**：挂买/挂卖先选择 side 再进入输入页；市场依赖异常时隐藏创建 CTA 但保留历史订单入口。
- **弹出 / Overlay**：Side Action Sheet 或双 Action Card；规则 Sheet。
- **推荐组件**：EligibilityCard、SideTabs、OrderBookList、MyOrdersPreview、RiskNotice。
- **状态覆盖**：Empty market；restricted；market dependency unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：功能权限服务端决定。
- **路由 / 返回上下文**：→M-OTC-002 / M-OTC-005。
- **禁止 / 验收**：不做博彩盘口矩阵、红绿赔率闪烁或深色高频交易界面。；明确“参考价和流动性不保证”。

#### `M-OTC-002｜OTC 下单输入` · P0

- **页面目标**：输入 side、数量和允许的价格/结算字段。
- **视觉结构**：Buy/Sell toggle；price；quantity；available/limit/power；settlement method；Next。
- **UI 视觉延展**：表单按 side→价格→数量→可用/额度/Power→结算方式组织，实时预览冻结影响。Max 是辅助按钮，不自动替用户填满。
- **主 CTA**：下一步。
- **次级按钮 / 可点区域**：切 Buy/Sell、Max、结算方式。
- **关键交互细节**：side→价格→数量→资源影响→结算方式；关键字段变化使 quote 失效。
- **基线交互补充**：每次关键字段变化重新获取/失效 quote；Power 不足时直接给 next_action，不在前端自行估算通过。
- **弹出 / Overlay**：Settlement Method Picker；Power不足原因 Sheet。
- **推荐组件**：SideToggle、PriceInput、QuantityInput、ResourcePreview、SettlementMethodPicker、StickyCTA。
- **状态覆盖**：字段错误；超额度；Power不足；结算方式无效。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：客户端校验只做体验，最终服务端。
- **路由 / 返回上下文**：→M-OTC-003。
- **禁止 / 验收**：不做营销 Banner；不使用金色主按钮。；修改 price/quantity 必须重新 quote。

#### `M-OTC-003｜OTC 订单确认` · P0

- **页面目标**：提交前确认价格、数量、费用、冻结、Power 和取消规则。
- **视觉结构**：Quote summary；fee；freeze；power；cancel rule；risk；Consent；submit。
- **UI 视觉延展**：确认页显示 quote 到期时间、价格/数量/fee、APT 冻结、Power 冻结、取消规则、Review 可能性。提交按钮和风险说明之间留足空间。
- **主 CTA**：提交订单。
- **次级按钮 / 可点区域**：返回修改、重新获取报价。
- **关键交互细节**：Quote、fee、APT Freeze、Power Freeze、取消规则、Review 可能性；quote 过期禁提交。
- **基线交互补充**：quote 过期自动禁止提交并给“重新获取报价”；提交前刷新 eligibility；Unknown 结果按原 idempotency 查询。
- **弹出 / Overlay**：Consent/Risk Sheet；MFA 可能插入。
- **推荐组件**：QuoteSummary、FreezeImpact、CountdownBadge、ConsentBlock、StickyCTA。
- **状态覆盖**：quote expired；eligibility changed；review required；unknown result。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：高风险可能 MFA。
- **路由 / 返回上下文**：→M-OTC-004。
- **禁止 / 验收**：不省略影响说明；不把确认做成一个小弹窗里塞满长文本。；Idempotency-Key；不能用按钮 success 代替 order status。

#### `M-OTC-004｜OTC 提交结果` · P0

- **页面目标**：展示订单是否已创建、是否审核、是否进入撮合。
- **视觉结构**：结果状态；order_id；冻结影响；Power；下一步；查看订单。
- **UI 视觉延展**：提交结果明确“已提交 ≠ 已成交”。顶部状态应是 Review / Matching / Rejected / Unknown 等，下面显示 order_id、APT/Power 冻结影响和下一步。
- **主 CTA**：查看订单详情。
- **次级按钮 / 可点区域**：返回市场。
- **关键交互细节**：明确 Submitted ≠ Completed；Review/Matching/Rejected/Unknown 显示冻结影响与下一步。
- **基线交互补充**：只提供“查看订单详情 / 返回市场”；Unknown 状态不出现重新下单按钮。
- **弹出 / Overlay**：Result Page；Unknown 禁止再次下单。
- **推荐组件**：ResultHero、OrderReference、FreezeSummary、ActionGroup。
- **状态覆盖**：review/matching/rejected/unknown/no_effect。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人。
- **路由 / 返回上下文**：→M-OTC-006 / M-OTC-001。
- **禁止 / 验收**：不使用金币雨/烟花；“已提交”不得画成“已完成”。；失败必须明确未冻结或已释放。

#### `M-OTC-005｜我的 OTC 订单` · P0

- **页面目标**：按状态查看自己的 OTC 订单。
- **视觉结构**：status tabs；order list；side/price/qty/filled/remaining。
- **UI 视觉延展**：订单列表按 Buy/Sell 与状态筛选，列表行展示价格、原数量、已成交、剩余、更新时间；Partial 用进度条辅助。
- **主 CTA**：打开订单。
- **次级按钮 / 可点区域**：Buy/Sell/状态筛选。
- **关键交互细节**：显示 Original/Filled/Remaining；Partial 进度条；分页失败不覆盖已加载数据。
- **基线交互补充**：保留筛选；点击进入详情；分页/刷新失败不影响已有数据。
- **弹出 / Overlay**：Filter Sheet。
- **推荐组件**：SegmentedTabs、OrderRow、FillProgress、FilterSheet。
- **状态覆盖**：Empty；分页失败。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人。
- **路由 / 返回上下文**：→M-OTC-006。
- **禁止 / 验收**：不把 Submitted/Matching/Partial 视觉上伪装为 Completed。；Partial 必须显式显示 filled/remaining。

#### `M-OTC-006｜OTC 订单详情` · P0

- **页面目标**：追踪审核、撮合、部分成交、完成、取消、争议与资产影响。
- **视觉结构**：订单摘要；状态时间线；trade list；APT ledger；Power impact；cancel/appeal。
- **UI 视觉延展**：订单详情顶部展示 side/status，核心区域显示原数量/已成交/剩余；下面分别是 Trade、APT Ledger、Power Impact、Timeline。Partial 是主视觉状态之一。
- **主 CTA**：取消剩余（仅 allowed）/ 申诉。
- **次级按钮 / 可点区域**：查看 Trade、APT Ledger、Power Impact。
- **关键交互细节**：Partial 为一级状态；取消只释放未成交部分；已成交不可回滚。
- **基线交互补充**：取消只在 allowed 时出现；取消确认明确释放多少 APT/Power。Disputed 提供 Support/申诉入口。
- **弹出 / Overlay**：Cancel Confirm Sheet；Dispute → Support。
- **推荐组件**：OrderHeader、FillSummary、TradeList、PowerImpact、Timeline、ActionPanel。
- **状态覆盖**：review/matching/partial/completed/cancelled/rejected/disputed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：取消按钮由服务端 allowed_actions。
- **路由 / 返回上下文**：→M-ASSET-003 / M-SUPPORT-002。
- **禁止 / 验收**：不把内部风控理由直接展示；异常状态必须保留记录入口。；取消只释放未成交部分；已成交不可回滚成未成交。

### 10.8 Security

> 视觉家族：安全事实与下一步优先；状态色克制；敏感信息脱敏；危险动作二次确认。

#### `M-SEC-001｜安全中心` · P0

- **页面目标**：集中看 MFA、设备、Session、登录记录和密码安全。
- **视觉结构**：security summary；MFA；devices；sessions；login audit；password。
- **UI 视觉延展**：安全中心用“已启用/需处理”的可信结构，不做夸张安全分数。MFA、设备、Session、登录记录、密码分别成组。
- **主 CTA**：处理最重要安全动作。
- **次级按钮 / 可点区域**：MFA、设备、Session、登录记录、改密码。
- **关键交互细节**：当前会话与其他会话区分；不做虚假安全分数；敏感信息脱敏。
- **基线交互补充**：高风险安全操作要求 MFA；撤销 Session 用 destructive confirm；当前会话与其他会话明确区分。
- **弹出 / Overlay**：安全操作 Confirm；MFA/Device 跳 M-SEC-002。
- **推荐组件**：SecuritySummary、SecuritySettingRow、DevicePreview、LoginActivity、ActionSheet。
- **状态覆盖**：风险限制；操作失败；依赖不可用。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：敏感操作二次验证。
- **路由 / 返回上下文**：→M-SEC-002 / M-AUTH-004。
- **禁止 / 验收**：不靠大面积红色制造恐慌；敏感信息必须脱敏。；不能展示完整 IP/敏感设备指纹。

#### `M-SEC-002｜MFA / 设备 / Session 管理` · P0

- **页面目标**：绑定验证器、查看并撤销其他会话。
- **视觉结构**：MFA enrollment；二维码/密钥安全流程；设备列表；revoke。
- **UI 视觉延展**：MFA enrollment、设备、Session 用分组设置列表；二维码/密钥只在绑定流程短时显示，Session 行含设备、地区粗粒度、最近活动和状态。
- **主 CTA**：绑定 MFA / 撤销 Session（按 Tab）。
- **次级按钮 / 可点区域**：切 MFA/Device/Session。
- **关键交互细节**：撤销后等服务端确认再移除；二维码/密钥只短时显示。
- **基线交互补充**：撤销后必须等待服务端确认再移除行；不能撤销关键当前会话时说明原因。二维码页阻止截图提示可选但不强依赖。
- **弹出 / Overlay**：Revoke Confirm Sheet；MFA Setup Full Flow。
- **推荐组件**：MfaSetupFlow、DeviceRow、SessionRow、ConfirmDialog、ResultState。
- **状态覆盖**：验证失败；不能撤销关键当前会话；risk held。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人 + MFA。
- **路由 / 返回上下文**：成功回 Security。
- **禁止 / 验收**：不在设置页塞业务营销内容。；撤销成功后服务端立即失效对应 session。

### 10.9 Support

> 视觉家族：轻量帮助中心 / 对话；用户消息、客服回复、系统事件分层；不做 CRM 工作台。

#### `M-SUPPORT-001｜帮助中心 / 工单列表` · P0

- **页面目标**：找帮助并查看自己的工单。
- **视觉结构**：FAQ；分类；创建工单；ticket list/status。
- **UI 视觉延展**：顶部搜索 + “我的工单”优先，FAQ 放次级；工单行展示标题、关联对象、状态、最后更新。整体像轻量帮助中心，不像客服后台。
- **主 CTA**：创建工单。
- **次级按钮 / 可点区域**：搜索 FAQ、筛选我的工单、打开工单。
- **关键交互细节**：我的工单优先于 FAQ；同对象已有未关闭工单时引导继续原工单。
- **基线交互补充**：若同对象已有未关闭工单，创建前提示继续原工单；搜索 FAQ 无结果时给创建入口。
- **弹出 / Overlay**：Category Filter Sheet；Duplicate Ticket Sheet。
- **推荐组件**：SearchField、TicketRow、CategoryChips、PrimaryCTA、EmptyState。
- **状态覆盖**：Empty；列表失败。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人；FAQ 可公开部分。
- **路由 / 返回上下文**：→M-SUPPORT-002 / 003。
- **禁止 / 验收**：不做复杂客服工作台样式。；相同问题已有工单时提示继续原工单。

#### `M-SUPPORT-002｜创建工单 / 申诉` · P0

- **页面目标**：提交可处理的问题并绑定具体对象。
- **视觉结构**：category；related object；description；attachments；contact；submit。
- **UI 视觉延展**：related object 选择放在最前，让工单天然绑定订单/流水/Robot；描述、附件、联系方式按表单分组。附件以可重试卡片展示。
- **主 CTA**：提交工单 / 申诉。
- **次级按钮 / 可点区域**：选择关联对象、上传附件、保存草稿。
- **关键交互细节**：先选 related object；附件单项重试；只能关联本人对象。
- **基线交互补充**：保存草稿（本地/服务端按实现）；附件失败不阻断其他字段；duplicate case 直接引导原工单。
- **弹出 / Overlay**：Object Picker Sheet；Upload Action Sheet。
- **推荐组件**：ObjectPicker、TextArea、UploadCard、ContactField、StickyCTA。
- **状态覆盖**：上传失败；字段错误；duplicate case。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：只能关联本人对象。
- **路由 / 返回上下文**：成功→M-SUPPORT-003。
- **禁止 / 验收**：不做营销 Banner；不使用金色主按钮。；失败保留草稿；附件单项可重试。

#### `M-SUPPORT-003｜工单详情` · P0

- **页面目标**：看处理进度、回复、补件和最终结论。
- **视觉结构**：status；SLA（若批准）；timeline；messages；attachments；related objects；reply。
- **UI 视觉延展**：顶部工单状态和关联对象，主体是对话 + 系统时间线，内部状态变化用系统消息区分普通回复；底部回复框固定。
- **主 CTA**：回复 / 补件（按状态）。
- **次级按钮 / 可点区域**：打开附件、关联对象。
- **关键交互细节**：User message / Agent reply / System timeline 三种视觉；resolved/closed 前有用户可见结论。
- **基线交互补充**：waiting_user 时突出待补充项；resolved/closed 前显示结论摘要。附件和对象链接都可独立打开。
- **弹出 / Overlay**：Attachment Preview；Object Link Sheet。
- **推荐组件**：TicketHeader、ConversationThread、SystemTimeline、AttachmentCard、ReplyComposer。
- **状态覆盖**：submitted/in_progress/waiting_user/under_review/resolved/closed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人。
- **路由 / 返回上下文**：深链到相关业务页。
- **禁止 / 验收**：不隐藏系统状态变化；附件不能和普通文本混为一行。；resolved/closed 前必须有用户可见结论。

### 10.10 Settings

> 视觉家族：原生系统设置感；分组列表、Picker Sheet、Switch；不放营销内容。

#### `M-SETTINGS-001｜设置` · P0

- **页面目标**：管理语言、时区、通知偏好和基础应用设置。
- **视觉结构**：language；timezone；notifications；legal/help；logout。
- **UI 视觉延展**：标准系统分组列表：语言/时区、通知、法律与帮助、退出。避免把资产、安全高风险操作或营销内容塞到设置。
- **主 CTA**：保存（若非即时保存）。
- **次级按钮 / 可点区域**：语言、时区、通知、法律、退出。
- **关键交互细节**：系统分组列表；语言切换保持当前状态；Logout 清 Session。
- **基线交互补充**：偏好即时或保存式提交均需明确状态；离线时保留本地选择但标记未同步。退出需确认并清理 session。
- **弹出 / Overlay**：Language Sheet；Timezone Sheet；Logout Confirm。
- **推荐组件**：SettingGroup、SettingRow、Switch、PickerSheet、LogoutButton。
- **状态覆盖**：保存失败；离线。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：本人。
- **路由 / 返回上下文**：Logout→M-AUTH-001。
- **禁止 / 验收**：不在设置页塞业务营销内容。；语言变化不能改变业务数值/规则语义。

### 10.11 P1 / Future

> 与 P0 共用同一设计系统。P1/Future 的“阶段属性”可存在于内部元数据；正式用户 UI 只显示产品允许的 Coming Later / Closed / Restricted 等真实状态，不显示 Mock/Sandbox/Fixture 标签。

#### `M-AI-001｜AI 数据 / Signal 详情` · P1

- **页面目标**：解释 AI 数据与信号来源、时间和非保证属性。
- **视觉结构**：signal summary；source/time；historical context；explanation。
- **UI 视觉延展**：参考 Sofascore 数据可视化思路，但强调数据来源/更新时间/延迟属性。Signal Summary、历史上下文、解释和图表分层，不做上涨箭头或“必胜”视觉。
- **主 CTA**：无高风险主 CTA。
- **次级按钮 / 可点区域**：时间范围/数据类型筛选、口径说明。
- **关键交互细节**：Signal Health、Source、Freshness、历史上下文；延迟时保留最后快照并标记 stale。
- **基线交互补充**：筛选时间/数据类型，查看数据口径说明；延迟/不可用时保留最后快照并显式标记。
- **弹出 / Overlay**：Data Source Sheet；Metric Definition Sheet。
- **推荐组件**：SignalSummary、ChartCard、SourceMeta、ExplanationBlock、DelayBadge。
- **状态覆盖**：data delayed/unavailable。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：只读。
- **路由 / 返回上下文**：返回 Home。
- **禁止 / 验收**：不使用保证性箭头、暴涨曲线或“必胜”视觉。；必须标实时/延迟/估算。

#### `M-GROWTH-001｜Referral / Team` · P1

- **页面目标**：查看邀请关系和符合条件的候选/已结算奖励。
- **视觉结构**：invite；relationship；candidate/held/payable/paid；rules。
- **UI 视觉延展**：邀请关系、候选/held/payable/paid 奖励分区，避免树状金字塔和层级夸张。分享入口是辅助动作。
- **主 CTA**：分享邀请（仅 allowed）。
- **次级按钮 / 可点区域**：关系记录、Reward 状态。
- **关键交互细节**：关系/候选/Held/Payable/Paid 分区；不做金字塔树或财富榜。
- **基线交互补充**：奖励状态不可由前端推导；budget closed 时保留历史并隐藏新活动 CTA。
- **弹出 / Overlay**：Share Sheet；Rule Sheet。
- **推荐组件**：InviteCard、RelationshipList、RewardStatusTabs、RuleNotice。
- **状态覆盖**：资格不足；budget closed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：由服务端资格决定。
- **路由 / 返回上下文**：Me/Robot 子入口。
- **禁止 / 验收**：不做层级金字塔、拉人头树形收益图。；不能承诺永久佣金或“拉人头收益”。

#### `M-PREDICT-FREE-001｜免费 YES/NO` · P1/Sandbox

- **页面目标**：提供不含真实价值的学习/互动预测。
- **视觉结构**：question；yes/no；free points；result/learning。
- **UI 视觉延展**：正式用户 UI 只显示 Free Points / 不含真实价值的学习互动属性；Sandbox 仅作为内部 Scenario/Fixture 分类，不显示给正式用户。问题、YES/NO、学习结果简单直观，不与真实 1X2 或 APT 订单视觉混淆。
- **主 CTA**：提交 YES/NO。
- **次级按钮 / 可点区域**：切选择、查看学习结果。
- **关键交互细节**：清楚显示 Free Points 与“无真实价值”属性；不可与 APT 或真实竞猜视觉混淆；Sandbox 标签只允许内部开发工具可见。
- **基线交互补充**：提交只影响不可兑付 points；结束后给学习/结果反馈，不出现现金价值。
- **弹出 / Overlay**：Submit Confirm Sheet。
- **推荐组件**：SandboxBadge、BinarySelector、PointsSummary、LearningResult。
- **状态覆盖**：closed/ended。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：必须是不可兑付 points。
- **路由 / 返回上下文**：Prediction 子入口。
- **禁止 / 验收**：不隐藏 Draw；不把倍数/估算做成保证性大数字。；不得与真实 APT 或收入混淆。

#### `M-MIGRATION-001｜APT-I → APT-C Migration` · Future/CLOSED

- **页面目标**：未来满足 Gate 后处理数量迁移。
- **视觉结构**：eligibility；quantity；wallet；confirmation；finality timeline。
- **UI 视觉延展**：Future/CLOSED 默认展示关闭说明，而不是可操作表单；只有在未来 Gate 开放的 Sandbox 才显示 eligibility→quantity→wallet→confirm→finality Stepper。
- **主 CTA**：默认无可执行 CTA。
- **次级按钮 / 可点区域**：查看 Gate/说明。
- **关键交互细节**：Future/CLOSED 默认关闭页；未来仅 Gate 开放时显示 Stepper。
- **基线交互补充**：P0 入口隐藏/禁用；未来提交后以 finality timeline 查询，不允许重复广播。
- **弹出 / Overlay**：Closed Explanation Sheet。
- **推荐组件**：ClosedState、MigrationStepper、WalletField、FinalityTimeline。
- **状态覆盖**：closed/review/broadcast/finality/failed/reversed。 原型通用 Frame：`Default / Loading / Empty / Error / Restricted / Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；业务特有状态继续按“必须画出的状态”单独出 Variant。
- **权限边界**：P0 必须 hidden/disabled。
- **路由 / 返回上下文**：Me/Asset 子入口（默认隐藏）。
- **禁止 / 验收**：不把所有步骤塞进一屏；上传失败不能清空整页。；未正式开启时不能用 mock 开关放出真实入口。

### 10.12 Cross-Family Final Guardrails

#### 10.12.1 Formal UI Fixture Rule

Fixture/Mock 数据内部必须携带：

```text
fixture_id
scenario
source
timestamp
metadata
```

正式用户视觉中禁止出现：

```text
Demo
Mock
Sandbox
Fixture
模拟数据
模拟环境
演示环境
```

这些信息只允许存在于：Developer Panel / QA Panel / Scenario Switcher / Fixture Metadata / Test Report。

#### 10.12.2 Member Level / XP Guardrail

除非 01 / 02 / 05 / 06 已正式存在 `Member Level Object / XP Source / Upgrade Rule / Member Benefit`：

```text
MEMBER_LEVEL_VISUAL = CONDITIONAL_PRODUCT_FEATURE
XP_FORMULA = UNDEFINED_BY_DESIGN
TASK_XP = FORBIDDEN_TO_INVENT
DAILY_XP = FORBIDDEN_TO_INVENT
LEVEL_THRESHOLD = FORBIDDEN_TO_INVENT
MEMBER_LEVEL_REWARD = FORBIDDEN_TO_INVENT
MEMBER_LEVEL_PERMISSION = FORBIDDEN_TO_INVENT
ROBOT_LEVEL != MEMBER_LEVEL
```

#### 10.12.3 OTC Visual Rule

OTC = `Controlled Matching`，不是 Crypto Exchange。

- Buy Action：Gainode Blue。
- Sell Action：Navy / Cyan。
- 不采用 Green Buy / Red Sell 的交易终端模式。
- 禁止 K-Line / Trading Chart / Order Book Terminal / Guaranteed Fill。
- Partial / Matching / Submitted 必须与 Completed 明确区分。

#### 10.12.4 ONE_LOCALE_ONE_LANGUAGE

中文 `zh-CN` 页面除锁定词外不得混入普通英文描述。

锁定词：

`Gainode / Robot / APT / APT-I / APT-C / OTC / Power / 1X2 / MFA / KYC / OTP / AI`

示例：

- `Level` → `等级`
- `Member Level` → `会员等级`
- `Momentum Seeker` → 必须本地化，除非未来被产品权威文件正式锁定为产品名。

#### 10.12.5 AI Image Generation Hard Gate

```text
ONE_PAGE_ONE_IMAGE = TRUE
FULL_PAGE_UI_ONLY = TRUE
PHONE_FRAME = FORBIDDEN
DEVICE_MOCKUP = FORBIDDEN
MARKETING_POSTER = FORBIDDEN
PRESENTATION_BOARD = FORBIDDEN
DESIGN_CONCEPT_TITLE_OUTSIDE_UI = FORBIDDEN
MULTIPLE_SCREENS = FORBIDDEN
MULTI_DEVICE = FORBIDDEN
COLLAGE = FORBIDDEN
BEFORE_AFTER = FORBIDDEN
PAGE_DESCRIPTION_OUTSIDE_UI = FORBIDDEN
ROOT_SCREEN_FRAGMENT = FORBIDDEN
```

每张 Root 图只允许：

```text
1 × Header
1 × Page Body
1 × Bottom Navigation
```

Robot Root 额外允许：

```text
1 × RobotFloatingActionBar
Body
↓
Floating Action Bar
↓
Bottom Navigation
```

不得出现上一页残片、第二个 Header、第二个 BottomNav、跨页面拼接。

任何 Page Image 生成前，Agent 必须读取：

`Page ID / Page Goal / Root Parent / Business State / Allowed Actions / Required Components / Forbidden Components / Locale`

不能只根据页面名称自由设计。


## 11. Cross-page Object Graph 与数据连续性

### 11.1 OTC
```text
OTC Order
→ APT Freeze
→ Power Frozen
→ Matching / Partial Fill
→ Filled Portion consumes Power
→ Remaining Portion stays frozen
→ Cancel / Expire releases unfilled portion
→ APT Ledger / Power Ledger
→ Notice
→ Support Ticket / Dispute (optional)
```

### 11.2 Prediction
```text
Prediction Order
→ Consent / Snapshot
→ Lock
→ Result
→ Settlement
→ APT Ledger
→ Refund / Correction (exception)
→ Reversal / New Posting
→ Notice
→ Support / Appeal
```

### 11.3 Robot
```text
Robot State
→ Run Activity
→ Reward Candidate/Held
→ Claim
→ APT Ledger
→ Notice
→ Activity Timeline
```

跨页规则：所有页面展示同一个 object_id 的状态必须来自同一 Fixture/API 对象；列表、详情、通知、流水、Support 不允许各自生成互相冲突的 Mock。

## 12. Return / Resume Context

- Root → Detail → Back：恢复 Root 的 Tab、Filter、Scroll。
- List → Detail → Back：恢复 Query、Sort、Pagination/Cursor、Scroll。
- MFA 插入高风险流程：验证成功必须回原 request/context。
- KYC/资格补全后：返回原 Robot/Prediction/OTC 入口并重新拉 entitlement，不简单跳 Home。
- Language 切换：保留 Page ID / Route / object_id / Tab / Filter / Scroll / Form draft。
- Unknown Result：回历史/详情查询原请求，不新建。

## 13. Multi-language / I18N

支持：

`zh-CN / en-US / ja-JP / ko-KR / th-TH / de-DE / fr-FR`

最终用户字符串只读取 `/i18n/*.json`；页面到 Key 的映射读取 `ui-copy-manifest.json`；固定术语读取 `terminology-lock.json`；敏感文案读取 `sensitive-copy-review.json` 的人工签核状态。

### 13.1 Gate

```text
MISSING_KEY = 0
RAW_ENUM_VISIBLE = 0
CROSS_LANGUAGE_POLLUTION = 0
```

正式出图前至少优先验证：

`zh-CN / en-US / de-DE / th-TH`

同时继续验证：

- German Text Expansion；
- Thai Wrapping / 上下附标；
- French Button Length；
- Japanese / Korean Line Breaking；
- Modal / Bottom Sheet / Drawer / Sticky Action 长文案布局。

### 13.2 ONE_LOCALE_ONE_LANGUAGE

见 §10.12.4。中文除锁定词外不得出现普通英文描述；英语/德语/法语也不得夹杂未本地化中文业务描述。

### 13.3 敏感文案

KYC、Consent、Prediction Risk、OTC Risk、APT Reference Valuation、Refund/Correction、MFA Security、Policy Restriction 等仍需 Product/Legal/Compliance Owner 人工签核。AI 不得把工程草稿标成最终生产法律/金融/合规文案。

## 14. Content Density / NORMAL_OPERATION_STATE

正常运营页面不能用 1 条通知、1 场竞猜、2 条 Ledger、2 个 OTC Order 来代表产品真实密度。Fixture 总数据结构最低推荐：

| Area | 最低/推荐密度 |
|---|---|
| Home Featured Predictions | >= 3 |
| Home Notices | >= 5 |
| Home Leaderboard | >= 5 |
| Home My Prediction State | >= 1 |
| Robot Activity | >= 10 |
| Robot Reward History | >= 7 |
| Prediction Events | >= 12；正常运营推荐 >= 24 |
| My Predictions | >= 12 |
| AI Signals | >= 20 |
| APT Ledger | >= 30 |
| OTC Orders | >= 12 |
| Support Tickets | >= 8 |
| Security Events | >= 10 |

首屏无需一次展示全部；必须通过分页/Load More/时间分组/筛选/折叠与连续滚动表现 `NORMAL_OPERATION_STATE`，同时保持当前视觉密度，不以巨大 Card 数量堆砌“真实性”。

Fixture 数据必须集中管理，并保证跨页同一 object_id 的状态、时间、Ledger、Notice、Support 相互一致。正式用户 UI 不显示任何 Fixture 标识。

## 15. 44 / 44 PAGE EXECUTION MATRIX

> 本矩阵不取代 03 V2.4。逐页出图/原型前，Page ID、Priority、Root Parent、Template、Required State、Overlay、Return/Resume、I18N、Image Rule 必须全部有值。

状态缩写：`Base = Default / Loading / Empty / Error / Restricted`；`Write = Invalid / Confirm / Submitting / Processing / Success / Failed / Unknown Result`；页面特有状态继续读取 §10 与 03 V2.4。

| Page ID | 页面 | Priority | Root Parent | Page Template | Required State | Overlay | Return / Resume | I18N | Image Rule |
|---|---|---|---|---|---|---|---|---|---|
| `M-AUTH-001` | 登录 | P0 | Pre-Root / Auth | 登录表单页 | Base+Write+Page-Specific | 无；必要安全锁定使用 Inline/Full state，不弹营销弹窗。 | 成功→M-AUTH-005 或 M-KYC-001 / M-HOME-001；忘记密码→M-AUTH-004。 | 7 locales / manifest | REQUIRED |
| `M-AUTH-002` | 注册 | P0 | Pre-Root / Auth | 注册表单页 | Base+Write+Page-Specific | 条款全文 Bottom Sheet / WebView；账号类型 SegmentedControl。 | 成功→M-AUTH-003。 | 7 locales / manifest | REQUIRED |
| `M-AUTH-003` | OTP 验证 | P0 | Pre-Root / Auth | OTP 验证页 | Base+Write+Page-Specific | 验证码过期 Inline State；重发成功轻 Toast。 | 成功按 challenge purpose 去下一步。 | 7 locales / manifest | REQUIRED |
| `M-AUTH-004` | 找回 / 重置密码 | P0 | Pre-Root / Auth | 三步重置流程 | Base+Write+Page-Specific | 完成 Result Page；不使用普通 Toast 代替结果。 | 完成→M-AUTH-001。 | 7 locales / manifest | REQUIRED |
| `M-AUTH-005` | MFA 二次验证 | P0 | Pre-Root / Auth | MFA Context Confirm | Base+Write+Page-Specific | 验证方式 Bottom Sheet；恢复方式 Sheet。 | 成功回原操作，不另造重复业务请求。 | 7 locales / manifest | REQUIRED |
| `M-KYC-001` | KYC 与功能准入概览 | P0 | Pre-Root / Admission | Admission Overview | Base+Write+Page-Specific | 原因说明 Bottom Sheet；申诉跳 Support，不弹通用 Permission。 | 填写→M-KYC-002；结果→M-KYC-003；申诉→M-SUPPORT-002。 | 7 locales / manifest | REQUIRED |
| `M-KYC-002` | KYC 资料提交 / 补件 | P0 | Pre-Root / Admission | Stepper + Upload | Base+Write+Page-Specific | 证件类型 Picker Sheet；Consent Full Sheet；上传源 Action Sheet。 | 提交成功→M-KYC-003。 | 7 locales / manifest | REQUIRED |
| `M-KYC-003` | KYC 状态 / 结果 | P0 | Pre-Root / Admission | Status Result + Timeline | Base+Write+Page-Specific | 补件原因 Sheet；申诉跳 Support。 | 补件→M-KYC-002。 | 7 locales / manifest | REQUIRED |
| `M-HOME-001` | 首页 | P0 | Home | Home Root | Base+Page-Specific | Notice preview / filter 可使用轻量 Bottom Sheet；高风险动作必须进入独立业务流程。 | 按 03 V2.4；Back 恢复来源上下文 | 7 locales / manifest | REQUIRED |
| `M-NOTICE-001` | 消息中心 | P0 | Home | Grouped Notice Feed | Base+Page-Specific | 通知正文可 Bottom Sheet；失效对象显示状态更新。 | 根据 object_type 深链跳转。 | 7 locales / manifest | REQUIRED |
| `M-ROBOT-001` | Robot 概览 | P0 | Robot | Robot Root | Base+Page-Specific | Start/Stop → M-ROBOT-002；Upgrade → 003；Level → 005/LevelDetailSheet；Rewa… | 按 03 V2.4；Back 恢复来源上下文 | 7 locales / manifest | REQUIRED |
| `M-ROBOT-002` | Robot 启动 / 停止确认 | P0 | Robot | High-risk Confirm | Base+Write+Page-Specific | 高风险全屏确认优先；简单状态切换可大型 Bottom Sheet。 | 成功回 M-ROBOT-001；review 显示处理中结果。 | 7 locales / manifest | REQUIRED |
| `M-ROBOT-003` | Robot 升级 | P0 | Robot | Upgrade Compare/Preview | Base+Write+Page-Specific | 目标等级 Picker / Level Sheet；Quote Expired Sheet。 | 提交→M-ROBOT-004。 | 7 locales / manifest | REQUIRED |
| `M-ROBOT-004` | 升级结果 | P0 | Robot | Result + Impact | Base+Write+Page-Specific | Result Page；Unknown 不出现重试创建。 | →M-ROBOT-001 / M-ASSET-003 / M-ROBOT-007。 | 7 locales / manifest | REQUIRED |
| `M-ROBOT-005` | 56 级等级地图 | P0 | Robot | Level Map | Base+Write+Page-Specific | Level Detail Bottom Sheet。 | →M-ROBOT-003。 | 7 locales / manifest | REQUIRED |
| `M-ROBOT-006` | Rewards & Claim | P0 | Robot | Reward Center | Base+Write+Page-Specific | Claim Confirm Sheet；Claim Result Sheet。 | Claim 结果留在本页/结果 sheet；流水→M-ASSET-003。 | 7 locales / manifest | REQUIRED |
| `M-ROBOT-007` | Robot 活动与记录 | P0 | Robot | Activity Feed | Base+Page-Specific | Filter Sheet；Event Detail Sheet。 | 深链到 Asset/Support。 | 7 locales / manifest | REQUIRED |
| `M-PREDICT-001` | 竞猜赛事列表 | P0 | Prediction | Prediction Root / Match List | Base+Page-Specific | 日期/联赛 Filter Sheet；赛事 → M-PREDICT-002；我的竞猜 → M-PREDICT-004。 | 按 03 V2.4；Back 恢复来源上下文 | 7 locales / manifest | REQUIRED |
| `M-PREDICT-002` | 赛事详情 | Football 1X2 · P0 | Prediction | Match Detail | Base+Page-Specific | 规则/数据 Bottom Sheet 或 Tab；不得默认高亮推荐。 | →M-PREDICT-003。 | 7 locales / manifest | REQUIRED |
| `M-PREDICT-003` | Prediction 确认 | P0 | Prediction | High-risk Confirm | Base+Write+Page-Specific | Consent Full Sheet；提交结果进入 Order Detail/Processing。 | 成功→M-PREDICT-005；unknown→处理中。 | 7 locales / manifest | REQUIRED |
| `M-PREDICT-004` | 我的 Prediction | P0 | Prediction | My Predictions List | Base+Page-Specific | Status Filter Sheet。 | →M-PREDICT-005。 | 7 locales / manifest | REQUIRED |
| `M-PREDICT-005` | Prediction 订单详情 | P0 | Prediction | Order Detail / Multi-axis Status | Base+Write+Page-Specific | Snapshot Sheet；追加方向 Confirm；申诉跳 Support。 | 异常→M-PREDICT-006；流水→M-ASSET-003；申诉→M-SUPPORT-002。 | 7 locales / manifest | REQUIRED |
| `M-PREDICT-006` | 异常 / 退款 / 更正详情 | P0 | Prediction | Exception / Correction | Base+Write+Page-Specific | Evidence Summary Sheet；Before/After Diff Sheet。 | →M-ASSET-003 / M-SUPPORT-002。 | 7 locales / manifest | REQUIRED |
| `M-ME-001` | 我的 | P0 | Me | Me Root | Base+Page-Specific | 账户信息 Sheet；语言/安全等进入对应独立页；不提供收益/Premium 弹层。 | 按 03 V2.4；Back 恢复来源上下文 | 7 locales / manifest | REQUIRED |
| `M-ASSET-001` | APT 资产 | P0 | Me | APT Quantity Summary | Base+Page-Specific | 数量状态 Explanation Sheet；OTC Quick Action Sheet。 | →M-ASSET-002 / M-OTC-001 / M-POWER-001。 | 7 locales / manifest | REQUIRED |
| `M-ASSET-002` | APT 流水列表 | P0 | Me | Ledger List | Base+Page-Specific | Filter Sheet。 | →M-ASSET-003。 | 7 locales / manifest | REQUIRED |
| `M-ASSET-003` | APT 流水详情 | P0 | Me | Ledger Object Detail | Base+Page-Specific | Copy Toast；Dispute → Support Create。 | 按 related_object 跳转。 | 7 locales / manifest | REQUIRED |
| `M-POWER-001` | Power | P0 | Me | Power Resource Root | Base+Page-Specific | Power Rule Sheet / Related Action Sheet；高风险确认使用对应业务 Fullscreen Confirm。 | 按 03 V2.4；Back 恢复来源上下文 | 7 locales / manifest | REQUIRED |
| `M-OTC-001` | OTC 市场 | P0 | Me | Controlled Matching Root | Base+Page-Specific | Side Action Sheet 或双 Action Card；规则 Sheet。 | →M-OTC-002 / M-OTC-005。 | 7 locales / manifest | REQUIRED |
| `M-OTC-002` | OTC 下单输入 | P0 | Me | Order Input + Preview | Base+Write+Page-Specific | Settlement Method Picker；Power不足原因 Sheet。 | →M-OTC-003。 | 7 locales / manifest | REQUIRED |
| `M-OTC-003` | OTC 订单确认 | P0 | Me | High-risk Confirm | Base+Write+Page-Specific | Consent/Risk Sheet；MFA 可能插入。 | →M-OTC-004。 | 7 locales / manifest | REQUIRED |
| `M-OTC-004` | OTC 提交结果 | P0 | Me | Submission Result | Base+Write+Page-Specific | Result Page；Unknown 禁止再次下单。 | →M-OTC-006 / M-OTC-001。 | 7 locales / manifest | REQUIRED |
| `M-OTC-005` | 我的 OTC 订单 | P0 | Me | OTC Orders List | Base+Page-Specific | Filter Sheet。 | →M-OTC-006。 | 7 locales / manifest | REQUIRED |
| `M-OTC-006` | OTC 订单详情 | P0 | Me | OTC Order Detail | Base+Write+Page-Specific | Cancel Confirm Sheet；Dispute → Support。 | →M-ASSET-003 / M-SUPPORT-002。 | 7 locales / manifest | REQUIRED |
| `M-SEC-001` | 安全中心 | P0 | Me | Security Center | Base+Page-Specific | 安全操作 Confirm；MFA/Device 跳 M-SEC-002。 | →M-SEC-002 / M-AUTH-004。 | 7 locales / manifest | REQUIRED |
| `M-SEC-002` | MFA / 设备 / Session 管理 | P0 | Me | Security Tabs / Session Manager | Base+Write+Page-Specific | Revoke Confirm Sheet；MFA Setup Full Flow。 | 成功回 Security。 | 7 locales / manifest | REQUIRED |
| `M-SUPPORT-001` | 帮助中心 / 工单列表 | P0 | Me | Help + Ticket List | Base+Write+Page-Specific | Category Filter Sheet；Duplicate Ticket Sheet。 | →M-SUPPORT-002 / 003。 | 7 locales / manifest | REQUIRED |
| `M-SUPPORT-002` | 创建工单 / 申诉 | P0 | Me | Ticket Create / Appeal | Base+Write+Page-Specific | Object Picker Sheet；Upload Action Sheet。 | 成功→M-SUPPORT-003。 | 7 locales / manifest | REQUIRED |
| `M-SUPPORT-003` | 工单详情 | P0 | Me | Conversation + System Timeline | Base+Write+Page-Specific | Attachment Preview；Object Link Sheet。 | 深链到相关业务页。 | 7 locales / manifest | REQUIRED |
| `M-SETTINGS-001` | 设置 | P0 | Me | System Settings | Base+Page-Specific | Language Sheet；Timezone Sheet；Logout Confirm。 | Logout→M-AUTH-001。 | 7 locales / manifest | REQUIRED |
| `M-AI-001` | AI 数据 / Signal 详情 | P1 | Home | AI Data Detail | Base+Page-Specific | Data Source Sheet；Metric Definition Sheet。 | 返回 Home。 | 7 locales / manifest | REQUIRED |
| `M-GROWTH-001` | Referral / Team | P1 | Me | Growth / Relationship | Base+Page-Specific | Share Sheet；Rule Sheet。 | Me/Robot 子入口。 | 7 locales / manifest | REQUIRED |
| `M-PREDICT-FREE-001` | 免费 YES/NO | P1/Sandbox | Prediction | Free Interaction Detail | Base+Write+Page-Specific | Submit Confirm Sheet。 | Prediction 子入口。 | 7 locales / manifest | REQUIRED |
| `M-MIGRATION-001` | APT-I → APT-C Migration | Future/CLOSED | Me | Future/CLOSED Gate | Base+Write+Page-Specific | Closed Explanation Sheet。 | Me/Asset 子入口（默认隐藏）。 | 7 locales / manifest | REQUIRED |

## 16. Flutter App 实现映射建议

建议 Flutter 组件语义与设计系统一致，但不要求与 Vue 组件同名：

| Design Component | Flutter 建议 |
|---|---|
| AppShell | Scaffold + SafeArea + IndexedStack/Router Shell |
| BottomNav | NavigationBar / 自定义 BottomNavigationBar |
| GainodeAppBar | 自定义 PreferredSizeWidget |
| Card | GainodeCard / Container + BoxDecoration |
| StatusBadge | GainodeStatusBadge |
| Bottom Sheet | showModalBottomSheet + SafeArea |
| Fullscreen Confirm | 独立 Route/Page |
| Sticky/Floating CTA | bottomNavigationBar/Stack Positioned，注意与 BottomNav 共存 |
| Timeline | ListView + 自定义 Timeline Row |
| Tabs | TabBar/SegmentedButton |
| Form | Form + TextFormField + 服务端错误映射 |
| State | Riverpod/Bloc/Provider 均可；对象状态与 UI 状态分离 |

### 16.1 Route Key
建议每个 Page ID 有稳定 route name，例如 `M-ROBOT-006 → /robot/rewards`；route arguments 必须携带 `object_id / source_page / return_context`。

### 16.2 UI State 与 Domain State 分离
```text
UI: loading / refreshing / sheetOpen / formDirty
Domain: active / review / partial / settled / restricted
```
不要用前端 UI state 伪造服务端业务状态。

## 17. High-Fidelity Acceptance Gate

- [ ] `PAGE_ID_COVERAGE = 44/44`，`PAGE_ID_DUPLICATE = 0`。
- [ ] Root 4/4 均为 P0：Home / Robot / Prediction / Me；`M-ME-001 = P0`。
- [ ] Home 无 Post Buy / Post Sell，存在 MyPredictionCard + TodayTaskCard。
- [ ] 44 页每页都有独立 Page Template、Required State、Overlay/无 Overlay 声明、Return/Resume、I18N、Image Rule。
- [ ] 四个 Root 属于同一产品家族；Logo/Header/BottomNav/Token 均读取 08 V2.4。
- [ ] 375 / 390 / 430 / 768 / 1024 无横向溢出、CTA 掉位、BottomNav/Floating Bar 遮挡。
- [ ] 每页一个主要 CTA；所有高风险动作走 Review/Confirm/Processing/Result/Record。
- [ ] Unknown Result 不允许重复创建，保留原 request/object/idempotency context。
- [ ] Prediction Home/Draw/Away 等权，Community Pick % 不是 Odds；正常赛事数据 >=12，推荐 >=24。
- [ ] Robot PowerBattery 存在；无 Upgrade Progress；Runtime Stage/Stepper/Activity 一致。
- [ ] Power 对齐 V6.1，数值只来自 Server/Active Rule/Parameter/Preview；Prediction P0 不套用 Power。
- [ ] APT 是 Quantity；Reference Valuation 有 Reference/Estimated/Snapshot/Timestamp。
- [ ] OTC = Controlled Matching；Submitted != Completed；Partial 显示 Filled/Remaining/Power；无 K-line/红绿终端。
- [ ] Member Level / XP 未授权规则数量 = 0；Robot Level 与 Member Level 分离。
- [ ] 正式用户 UI 不出现 Demo/Mock/Sandbox/Fixture；内部 Fixture Metadata 完整。
- [ ] `ONE_LOCALE_ONE_LANGUAGE`；MISSING_KEY=0；RAW_ENUM_VISIBLE=0；CROSS_LANGUAGE_POLLUTION=0。
- [ ] `ONE_PAGE_ONE_IMAGE`；无 Phone Frame / Device Mockup / Collage / Multiple Screens / Root Fragment。
- [ ] 所有 Token 均标记/理解为 `MIRROR_OF_08_V2.4`，本文件不成为第二 Token Source。
- [ ] `Gainode_Mobile_H5_Design_System_V1.1.md = MERGED / ARCHIVED`，不再并行维护。

## 18. 本文件的维护规则

本文件只做“设计执行视图”整合，不承担业务权威。

- 功能/产品范围变化 → 先更新 01。
- 经济/Power/Reward 业务规则变化 → 先更新 02。
- Mobile/H5 页面变化 → 先更新 03 V2.4 的后续正式版本。
- Admin 页面变化 → 先更新 04。
- 数据/状态/权限/API → 先更新 05。
- 参数 → 先更新 06。
- 验收 → 先更新 07。
- Visual Token / Interaction / I18N → 先更新 08。
- 用户文案 → 先更新 `/i18n`。
- Root Visual Anchor 正式变化 → 更新 08/03 后再同步本伴随文档并做 44 页回归。

归档：

```text
Gainode_Mobile_H5_Design_System_V1.1.md = MERGED / ARCHIVED
PARALLEL_DESIGN_SYSTEM_MAINTENANCE = NO
```


## 19. Final Self-Check Gate

```text
DOCUMENT_ROLE =
DESIGN_EXECUTION_COMPANION

CURRENT_03_VERSION =
V2.4

CURRENT_08_VERSION =
V2.4

PAGE_ID_COVERAGE =
44/44

ROOT_PAGE_COVERAGE =
4/4

M_ME_001_PRIORITY =
P0

HOME_POST_BUY_SELL =
REMOVED

HOME_MY_PREDICTION =
PRESENT

HOME_TODAY_TASK =
PRESENT

FIXTURE_VISIBLE_IN_FORMAL_UI =
NO

POWER_V6_1_ALIGNMENT =
PASS

MEMBER_LEVEL_UNAUTHORIZED_RULE =
0

ZH_LANGUAGE_POLLUTION_RULE =
PRESENT

ONE_PAGE_ONE_IMAGE =
REQUIRED

PHONE_FRAME =
FORBIDDEN

COLLAGE =
FORBIDDEN

MULTIPLE_PAGE_IN_ONE_IMAGE =
FORBIDDEN

ROOT_SCREEN_FRAGMENT =
FORBIDDEN

DESIGN_TOKEN_SECOND_SOURCE =
NO

DUPLICATE_DESIGN_SYSTEM =
NO

VERDICT =
READY_FOR_FULL_HIFI_EXECUTION
```

> `READY_FOR_FULL_HIFI_EXECUTION` 只代表设计结构与交互执行条件通过；敏感七语言文案仍需要人工 Product/Legal/Compliance Owner 签核，不能据此声明 `READY_FOR_FINAL_PRODUCTION_LEGAL_COPY = YES`。
