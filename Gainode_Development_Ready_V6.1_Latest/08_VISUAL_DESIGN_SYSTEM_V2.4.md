# 08 · Gainode 视觉设计系统

> 版本：V2.5 · State Color Semantics & I18N Coverage Closure  
> 状态：`HIFI_VISUAL_AND_I18N_BASELINE`
> 用途：Mobile / H5 / Admin 高保真原型与前端 UI 实现的唯一全局视觉基线。  
> 来源：基于本包 `assets/logo/` 中的 Gainode Logo 资产重新提炼；**不采用旧大 Figma、旧 Flutter、旧 Admin 的视觉作为基线。**

## 0. 先说人话

这份文档只解决一件事：**以后不管谁做 Gainode 页面，都要看起来像同一个产品。**

Logo 本身有很强的蓝色、金色、体育、数据和运动轨迹特征，但 UI 不照抄 Logo 的 3D 高光。Logo 负责“品牌识别”，界面负责“清楚、可信、好用”。

最终气质：

```text
可信的 AI Sports 产品
+ 清楚的数据工具
+ 克制的品牌科技感
+ 少量体育运动感
- 博彩感
- 交易所喊单感
- 黑金暴富感
- 高收益大屏感
```

---


## 0.1 Latest Visual Direction Lock

2026-08-10 最新项目结论：

```text
VISUAL_ANCHOR = Latest approved Home / Robot / Prediction / Me root visuals
OLD_BIG_FIGMA = NOT_BASELINE
LIGHT_APP_SHELL = YES
PRIMARY_REVIEW_LANGUAGE = English UI
STYLE = Western + Premium + Sports-Tech + Operational
NO_PHONE_DEVICE_FRAME = YES
ONE_PAGE_ONE_IMAGE = YES
GENERIC_CARD_FEEL = REDUCE
```

解释：

- 最近已经确认的新 Home / Robot / Prediction / Me Root 视觉是后续子页面的**视觉锚点**；旧大 Figma 仍然禁止作为基线。
- 四个 Root 必须共享同一个 AppShell、Header、BottomNav、圆角、Typography、Icon、CTA、Sheet/Modal 体系。
- Robot 可以保留 Dark Hero，但 Hero 以下回到 Light Shell；不能整页纯深色。
- 页面信息可以真实、丰富，但不要通过“每件事一张大卡片”解决。优先使用轻列表、时间分组、Segment、数据行、折叠与留白。
- 设计稿/审核图直接输出页面画板，不套手机设备框；一页一图，不做四屏拼图。
- 原型运行时仍然可以是完整可点击 H5；“一页一图”只约束视觉设计交付与人工审核证据。


## 1. Logo 资产与使用规则

### 1.1 资产表

| 文件 | 用途 | 允许场景 |
|---|---|---|
| `assets/logo/logo_source_01_primary_light.png` | 浅色背景完整 Logo | 品牌页、登录页、文档封面 |
| `assets/logo/logo_source_02_symbol_transparent.png` | 单独图形标 | App 内小型品牌位、头像、加载页 |
| `assets/logo/logo_source_03_app_icon_dark.png` | App Icon | App 图标、PWA 图标、启动器 |
| `assets/logo/logo_source_04_vertical_transparent.png` | 竖版组合标 | Splash、品牌介绍、空态品牌页 |
| `assets/logo/logo_source_05_horizontal_transparent.png` | 横版组合标 | H5 Header、Admin Header、登录页横向区域 |
| `assets/logo/logo_source_06_mono_light.png` | 浅色单色标 | 深蓝底、深色侧栏、深色 Footer |
| `assets/logo/logo_source_07_dark_splash.png` | 深色完整品牌图 | App Splash / Launch Screen |
| `assets/logo/logo_source_08_mono_dark.png` | 深色单色标 | 白底打印、低彩环境、无渐变场景 |

### 1.2 固定使用方式

- **App 图标**：只用 `logo_source_03_app_icon_dark.png`。
- **App Splash**：优先 `logo_source_07_dark_splash.png`。
- **Mobile 页面内部**：只用 symbol，默认不重复放完整 GAINODE 字标。
- **Mobile 登录/注册**：浅色页面用 `logo_source_01_primary_light.png` 或 `logo_source_04_vertical_transparent.png`。
- **H5 顶部品牌位**：优先 `logo_source_05_horizontal_transparent.png`。
- **Admin 左侧深蓝导航**：展开态用 `logo_source_06_mono_light.png`；收起态用单独 symbol。
- **Admin 登录页**：深色品牌区可用 `logo_source_07_dark_splash.png`，业务表单区仍保持浅色。

