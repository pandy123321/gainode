# Owner Decision Request: S01-P08 AI Operations P1 合同

> 状态：**OWNER_SIGNED**（2026-08-16，9 项 D1~D9 全部采纳 OPTION_A）+ 1 项 LOCKED（D10 C 端边界）
> 起草日期：2026-08-16
> 任务：`.project-ai/tasks/TASK-20260816-007/`
> 关联候选合同：`sql/MACHINE_CONTRACT_AI_OPERATIONS_P1_FREEZE.md`
> 裁决后动作：更新 05 / 06 → Freeze Candidate → Independent Review → FROZEN → 快照 2（建 DDL/Model/DAO/Service/command）
> 默认（未签）：三对象（AISignal/AIRecommendation/SimulationRun）CONTRACT_GAP/FAIL_CLOSED，不建表；C 端泄露边界 FORBIDDEN；不继承 V1.x 矿机套利语义。

---

## D1 — AISignal 状态枚举

```text
DECISION_ID = AI-OPS-01
DECISION_REQUIRED = AISignal.status 的 canonical enum
AFFECTED_OBJECTS = AISignal
CURRENT_AUTHORITY = Owner（05 未定义；V1.x status 1有效/2过期/3已用尽/4关闭/5无效）
OPTION_A = active / expired / consumed / closed / invalid
OPTION_B = active / expired / consumed
RECOMMENDED_OPTION = OPTION_A（对齐 V1.x 五态，含 closed/invalid 表达管理关闭与数学校验失败）
RISK_OF_EACH_OPTION = A：五态语义需定义转移；B：丢失 closed/invalid 粒度
SAFE_WORK_CONTINUING = 是（S01-P09 收口不依赖 AI 状态机）
RESUME_CONDITION = 裁决后补 05 §4
```

## D2 — AIRecommendation 状态枚举

```text
DECISION_ID = AI-OPS-02
DECISION_REQUIRED = AIRecommendation.status 的 canonical enum
AFFECTED_OBJECTS = AIRecommendation
CURRENT_AUTHORITY = Owner（05 未定义）
OPTION_A = draft / active / expired / superseded
OPTION_B = active / expired
RECOMMENDED_OPTION = OPTION_A（推荐有草稿与「被新版本取代」语义）
RISK_OF_EACH_OPTION = A：superseded 触发规则待定义；B：无法表达版本取代
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 §4
```

## D3 — SimulationRun 状态枚举

```text
DECISION_ID = AI-OPS-03
DECISION_REQUIRED = SimulationRun.status 的 canonical enum
AFFECTED_OBJECTS = SimulationRun
CURRENT_AUTHORITY = Owner（05 未定义）
OPTION_A = pending / running / completed / failed / cancelled
OPTION_B = running / completed / failed
RECOMMENDED_OPTION = OPTION_A（含 pending/cancelled 表达排队与取消）
RISK_OF_EACH_OPTION = A：五态转移需定义；B：丢失排队/取消语义
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 §4
```

## D4 — retention 期限

```text
DECISION_ID = AI-OPS-04
DECISION_REQUIRED = 供应商信号/raw payload 的保留期限
AFFECTED_OBJECTS = AISignal
CURRENT_AUTHORITY = Owner（07 §S01-P08 retention 候选，未定）
OPTION_A = 固定期限（如 raw 30 天 / 归一化 90 天，具体天数 Owner 定）
OPTION_B = 无限保留（仅内部，无自动清理）
RECOMMENDED_OPTION = OPTION_A（合规 + 存储成本；具体天数请 Owner 填写）
RISK_OF_EACH_OPTION = A：需定具体天数；B：无限保留合规/成本风险
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 06 §AI retention
```

## D5 — 供应商许可

```text
DECISION_ID = AI-OPS-05
DECISION_REQUIRED = BetBurger/API-Football 许可范围/再分发限制
AFFECTED_OBJECTS = AISignal / AIRecommendation
CURRENT_AUTHORITY = Owner（07 §S01-P08 供应商许可，未定）
OPTION_A = 仅内部使用（不向 C 端再分发任何供应商数据/衍生 signal）
OPTION_B = 有限再分发（需补充再分发条款）
RECOMMENDED_OPTION = OPTION_A（默认最保守，对齐「供应商仅内部输入」边界）
RISK_OF_EACH_OPTION = A：功能受限；B：需法律审阅再分发条款
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 内部对象附录
```

## D6 — Authoritative Writer

```text
DECISION_ID = AI-OPS-06
DECISION_REQUIRED = 三对象各自 Authoritative Writer 角色
AFFECTED_OBJECTS = AISignal / AIRecommendation / SimulationRun
CURRENT_AUTHORITY = Owner（05 §8 未定义 AI 写角色）
OPTION_A = 系统内部（采集/推荐/模拟由系统进程写，无 END_USER 写路径）
OPTION_B = 引入 AI_OPS_OPERATOR 角色（运营可写/管理）
RECOMMENDED_OPTION = OPTION_A（三对象均为内部系统写，禁止用户写；管理走 ADMIN_SECURITY）
RISK_OF_EACH_OPTION = A：无人工干预入口；B：新增角色需 SoD 定义
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 §8
```

