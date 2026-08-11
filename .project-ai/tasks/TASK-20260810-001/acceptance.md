# TASK-20260810-001 · 验收标准

## 验收清单

### 视觉合规
- [ ] 全局颜色、Logo、字体、间距、圆角与 08 文档一致
- [ ] Logo 每个场景使用正确版本（08 §1.1）
- [ ] 蓝/金使用比例克制（金色 ≤ 5% 面积）
- [ ] 没有把 Logo 的 3D 质感复制到全站 UI
- [ ] 视觉风格符合 Western/Premium/Sports-Tech/Operational
- [ ] 无旧大 Figma 视觉残留

### 页面覆盖
- [ ] 03 文档中全部 P0 页面已生成
- [ ] Flow A–E 全部可点击（从产品基线中的 5 条主流程）
- [ ] 每个 Page ID 有独立 Route/Frame
- [ ] Home 已使用 NoticeTicker + DailyClaimCard，UpgradeLeaderboard 在底部（非财富榜）
- [ ] Robot Root 有 Power Battery + RobotFloatingActionBar，无 Upgrade Progress Bar
- [ ] Prediction Root 12–24 场以上赛事，非单场 Hero
- [ ] 中文端 Prediction 使用「竞猜」

### 状态覆盖
- [ ] 每页实现 Default/Loading/Empty/Error/Restricted
- [ ] 写操作页额外实现 Submitting/Processing/Success/Failed
- [ ] Unknown Result 状态正确处理（不提示重试）
- [ ] Restricted 和 Error 使用不同文案

### 交互合规
- [ ] 所有按钮消费 allowed_actions / entitlement（无前端自判资格）
- [ ] 资产类数字不使用 JS float 做业务计算
- [ ] 页面刷新后从服务端恢复真实状态（无本地缓存残留）
- [ ] 状态同时使用文字+颜色（不单靠颜色）

### 适配合规
- [ ] 375×812 宽逐页回归（无 CTA 掉位、异常换行）
- [ ] 390×844 基准无问题（本文件指定基准宽度）
- [ ] 430×932 宽逐页回归（无 Bottom Sheet/Floating Bar 冲突）
- [ ] H5 768px 以下完全按 Mobile 规则
- [ ] H5 不出现超宽弹窗/PC 式表格压缩

### 交付合规
- [ ] 每页独立画板/独立图（一页一图）
- [ ] 不加手机设备边框
- [ ] 不做四页拼图
- [ ] 正式 UI 不出现 Demo/Mock/Sandbox/Page ID/Scenario 文案

### 文案合规
- [ ] 7 语言 i18n key 集一致（`MISSING_I18N_KEY = 0`）
- [ ] 无 raw enum 直接显示（`RAW_ENUM_VISIBLE = 0`）
- [ ] 无半翻译 UI（`CROSS_LANGUAGE_POLLUTION = 0`）
- [ ] OTC expired 状态有对应 I18N key（`otc.status.expired`）
- [ ] 无 APR/APY/固定收益/保本/稳赚/回本周期
- [ ] 无下注/投注/博彩/赔率/盘口/押注

### 无障碍
- [ ] 所有点击目标 ≥44×44px
- [ ] 普通文字对比度 ≥4.5:1
- [ ] 大字/大图标对比度 ≥3:1
- [ ] 状态不单靠颜色
- [ ] 表单 Label 不依赖 placeholder

## 验收方式

1. 逐页视觉审核（对照 03/08 文档规范）
2. 375/390/430 三尺寸截图回归
3. i18n key 完整性扫描（`ui-copy-manifest.json` 对比）
4. 自审检查表逐条确认（08 §14）

## 备注

- 本验收标准基于 07 §6 原型验收规则和 08 §14 自审检查表
- 敏感文案（I18N `sensitive-copy-review.json`）在人工签核前标记 PENDING_HUMAN_REVIEW，不阻塞原型阶段
- 生产参数值保持 TBC/null，不影响原型阶段开发

## 信息来源
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（§6 原型验收）
- `Gainode_Development_Ready_V6.1_Latest/08_VISUAL_DESIGN_SYSTEM_V2.4.md`（§14 自审检查表、§15 I18N Gate）
- `Gainode_Development_Ready_V6.1_Latest/03_MOBILE_H5_HIFI_PROTOTYPE_SPEC_V2.4.md`（§2.1 V6 全局体验覆盖规则）