### 1.3 禁止

- 不拉伸、不压扁、不旋转。
- 不自行换足球、海豚、柱形图或字标位置。
- 不给完整 Logo 再加外发光、描边或阴影。
- 不把 Logo 当大面积半透明背景纹理铺满页面。
- 不把 Logo 的 3D 金属高光复制到按钮、表格、全部卡片。
- 非单色资产不要擅自改色；需要单色时直接使用已有 mono 版本。

### 1.4 安全空间与最小尺寸

- 完整 Logo 四周安全空间：至少为 symbol 宽度的 `25%`。
- Mobile 完整字标最小宽度：`104px`。
- H5 / Admin Header 横版字标最小宽度：`120px`。
- 单独 symbol 最小尺寸：`24×24px`；常用 `28 / 32 / 40px`。
- App Icon 不在 UI 页面里当普通图标使用。

---

## 2. 品牌色 Token

以下 UI Token 是从附件 Logo 的主色群提炼出的产品色，不等于商标印刷色规范；以后 UI 开发直接使用这些 Token，不再每页自己取色。

```css
--brand-navy-950: #071226;
--brand-navy-900: #05285D;
--brand-blue-800: #024EC2;
--brand-blue-600: #057CF1;
--brand-cyan-500: #06A9FE;
--brand-cyan-300: #3ACFFD;
--brand-gold-500: #F4D016;
--brand-gold-300: #FFE27A;
```

### 2.1 中性色

```css
--gray-950: #0F172A;
--gray-800: #1E293B;
--gray-700: #334155;
--gray-600: #475569;
--gray-500: #64748B;
--gray-400: #94A3B8;
--gray-300: #CBD5E1;
--gray-200: #E2E8F0;
--gray-100: #F1F5F9;
--gray-50:  #F8FAFC;
--white:    #FFFFFF;
```

### 2.2 业务状态色

```css
--success-600: #059669;
--success-100: #D1FAE5;
--warning-600: #D97706;
--warning-100: #FEF3C7;
--danger-600:  #DC2626;
--danger-100:  #FEE2E2;
--info-600:    #0284C7;
--info-100:    #E0F2FE;
```

### 2.3 用色比例

普通业务页建议：

```text
白 / 浅灰中性色   70–80%
深蓝 / 品牌蓝     15–25%
金色              <= 5%
```

**金色只用于：** Level、Reward 资格、升级关键变化、非常少量品牌装饰。  
**金色不用作：** 所有按钮、所有金额、所有图表、所有成功状态。

---

## 3. 背景与主题

### Mobile / H5 P0

- 默认浅色主题。
- 页面背景：`gray-50`。
- 卡片：`white`。
- 首页 / Robot 的品牌 Hero 可用深蓝渐变：`brand-navy-900 → brand-blue-800`。
- 高风险确认页保持浅色，不用黑底制造交易刺激感。
- P0 不做全局 Dark Mode；以后需要再单独设计。

### Admin P0

- 左侧导航：深蓝 `brand-navy-950 / 900`。
- 主内容区：`gray-50`。
- Header / Card / Table：白色。
- 只有系统健康、异常、审批风险状态使用状态色，不做黑色数据大屏。

---

## 4. 字体与排版

### 字体栈

```css
font-family: Inter, "PingFang SC", "HarmonyOS Sans SC", "Noto Sans SC", system-ui, sans-serif;
font-variant-numeric: tabular-nums;
```

### Mobile / H5

| Token | 字号 / 行高 | 字重 | 用途 |
|---|---|---|---|
| Display | 28 / 36 | 700 | 少量品牌 Hero |
| H1 | 24 / 32 | 700 | 页面一级标题 |
| H2 | 20 / 28 | 650 | 主要卡片标题 |
| H3 | 17 / 24 | 600 | 分组标题 |
| Body | 15 / 22 | 400 | 正文 |
| Body Strong | 15 / 22 | 600 | 重要正文 |
| Meta | 13 / 18 | 400 | 时间、规则版本 |
| Caption | 12 / 16 | 400 | 最小辅助文字 |
| Data L | 28 / 34 | 700 | 关键数量，谨慎使用 |
| Data M | 20 / 28 | 650 | 卡片指标 |

