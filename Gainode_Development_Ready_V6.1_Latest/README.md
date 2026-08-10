# Gainode Development Ready V6.1 · Latest Project Baseline

> 日期：2026-08-10  
> 状态：`CURRENT_DEVELOPMENT_BASELINE`  
> 文档格式：Markdown  
> Source of Truth：`README + 01–08 + /i18n + /assets/logo`

## 先说人话

这个包已经把 Gainode 当前项目内最新确认结论回写进正式开发资料。

本地 Agent 接手后，**不要再回到 D01–D20、旧大 Figma、旧 Flutter、旧 Admin、旧视觉策划去重新判断产品**。

`E:\github\sports\历史文档\` 中的内容仅用于历史追溯，不具备需求权威性。**禁止从历史文档、旧 Figma、旧代码或旧包反推当前需求。**

如果包内有冲突：

```text
产品功能               → 01
经济模型 / Power规则    → 02 V2.2
Mobile / H5 页面        → 03 V2.5
Admin 页面              → 04 V2.3
数据 / 状态 / 权限/API  → 05 V2.2
参数                    → 06 V2.2
开发 / 验收             → 07 V2.2
视觉 / 交互 / I18N      → 08 V2.5
实际用户字符串           → /i18n
Logo                    → /assets/logo
```

## 本轮最新结论已经合并

1. Home 成长/升级榜移到页面最底部，不抢主任务。
2. Robot 删除 Upgrade Progress Bar，改用 Power Battery。
3. Power Cap 随 Robot 成长，由 Active Rule/Parameter 决定具体数值。
4. OTC Sell、Withdrawal、Robot Start/Auto-execution Activation 均纳入 Power 使用场景；具体数量/扣减时点不得由前端猜。
5. Prediction Root 必须表现为多场竞猜同时运营，正常 Fixture 至少 12–24 场。
6. 最新 Root 视觉方向作为视觉锚点；旧大 Figma 仍不是基线。
7. 视觉关键词：Western / Premium / Sports-Tech / Operational。
8. 设计审核图：每页一张、不加手机边框、不做四页拼图。
9. 继续降低 Generic Card Feel：真实数据更多，但界面不能更拥挤。
10. 375 / 390 / 430 三尺寸逐页视觉回归。
11. 正式 UI 禁止 Demo / Mock / Sandbox / Page ID；开发工具里可以保留。
12. OTC 新增 `expired` 状态；OTC 资格不是状态机（见 05）。
13. 新增 Notice / NotificationDelivery 通知体系；通知与业务事务解耦（见 05）。
14. 新增资金/储备/运营预算隔离边界（见 02 §4.1）。
15. 跨端状态与对象一致性验收规则（见 07 §7.1）。
16. 紧急操作控制（见 04 A-EMERGENCY-001、05 §11.2）。

## Power 特别说明

Power 是可消耗、可恢复操作资源。

- OTC Sell：freeze → fill consumes → remaining stays frozen → cancel/expiry releases。
- Withdrawal：使用 Power；具体规则由服务端 Preview + Active Parameter 决定。
- Robot Start：启动自动执行能力使用 Power；C 端仍写"启动 Robot / 启动运行"，不要重新做成 Crypto Arbitrage Robot。
- Prediction P0 当前不默认消耗 Power。

## 设计执行伴随文档

`/design-system/` 目录包含 Mobile/H5 设计执行伴随文档，用于 UI/原型/Flutter/Vue 的实现指导。

| 文件 | 角色 |
|---|---|
| `design-system/10_HIFI_Prototype_Design_System_V1.2.md` | **ACTIVE** — 44 页 HIFI 设计执行伴随文档 |
| `design-system/11_Full_Page_Visual_Interaction_Plan_V1.1.md` | MERGED — 75 页全量视觉交互策划 V1.1 |
| `design-system/11_Full_Page_Visual_Interaction_Plan_V1.2_REFERENCE_ONLY.md` | REFERENCE ONLY — 仅保留策划背景 |
| `design-system/10_Mobile_H5_Design_System_V1.1_ARCHIVED.md` | ARCHIVED — 有效增量已并入 V1.2 |

> ⚠ 以上均为 `DESIGN_EXECUTION_COMPANION`。发生冲突时，以 `01–08`、`/i18n` 和 `/assets/logo` 为准。详见 `design-system/README.md`。

## 原型 / HIFI 下一步

本地 Agent 直接基于本包继续：

1. 读取 `08` + `03`。
2. 继续逐页 HIFI / H5 视觉精修。
3. 先处理仍有 Generic Card Feel 的页面家族：Auth/KYC、APT/Power、OTC、Security/Support、Notice、Robot Activity、Prediction My Orders/Order Detail、Settings、AI Signal/P1。
4. 做 44 页 375/390/430 全量视觉回归。
5. 不重新策划业务、不新增第 09 份产品文档。

## 生产 Gate

Development/Sandbox 可以继续。生产参数、地区/KYC、敏感七语言文案等仍遵守 06/07 的 Gate；TBC 不允许本地 Agent 自行填成正式值。

## 归档说明

`AGENT_HANDOFF.md` 中如有仍有效的规则，已合并到 01–08；该文件已归档到 `E:\github\sports\历史文档\V6.1_已合并辅助文档\`。其余历史文档全部归档，不可作为当前需求基线。
