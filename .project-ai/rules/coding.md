# 编码规则

## 已确认信息

### 通用编码约束

1. **API 契约优先**：所有 API 必须与 OpenAPI 3.1 规范一致；实现不能偏离已定义的请求/响应结构。
2. **幂等性**：所有写操作必须有 idempotency_key；重复请求不得重复资金效果。
3. **并发安全**：状态变更使用 If-Match / object_version 防并发覆盖。
4. **时间处理**：服务端统一 UTC timestamp；客户端负责本地化。
5. **金额精度**：资产类数字使用 string decimal，禁止 JS float / double 做业务计算。
6. **枚举安全**：所有状态 enum 只能由后端定义；前端只读取和显示。
7. **Mock 隔离**：前端 mock fixture 与 production config 分目录；生产 API 无值时不能 fallback 到 mock。
8. **TBC 处理**：生产参数值为 TBC/null 时，对应功能必须 fail-closed，不得用本地默认值补齐。

### 前端规则

1. **资格判断**：按钮/功能可用性只读 `allowed_actions` 或 `FeatureEntitlement`；不通过 `if level > 20` 自行推导。
2. **Power 处理**：Power Cap、恢复量、消耗量全部由服务端返回；客户端只展示 Preview 与最终 Ledger 结果。
3. **写操作状态**：所有写操作必须覆盖 Default → Submitting → Processing → Success/Failed。
4. **错误处理**：区分 VALIDATION_ERROR、POLICY_DENIED、INSUFFICIENT_APT、RESULT_UNKNOWN 等不同错误码。RESULT_UNKNOWN(202) 不得提示用户重试。
5. **页面状态**：每个 P0 页面必须实现 Loading / Content / Empty / Error / Restricted 五种状态。
6. **状态展示**：所有状态同时使用文字+颜色，不得只靠颜色区分。Badge 必须包含下一步指引。
7. **数据刷新**：页面恢复/网络重连后重新查询对象终态，不依靠本地缓存。
8. **数字展示**：所有业务数字必须回答：这是什么？单位是什么？什么状态？数据是哪一刻？
9. **I18N**：所有用户可见字符串必须通过 i18n key 读取。禁止 raw enum 直接显示。
10. **兼容性**：Mobile 必须兼容 375/390/430 三宽度。点击目标最小 44×44px。
11. **视觉禁止**：正式 UI 不得出现 Demo/Mock/Sandbox。不做四页拼图。不加手机边框。
12. **文案禁止**：不使用 APR/APY/固定收益/保本/下注/投注/博彩/赔率/盘口/押注等词汇。
13. **金色使用**：仅用于 Level、Reward 资格、升级关键变化、品牌装饰。不用作所有按钮/金额。
14. **CTA 层级**：每页只有一个最主要 CTA；次要动作降级为 link/secondary button。

### Vue 3 + TypeScript 编码规则（H5 / Admin Web）

> 依赖等级：**MUST** = 能力必须实现 / **RECOMMENDED** = 推荐选型 / **TBC** = Agent 不得自行安装

#### 能力要求（MUST — CAPABILITY）

1. **框架版本**：Vue 3.4+，使用 Composition API + `<script setup lang="ts">` **[MUST]**
2. **TypeScript 严格模式**：启用 `strict: true`，禁止 `any` 类型（业务逻辑层） **[MUST]**
3. **7 语言 I18N**：所有用户可见字符串通过 i18n key 读取，7 语言 key 集一致 **[MUST]**
4. **精确十进制计算**：所有资产类数字使用 `string` 类型做业务计算，禁止 `number`/`parseFloat` **[MUST]**
5. **页面路由**：前端路由基于 Page ID 命名规范 **[MUST]**
6. **组件颗粒度**：页面级组件按 Page ID 命名（如 `AUser004AssetAdjustment.vue`）；通用组件放 `components/common/` **[MUST]**

#### 推荐包选型（RECOMMENDED — PACKAGE）