### Admin

| Token | 字号 / 行高 | 用途 |
|---|---|---|
| Page Title | 22 / 30 | 页面标题 |
| Section | 16 / 24 | 分区标题 |
| Table / Form | 14 / 20 | 默认后台正文 |
| Meta | 12 / 18 | ID、版本、时间 |
| Data | 20 / 28 | 摘要卡 |

**人话备注：** 数字可以清楚，但不要为了“有金融感”把数字做到比页面标题还大。

---

## 5. 8pt 间距与布局 Token

```css
--space-1: 4px;
--space-2: 8px;
--space-3: 12px;
--space-4: 16px;
--space-5: 20px;
--space-6: 24px;
--space-8: 32px;
--space-10: 40px;
--space-12: 48px;
```

### Mobile

- 基准画布：`390×844`。
- 必须兼容：`375×812`、`430×932`。
- 页面左右边距：`16px`。
- 卡片内边距：`16px`；复杂卡 `20px`。
- 区块间距：`20–24px`。
- App Bar：`56px + safe area`。
- Bottom Nav：`64px + safe area`。
- 固定 CTA：`64–72px + safe area` 容器。

### H5

- `<= 767px`：完全按 Mobile 规则。
- `768–1023px`：仍保持 Mobile IA；内容居中，不改成桌面后台。
- 表单 / 确认页最大宽度：`520px`。
- 详情 / 列表页最大宽度：`720px`。
- Bottom Nav 在 H5 App Shell 内继续保留，避免同一个产品出现两套导航。

### Admin

- 基准画布：`1440×900`。
- 支持：`1280 / 1440 / 1920`。
- Sidebar：展开 `240px`；收起 `72px`。
- Top Header：`64px`。
- 内容边距：`24px`；1920 宽屏可 `32px`。
- 内容最大宽度：`1600px`，超宽屏不无限拉长。
- 表格默认行高：`48px`；紧凑模式 `40px` 仅 Audit / Log 类页面。
- 筛选栏默认高度：`56px`，复杂筛选允许两行。
- Drawer：普通 `480px`；复杂对象 `640px`。
- 详情页 Tab 内容宽度：建议 `960–1280px`。

---

## 6. 圆角、边框、阴影

```css
--radius-sm: 8px;
--radius-md: 12px;
--radius-lg: 16px;
--radius-xl: 20px;
--border-default: 1px solid #E2E8F0;
--shadow-card: 0 4px 16px rgba(15, 23, 42, .06);
--shadow-float: 0 12px 32px rgba(15, 23, 42, .12);
```

- Mobile 普通卡片：`16px`。
- 表单 / 按钮：`12px`。
- Admin 卡片：`12–14px`。
- Admin Table 不做每行卡片阴影。
- Hero 可用渐变，但不加厚重发光。

---

## 7. 核心组件

### Button

- Mobile 主按钮：高度 `48px`；最小点击区域 `44px`。
- Admin 主按钮：`36–40px`。
- Primary：`brand-blue-600` 实底。
- Secondary：白底 + `gray-200` 边框。
- Destructive：默认白底/浅红底 + danger 文案；只有最终不可逆确认才允许红色实底。
- Loading 时保持按钮原宽，不发生布局跳动。

### Input

- Mobile 高度：`48px`。
- Admin 高度：`36–40px`。
- Focus：`2px` 品牌蓝 focus ring。
- Error：红色边框 + 文案 + 图标；不能只变红不解释。

### Card

统一结构：

```text
Title / Status
Supporting text
Main content
Meta / Time / Version
Action（可选）
```

### Status Badge

必须同时有**文字 + 颜色**。例如：

```text
ACTIVE · 蓝/绿
REVIEW · 黄
RESTRICTED · 橙
FAILED · 红
CLOSED · 灰
```

禁止只靠颜色表示状态。

### 状态颜色语义

> 补充规则：所有状态不得只靠颜色表达，必须配合文字、图标和下一步指引。

