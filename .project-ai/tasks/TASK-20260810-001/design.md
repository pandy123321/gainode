# TASK-20260810-001 · 设计说明

## 设计范围

当前任务为 Mobile/H5 端 HIFI 原型视觉精修，属于 Stage 1: Prototype Freeze。

后续阶段（不在本任务范围）：
- Stage 2: Contract Freeze（OpenAPI 契约）
- Stage 3: Backend Core（后端核心实现）
- Stage 4: Frontend Integration（前后端联调）
- Stage 5: Sandbox E2E（沙盒端到端测试）

## 页面层级

```text
Root 页面（视觉锚点，优先定稿）：
  M-HOME-001   首页
  M-ROBOT-001  Robot
  M-PREDICT-001   竞猜
  M-ME-001     我的

Sub 页面（继承 Root 视觉体系）：
  Auth/KYC 家族
  APT/Power 家族
  OTC 家族
  Security/Support 家族
  Notice 家族
  Robot Activity 家族
  Prediction My Orders/Order Detail 家族
  Settings 家族
  AI Signal/P1
```

## 技术约束

- 基准画布：390×844，兼容 375×812、430×932
- 字体栈：Inter, PingFang SC, HarmonyOS Sans SC, Noto Sans SC, system-ui, sans-serif
- CSS 变量已定义（08 §2-§7），禁止 Agent 自定义颜色/圆角/字体
- Bottom Nav：4 个固定 Tab，高度 64px + safe-area
- RobotFloatingActionBar：64–72px，与 BottomNav 间隔 8–12px

## 核心组件

以下为 V6 正式组件契约，页面只组合和配置 Variant，不重新发明样式：

- RobotFloatingActionBar
- DailyClaimCard
- RobotRuntimeTimeline
- RewardTrendCard
- NoticeTicker
- UpgradeLeaderboard
- CommunityActivityCard
- PredictionParticipationBar
- PredictionThreeWayCard
- PowerMeter（Battery Variant）
- PowerImpactSummary
- OTCQuickAction
- OTCPartialProgress

## 设计原则

- 降低 Generic Card Feel：优先轻列表、时间分组、Segment、数据行、折叠与留白
- 每页一个明确视觉焦点
- 金色占比 ≤5%（仅用于 Level/Reward 资格/升级关键变化/少量品牌装饰）
- 不使用红绿 K 线/赌场风/高收益大屏/币价拉盘感

## 信息来源
- `Gainode_Development_Ready_V6.1_Latest/03_MOBILE_H5_HIFI_PROTOTYPE_SPEC_V2.4.md`
- `Gainode_Development_Ready_V6.1_Latest/08_VISUAL_DESIGN_SYSTEM_V2.4.md`
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`
