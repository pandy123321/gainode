# Gainode Mobile H5 Design System V1.1

> 文档类型：移动端 H5 / App 视觉设计系统  
> 适用范围：Gainode C 端 Root 页面与二级页面  
> 基于对象：当前已确认的 Gainode Logo、Home、Robot、Prediction、Me、`我的竞猜` 卡片、`今日任务` 卡片  
> 状态：`CURRENT_VISUAL_BASELINE_CANDIDATE`  
> 版本：V1.1  
> 目标：统一品牌、组件、信息层级、状态表达与业务视觉边界，作为后续高保真页面生成、原型实现和前端开发的共同参考。

---

# 0. 本次自审结论

上一版设计系统方向基本正确，但存在若干需要修正的问题。

## 0.1 已确认正确的部分

以下方向继续保留：

- Gainode 官方 Logo 为唯一品牌基准；
- Root 导航固定为 `首页 / Robot / 竞猜 / 我的`；
- Light App Shell + White Cards 为默认页面骨架；
- Gainode Blue / Deep Navy 为主要视觉识别；
- Gold 仅用于 Reward / Level / Milestone 等少量强调；
- Robot 允许使用 `Dark Hero + Light Body`；
- Prediction 必须保持“会员与会员之间的竞猜社区”定位；
- Home 负责运营引导、每日回访与关键状态；
- Me 负责账户、APT、Power、OTC、安全与个人操作；
- `我的竞猜` 与 `今日任务` 已形成首页运营快捷卡的核心组件体系；
- 禁止将 Gainode 视觉导向交易所、博彩、套利机器人或高收益平台。

## 0.2 上一版需要纠正的问题

### 1. Token 过早“锁死”

上一版直接给出了部分 Hex、尺寸、阴影值，并声明 `LOCKED`，但当前对话并未提供完整的 `08_VISUAL_DESIGN_SYSTEM` 原始 Token 作为逐项证据。

因此本版调整为：

- 已由当前高保真页面稳定体现的规则：定义为 `BASELINE`；
- 需要从官方设计文件 / Figma / 08 文档进一步确认的数值：定义为 `REFERENCE_TOKEN`；
- 不再把推导值错误声明为不可修改的正式 Token。

### 2. Typography 不完整

上一版只有 H1 / H2 / Body / Number，不足以支持完整产品。

本版增加：

- Display
- Page Title
- Section Title
- Card Title
- Body
- Supporting
- Label
- Caption
- Metric

并补充 Line Height 与多语言约束。

### 3. 缺少 Semantic Token

上一版主要使用原始颜色名。

本版统一区分：

- Primitive Token
- Semantic Token
- Component Token

前端实现优先使用 Semantic Token，禁止业务组件直接散落硬编码色值。

### 4. 缺少完整组件状态

上一版只有组件名称，没有定义：

- Default
- Hover / Pressed
- Selected
- Disabled
- Loading
- Empty
- Error
- Success
- Claimable
- Locked

本版补齐。

### 5. 缺少响应式与 H5 规则

本版正式加入：

- 375 × 812
- 390 × 844
- 430 × 932
- Safe Area
- Sticky Bottom Navigation
- Robot Floating Action Bar
- 长文案和多语言适配

### 6. 缺少内容设计规则

Gainode 是面向用户的产品，不应出现 PRD 腔、AI 营销腔、技术后台腔。

本版新增 Content Design。

### 7. Member Level / XP 不能由视觉反向定义业务

设计可以展示现有业务基线确认的等级、成长状态，但：

- 不允许设计 Agent 自行创造 XP；
- 不允许因为卡片好看而发明奖励数量；
- 不允许自行定义“任务赚 Power”。

此项升级为全局业务视觉红线。

---

# 1. Design System Principles

Gainode 的设计系统遵循以下 8 项原则。

## 1.1 Brand First

品牌识别优先于页面创意。

必须保持：

- 官方 Gainode Logo；
- Gainode Blue / Navy 主体系；
- Sports-Tech + Member Platform 的品牌认知。

禁止：

- 自创新 Logo；
- 替换成通用 G 图标；
- 重新定义品牌主色；
- 为单页视觉效果破坏整体品牌一致性。

---

## 1.2 Product Before Decoration