- **Success Green**：只表示完成、可用或成功，不表示收益、上涨、赚钱或正向投资结果。
- **Warning Yellow/Amber**：表示注意、待处理、即将发生或需要用户动作，不替代 Error。
- **Danger Red**：表示失败、拒绝、不可逆操作或真实高风险，不用于普通金额涨跌。
- **Restricted**：可以根据严重程度使用 Warning/Orange/Danger，但必须有文字、图标和下一步说明。
- **Paused / Closed / Historical**：不应一律使用红色；按语义使用橙色或灰色。
- **Expired**：使用灰色或次色调，表示自然时间结束，不表示失败或拒绝。

### 状态色应用约束

| canonical 状态 | 推荐颜色 | 使用场景 |
|---|---|---|
| `active` | Success Green | 运行中、已激活 |
| `completed` | Success Green | 已完成成交 |
| `approved` | Success Green | 已批准 |
| `pending / review` | Warning Amber | 待审核、处理中 |
| `rejected` | Danger Red | 审批驳回 |
| `failed` | Danger Red | 执行失败 |
| `cancelled` | Neutral Gray | 用户主动取消 |
| `expired` | Neutral Gray | 自然到期（不表示错误） |
| `paused` | Warning Orange | 已暂停 |
| `restricted` | Warning Orange 或 Danger Red | 受限（按严重程度） |
| `suspended` | Danger Red | 已暂停（账户级） |
| `void` | Neutral Gray | 已作废 |
| `disputed` | Warning Amber | 争议中 |
| `archived` | Neutral Gray | 已归档，历史参考 |

### Sheet / Modal / Drawer

- Mobile 普通选择与说明：Bottom Sheet。
- Mobile 高风险最终确认：全屏确认页优先；简单确认可 Bottom Sheet。
- H5：同一逻辑可转居中 Modal。
- Admin 轻详情：Drawer；复杂业务对象：独立 Detail Page。

### Skeleton

- Skeleton 必须跟真实布局一致。
- 不用全屏 Spinner 替代复杂页。
- 卡片独立加载时，单卡失败不阻断整页。


### V6 新增全局组件

以下组件是 V6 的正式组件契约。页面只组合和配置 Variant，不得在每一页重新发明一套样式。

#### `RobotFloatingActionBar`

用途：`M-ROBOT-001` Root 的持续操作入口。

- 高度：`64–72px`
- 位置：Fixed / Sticky，始终位于 BottomNav 上方
- 与 BottomNav 间隔：`8–12px`
- 左侧：状态/Running Duration/Claimable 简报
- 右侧：一个主 CTA
- Variant：`Start / Running / Claimable / Error / Restricted`
- 键盘出现时：隐藏或上移，不能遮挡输入与系统键盘

#### `DailyClaimCard`

用途：首页与 Reward 页展示真实可领取 Reward。

必须支持：

`today_generated / claimable / claimed_today / yesterday / next_cycle / rule_version`

没有可领取时不制造签到 Reward；动态系数可为 0。

#### `RobotRuntimeTimeline`

用途：把 Robot 的运行过程变成用户可理解的时间线。

UI Stage：

`PREPARING / STARTING / RUNNING / OUTPUT_READY / CLAIMABLE / CLAIMED / PAUSING / PAUSED / UPGRADING / RESTRICTED / ERROR`

这些是**表现层 stage**，不能替代 `05` 的 Robot / Reward Domain State。

#### `RewardTrendCard`

用途：今日、昨日、7 日 Reward 趋势。

- 主线使用 Brand Blue
- 不使用 K 线/涨跌红绿
- 必须带时间范围、状态、更新时间/Rule Version
- 禁止 APR/APY/固定收益预测

#### `NoticeTicker`

用途：Home Banner 下方的单行重要通知。

- 高度 `40–44px`
- Icon + 单行标题 + 可选轮播 + Chevron
- 点击进入 `M-NOTICE-001`
- 不代替紧急风险弹窗

#### `UpgradeLeaderboard`

用途：社区成长/活跃排行榜。Home 中固定放在 AI Data Summary 之后、BottomNav 之前，不抢首屏主任务。

允许：排名、脱敏昵称、Robot Level、本周升级数量/活跃度、我的排名、距下一名差距。

禁止：资产余额、Reward 金额、财富、收入、参考估值排名。

