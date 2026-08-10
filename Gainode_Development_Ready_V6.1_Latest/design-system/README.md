# Gainode Design System · Companion Documents

> **ROLE = DESIGN_EXECUTION_COMPANION**  
> **NOT_A_SOURCE_OF_TRUTH = YES**  
> **SOURCE_OF_TRUTH = ../01–08 + /i18n + /assets/logo**

本目录存放与 01–08 核心规格配套的设计执行伴随文档。发生冲突时，以 `01–08`、`/i18n` 和 `/assets/logo` 为准，不以本目录覆盖权威基线。

---

## 文件清单

| 文件 | 角色 | 状态 |
|---|---|---|
| `10_HIFI_Prototype_Design_System_V1.2.md` | Mobile/H5 44 页 HIFI 设计执行伴随文档 | **ACTIVE** — 授权引用 `03 V2.4 / 08 V2.4` |
| `10_Mobile_H5_Design_System_V1.1.md` | V1.1 移动端 H5 设计系统 | **MERGED / ARCHIVED** — 有效增量已并入 V1.2 |
| `10_Mobile_H5_Design_System_V1.1_ARCHIVE_TOMBSTONE.md` | V1.1 归档墓碑标记 | **ARCHIVED_DO_NOT_USE** |
| `11_Full_Page_Visual_Interaction_Plan_V1.1.md` | 75 页全量视觉交互策划 V1.1 | **MERGED** — 已并入 03/04 V2.2 |
| `11_Full_Page_Visual_Interaction_Plan_V1.2_REFERENCE_ONLY.md` | 75 页全量视觉交互策划 V1.2 | **REFERENCE_ONLY** — 仅保留策划背景与合并追溯 |

## 权威关系

```text
01 产品功能基线 ← SOURCE OF TRUTH
02 经济模型与业务规则
03 Mobile/H5 HIFI Page Execution Spec
04 Admin HIFI Page Execution Spec
05 数据 / 状态 / 权限 / API
06 参数字典
07 开发与验收
08 全局视觉 + I18N/L10N
/i18n 七语言字符串
/assets/logo Logo 资产

本目录 ← DESIGN_EXECUTION_COMPANION (冲突时以上述为准)
```

## 使用方式

- 原型 Agent 生成 Mobile/H5 高保真页面时，先读 `03` 和 `08`，再参考本目录的 `10_HIFI_Prototype_Design_System_V1.2.md`。
- 不得仅读取本目录文档执行 HIFI。
- `ARCHIVED` 和 `REFERENCE_ONLY` 文档不可用于新页面的创建或审核。