任何视觉元素都必须服务于：

- 用户理解；
- 用户行动；
- 用户状态；
- 业务结果。

禁止无业务作用的大面积装饰、3D 背景、炫光和营销海报构图。

---

## 1.3 Operational, Not Technical

Gainode 面向客户，而不是内部工程师。

页面优先表达：

- 今天发生了什么；
- 用户现在能做什么；
- 哪些状态需要关注；
- 哪些内容值得回来查看。

不要把页面设计成技术 Dashboard。

---

## 1.4 Participation, Not Betting

Prediction 的产品认知必须是：

> 会员与会员之间的竞猜参与社区。

禁止视觉或文案向以下方向漂移：

- Odds
- Stake
- Bet
- Bookmaker
- Winnings
- Guaranteed Win
- 投注
- 下注
- 庄家盘口
- 赔率

---

## 1.5 Reward, Not Yield

Robot Reward 可以展示：

- 当前可领取；
- 已领取；
- 周期；
- 更新时间；
- Rule Snapshot / Version。

禁止展示：

- APR
- APY
- Fixed Return
- Guaranteed Reward
- 高收益
- 日化收益率
- 固定回报

---

## 1.6 State First

状态必须优先于装饰。

例如：

Robot：

- 运行中
- 已停止
- 处理中
- 异常
- 可领取

Prediction：

- 可参与
- 即将截止
- 已截止
- 等待结果
- 已完成

Task：

- 已完成
- 当前推荐
- 待完成

---

## 1.7 Consistency Over Page Creativity

同类组件必须统一：

- Radius
- Padding
- Typography
- Icon Container
- Chevron
- Badge
- CTA
- Shadow
- Divider

禁止每个页面重新设计一套组件。

---

## 1.8 Responsive by Default

所有移动端 Root 页面和组件必须验证：

- 375 × 812
- 390 × 844
- 430 × 932

并确保：

- 底部导航不掉位；
- Floating Bar 不遮挡；
- 德语长文案不撑爆按钮；
- 泰语不截字；
- 日文 / 韩文不产生异常换行。

---

# 2. Brand Foundation

## 2.1 Brand Name

```text
Gainode
```

## 2.2 Brand Personality

- Premium
- Modern
- Trustworthy
- Sports-Tech
- Data-aware
- Operational
- Community-driven
- Controlled Energy

## 2.3 Forbidden Brand Directions

禁止设计成：

- Crypto Exchange
- Trading Terminal
- Sportsbook
- Casino
- Forex Bot
- Mining App
- Yield App
- Dark Gold Wealth App
- AI Marketing Poster

---

# 3. Logo System

## 3.1 Official Logo

唯一官方品牌 Logo：

> 蓝色海豚 / G 形结构 + 足球 + 金色增长曲线 + GAINODE 字标。

禁止：

- 改变 Logo 图形结构；
- 自创简化 G；
- 更换海豚形态；
- 更改足球位置；
- 更改金色增长线；
- AI 重新绘制成其它 Logo；
- 用 Robot 图标代替品牌 Logo。

## 3.2 Header Logo

推荐视觉规则：

```text
位置：左上
垂直居中
不加卡片背景
不加外发光
不加额外阴影
```

高度采用响应式 Token：

```text
logo-header-height-sm
logo-header-height-md
```

具体 Pixel 值以后以官方 Figma / 08 Visual Design System 为唯一准值。

## 3.3 Clear Space

Logo 四周至少保留一个 Logo 图标内部“足球直径”级别的视觉安全区。

---

# 4. Color System

> 以下数值作为当前视觉方向的 `REFERENCE_TOKEN`。最终生产值应与官方视觉资产 / Figma Token 对齐。

## 4.1 Primitive Palette

```css
--gainode-blue-500: #1468FF;
--gainode-blue-600: #0D57E8;
--gainode-navy-900: #071A3D;
--gainode-cyan-400: #00C8FF;

--neutral-0: #FFFFFF;
--neutral-25: #F8FAFC;
--neutral-50: #F5F7FA;
--neutral-100: #EEF2F7;
--neutral-200: #E4EAF2;
--neutral-500: #667085;
--neutral-700: #344054;
--neutral-900: #101828;

--success-500: #12B76A;
--warning-500: #F79009;
--danger-500: #F04438;

--gold-400: #F6C453;
--gold-500: #F6B73C;
```