#### `CommunityActivityCard`

用途：展示社区真实活跃行为摘要，例如升级、竞猜参与、活动完成；只展示经过隐私处理的非敏感信息。

#### `PredictionParticipationBar`

用途：展示会员参与人数/热度/三方向比例。不是 Odds，不显示庄家式赔率。

#### `PredictionThreeWayCard`

用途：Football 1X2 的 `Home / Draw / Away` 三方向。

- 三方向同级
- 同尺寸、同字重、同默认边框
- 不允许默认高亮 AI 推荐
- 选择后只高亮“我的选择”

#### `PowerMeter`

用途：Robot Root 与 Power Root 的 Power Battery / 资源状态。

必须支持：

`Available / Frozen / Consumed / Released / Recovering / Power Cap`

Battery Variant：
- Robot Root 首屏使用；
- 显示 `available / cap` 与百分比；
- 可以显示恢复状态 / next restore，但实际值必须来自服务端；
- 不用游戏体力闪光、金币、挖矿、收益算力视觉。

Power Cap 可以随着 Robot Level 成长，但具体映射只读 Active Rule / Parameter。

**明确禁止：** Robot 用 `UpgradeProgress` 百分比条表达长期成长。升级关系使用 Current Level / Next Level / Capability Diff / Power Cap Diff。

#### `PowerImpactSummary`

用途：OTC Sell 前后解释 Power 变化。

必须能表达：

`freeze → filled portion consumes → unfilled stays frozen → cancel/expiry releases`

Buy 不展示 Sell Power 消耗。

#### `OTCQuickAction`

用途：在 Home / APT / Power / OTC Root 提供高频 `挂买 / 挂卖` 快捷入口。

- 两个动作首屏可见
- 不新增第五个 Bottom Tab
- 不采用 Buy Green / Sell Red 的交易所配色

#### `OTCPartialProgress`

用途：订单 Partial Fill 的进度与资源影响。

同时显示：

`filled quantity / remaining quantity / consumed Power / still frozen Power / released Power（如有）`

禁止只用百分比不解释资产与 Power 变化。


---


## 7.1 HIFI 设计稿输出与密度规则

### 每页一图

- Home / Robot / Prediction / Me 必须分别输出独立页面设计稿。
- 其他页面同样一 Page ID 一张主设计稿；状态 Variant 可单独补图。
- 禁止四页拼在一张展示板后当作页面交付。

### 不加设备边框

- 设计审核图直接使用 390px 基准画板或实际页面长度。
- 不套 iPhone/Android 设备 Mockup。
- 页面很长时允许纵向延长画板，不为了塞进“手机截图”而压缩内容。

### 欧美 App 信息密度

- 增加真实运营数据量时，不等于增加更多大型 Card。
- 同类重复数据优先使用轻量 Row/List、Section Group、Timeline、Segment。
- 卡片之间留白必须有节奏，避免中文互联网式模块堆叠。
- 每屏要有明确视觉焦点；Secondary 信息降低字重和对比度。
- Prediction 可以有 12–24 场以上真实运营 Fixture，但单卡必须紧凑可扫读。


## 8. 数据、图表与数字展示

### 数字四件套

任何业务数字必须能回答：

```text
这是什么？
单位是什么？
现在是什么状态？
数据是哪一刻 / 哪个快照？
```

### 图表

- 主线：品牌蓝。
- 对比线：灰蓝。
- 关键当前节点可用金色。
- 图表至少显示时间范围、数据口径、更新时间。
- P0 不使用红绿 K 线视觉作为背景装饰。

### 估算 / 历史 / 待确认

必须显式 Badge：

```text
实时
延迟
估算
历史
待确认
```

---

## 9. 状态页面统一设计

每个 P0 页面都必须有：

```text
Default
Loading
Empty
Error
Restricted
```

有写操作的页面再增加：

```text
Invalid
Confirm
Submitting
Processing / Review
Success
Failed
Unknown Result
```

### Error

必须包含：

```text
发生了什么
本次有没有产生业务影响
用户现在能做什么
```

### Restricted

必须包含：

```text
当前不能做什么
原因分类
恢复/补件/申诉/等待中的下一步
```

### Unknown Result

绝不能当普通 Error 让用户再点一次；必须显示“正在确认结果”，并使用原请求标识查询。

