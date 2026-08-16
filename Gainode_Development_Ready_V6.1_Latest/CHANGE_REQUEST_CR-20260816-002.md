# Change Request — 冻结开发执行计划 07 全量重构（CR-20260816-002）

```text
CHANGE_REQUEST_ID = CR-20260816-002
PROJECT = Gainode
SUBMITTED_BY = QUALITY-01（质量 Agent）
SUBMITTED_AT = 2026-08-16T11:15+08:00
AFFECTED_FREEZE = DEVELOPMENT_EXECUTION_PLAN（Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md）
AFFECTED_FREEZE_STATUS = FROZEN_FOR_EXECUTION
CHANGE_CONTROL = OWNER_APPROVAL_REQUIRED
OUT_OF_SCOPE_HUMAN_CONFIRMATION_REQUIRED = YES
```

## 一、变更对象与现状

`07_DEVELOPMENT_AND_ACCEPTANCE.md` 是已冻结的开发执行计划（manifest：
`DEVELOPMENT_EXECUTION_PLAN_STATUS = FROZEN_FOR_EXECUTION`，
`DEVELOPMENT_EXECUTION_PLAN_CHANGE_CONTROL = OWNER_APPROVAL_REQUIRED`）。

开发 Agent（Gainode开发执行）在工作树中对本文件做了**未提交**的全量重构：280+ 插入 / 238- 删除，覆盖 STAGE-01～STAGE-06 全部阶段。

## 二、变更性质分类

### A. 纯结构重构（无业务语义变化）

- 每个 Package 从扁平 bullet 列表重构为统一结构：`输入/绑定`、`目标`、`允许/禁止路径`、`固定步骤`、`验证/交付`、`停止条件`、`验收`。
- 这一部分只改变排版与可读性，不改变任何冻结规则。

### B. 实质约束新增（需逐项裁决）

| # | 新增内容 | 位置 | 影响 |
|---|---|---|---|
| B1 | 强制输出 `OBJECT_COVERAGE_MATRIX`（对象/来源章节/持久化类型/DDL/Model/DAO/Service/Authoritative Writer/状态合同/测试证据） | STAGE-01 阶段根目录 | 新增交付物，提高可追溯性 |
| B2 | 新增 `tests/contract/Stage01Batch2B1*Test.php` 为 S01-P03 目标文件 | S01-P03 | 新增合同测试要求 |
| B3 | 新增 `tests/contract/Stage01Batch2B2*Test.php` + 显式对象批次顺序（ApprovalRequest→ParameterRelease/ParameterSnapshot→AuthSession/MfaEnrollment/KycCase→RiskCase→Ticket→Notice→SettlementMethod）+ "禁止建表对象"清单（FeatureEntitlement/OtcEligibility/OtcCapacity/PowerImpactPreview/SecurityProfile/SessionDevice/LoginAudit） | S01-P05 | 新增测试要求 + 批次顺序 + 禁止清单 |
| B4 | 固定实现顺序 `Contract→Controller/Validator→Application Service→Domain Service/DAO→Transaction/Outbox/Audit→Tests` | STAGE-02 阶段根目录 | 新增强制顺序 |
| B5 | 显式 Page ID 注册表 + Admin "逐导航批次（Quality 每批单独快照）" | STAGE-03 | 新增前端约束 |
| B6 | 新增 `STAGE-01-OBJECT-COVERAGE-MATRIX.md` 为 S01-P09 交付物 | S01-P09 | 新增交付物 |

## 三、为何需 Owner 裁决

冻结计划的任何实质变更都改变「执行 Agent 的强制约束边界」。若未经 Owner 批准即生效，会形成：
1. 执行 Agent 按新约束工作，但约束未获 Owner 签署，失去治理可追溯性；
2. 已按旧约束完成/正在进行的包（如 S01-P03 已交付但 B2 合同测试是新增项）产生「追溯适用」歧义。

## 四、裁决选项

```text
OPTION_A = 全量批准：把 dev agent 的重构正式化为计划 V3.2（OWNER 签署），后续所有包按新结构执行；
          已完成的 S01-P01/P02/P03 不追溯补合同测试（B2 仅适用于 S01-P05 起的后续包）。
OPTION_B = 部分批准：只批准 A 类（结构重构），B1~B6 逐项裁决后再并入计划。
OPTION_C = 拒绝：回退到冻结版 V3.1，要求 dev agent 撤销未提交改动，不得修改冻结计划。
```

## 五、推荐与影响

```text
RECOMMENDED_OPTION = OPTION_A
IMPACT_OF_OPTION_A = 计划可执行性/可追溯性增强；S01-P01/P02/P03 已完成证据不受影响；
                     S01-P04 起按新结构 + 新增合同测试要求执行；需 Owner 明确签署。
IMPACT_OF_OPTION_B = 更保守，但 B1~B6 逐项裁决耗时，且多数为纯增益项，收益/成本比低。
IMPACT_OF_OPTION_C = 最保守，但会阻塞 dev agent 已进行的工作，且丢失所有可追溯性增强。
DEVELOPMENT_PATHS_BLOCKED = 若悬而未决：dev agent 的 07 未提交改动无法纳入任何后续提交/推送。
DEVELOPMENT_PATHS_NOT_BLOCKED = S01-P03 本地审核已完成（不依赖 07 改动）；外部审核提交通道已就绪。
```

## 六、Owner 决策记录

```text
OWNER_DECISION = OPTION_A（全量批准）
OWNER_SIGNED_AT = 2026-08-16T11:35+08:00
OWNER_ADDITIONAL_CONDITIONS = 认可开发 Agent 提交的 V3.2（commit 057e810）为正式计划；
  已完成的 S01-P01/P02/P03/P04 不追溯补合同测试；B1~B6 新增约束自 S01-P05 起适用。
  开发 Agent 已自标 FROZEN_FOR_EXECUTION，本次 Owner 批准补足治理可追溯性，冻结状态维持。
RESOLUTION = APPROVED
```