## 4.2 Semantic Colors

前端禁止大量直接引用 Primitive Token。

优先使用：

```css
--color-bg-page;
--color-bg-card;
--color-bg-subtle;

--color-text-primary;
--color-text-secondary;
--color-text-muted;
--color-text-inverse;

--color-primary;
--color-primary-hover;
--color-primary-subtle;

--color-border-default;
--color-divider;

--color-state-success;
--color-state-warning;
--color-state-error;
--color-state-info;

--color-reward;
--color-level;
```

## 4.3 Gold Usage

Gold 仅用于：

- Reward Claimable；
- Level / Milestone；
- 少量成长里程碑；
- 高优先级奖励 CTA。

禁止用于：

- 大面积背景；
- 普通按钮；
- Prediction 选择方向；
- OTC 操作；
- 页面整体主题。

建议整体视觉面积保持极低占比。

---

# 5. Typography System

## 5.1 Font Stack

### Latin

```css
Inter,
SF Pro Text,
-apple-system,
BlinkMacSystemFont,
"Segoe UI",
sans-serif
```

### Simplified Chinese

```css
"PingFang SC",
"Noto Sans CJK SC",
"Microsoft YaHei",
sans-serif
```

### Japanese

```css
"Noto Sans JP",
"Hiragino Sans",
sans-serif
```

### Korean

```css
"Noto Sans KR",
"Apple SD Gothic Neo",
sans-serif
```

### Thai

```css
"Noto Sans Thai",
"Leelawadee UI",
sans-serif
```

## 5.2 Type Scale

| Token | 用途 | 推荐大小 | Weight |
|---|---|---:|---:|
| `type-display` | Hero 核心数字 | 28–32 | 700 |
| `type-page-title` | 页面标题 | 24 | 700 |
| `type-section-title` | 模块标题 | 18 | 700 |
| `type-card-title` | 卡片标题 | 16 | 600–700 |
| `type-body` | 主正文 | 14 | 400–500 |
| `type-supporting` | 副说明 | 13 | 400 |
| `type-label` | Badge / Label | 12 | 500–600 |
| `type-caption` | Meta / 时间 | 11–12 | 400 |
| `type-metric` | 核心数字 | 22–28 | 700 |

## 5.3 Line Height

原则：

```text
标题：1.20–1.30
正文：1.45–1.60
Caption：1.35–1.45
```

中文禁止过小行高造成拥挤。

---

# 6. Spacing System

基础节奏：

```text
4 / 8 / 12 / 16 / 20 / 24 / 32
```

建议 Token：

```css
--space-1: 4px;
--space-2: 8px;
--space-3: 12px;
--space-4: 16px;
--space-5: 20px;
--space-6: 24px;
--space-8: 32px;
```

## 6.1 Root Page

```text
页面左右 Safe Padding：
16px 为基础参考

Section Gap：
20–24px

Card Gap：
12–16px
```

## 6.2 Card Internal Padding

普通卡：

```text
16px
```

重点 Hero / Complex Card：

```text
16–20px
```

---

# 7. Radius System

推荐：

```css
--radius-sm: 8px;
--radius-md: 12px;
--radius-lg: 16px;
--radius-xl: 20px;
--radius-pill: 999px;
```

### 使用建议

| 场景 | Radius |
|---|---|
| Badge | pill |
| Small Control | 8–12 |
| Button | 12 |
| Standard Card | 16 |
| Hero | 16–20 |
| Bottom Floating Action Bar | 16–20 |

`我的竞猜` 与 `今日任务` 必须使用同一个 Standard Operational Card Radius Token。

---

# 8. Border & Elevation

## 8.1 Border

默认：

```css
1px solid var(--color-border-default)
```

## 8.2 Divider

只用于：

- List Row；
- Metric 分组；
- Timeline；
- Table-like 状态。

不要过度使用框线。

## 8.3 Shadow

整体遵循：

> Shadow 只是层级辅助，不是视觉主体。

推荐：

```css
--shadow-card:
0 4px 16px rgba(20, 104, 255, 0.06);

--shadow-floating:
0 8px 24px rgba(7, 26, 61, 0.12);
```