---

## 10. 动效

- 页面 / Drawer：`180–240ms`。
- Bottom Sheet：`220–280ms`。
- Button state：`120–160ms`。
- Skeleton 使用低对比 shimmer；不做高亮扫光。
- 成功动画最多 `600ms`，不能一直循环。
- 支持 `prefers-reduced-motion`。

禁止：数字连续翻滚、老虎机效果、金币雨、大面积粒子、闪烁 K 线、持续发光 CTA。

---

## 11. 无障碍与可用性

必须满足：

- 普通文字对比度目标 `>= 4.5:1`。
- 大字 / 大图标目标 `>= 3:1`。
- 点击目标最小 `44×44px`。
- 键盘 Focus 清晰；Admin 所有主要操作可键盘访问。
- 状态不能只靠颜色。
- 表单 Label 不用 placeholder 代替。
- 错误提示与具体字段绑定。
- 图标按钮必须有可读 label / tooltip。
- 支持 200% 浏览器缩放时核心功能仍可使用。
- 减少动效模式下不依赖动画传达状态。

---

## 12. 模块视觉区分

| 模块 | 视觉角色 | 允许强调色 | 不要做成 |
|---|---|---|---|
| Home | 状态与任务分流 | 蓝 | 数据大盘 |
| Robot | 能力、等级、运行状态、动态 Reward | 蓝 + 少量金 | 固定收益/ROI 仪表盘 |
| Reward | 今日/历史动态 Reward、状态、资格、领取 | 蓝 + 少量金 | 固定收益/提现赚钱页 |
| Prediction | 会员竞猜、体育数据、规则、订单 | 蓝 / Cyan | Sportsbook / 博彩盘口 |
| APT | 数量账与记录 | 深蓝 / 蓝 | 币价资产炒作页 |
| OTC | 受控订单 | 蓝 / 中性色 | 高频交易所 |
| Security / KYC | 信任与安全 | 蓝 / 状态色 | 审讯/警告页 |
| Support | 问题解决 | 中性色 / 蓝 | 复杂 CRM |
| Admin | 对象、状态、审批、账本 | 深蓝 + 中性色 | 黑色监控大屏 |

---

## 13. 高保真原型生成硬规则

1. `03` / `04` 中每个 Page ID 都必须有独立 Route / Frame。
2. 每页视觉必须服从本文件 Token，不允许 Agent 自己重新定一套颜色、圆角、字体。
3. 同一种组件只设计一次，其他页面复用 Variant。
4. 高风险流程必须完整画出 Confirm → Submitting → Processing/Review → Result。
5. 旧大 Figma 不作为布局、组件、颜色、视觉参考。
6. Logo 只按第 1 节规则使用。
7. 原型继续由 Mock / Fixture 驱动，但正式用户页面**禁止显式显示** `Demo / Mock / Sandbox / 模拟数据 / 模拟环境 / 演示环境`。这些标签只允许出现在 Prototype Developer Panel、QA Scenario Switcher 或 Fixture Metadata。Fixture 仍不得被解释为正式参数。
8. 原型完成后再进入真实前端开发；不允许开发人员自行补一个未设计的 P0 页面。

9. Root 页面如使用 Fixed Action Bar，必须遵守：`BottomNav 64px + safe-area`、`FloatingAction 64–72px`、间隔 `8–12px`，正文预留足够 bottom padding。
10. 中文用户可见 `Prediction` 优先本地化为「竞猜」；页面不得出现下注/投注/博彩/赔率/盘口/押注等博彩化词汇。
11. Robot 可清楚显示今日/昨日/7日/可领取/已领取 Reward，但不得出现 APR/APY/固定收益/保本/回本。
12. OTC Root、APT、Power 可以提供挂买/挂卖快捷入口，但 Bottom Navigation 仍严格保持 4 个。


---

## 14. 自审检查表