## D7 — 重试 / 幂等

```text
DECISION_ID = AI-OPS-07
DECISION_REQUIRED = 信号去重、模拟重试语义
AFFECTED_OBJECTS = AISignal / SimulationRun
CURRENT_AUTHORITY = Owner
OPTION_A = dedupe_key 强制唯一（信号 upsert 幂等）；SimulationRun 失败不自动重试（人工/系统显式重跑）
OPTION_B = 自动重试（失败后自动重排队）
RECOMMENDED_OPTION = OPTION_A（去重强制唯一；模拟确定性，自动重试收益低且消耗资源）
RISK_OF_EACH_OPTION = A：失败需人工重跑；B：自动重试可能重复计费/资源
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 §4
```

## D8 — 预算连接

```text
DECISION_ID = AI-OPS-08
DECISION_REQUIRED = AI Reward Budget 与内部经济引擎连接（02 §5.4 confirmed/reference/mapped/daily）
AFFECTED_OBJECTS = SimulationRun / AIRecommendation
CURRENT_AUTHORITY = Owner（02 §5.4 结构已有，生产连接未签）
OPTION_A = 预留连接字段（budget_ref/mapped_apt_budget），P1 不启用（默认关闭）
OPTION_B = P1 即启用预算映射
RECOMMENDED_OPTION = OPTION_A（02 §11 隔离 + 06 §4 TBC，P1 不启用正式经济计算）
RISK_OF_EACH_OPTION = A：功能延迟；B：未批准预算映射违法 02 约束
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 06 §4 / 02 §5.4
```

## D9 — 模型版本

```text
DECISION_ID = AI-OPS-09
DECISION_REQUIRED = model/rule/parameter version 管理策略
AFFECTED_OBJECTS = AIRecommendation / SimulationRun
CURRENT_AUTHORITY = Owner（06 §4 参数版本化，AI 模型版本未定）
OPTION_A = 复用 06 参数生命周期（Definition→Candidate→Simulation→Approval→Release），model_version 引用 Release
OPTION_B = 独立 AI 模型版本管理
RECOMMENDED_OPTION = OPTION_A（复用参数中心，避免第二套版本体系）
RISK_OF_EACH_OPTION = A：AI 模型特有字段需映射；B：第二套版本体系成本高
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 06 §AI
```

## D10 — C 端输出边界（LOCKED，非 Owner 决策）

```text
DECISION_ID = AI-OPS-10
STATUS = LOCKED（07 §S01-P08 固定边界，无需 Owner 决策）
RULE = C 端不得返回 arbitrage signal、profit、position 或供应商原始 payload
FORBIDDEN_FIELDS = signal 明细 / profit detail / position / leg1-leg2 odds / 供应商 payload
INTERNAL_ONLY = YES（所有对外 serializer 明确 deny 内部字段）
VIOLATION = Scope Finding（直接禁止并报 Scope Finding，不可申请豁免）
```

---

## 裁决汇总

```text
OWNER_DECISION_COUNT = 9（D1~D9）
LOCKED_COUNT = 1（D10 C 端边界）
OWNER_SIGNOFF_DATE = 2026-08-16
OWNER_SIGNOFF_SCOPE = 全部 9 项采纳 OPTION_A
ALL_OPEN = NO
DEFAULT_FAIL_CLOSED = 解除（三对象可进入快照 2：建 DDL/Model/DAO/Service/command 骨架）
C_ENDPOINT_INTERNAL_LEAK = FORBIDDEN
NO_LEGACY_MINER_INHERITANCE = YES
```

逐项签核结果（2026-08-16，全部 OPTION_A）：

| # | 决策 | 落定值 |
|---|---|---|
| D1 | AISignal.status | `active / expired / consumed / closed / invalid` |
| D2 | AIRecommendation.status | `draft / active / expired / superseded` |
| D3 | SimulationRun.status | `pending / running / completed / failed / cancelled` |
| D4 | retention 期限 | 固定期限：raw 30 天 / 归一化 90 天 |
| D5 | 供应商许可 | 仅内部使用（不向 C 端再分发任何供应商数据/衍生 signal） |
| D6 | Authoritative Writer | 系统内部进程写（无 END_USER 写路径；管理走 ADMIN_SECURITY） |
| D7 | 重试/幂等 | dedupe_key 强制唯一（信号 upsert 幂等）；SimulationRun 失败不自动重试 |
| D8 | 预算连接 | 预留连接字段（budget_ref/mapped_apt_budget），P1 不启用 |
| D9 | 模型版本 | 复用 06 参数生命周期（Definition→Candidate→Simulation→Approval→Release） |
| D10 | C 端输出边界 | LOCKED（非 Owner 决策）：C 端不得返回 signal/profit/position/payload |
