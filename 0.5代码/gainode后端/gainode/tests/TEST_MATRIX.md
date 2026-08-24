# Gainode 后端测试覆盖矩阵（NEXT-01 步骤①）

> 生成：2026-08-22 · 中控调度 Agent（Developer 工作底稿）
> 基线：26 套件全绿（`composer test`，见 tests/run_all.php）
> 用途：映射 05 合同 §域 → 现有测试套件；标注缺口与补齐计划（步骤④⑤）。

## 一、覆盖矩阵

| 05 合同 §域 | Contract | Integration/State | Projection | 缺口 |
|---|---|---|---|---|
| §6.1 Auth/Session（登录/MFA/设备） | EnvelopeContractTest, SecurityReasonMapContractTest | KernelContractTest, S02P02StateMachineTest | LoginAuditProjectionTest, SessionDeviceProjectionTest, SecurityProfileProjectionTest | HTTP 层真实路由测试未建（步骤⑤） |
| §6.2 KYC / FeatureEntitlement | — | — | FeatureEntitlementProjectionTest | C 端 submit→admin 审核闭环 E2E 未建 |
| §6.3 Ledger（append-only 双分录） | S02P03LedgerContractTest | S02P03LedgerMutationTest | — | ledger\LedgerAppendOnlyMutationMatrixTest 已含矩阵；幂等重放用例待加密钥场景 |
| §6.4 AI/Robot（规则/预算/56 级/Power/升级） | S02P04RobotRuleContractTest | S02P04RobotStateMachineTest | PowerImpactPreviewProjectionTest | 升级成本 TBC→fail-closed 分支已有；真实成本引擎待 S02-P04 解冻后补 |
| §6.5 Prediction（盘口/订单/结算） | S02P05PredictionContractTest | S02P05PredictionStateMachineTest | — | 结算 async/RESULT_UNKNOWN 幂等查询路径未测（步骤④） |
| §6.6 OTC（报价/订单簿/容量/资格） | S02P06OtcContractTest | S02P06OtcStateMachineTest | OtcCapacityProjectionTest, OtcEligibilityProjectionTest | 报价过期竞态未测 |
| §6.7 Policy/Parameter（状态机 PR1-PR11 + snapshot append-only） | S02P07PolicyContractTest | S02P07PolicyStateMachineTest | — | snapshot 冻结副作用（TBC 参数值）未测 |
| §6.8 AiOps 引擎 | S02P08AiOpsContractTest | S02P08AiOpsEngineTest | — | — |
| §11.3 Admin 治理角色 | AdminGovernanceRoleServiceContractTest（13 角色/无重复/ROLE_MAP 空 fail-closed） | — | — | role_id→roles 映射测试依赖 DR-01 Owner 冻结 |
| 控制器装配 | S02P09ControllerWiringContractTest（静态反射校验） | — | — | 静态弱校验：真实 HTTP 请求/响应断言未建（步骤⑤） |
| Envelope 规范（result_code/request_id/六头语义服务端侧） | EnvelopeContractTest | — | — | Idempotency-Key 重放窗口行为未测 |

## 二、运行方式

```bash
composer test               # 全量 26 套件
composer test:contract      # tests/Contract (10)
composer test:integration   # tests/Integration (8)
composer test:ledger        # ledger 矩阵 (1)
composer test:projection    # projection (7)
php tests/run_all.php Keyword   # 子串过滤单跑
```

## 三、缺口补齐计划

1. **步骤④**：为 PredictionController::orderCreate（锁盘参数/资格/stake 校验）、OtcController::orderCreate、RobotController::upgradeOrderCreate 增加真实控制器级测试（构造 Request→调用→断言 Envelope+DB 副作用），替代静态 wiring 反射的弱保证。
2. **步骤⑤（处置更新 2026-08-25）**：中控实测探针证明沙箱内可启动 Webman 并完成真实 HTTP 往返（`php start.php start` 托管后台 + Invoke-WebRequest），但 V2 路由运行时从 `sys_route` 表加载（config/route/v2.php → getRouteList('api_v2')），hermetic 冒烟需 SQLite 化种子（UTF-16LE MySQL 方言转换）+ 环境覆盖。**决策：HTTP 冒烟随 DR-08 FLUTTER_CI_ENV 一并在 CI 环境实施**，本地 NEXT-01 以控制器级契约测试（29 套件，含真实 Request→控制器→服务→envelopeError 全链）为 Gate 证据收口；本行保留为 CI 待办而非阻塞项。
3. **解冻前置**（BE-07）：全部 V2 写端点补 validator 后，矩阵新增 Validator 列并逐项打勾。
4. DR-01 冻结后：AdminGovernanceRoleServiceContractTest 增加 ROLE_MAP 映射断言。

## 四、纪律

- 新套件必须遵循 standalone CLI 约定：`check(): bool` + `summary(): void`，进程退出码 0/1，run_all.php 自动发现。
- 禁止引入 PHPUnit 外部依赖；存储一律 SQLite 内存/Null 实现。