- [ ] Logo 每个场景使用了正确版本。
- [ ] 没有把 Logo 的 3D 质感复制到全站 UI。
- [ ] 蓝 / 金使用比例克制。
- [ ] Mobile 所有点击区域 >= 44px。
- [ ] Admin 表格/筛选/Drawer 密度统一。
- [ ] 所有状态同时使用文字与颜色。
- [ ] 所有业务数字有单位 / 状态 / 时间或快照。
- [ ] Error / Restricted / Unknown Result 都说清楚下一步。
- [ ] H5 没有另外发明一套 IA。
- [ ] 无旧 Figma 视觉残留。
- [ ] Home 已使用 NoticeTicker + DailyClaimCard；UpgradeLeaderboard 位于 AI Data Summary 之后、BottomNav 之前，且不是财富榜。
- [ ] Robot Root 有 Power Battery + RobotFloatingActionBar，且不会被 BottomNav/键盘遮挡；不存在 Upgrade Progress Bar。
- [ ] Prediction 中文用户文案已使用「竞猜」，Home/Draw/Away 同级，无默认推荐方向。
- [ ] Power 可解释 Available/Frozen/Consumed/Released 与 Partial Fill 流程。
- [ ] OTC 挂买/挂卖首屏可见，且无 K-Line/交易终端视觉。
- [ ] 正式用户页面未出现 Demo/Mock/Sandbox/模拟数据等原型标签。

---

## 15. I18N / L10N 全局基线

### 15.1 人话说明

多语言不改变业务。换语言之后，Page ID、Route、对象、状态、权限、参数、金额和请求上下文都还是同一个；只改变用户看到的文字与本地格式。

### 15.2 支持语言与 fallback

```text
zh-CN
en-US
ja-JP
ko-KR
th-TH
de-DE
fr-FR
```

优先级：用户显式选择 > 账号语言 > 设备/浏览器语言 > `en-US` fallback。

语言切换必须保留：`Route / Page ID / object_id / Tab / Filter / Sort / Page / Scroll / Form draft / request context`。高风险 Confirm/Consent 使用新语言重新渲染，但不得改变原 request 或业务参数。

### 15.3 术语锁定

**固定产品/协议词，不翻译：** `Gainode / APT / APT-I / APT-C / Robot / OTC / Power / 1X2 / MFA / KYC / OTP / AI`。

**用户界面正常本地化：** `Home / Prediction / Me / Rewards / Claim / Order / Market Detail / Settings / Support / Under Review / Restricted / Refund / Correction / Parameter Center / Risk Case`。

中文用户端 `Prediction` 的正式显示词统一为「竞猜」；其他 locale 按当地真实 App 用语本地化。内部 Page ID / API / Domain Name 仍保持 canonical `Prediction`。

内部 module/code 可以继续使用 canonical 英文名称，但导航、页面标题和普通用户文案必须读取 locale 字符串。


### 15.4 ONE_LOCALE_ONE_LANGUAGE

除“术语锁定”中的 canonical 产品/协议词、用户原始内容和官方实体名 fallback 外：

- 一个 locale 的用户界面只出现该 locale 的自然语言；
- 禁止使用英文整句临时 fallback 填充 ja/ko/th/de/fr；
- 禁止“Parameter Center · 定義/Candidate”这类半翻译 UI；
- 所有 Page Title、Description、Primary Action、状态、提示、表单帮助、Error/Restricted/Consent/Risk Copy 都必须命中对应 locale key；
- `MISSING_KEY = 0`
- `RAW_ENUM_VISIBLE = 0`
- `CROSS_LANGUAGE_POLLUTION = 0`

本地化目标不是逐字翻译 PRD，而是符合当地 App 的短句习惯。比如中文优先“暂时还不能参与”“正在处理中，请稍后查看结果”，不要使用机器式长句。


### 15.5 字符串资产契约

```text
/i18n/zh-CN.json
/i18n/en-US.json
/i18n/ja-JP.json
/i18n/ko-KR.json
/i18n/th-TH.json
/i18n/de-DE.json
/i18n/fr-FR.json
/i18n/ui-copy-manifest.json
/i18n/terminology-lock.json
/i18n/sensitive-copy-review.json
```

规则：

- 任何用户可见字符串必须先进入 i18n key；禁止 raw enum 直接显示。
- 新增页面或新增文案，原型/CI 必须检查 7 个 locale 的 key 集一致。
- `id / code / version / hash / parameter_key / audit_event_code / request_id / object_id` 不翻译。
- 用户原始姓名、证件号、自己提交的 Support 文本默认保留原文；如提供机器译文必须标注来源。
- 赛事/联赛/球队名称优先官方 locale 名；缺失时 fallback 英文，不能由 UI 临时自由翻译。

