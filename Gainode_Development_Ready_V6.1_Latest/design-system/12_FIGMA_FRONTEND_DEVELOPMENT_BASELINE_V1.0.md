# 12 · Gainode2.0 App/H5 前端开发基线

> 版本：V1.0  
> 生效日期：2026-08-12  
> 状态：`ACTIVE / REQUIRED`  
> 适用范围：App 与 H5 前端的视觉、布局、组件、交互、状态呈现和响应式实现  
> Figma：[Gainode2.0](https://www.figma.com/design/PzRx431rPRiT1vE3k7NZrp/Gainode2.0?node-id=0-1)  
> File Key：`PzRx431rPRiT1vE3k7NZrp`；入口 Node：`0:1`

## 1. 基线决定

1. 后端方案不变，继续使用 **PHP 8.2 + Webman 2.1 + Workerman**；本文件不引入 Go 迁移。
2. Gainode2.0 是 App/H5 唯一有效的前端设计实施基线。页面不得凭开发习惯重新排版，不得用历史 Figma、旧 Flutter 页面或第三方模板覆盖。
3. “完全按照设计图执行”包括：信息层级、布局、尺寸、间距、颜色、字体、圆角、阴影、图标、品牌资产、组件变体、交互反馈、页面状态、固定区域、安全区和响应式行为。
4. 未获得产品/设计书面批准，不得新增近似组件、替换资产、改变 CTA 层级、删减状态、合并页面或创造设计稿不存在的交互。

## 2. 权责边界与冲突处理

| 内容 | 最终依据 |
|---|---|
| 产品范围、业务规则、经济模型、Power、参数 | `01/02/06` |
| 对象、状态机、权限、API、安全、幂等 | `05/07` |
| 正式多语言字符串 | `/i18n` |
| Logo 原始资产与使用限制 | `/assets/logo`、Gainode2.0 `00_Brand_Assets` |
| App/H5 页面视觉、布局、组件、交互、响应式 | Gainode2.0 Figma |
| 全局视觉语义与无障碍约束 | `08`；并落实到 Gainode2.0 |

如果 Figma 与业务文档冲突：

1. 立即登记差异，附文档条款、Figma Node ID、截图和影响范围；
2. 业务事实临时以 `01/02/05/06/07` 为准；
3. 在 Figma 修正并确认前，不得静默实现开发者自行选择的版本；
4. 设计修正后，再按最新确认节点完成代码和回归。

该规则不允许前端忽略 Figma，也不允许 Figma改变已锁定的经济模型、参数、权限或服务端状态。

## 3. Figma 有效区域

开发和验收应使用以下正式页面/章节：

- `00_START_HERE`
- `01_ROOT_SCREENS`
- `02_AUTH_KYC_NOTICE`
- `03_ROBOT`
- `04_PREDICTION`
- `05_APT_POWER_OTC`
- `06_SECURITY_SUPPORT_SETTINGS`
- `07_AI_GROWTH_MIGRATION`
- `08_PRODUCT_UI_DESIGN_SYSTEM`
- `09_PROTOTYPE_FLOWS`
- `10_RESPONSIVE_QA_FINAL`
- `11_QA_ALL_044_FINAL`
- `00_Brand_Assets`
- `01_Product_UI_Foundations`
- `02_Product_UI_Components`
- `03_Product_UI_Patterns`
- `04_Product_UI_States`
- `05_Product_UI_Responsive`
- `06_Product_UI_Examples`

`ZZ_ARCHIVE — DO NOT USE FOR DEVELOPMENT` 以及其他标记为 Archive、Reference Only、Deprecated 的内容禁止用于新开发和验收。

## 4. 组件与品牌执行

- 优先复用 Figma 已定义的 Button、Field、Toast、Notice、Header、Bottom Navigation、Card、List Row、Bottom Sheet、Dialog、Switch、Checkbox、Prediction、Robot、OTC、APT、Growth、Operational 等组件及其变体。
- Design Token 必须统一映射到代码 Token；颜色、字号、行高、间距、圆角和阴影不得散落为无来源的硬编码值。
- Logo 必须使用正式资产，不得重画、拉伸、改色或改变比例。
- Robot IP 允许按场景调整动作、姿态、角度和构图，但必须保留脸部、眼睛、头身结构、材质语言、Logo 位置与整体渲染风格。
- UI 风格锁定为 Modern、Clean、Semi-flat、Premium、Operational、Readable、Sports-Tech；不得把 Logo 的 3D 效果复制到整套 UI。

## 5. 响应式与状态覆盖

每个 P0 页面必须至少在以下视口完成设计对照和截图回归：

- 375px：iPhone SE 档；
- 390px：iPhone 15 档；
- 430px：iPhone 15 Pro Max 档。

App 与 H5 共用信息架构、Token 和核心组件。平台差异仅允许用于 safe area、键盘、浏览器栏、系统返回和输入能力适配，并必须记录，不得借适配重新设计页面。

页面至少覆盖 Default、Loading、Empty、Error、Restricted。写操作还必须覆盖 Invalid、Confirm、Submitting、Processing、Success、Failed、Unknown/Recovery；具体业务状态以 `05` 为准。

## 6. 开发任务与合并门槛

每个 App/H5 前端任务必须包含：

1. 页面名称和对应 Figma Node ID；
2. 使用的组件与 Token 清单；
3. 375/390/430 三档实现截图；
4. Default/异常/受限/写操作状态覆盖清单；
5. 与 Figma 的视觉对照结果；
6. 如有差异，提供批准记录、原因和回补计划。

验收遵循“像素级一致、无未经批准偏差”。字体渲染和平台抗锯齿的自然差异可由自动化视觉回归阈值吸收，但不得用该阈值掩盖布局、间距、字号、颜色、图标、组件或交互差异。

以下任一情况不得通过前端验收：

- 无法追溯到 Figma Node；
- 只实现 Default，缺少规定状态；
- 375/390/430 任一尺寸出现遮挡、溢出、错位或触控区域不合格；
- 自行重算或猜测资格、Reward、Power、OTC、Prediction 等服务端事实；
- 使用 Archive、旧 Figma、旧代码视觉或非正式品牌资产；
- 存在未批准的视觉或交互偏差。

## 7. 设计变更流程

1. 设计变更先在 Gainode2.0 完成并通过评审；
2. 记录变更日期、页面、Node ID、影响组件和关联需求；
3. 如涉及业务、参数、状态、API 或文案，同步更新对应 `01–08` 或 `/i18n`；
4. 前端按确认版本实现并完成三尺寸回归；
5. 禁止用代码中的临时实现反向覆盖 Figma，也禁止只改 Figma 不通知开发和测试。

本基线自 2026-08-12 起适用于所有新增、重构和缺陷修复涉及的 App/H5 页面。