---

# 9. Icon System

## 9.1 Style

统一：

- 线性；
- 半填充；
- Rounded；
- 2px 左右视觉 Stroke；
- 不同 Icon 来自同一图标语言。

禁止：

- 同页混合写实 3D icon + 极简 outline；
- emoji 作为正式 UI icon；
- 博彩筹码；
- 钱袋；
- K 线图作为 Prediction icon。

## 9.2 Icon Container

运营卡标准：

```text
48 × 48
Radius：12–14
Background：Primary Subtle
Icon：Gainode Blue
```

`我的竞猜` 与 `今日任务` 必须完全共用此组件。

---

# 10. App Shell

## 10.1 Root Shell

默认：

```text
Light App Shell = YES
```

背景：

```text
Very Light Cool Gray / White
```

卡片：

```text
White
```

主文字：

```text
Deep Navy
```

## 10.2 Robot Exception

Robot Root：

```text
Light Shell
+
Dark Robot Hero
+
Light Body
```

禁止 Robot 全页纯深色。

---

# 11. Header

组件：

```text
GainodeHeader
```

Root 页面统一：

```text
[Gainode Logo]              [Language] [Notification]
```

## 11.1 Language

允许：

```text
🌐 中文
```

或语言 Code / Name。

Header 中语言入口位置必须在 Notification 前。

## 11.2 Notification

Unread：

```text
Bell + Small Red Dot
```

Dot 只表达未读，不做大面积红色。

---

# 12. Bottom Navigation

组件：

```text
GainodeBottomNav
```

固定 4 Tab：

```text
首页
Robot
竞猜
我的
```

禁止增加第 5 个 Root Tab。

## 12.1 Default

```text
White / Very Light Surface
```

## 12.2 Active

```text
Gainode Blue icon
Gainode Blue label
```

## 12.3 Inactive

```text
Neutral Gray / Navy Gray
```

---

# 13. Button System

## 13.1 Primary Button

用途：

- 领取奖励；
- 去完成；
- 参与；
- 确认类主动作。

推荐：

```text
Height：44–48
Radius：12
```

默认：

```text
Gainode Blue
```

Reward Claimable 可使用小面积 Gold。

## 13.2 Secondary Button

用途：

- 查看 Robot；
- 查看详情；
- 取消；
- 次级动作。

样式：

```text
White / Transparent
Blue Border / Blue Text
```

## 13.3 Text Action

例如：

```text
查看全部 ›
查看详情 ›
```

不能和 Primary CTA 抢层级。

---

# 14. Status Badge

组件：

```text
StatusBadge
```

## 14.1 Success

```text
已完成
运行正常
可参与
```

使用绿色。

## 14.2 Warning

```text
即将截止
需要关注
```

使用 Orange。

## 14.3 Info

```text
处理中
等待结果
```

使用 Blue / Cyan。

## 14.4 Neutral

```text
已截止
未开始
不可用
```

使用 Gray。

---

# 15. Home Root Design Pattern

Home 的核心目标：

> 让用户 3 秒内知道今天最值得关注和处理的事情。

Home 负责：

1. Robot 状态；
2. Reward Claimable；
3. 重要通知；
4. 我的竞猜；
5. 今日任务；
6. 热门竞猜；
7. Data Insight；
8. Growth / Activity；
9. Bottom Navigation。

Home 不负责：

- 重复 Me 页个人资产操作；
- 完整 OTC；
- 完整 Prediction Detail；
- Robot Detail。

---

# 16. Home Hero

组件：

```text
HomeRobotHero
```

建议结构：

```text
Robot 正在运行

今日可领取
12.46 APT

[领取奖励]

查看 Robot >
```

右侧：

```text
Gainode Robot Mascot
```

## 16.1 Hero Visual

```text
Deep Navy → Gainode Blue
```

Robot 可使用高质量 3D / Semi-3D Mascot，但只用于 Hero / 品牌重点区域。

普通 UI Icon 仍使用统一线性组件。

---

# 17. Notice Ticker

组件：

```text
NoticeTicker
```

结构：

```text
[Icon] 单行通知文案                       >
```

禁止：

