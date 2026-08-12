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

每个实体必须有完整的状态机：

- **状态转换图**：定义所有合法状态转换路径
- **转换条件**：每个转换的前置条件和校验规则
- **转换副作用**：每次转换触发的 Event / 日志 / 通知
- **不可逆状态**：标记哪些状态不可回退（如 settled、burned）

### 关键领域模型（按模块）

#### Robot
```
Robot: [pending] → active → upgrading → active | paused | disabled
Reward: [pending] → calculated → payable → claimed | expired
UpgradeOrder: [pending] → payment_confirmed → completed | failed
```

#### APT Ledger
```
LedgerEntry: append-only, reversal_of → reversal_entry
四账：available | frozen | pending | held | payable | claimed | burned
```

#### Prediction
```
Market: [upcoming] → open → locked → in_progress → completed → settled | cancelled | abandoned
PredictionOrder: [pending] → confirmed → settled(won|lost) | refunded | corrected
```

#### OTC
```
OtcOrder: [draft] → review → matching → partial → completed | cancelled | expired | rejected | disputed
```

#### Power
```
PowerAccount: balance tracking, append-only
PowerTransaction: consume | recover | convert
```

#### Affiliate / Agent
```
Agent: [pending] → active → suspended | terminated
Referral: [pending] → active → settled | revoked
AgentEarning: [pending] → calculated → payable → claimed
```

#### AI 运营
```
AISignal: generated → validated → published | rejected
AIRecommendation: draft → reviewed → published | dismissed
SimulationRun: queued → running → completed | failed | cancelled
```

## 不可做

1. 不修改 `support/extend/` 基类（除非所有模块共同需要）
2. 不删除 `sql/database.sql`（保留为审计历史，新 DDL 用日期命名单文件）
3. 不给 C 端暴露 arbitrage 信号、利润、仓位等内部数据
4. 不在 Service 中硬编码状态值
5. 不绕过 Service 层在 Controller 中直接操作 DAO
6. 不手动编辑 `config/route/` 文件（路由走 `sys_route` 表）
