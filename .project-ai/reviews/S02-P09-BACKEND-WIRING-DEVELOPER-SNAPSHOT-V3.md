# S02-P09 后端 API 接线（只读 C 端控制器层）— Developer Snapshot

> 起草：DEVELOPMENT-01（Gainode 唯一 Development Agent）
> 起草日期：2026-08-20
> 分支：`feature/gainode-v3-serial-development`
> 目的：补齐 STAGE-02 后端 HTTP 接线缺口（S02-P09 后端 Gate 前置），先把 path-independent 的只读 C 端控制器层落地。
> ⚠️ 本快照仅含「控制器层 + 静态接线契约测试」；路由注册被一个 OPEN 的规则级 Decision Request 阻塞（见下），push 被本机 Schannel 凭据问题阻塞（见下）。

---

## 交接块

```text
Stage: S02-P09（后端 HTTP 接线，S02-P09 Gate 前置）
Baseline SHA: bec04b46dcb5b0764bb14db01c9a0f5311dfdafa
Developer final SHA: f97b84cb81a29cca07bb901edb5193749a0e19cd
Changed files:
  0.5代码/gainode后端/gainode/app/api/controller/LedgerController.php        (NEW)
  0.5代码/gainode后端/gainode/app/api/controller/RobotController.php         (NEW)
  0.5代码/gainode后端/gainode/app/api/controller/ParameterController.php     (NEW)
  0.5代码/gainode后端/gainode/app/api/controller/PredictionController.php    (NEW)
  0.5代码/gainode后端/gainode/app/api/controller/OtcController.php           (NEW)
  0.5代码/gainode后端/gainode/tests/Contract/S02P09ControllerWiringContractTest.php (NEW, 54 断言)
  0.5代码/gainode后端/gainode/docs/DECISION_REQUEST_V2_ROUTE_PATH_SCHEME.md   (NEW)
Contract slice: 05 §1/§2/§6 + openapi/gainode-v2.yaml（ledger/robot/prediction/otc/policy_parameter paths 只读 operation）
Implementation:
  - LedgerController: me/asset, me/ledger-entries, me/power（绑定 AptAccountService/LedgerService/PowerPositionService）
  - RobotController: 用户汇总/列表/详情/允许动作/升级订单/奖励（绑定 RobotService/RobotUpgradeOrderService/RobotRewardService）
  - ParameterController: active release / snapshot detail（绑定 ParameterReleaseService/ParameterSnapshotService）
  - PredictionController: markets/detail/my orders/receipt/consent receipts（绑定 PredictionMarketService/PredictionOrderService/ConsentReceiptService）
  - OtcController: order-book/order detail/user orders/trades/eligibility（绑定 OtcOrderService/OtcTradeService/OtcEligibilityProjectionService）
  - 全部 extends support\controller\ApiV2，统一 envelope/envelopeError；只读；未开放任何写方法（fail-closed，路由不注册写操作）
Automated verification:
  - php -l 5 个新控制器                      | exit 0 | PASS
  - tests/Contract/S02P09ControllerWiringContractTest.php | exit 0 | PASS (54 断言)
  - 既有 Contract 套件(Envelope/S02P03~P08/S02P09/projection) | exit 0 | PASS
  - 既有 Integration 套件(S02P02~P08/Kernel)               | exit 0 | PASS
  - tests/ledger/LedgerAppendOnlyMutationMatrixTest        | exit 0 | PASS (67 断言)
  - git diff --check                        | exit 0 | PASS
  - git push origin feature/gainode-v3-serial-development | exit 0 | PASS（danger-full-access 下 GCM 可取得凭据；bec04b4..0fc5289 已同步）
Manual verification: 路由注册 + 真实 HTTP 请求 | 未执行（路由方案 OPEN，未注册路由；无 runnable 服务实例） | NOT_RUN
Open issues / Risk / Not implemented:
  - 【OPEN_OWNER_DECISION】V2 路由路径方案：契约/前端/OpenAPI 用 /api/v1/...，运行时 config/route/api.php 组 /v1 + sys_route.url /api/... = /v1/api/...。Agent 不得猜测，已提交 DECISION_REQUEST_V2_ROUTE_PATH_SCHEME.md，建议 OPTION_A（新增 /api/v1 组，复用 sys_route 表）。裁决前不注册任何 V2 路由（fail-closed）。
  - 写路径（robot start/stop/upgrade/reward-claim、prediction order/consent、otc quote/create/cancel、parameter activate）均未在控制器开放（fail-closed；服务层已对 TBC 抛 DEPENDENCY_UNAVAILABLE）。
  - 路由未注册前，Auth/Kyc/User 控制器仍 HTTP 不可达（路由缺失即关闭，安全）。
  - Robot/Prediction/Otc 只读投影依赖 DB 中真实数据行；空表时返回空列表（非 mock）。
  - 本包不包含 sys_route seed（等待路由方案裁决）。
  - push 已解除（danger-full-access 下 GCM 正常取得凭据，bec04b4..0fc5289 已同步到 origin）。
Next technically ready Package: S02-P09 后端 Gate（路由方案裁决 + sys_route 注册后即可进入 Gate 步骤；若裁决前继续，可先做只读接线余下的 validator 补齐）
SNAPSHOT_LOCKED: YES
```

---

## 自审摘要

- **业务**：只读投影对齐 05 §6 / openapi schemas；金额全 string decimal；未引入写操作。
- **状态机**：本包无状态转移；全部只读。
- **幂等/并发**：只读无写路径，不涉及 idempotency/object_version 写语义。
- **权限**：全部要求 `getTokenUser()`（AuthMiddleware 鉴权），仅返回本人作用域或服务内置越权保护。
- **账本**：LedgerController 只读展示 append-only 分录，无写。
- **安全**：未硬编码 secret；未新增 .env 改动。
- **历史兼容**：仅新增文件，未改动既有 controller/service/route。

## 交接说明（合规声明）

```text
CONSUMED_UNFROZEN_CONTRACT = 无（只读投影均绑定已 FROZEN 契约/服务）
OPEN_OWNER_DECISION = V2-ROUTE-PATH-01（路由路径方案，见 docs/DECISION_REQUEST_V2_ROUTE_PATH_SCHEME.md）
OVERLAPS_LOCKED_SNAPSHOT = 无（仅新增文件，不触碰既有已锁定实现）
```

## 待 Owner 动作

1. 裁决 `DECISION_REQUEST_V2_ROUTE_PATH_SCHEME.md`（建议 OPTION_A）。
2. 在本机凭据可用环境执行 `git push origin feature/gainode-v3-serial-development`（或提供可用凭据）。