- 大卡片；
- 多段公告；
- 营销 Banner。

---

# 18. Operational Quick Card System

首页运营快捷卡使用统一组件：

```text
OperationalQuickCard
```

当前正式变体：

```text
MyPredictionCard
TodayTaskCard
```

两张卡必须统一：

- Height；
- Radius；
- Shadow；
- Internal Padding；
- Title Size；
- Icon Container；
- Chevron；
- Border；
- Background treatment。

---

# 19. MyPredictionCard

目标：

> 用户一眼知道“我之前参加的竞猜现在怎么样了？”

## 19.1 Default State

```text
我的竞猜

2 场进行中 · 1 场等待结果

英超联赛
曼城 vs 阿森纳

我的选择：主胜
今晚 22:00

即将截止

进行中 2
等待结果 1
已完成 8
```

## 19.2 Empty State

```text
我的竞猜

还没有参与中的竞猜
看看今天有哪些热门场次

[去看看]
```

## 19.3 Closing Soon

```text
我的竞猜

1 场即将截止
还有 28 分钟

[查看]
```

## 19.4 Result Ready

```text
我的竞猜

2 场已有结果
看看竞猜结果

[查看结果]
```

## 19.5 Forbidden

禁止：

- 参与金额；
- 赔率；
- 赢多少钱；
- 预计收益；
- 红绿涨跌；
- 大型博彩视觉。

---

# 20. TodayTaskCard

目标：

> 用户一眼知道“今天还有什么值得我处理？”

不是 Game Task Center。

## 20.1 Default

```text
今日任务

今日已完成 2 / 4

[Progress 50%]

✓ 领取今日 Robot 奖励
● 查看等待结果的竞猜
○ 检查即将截止的竞猜

还有 2 项待完成

去完成 >
```

## 20.2 Task Source

任务必须来自实时用户状态。

允许动态生成：

```text
启动 Robot
领取 Robot 奖励
查看等待结果
检查即将截止竞猜
检查账户安全提醒
完成 KYC 后续步骤
```

但仅限已有业务能力。

## 20.3 Forbidden

禁止：

```text
完成任务获得 50 Power
连续签到奖励
金币
积分
收益翻倍
任务赚收益
任务提升胜率
```

除非未来产品基线正式定义。

---

# 21. Prediction System

定位：

```text
Member-to-member Prediction Community
```

中文：

```text
会员与会员之间的竞猜社区
```

## 21.1 Prediction Root

至少支持多场同时存在。

Root 结构：

```text
Title
Tabs
Filter

Featured / Priority Matches
Compact Match List
Data Insight
Community Leaderboard
My Prediction Entry
Bottom Nav
```

---

# 22. Match Card

组件：

```text
MatchCard
```

必须支持：

```text
League
Home Team
Away Team
Match Time
Prediction Closing Time
Member Count
Status
Community Picks
```

## 22.1 1X2

三个方向：

```text
主胜
平局
客胜
```

必须：

- 同权重；
- 同尺寸；
- 默认不选中；
- 不使用赔率格式；
- 不做“AI 推荐”。

## 22.2 Distribution

百分比含义：

```text
Community Picks Distribution
```

不是预测赔率。

视觉推荐：

- Home：Blue；
- Draw：Gray Blue；
- Away：Cyan Blue。

禁止用高风险红色代表 Away。

---

# 23. Prediction Status

支持：

```text
可参与
即将截止
已截止
等待结果
已完成
```

英文版本：

```text
Open
Closing Soon
Locked
Waiting for Result
Completed
```

---

# 24. Prediction Data Insight

允许：

```text
球队状态
阵容信息
伤病情况
近期表现
数据质量
```

禁止：

```text
AI 推荐主胜
Strong Pick
Guaranteed Prediction
```

---

# 25. Robot System

Robot Root 核心目标：

> 用户明确知道 Robot 是否运行、当前处理到哪一步、Power 状态和 Reward 状态。

---

# 26. Robot Hero

组件：

```text
RobotHero
```

结构：

```text
Gainode Robot

等级
状态

Robot 正在运行，并处理今日数据

Power
780 / 1000
78%
```

---

# 27. Power Component

组件：

```text
PowerBattery
```

展示：

```text
Power

可用：780
容量：1,000
78%
```

