# Design: 后端 V6.1 全模块领域对象骨架搭建

## 技术约束

- 语言：PHP ≥8.2（`declare(strict_types=1)`）
- 框架：Webman (Workerman)，事件驱动、常驻内存
- 数据库：MySQL 8.4.9（`webman` 库 = 主库，`gainode` 库 = 未来账本独立）
- 缓存：Redis 3 实例
- 认证：JWT (firebase/php-jwt)
- 权限：Casbin RBAC + RESTful
- 日志：Monolog 多通道
- 校验：Laravel Illuminate Validation

## 现有代码复用

| 现有能力 | 复用方式 |
|---|---|
| `support/extend/` 基类（Model/Dao/Service/Controller） | 直接继承，新模块不重复造轮子 |
| JWT + Casbin 认证授权 | 直接复用，新模块路由注册后自动受保护 |
| `sys_route` 表驱动路由 | 新 API 插入 `sys_route` 表即可 |
| `sys_dict` / `sys_config` | 暂用，待 Parameter Center 完成版本化管理后迁移 |
| `sys_operation_logs` | 复用，新增 audit_event_id 关联 |
| Redis Queue 异步 | 新模块的耗时操作走 Redis Queue |
| `sys_crontab` 定时任务 | 新模块的定时任务（如 Reward 结算）插入表即可 |

## 模块骨架设计

### 目录结构（以 Robot 为例）

```text
library/
  model/robot/
    RobotModel.php           # robots 表映射
    RobotLevelModel.php      # 等级配置
    RobotRewardModel.php     # 奖励记录
    RobotUpgradeOrderModel.php # 升级订单
  dao/robot/
    RobotDao.php
    RobotLevelDao.php
    RobotRewardDao.php
    RobotUpgradeOrderDao.php
  service/robot/
    RobotService.php         # Robot CRUD + 状态机
    RobotRewardService.php   # Reward 计算与发放
    RobotUpgradeService.php  # 升级逻辑 + Power Cap 联动

app/
  api/controller/
    RobotController.php      # C 端 Robot API
  admin/controller/robot/
    RobotAdminController.php # Admin Robot 管理

sql/
  20260811_create_robot_tables.sql  # DDL（阶段一）
```

### 每个模块交付物

| 交付物 | 内容 |
|---|---|
| **Model** | 表映射、状态常量、时间戳自动管理 |
| **DAO** | CRUD 基础操作、按条件查询 |
| **Service** | 业务状态机、事务管理、Event::emit() |
| **DDL** | `sql/` 目录下日期命名 SQL 文件 |
| **路由** | `sys_route` 表插入记录 |
| **错误码** | `library/dict/ErrorDict.php` 新增 |

### 状态机设计原则

> **规则：Domain State 全部来自 `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md` canonical enum。无自创状态。**

每个实体必须有完整的状态机：

- **状态转换图**：定义所有合法状态转换路径
- **转换条件**：每个转换的前置条件和校验规则
- **转换副作用**：每次转换触发的 Event / 日志 / 通知
- **不可逆状态**：标记哪些状态不可回退（如 settled、void）

### 关键领域模型（按模块）— ALL FROM 05 CANONICAL

#### Robot（05 §4 canonical）
```
Robot: inactive / active / cooling / review / restricted / paused
```
- cooling: 连续运行后的冷却期，禁止立即重启
- review: 触发风控/异常时的审计锁定
- restricted: 策略受限运行（部分功能禁用）
- paused: 管理员手动暂停

#### AI Reward（05 §4 canonical）
```
AI Reward: candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed
```
- candidate: 奖励候选（预算内、待确认）
- held: 已记账持有（不可提）
- pending_claim: 进入领取窗口
- claiming: 领取处理中（防重）
- expired_returned: 过期退回预算池
- review: 风控冻结审计中
- reversed: 冲正（非业务取消，是财务纠正，生成 reversal entry）