7. **构建工具**：Vite 5+ **[RECOMMENDED]**
8. **路由库**：Vue Router 4+ **[RECOMMENDED]**
9. **I18N 库**：vue-i18n 9+ **[RECOMMENDED]**
10. **精度库**：`decimal.js` **[RECOMMENDED]**
11. **状态管理**：Pinia（按模块拆分 store，不使用单一巨型 store） **[RECOMMENDED]**
12. **API 调用封装**：统一 Axios 实例，自动注入六个请求头（Token/Sign/Timestamp/Version/Language/TraceId） **[RECOMMENDED]**
13. **样式方案**：SCSS + `<style scoped>`。全局设计令牌从 `08_VISUAL_DESIGN_SYSTEM_V2.4.md` 提取为 CSS 变量 **[RECOMMENDED]**

#### 已决策应用依赖（OWNER_DIRECTIVE）

14. **UI 组件库**：H5 端 **Vant 4**（移动端优先，~50KB gzip）；Admin 端 **Element Plus**（Vue 3 原生，迁自 Layui Vue） **[OWNER_DIRECTIVE 2026-08-12]**

### Flutter 编码规则（App）

> 依赖等级同 Vue 规则。

#### 能力要求（MUST — CAPABILITY）

1. **Dart 版本**：Dart 3+，启用 null safety **[MUST]**
2. **7 语言 I18N**：使用 Flutter 官方 i18n 方案，ARB 文件 key 集与 `ui-copy-manifest.json` 一致 **[MUST]**
3. **精确十进制计算**：禁止 `double` 做业务计算 **[MUST]**
4. **组件化**：每个 Page ID 一个独立 Widget；通用组件放 `lib/widgets/` **[MUST]**

#### 推荐包选型（RECOMMENDED — PACKAGE）

5. **精度包**：`decimal` **[RECOMMENDED]**
6. **路由**：GoRouter，基于页面 ID 的路由命名规范 **[RECOMMENDED]**
7. **网络层**：Dio，统一封装六个请求头 **[RECOMMENDED]**

#### 待定（TBC — NOT_AUTHORIZED_TO_INSTALL）

8. **状态管理**：Riverpod 或 Bloc **[TBC]**

### PHP 后端编码规则

#### 框架约定

1. **PHP 版本**：≥8.2（与 Dockerfile php:8.2-cli 一致）。使用 `declare(strict_types=1)`。
2. **命名空间**：遵循 PSR-4。Controller 在 `app\` 下，业务代码在 `library\` 下，基础设施在 `support\` 下。
3. **文件命名**：类名与文件名一致，使用 PascalCase。Controller 类名必须以 `Controller` 结尾。
4. **composer.json**：新增类必须在 PSR-4 autoload 中注册命名空间映射。

#### 分层约定（Controller → Service → DAO → Model，不可跨层）

```php
// Controller: 只做参数接收、校验、调用 Service、返回响应
class XxxController extends Controller
{
    public function create(Request $request): Response
    {
        $data = $request->post();
        $service = new XxxService();
        $result = $service->create($data);
        return json(['code' => 200, 'data' => $result]);
    }
}

// Service: 业务逻辑编排，必须 extends support\extend\Service
class XxxService extends Service
{
    public function __construct()
    {
        $this->dao = XxxDao::class;
        parent::__construct();
    }
}

// DAO: 数据库查询封装，必须 extends support\extend\Dao
class XxxDao extends Dao { ... }