说明原则：

```text
Power 会随当前规则恢复或变化。
容量可随已定义的 Robot 规则变化。
```

禁止 Design System 自行定义：

- 具体消耗公式；
- 恢复速度；
- Power 产出方式；
- 任务赚 Power。

具体业务规则必须由 Economic Model / Parameter Dictionary 决定。

---

# 28. Robot Running Process

组件：

```text
RobotRunningProcess
```

当前标准阶段：

```text
数据同步
→
数据分析
→
运行处理中
→
本轮完成
```

状态必须一致。

例如当前为 Processing：

```text
数据同步 ✓
数据分析 ✓
运行处理中 ●
本轮完成 ○
```

Activity Timeline 也必须与此一致。

---

# 29. Robot Reward

组件：

```text
RobotRewardCard
```

结构：

```text
今日奖励

12.46 APT

可领取

[领取奖励]

下一轮奖励周期
10:24:36

更新时间
当前规则 / 版本
```

Reward Unit 只能使用当前经济文档正式定义的合法单位。

---

# 30. Robot Floating Action Bar

组件：

```text
RobotFloatingActionBar
```

固定：

```text
Bottom Navigation 上方
```

## Claimable

```text
运行中
一切正常

[领取奖励]
```

## Running / No Reward

```text
运行中

[管理运行]
```

## Stopped

```text
未运行

[启动 Robot]
```

## Error

```text
需要处理

[查看问题]
```

主 CTA 必须根据当前真实状态变化。

---

# 31. Me Root

Me 的核心目标：

> 账户、APT、Power、OTC、安全和个人状态集中管理。

---

# 32. Member vs Robot Level

必须分离：

```text
Member Level
Robot Level
```

禁止：

- 合并为一个 Level；
- 让 Robot Level 被误认为用户会员级别。

如果 Member Level / XP 尚未由产品基线正式确认，设计不得反向创造完整 XP 经济体系。

---

# 33. APT Display

APT 优先显示：

```text
Quantity
```

若展示 Reference Value：

```text
APT Balance
1,250.75 APT

参考价值
≈ $1,875.22

更新时间
09:35
```

必须显式标记：

```text
参考
估算
Snapshot
```

禁止视觉暗示：

```text
APT = 现金余额
```

---

# 34. OTC

Me Root 首屏必须保留：

```text
挂买
发布买入需求

挂卖
发布卖出需求
```

Home 不重复该操作入口。

---

# 35. Quick Access

标准功能入口：

```text
APT
APT 明细
Power
OTC 订单
安全中心
KYC
帮助与工单
设置
```

禁止用一个过大的：

```text
资产与参与
```

把所有功能再次隐藏。

---

# 36. Data Insight Card

组件：

```text
DataInsightCard
```

首页摘要：

```text
数据来源
今日信号
最近更新
数据状态
```

Prediction：

```text
球队状态
阵容
伤病
数据质量
```

Robot：

```text
运行数据
历史周期
Power 使用
```

禁止把 Data Insight 做成：

```text
AI Recommendation
```

---

# 37. Leaderboard System

允许排行榜：

```text
成长榜
升级榜
活跃榜
竞猜准确率榜
参与榜
连续参与榜
```

禁止：

```text
财富榜
资产榜
收益榜
最高下注榜
最大赢家榜
```

---

# 38. Empty State

所有主要组件必须定义 Empty State。

建议结构：

```text
Icon
Short Title
One Supporting Line
Optional CTA
```

例：

```text
还没有参与中的竞猜

看看今天有哪些热门场次

[去看看]
```

禁止用长 PRD 文案解释空状态。

---

# 39. Loading State

使用：

- Skeleton；
- Progress；
- Inline Loading。

禁止整页长期使用 Spinner。

关键数据加载失败时，不得用 `0` 伪装真实数据。

---

# 40. Error State

必须区分：

```text
网络错误
数据暂不可用
权限受限
账户资格不足
系统维护
```

错误文案要告诉用户：

1. 发生什么；
2. 是否影响操作；
3. 下一步是什么。

---

# 41. Content Design

## 41.1 Tone

Gainode 文案应：

- 清晰；
- 克制；
- 自然；
- 行动导向；
- 状态导向。