#### APT Ledger（05 §4 canonical）
```
LedgerEntry: pending / posted / reversed / disputed
```
四账分离模型（05 AptAccount Object）:
1. **APT 数量账**（AptAccount：balance_apt_i + balance_apt_c + frozen_apt_i + frozen_apt_c）
2. **APT 参考估值账**（Reference Price/Valuation，独立对象）
3. **功能货币收入账**（Functional Currency Revenue，独立边界）
4. **Reward / 预算账**（Budget allocation + consumption，独立边界）

> **P1-009 FIX**: 前版本误将"APT 数量账的 internal bucket"描述成"四账"。数量账的 `available/frozen/pending/held/payable/claimed/burned` 是单账内部的 bucket，不是四账分离模型。

所有 Ledger 为 append-only。更正（撤账）通过 reversal 分录实现，不得修改原文。

#### Prediction（05 §4 canonical）
```
Market: draft / open / closing / locked / awaiting_result / settlement / settled / void / exception
PredictionOrder: submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected
```
- RESULT_UNKNOWN must be separated as a PredictionResult enum (WON/LOST/DRAW/CANCELLED)，不得混入 Order 状态
- correcting/corrected: 纠错状态流，仅在 settlement error 触发

#### OTC（05 §4 canonical — VERIFIED: matches 05）
```
OtcOrder: draft / review / matching / partial / completed / cancelled / expired / rejected / disputed
```

#### Power（05 无 canonical state machine）
PowerPosition 对象（05:151-166）使用 scalar fields（available, frozen, consumed_period），无单独 status enum。
- **PREREQUISITE**: 若 V6.1 业务需要显式 Power state machine，应先冻结进 05 再实现。当前阶段仅建 PowerPosition 对象骨架和 Power Ledger。

#### Affiliate / Agent（05: NOT DEFINED）
- **PREREQUISITE**: Affiliate/Agent 对象不在 05 canonical 定义中。需先完成 Contract Freeze（05 新增 §Agent 定义）再在 DB 中创建对应对象。
- 当前阶段：仅建 `agent`、`referral`、`agent_earning` 三张表的结构骨架（字段 TBC），枚举列作为 VARCHAR 暂存，等待 05 冻结后改为 ENUM。

#### AI 运营（05: NOT DEFINED）
- **PREREQUISITE**: AI Signal/Recommendation/Simulation 不在 05 中。需先冻结 AI 运营 Contract。
- 当前阶段：仅建对象骨架表，所有状态列标记为 TBC，功能 fail-closed。

## Machine Contract 第一批（STAGE-01 前必须）

### DB DDL — 8 核心实体

下列 8 个核心实体的 DDL 须在 STAGE-01 各模块开工前完成：

1. `apt_accounts` — APT 数量账主账号表（balance_apt_i, balance_apt_c, frozen_apt_i, frozen_apt_c）
2. `apt_ledger_entries` — APT 账本分录（append-only, reversal_of 字段）
3. `robots` — Robot 主表（56 级，状态机 inactive/active/cooling/review/restricted/paused）
4. `robot_rewards` — AI Reward 记录（canonical 8 状态）
5. `prediction_markets` — 预测市场（canonical 9 状态）
6. `prediction_orders` — 预测订单（canonical 9 状态）
7. `otc_orders` — OTC 订单（canonical 9 状态）
8. `power_positions` — Power 持仓（scalar fields）

### Canonical State Freeze

上述 8 个核心实体的状态枚举必须与 05 canonical 严格一致，冻结后不可修改（修改需走 05 变更流程）。

## 不可做

1. 不修改 `support/extend/` 基类（除非所有模块共同需要）
2. 不删除 `sql/database.sql`（保留为审计历史，新 DDL 用日期命名单文件）
3. 不给 C 端暴露 arbitrage 信号、利润、仓位等内部数据
4. 不在 Service 中硬编码状态值
5. 不绕过 Service 层在 Controller 中直接操作 DAO
6. 不手动编辑 `config/route/` 文件（路由走 `sys_route` 表）