// Model: 数据表映射，必须 extends support\extend\Model
class XxxModel extends Model
{
    protected string $table = 'xxx_table';
    protected string $pk = 'id';
}
```

5. **Service 调用 DAO**：通过 `$this->getNewDao()` 获取 DAO 实例，基类自动代理 `create/update/get/find/fetch/fetchAll` 等方法。
6. **禁止**：Controller 中直接 new DAO 或 Model；不允许 Controller 绕过 Service 执行业务逻辑。

#### 状态与常量

7. **Model 常量**：业务状态常量定义在对应的 Model 类中，不在 Service 中硬编码。
8. **错误码**：定义在 `library\dict\ErrorDict.php` 中，全局统一引用。
9. **队列名**：定义在 `library\dict\QueueDict.php` 中。

#### 路由与 API

10. **路由管理**：新增 API 路由通过 `sys_route` 表插入记录。不手动编辑 `config/route/admin.php` 或 `api.php`。
11. **API 前缀**：所有 API 统一 `/v1/` 前缀。
12. **请求头**：客户端必须传 Token / Sign / Timestamp / Version / Language / TraceId 六个头。

#### 认证与权限

13. **Auth 中间件**：API 路由通过 `AuthMiddleware` 做 JWT Token 校验。Token 存储在 JWT Cache（Redis），退出时加入黑名单。
14. **Casbin 权限**：RBAC 权限通过 Casbin 策略管理。新增权限需更新 `sys_casbin_rbac` 和 `sys_casbin_restful` 表。

#### 数据库与事务

15. **数据表前缀**：不使用表前缀。
16. **字符集**：utf8mb4 / utf8mb4_unicode_ci。引擎：InnoDB。
17. **事务**：涉及多表写入必须使用数据库事务，在 Service 层控制。
18. **Migration**：
    - **阶段一（当前）**：通过 `sql/YYYYMMDD_description.sql` 独立文件管理
    - **阶段二（DDL 变更超过 10 次后）**：引入 Phinx。必须设立 PHINX_ADOPTION_POINT；已有环境创建 baseline/stamp；新环境使用不可变 baseline 构件
    - **禁止**：把已执行的历史 SQL 直接转成 migration 后无差别重新执行
    - 历史 dated SQL 不删除（保留为审计轨迹）
19. **连接池**：使用 illuminate/database 连接池配置，默认最大 5 连接、最小 1 连接。

#### 日志、环境与进程

20. **日志通道**：业务日志使用 `Log::info/error/warn`。API 请求日志自动由 ActionHook 中间件记录。定时任务日志使用独立 crontab 通道。套利引擎日志使用 library 通道。
21. **环境变量**：所有敏感配置通过 `.env` 文件管理。非敏感配置放在 `config/*.php` 中。
22. **生产环境**：`APP_DEBUG=false`，`APP_PROCESS_LIST` 仅包含必要进程（task_server,crontab_task,arb_task）。
23. **异步任务**：耗时操作通过 Redis Queue 投递，消费者在 `app/queue/redis/` 中定义。
24. **消息推送**：实时推送通过 Channel Server (2206) 发布，Pusher Server (8888) 投递到 WebSocket 客户端。

### 新增 V6.1 模块的目录约定

每个新业务模块（Robot/Power/OTC/Prediction/Affiliate/AI 运营）按以下结构组织：

```text
library/
  model/{module}/     # XxxModel.php（extends support\extend\Model）
  dao/{module}/       # XxxDao.php（extends support\extend\Dao）
  service/{module}/   # XxxService.php（extends support\extend\Service）

app/
  api/controller/     # C 端 API Controller
  admin/controller/{module}/  # Admin Controller
```

### 后端通用规则（V6.1 新增模块也必须遵守）

1. **账本不可变**：APT 和 Power 账本 append-only。修正用 reversal（追加反向记录），不可覆盖或删除历史。
2. **状态分离**：Market status、Result status、Settlement status、Order status 必须按对象分别管理。
3. **资格服务端解析**：所有资格、参数、Policy 在服务端解析后返回。前端不持有判断逻辑。
4. **Snapshot 机制**：历史订单关联 snapshot_id，不可用当前参数回算历史。
5. **通知解耦**：业务提交与通知投递不是同一事务。通知失败不回滚业务。投递用 Outbox + 去重 key + 异步重试。
6. **审批隔离**：高风险操作必须走 Approval 工作流。SoD 为 Actor-level Invariant，基于 Actor ID 检查，不可通过切换 active role 绕过。
7. **RBAC/ABAC**：`FINAL_AUTHORIZATION = canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD`。禁止纯 `if hasRole(...)` 放行。
8. **参数生命周期**：Definition → Candidate → Approved → Active。保存≠生效。Approved≠Active。
9. **故障安全**：Policy 服务超时/失败时默认 fail-closed（deny）。
10. **异步安全**：Outbox/Async 重试不重复资金效果。重试使用 dedupe key。
11. **紧急操作**：影响资产/账本/资格的紧急操作默认双人授权。每笔必须有 case_id + 理由 + 影响范围 + 审计记录。
12. **API 统一响应**：所有写操作至少包含 request_id、idempotency_key、object_type、object_id、status、result_code、rule_version、parameter_release_id、snapshot_id、audit_event_id。

### 数据库规则

1. 字符集：utf8mb4 / utf8mb4_unicode_ci。引擎 InnoDB。
2. 账本表：必须 append-only，支持 reversal 引用（字段 `reversal_of` 指向上一条记录 ID）。
3. 敏感写操作：关联 audit_event_id。
4. Snapshot：历史订单通过 snapshot_id 关联参数版本。
5. 并发控制：通过 object_version 字段（int，每次更新 +1）或等效机制。
6. DDL 变更纪律：写在 `sql/` 目录下的独立 .sql 文件中，文件名包含日期，顶部注释变更原因和影响范围。
7. 主键：使用 Snowflake ID 或 UUID v4，不使用自增 ID。

### 测试规则

1. 测试框架：PHPUnit 10+，`tests/` 根目录，分 Unit / Integration / Feature 三层。
2. 必须覆盖的极端情况：
   - 用户连续双击提交（幂等验证）
   - 客户端超时但服务端已成功（idempotency-key 查询）
   - Parameter Release 在确认页停留期间变化
   - Market 在确认页停留期间进入 Locked
   - 用户余额在 quote 后变化
   - KYC/地区资格在提交瞬间变为 restricted
   - OTC 部分成交后取消剩余
   - Result 主备源冲突
   - Settlement posting 成功但通知失败
   - Refund 中途某 batch 失败
   - Correction 重复执行
   - Audit/Outbox 重放不重复资金效果
   - Policy 服务超时
3. 跨端状态一致性验收：
   - KYC 决定 → 用户端+后台同时更新
   - Robot 升级 → Level/UpgradeOrder/Ledger/PowerCap 同步
   - OTC 订单 → Order/Trade/APT 冻结/Power 冻结同步
   - Prediction → Market/Result/Settlement/Order 分别更新
   - 风险限制 → 禁用按钮 + 解释文案 + 支持入口

### 安全规则

1. 不读取或提交 `.env`、秘钥、凭证、生产密码。
2. 敏感数据脱敏：User360 中敏感字段按角色控制可见性。
3. 通知正文不包含内部风控规则、模型参数或他人数据。
4. KYC/Consent/Prediction Risk/OTC 风险等敏感文案必须由人工签核，AI 不自签 PASS。
5. 所有 Consent 类确认必须带 content_version，用户主动确认当前版本。
6. 登录失败不泄露账号是否存在等敏感判断。
7. 敏感资料严格最小化访问（KYC Reviewer 不可接触资产/Trade）。
8. 生产环境禁用热重载（`file_monitor` 进程）、debug 模式、error_reporting 详细输出。

## 基于代码的推断

- 现有 Service→DAO→Model 三层结构可直接作为 V6.1 新模块模板。
- 现有 `sys_route` 表驱动路由机制可以支撑 OpenAPI 3.1 文档自动生成。
- 现有 JWT + Casbin 认证授权体系基本满足 V6.1 的 RBAC 需求，但需新增 MFA 和 FeatureEntitlement。
- 现有 arbitrage 引擎的日程编排模式可作为 Prediction 结算引擎参考。

## 待确认事项

- [ ] CI/CD 管线的具体配置（GitHub Actions / GitLab CI 选型）
- [ ] PHPUnit 覆盖率目标和 CI 集成方案
- [ ] 前端 ESLint / Prettier / Stylelint 配置规范
- [x] Vue 3 UI 组件库：H5 = Vant 4，Admin = Element Plus（OWNER_DIRECTIVE 2026-08-12）
- [ ] Flutter 状态管理选型（Riverpod / Bloc）

## 信息来源

- `0.5代码/gainode后端/gainode/composer.json`（框架和依赖版本）
- `0.5代码/gainode后端/gainode/config/`（app.php / database.php / process.php / route/）
- `0.5代码/gainode后端/gainode/support/extend/Service.php`（Service 基类）
- `0.5代码/gainode后端/gainode/support/extend/Model.php`（Model 基类）
- `0.5代码/gainode后端/gainode/library/dict/ErrorDict.php`（错误码规范）
- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`
- `Gainode_Development_Ready_V6.1_Latest/08_VISUAL_DESIGN_SYSTEM_V2.4.md`