## 41.2 禁止 AI 腔

禁止：

```text
AI 驱动未来
赋能你的每一次决策
智能开启财富未来
让胜率更高
抓住每一次机会
```

## 41.3 推荐

```text
Robot 正在运行
今日有奖励可领取
1 场竞猜即将截止
还有 2 项待完成
数据刚刚更新
```

---

# 42. Localization

目标 Locale：

```text
zh-CN
en-US
ja-JP
ko-KR
th-TH
de-DE
fr-FR
```

固定产品词可保留：

```text
Gainode
Robot
APT
APT-I
APT-C
OTC
Power
KYC
MFA
OTP
AI
1X2
```

其它普通 UI 文案必须本地化。

---

# 43. Accessibility

最低要求：

- 正文不使用过浅灰；
- 关键文本与背景满足可读性对比；
- 不依赖颜色作为唯一状态信息；
- Badge 同时使用文字；
- 点击区域建议不小于 44 × 44 CSS px；
- Progress 必须有数字或文字状态；
- Disabled 状态仍保持可辨识。

---

# 44. Responsive Rules

验证：

```text
375 × 812
390 × 844
430 × 932
```

## 44.1 Long Copy

德语按钮不得强制单行溢出。

允许：

- 按钮适度增宽；
- 文案缩短；
- 二行 Label（非 Primary CTA）。

## 44.2 Thai

必须验证字体上 / 下附标不被裁切。

## 44.3 Japanese / Korean

禁止自动插入异常断字。

---

# 45. Motion

动画应克制。

允许：

Robot Running：

- Status Pulse；
- Process Step；
- Battery Recovery；
- Floating Bar 微状态变化。

Prediction：

- Tab Transition；
- Status Update；
- Progress Distribution Transition。

禁止：

- 大面积粒子；
- 连续闪烁；
- 金币飞入；
- 赌博式 Celebration。

---

# 46. Component Library

正式组件目录建议：

```text
Foundations/
  Color
  Typography
  Spacing
  Radius
  Elevation
  Iconography

Navigation/
  GainodeHeader
  GainodeBottomNav

Buttons/
  PrimaryButton
  SecondaryButton
  TextAction

Status/
  StatusBadge
  ProgressBar
  PowerBattery

Home/
  HomeRobotHero
  NoticeTicker
  OperationalQuickCard
  MyPredictionCard
  TodayTaskCard

Prediction/
  MatchCard
  CommunityPicks
  PredictionStatus
  PredictionInsightCard

Robot/
  RobotHero
  RobotRunningProcess
  RobotRewardCard
  RobotActivityTimeline
  RobotFloatingActionBar

Me/
  MemberLevelCard
  RobotLevelCard
  APTSummaryCard
  PowerSummaryCard
  OTCSummaryCard
  QuickAccessGrid

Shared/
  MetricCard
  RankingCard
  ListRow
  EmptyState
  LoadingState
  ErrorState
```

---

# 47. Root Page Responsibility Matrix

| 页面 | 核心职责 | 不应承担 |
|---|---|---|
| Home | 今日状态、运营引导、回访入口 | 完整 OTC / 完整资产管理 |
| Robot | 运行状态、Power、流程、Reward | Crypto Trading / Arbitrage |
| Prediction | 多赛事、社区选择、状态、数据辅助 | 博彩赔率 / 庄家盘口 |
| Me | 账户、APT、Power、OTC、安全 | 首页运营推荐 |

---

# 48. Business Visual Guardrails

## 48.1 Power

设计系统不得自行定义：

- Power 产出；
- 恢复速度；
- 消耗公式；
- 与任务绑定奖励。

## 48.2 Member Level

设计系统不得自行定义：

- XP；
- 升级数量；
- 权益门槛；
- 每日成长奖励。

## 48.3 Reward

必须来自经济文档正式规则。

## 48.4 Prediction

必须是会员竞猜参与，而非投注产品。

---

# 49. AI Image Generation Guardrails

以后使用 AI 生成 Gainode 页面，必须带上以下硬约束：