### 15.6 Locale 排版与格式化

- 中文：`PingFang SC / HarmonyOS Sans SC / Noto Sans SC`。
- 日文：`Hiragino Sans / Noto Sans JP`；避免强制中文字体字形。
- 韩文：`Apple SD Gothic Neo / Noto Sans KR`。
- 泰文：`Noto Sans Thai / system-ui`；行高比拉丁文本增加约 8–12%，不使用破坏组合字符的手工断字。
- 英/德/法：`Inter / system-ui`；德语按钮与 Badge 按 1.35–1.6× 文案膨胀测试。
- 数字业务值不因语言改变；展示格式使用 `Intl.NumberFormat(locale)`。
- 日期时间使用 `Intl.DateTimeFormat(locale, user_timezone)`；锁定/结算相关时间必须明确时区。
- 百分比、小数、千分位按 locale 展示；ID/版本号保持 ASCII canonical。
- 风险/Consent 文案不得用省略号截断；必须完整可读并记录 language + content_version。

### 15.7 敏感文案审核

KYC、Consent、Prediction Risk、OTC 风险/状态、APT Reference Valuation、Refund/Correction、MFA Security、Policy Restriction 等字符串可以作为内部工程草稿使用，但不能由 AI 自行签发“人工审核 PASS”。

`/i18n/sensitive-copy-review.json` 中列出的 key 必须由 Product/Legal/Compliance Owner 最终签核。签核前可用于内部结构原型与 7 语言排版测试，不能声称是最终生产法律/金融/合规文案。

### 15.8 新增状态 I18N key 覆盖要求

以下状态需要在七语言中补齐 key（如尚不存在），同步更新 `ui-copy-manifest.json` 和 `sensitive-copy-review.json`：

- OTC `expired`：`otc.status.expired`
- Notice 投递：`notice.status.delivery_failed`, `notice.status.read`, `notice.status.unread`, `notice.status.object_unavailable`
- Robot 展示：`robot.status.inactive`, `robot.status.cooling`
- KYC 展示：`kyc.status.not_started`, `kyc.status.review`
- Prediction 映射：`prediction.status.settlement_processing`, `prediction.status.exception`, `prediction.status.voided`
- Approval 展示：`approval.status.changes_requested`, `approval.status.executing`, `approval.status.executed`, `approval.status.failed`
- ParameterRelease 展示：`parameter.status.pending_approval`, `parameter.status.scheduled`, `parameter.status.active`, `parameter.status.paused`, `parameter.status.rolled_back`, `parameter.status.archived`
- OTC 风险披露：`otc.risk_disclosure.body`

所有敏感文案继续标记 `PENDING_HUMAN_REVIEW`，不得伪造人工审核通过。

### 15.9 I18N Gate

```text
LANGUAGE_COUNT = 7
PAGE_ID_COVERAGE = 75/75
I18N_MANIFEST_REFERENCE_MISSING = 0
MISSING_I18N_KEY = 0
RAW_ENUM_VISIBLE = 0
CROSS_LANGUAGE_POLLUTION = 0
V6_STRING_ASSETS = COMPLETE_ENGINEERING_DRAFT

RISK_COPY_REVIEW = HUMAN_OWNER_REQUIRED
KYC_COPY_REVIEW = HUMAN_OWNER_REQUIRED
CONSENT_COPY_REVIEW = HUMAN_OWNER_REQUIRED
FINANCIAL_COPY_REVIEW = HUMAN_OWNER_REQUIRED

READY_FOR_HIFI_STRUCTURE = YES
READY_FOR_CHINESE_HIFI = YES
READY_FOR_INTERNAL_7_LANGUAGE_LAYOUT_TEST = YES
V6_DOCUMENTS_READY_FOR_HIFI = YES
READY_FOR_FINAL_PRODUCTION_LEGAL_COPY = NO
READY_FOR_FINAL_7_LANGUAGE_HIFI = NO
```

只有敏感文案被授权人工 Owner 签核，并且实现侧完成 `RAW_ENUM_VISIBLE=0`、日期/数字 locale、Thai rendering、German expansion 等回归后，才允许把 `READY_FOR_FINAL_7_LANGUAGE_HIFI` 改为 `YES`。

