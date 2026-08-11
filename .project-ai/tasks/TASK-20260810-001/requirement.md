# TASK-20260810-001 · H5/Mobile HIFI 原型视觉精修

## 需求摘要

基于 V6.1 基线文档，对 Mobile/H5 端进行全量 HIFI 高保真原型视觉精修。当前阶段为 Stage 1: Prototype Freeze。

## 需求详情

### 背景

V6.1 项目基线已经锁定。本地 Agent 接手后，不再回到旧文档、旧 Figma、旧 Flutter 去重新判断产品。

当前已知：
- 全局视觉基线：`08_VISUAL_DESIGN_SYSTEM_V2.4.md`
- Mobile 页面规范：`03_MOBILE_H5_HIFI_PROTOTYPE_SPEC_V2.4.md`
- Admin 页面规范：`04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md`
- 视觉关键词：Western / Premium / Sports-Tech / Operational
- 设计交付：一页一图、不加手机边框、不做四页拼图

### 核心任务

1. 处理仍有 Generic Card Feel 的页面家族：
   - Auth/KYC
   - APT/Power
   - OTC
   - Security/Support
   - Notice
   - Robot Activity
   - Prediction My Orders/Order Detail
   - Settings
   - AI Signal/P1

2. 完成 44 页 375/390/430 全量视觉回归

3. HIFI 视觉锚点（已确认的 Root 视觉）：
   - Home / Robot / Prediction / Me 四个 Root 作为子页面的视觉锚点
   - Robot 保留 Dark Hero，Hero 以下回 Light Shell
   - Robot 不出现 Upgrade Progress Bar，用 Power Battery
   - Prediction Root 多场竞猜同时运营（至少 12–24 场）
   - Home 成长榜移到底部

### 强制要求
- 不使用旧大 Figma 作为视觉、页面或交互基线
- 正式 UI 禁止 Demo/Mock/Sandbox/Page ID
- 不重新策划业务、不新增第 09 份产品文档
- 视觉默认优先 English UI 作为审美校验语言

### 信息来源
- `Gainode_Development_Ready_V6.1_Latest/README.md`