```text
Use the Gainode official logo.
Do not redesign the logo.

Single mobile H5 page only.
No phone frame.
No marketing poster.
No collage.
No presentation board.
No page-external title.

Use Gainode Light App Shell.
Use white cards.
Use Gainode Blue and Deep Navy.
Use Gold only for limited Reward / Level highlights.

Keep Header and Bottom Navigation consistent.

Do not create:
Crypto Exchange UI
Trading Terminal
Sportsbook UI
Casino UI
Auto-Arbitrage Robot
Yield Dashboard
AI Marketing Poster

Do not invent:
XP
Power rewards
New asset units
APR / APY
Economic parameters

All UI content must match current Gainode business rules.
```

---

# 50. Design QA Checklist

每次提交高保真页面前必须检查：

## Brand

- [ ] 使用官方 Gainode Logo
- [ ] Logo 未重新绘制
- [ ] Brand Color 未漂移

## Shell

- [ ] Header 一致
- [ ] Bottom Navigation 一致
- [ ] Radius 一致
- [ ] Shadow 一致
- [ ] Typography 一致

## Home

- [ ] Robot 状态可见
- [ ] Reward 状态可见
- [ ] Notice 为轻量 Ticker
- [ ] 我的竞猜存在
- [ ] 今日任务存在
- [ ] 热门竞猜不博彩化
- [ ] 成长榜不是财富榜

## Robot

- [ ] 无 Arbitrage
- [ ] 无 Crypto Pair
- [ ] Power 表达未擅自定义经济规则
- [ ] Running Process 与 Timeline 一致
- [ ] Claimable 时 Claim 为主 CTA
- [ ] Floating Action Bar 存在

## Prediction

- [ ] 多场赛事
- [ ] 主胜 / 平局 / 客胜同权重
- [ ] 百分比为 Community Picks
- [ ] 无 Odds
- [ ] 无 Bet
- [ ] Data Insight 无方向推荐

## Me

- [ ] Member / Robot Level 分离
- [ ] APT Reference Value 明确为参考
- [ ] Post Buy / Post Sell 首屏可见
- [ ] Power 无无意义排名
- [ ] Reward 有周期定义

## Localization

- [ ] 中文无普通英文污染
- [ ] 固定产品词使用正确
- [ ] 多语言长文案可容纳

---

# 51. Versioning & Governance

本设计系统建议进入以下版本治理：

```text
V1.1
CURRENT_VISUAL_BASELINE_CANDIDATE
```

在以下资产完成正式核对后，可升级：

```text
OFFICIAL_DESIGN_SYSTEM_BASELINE
```

需要核对：

1. 官方 Logo 原始资产；
2. 最新 08 Visual Design System；
3. Figma / Design Token；
4. 最新 Product Functional Baseline；
5. 最新 Economic Model；
6. 最新 Parameter Dictionary；
7. 最新 Mobile/H5 HIFI Spec。

---

# 52. Current Status

```text
GAINODE_DESIGN_SYSTEM_VERSION = V1.1

BRAND_DIRECTION = BASELINED
LOGO_MAPPING = BASELINED
ROOT_NAVIGATION = BASELINED
LIGHT_APP_SHELL = BASELINED
ROBOT_DARK_HERO = BASELINED
PREDICTION_VISUAL_BOUNDARY = BASELINED
HOME_OPERATIONAL_CARD_SYSTEM = BASELINED

MY_PREDICTION_CARD = BASELINE_CANDIDATE
TODAY_TASK_CARD = BASELINE_CANDIDATE

RAW_COLOR_VALUES = REFERENCE_TOKEN
RAW_PIXEL_VALUES = REFERENCE_TOKEN

ECONOMIC_RULE_CREATION_BY_DESIGN = FORBIDDEN
BUSINESS_RULE_OVERRIDE_BY_DESIGN = FORBIDDEN

READY_FOR_HIFI_PAGE_EXTENSION = YES
READY_FOR_FRONTEND_TOKENIZATION = YES_WITH_OFFICIAL_TOKEN_REVIEW
```

---

# 53. 核心执行原则

后续所有 Gainode 设计工作必须遵守：

```text
Logo 不跑偏
导航不跑偏
业务表达不跑偏

保留已经确认的视觉方向
不重新发明产品
不重新发明经济模型

先使用 Design System
再设计页面

先判断状态
再决定组件

先保证业务正确
再追求视觉创意
```
