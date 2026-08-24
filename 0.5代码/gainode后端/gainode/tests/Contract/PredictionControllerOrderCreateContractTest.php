<?php

declare(strict_types=1);

/**
 * NEXT-01 步骤④：PredictionController::orderCreate 控制器级真实调用契约测试。
 *
 * 替代 S02P09ControllerWiringContractTest 的静态反射弱校验：
 * 构造真实 support\Request → 真实调用控制器 → 断言 Envelope + DB 副作用。
 *
 * 覆盖分支（本轮收窄范围）：
 *   (a) 未登录（无 Token 头，真实 getTokenUser 抛 AuthorizeException）→ 断言 error Envelope
 *       （BE-11 已修复：ApiV2::envelopeError 为 AuthorizeException 经继承由 DomainException 首分支承接，
 *       映射为契约 05§7 的 AUTH_UNAUTHENTICATED/401）；
 *   (c) 合法参数 → 服务层 fail-closed：PredictionOrderService::submit() 对锁盘参数/资格/stake
 *       （06 TBC 未冻结）无条件抛 DEPENDENCY_UNAVAILABLE → 断言 503 Envelope 且无订单落库副作用。
 *       SKIP 注明：成功 + 订单落库路径在服务解冻前不可构造，非静默跳过。
 *
 * 方法约定：standalone CLI（check()/summary()，exit 0/1）；SQLite in-memory（命名 'mysql'）；
 * 登录态用 token stub 直接注入（不引入真实 JWT）；不触真实 DB/.env/网络。
 */

require __DIR__ . '/_bootstrap.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use library\dict\ErrorDict;
use support\exception\DomainException;
use Workerman\Coroutine\Context;

// ---- 生产加载语义镜像（webman 运行时经 config/autoload 'files' 加载项目版 support\Request；
//      composer psr-4 将 support\ 指向 vendor 框架类，CLI 下必须先显式加载项目版再构造请求）----
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}
require_once BASE_PATH . '/support/Request.php';
require_once BASE_PATH . '/support/Response.php';

// ---- SQLite in-memory（命名 'mysql'，对齐 Model::$connection='mysql'）----
$capsule = new Capsule(Container::getInstance());
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'prefix'   => '',
], 'mysql');
$capsule->setAsGlobal();
$capsule->bootEloquent();

$schema = Capsule::connection('mysql')->getSchemaBuilder();
$mk = function (string $table, callable $def) use ($schema) {
    if (!$schema->hasTable($table)) {
        $schema->create($table, $def);
    }
};

// AuthorizeException 构造器会经 LangKeyService 落翻译键（生产行为），SQLite 承接。
$mk('sys_lang_key', function ($t) {
    $t->increments('id');
    $t->string('name', 191)->default('');
    $t->integer('parent_id')->default(0);
    $t->string('type', 32)->nullable();
    $t->integer('sort')->default(0);
    $t->text('content')->nullable();
    $t->string('source', 64)->nullable();
    $t->integer('status')->default(1);
    $t->string('create_at', 32)->nullable();
    $t->string('update_at', 32)->nullable();
    $t->integer('created_time')->default(0);
});

$mk('prediction_orders', function ($t) {
    $t->string('order_id', 32)->primary();
    $t->string('user_id', 32);
    $t->string('market_id', 32);
    $t->string('selection', 16)->default('HOME');
    $t->string('amount_apt', 64)->default('0');
    $t->string('order_status', 24)->default('submitted');
    $t->string('asset_status', 32)->nullable();
    $t->string('risk_status', 32)->nullable();
    $t->string('consent_receipt_id', 32)->default('0');
    $t->string('submit_snapshot_id', 32)->default('0');
    $t->string('parameter_release_id', 32)->default('0');
    $t->string('policy_version', 64)->default('');
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('audit_events', function ($t) {
    $t->string('audit_event_id', 32)->primary();
    $t->string('event_code', 64);
    $t->string('actor_id', 32);
    $t->string('actor_role', 32);
    $t->string('target_object_type', 64);
    $t->string('target_object_id', 32);
    $t->string('before_snapshot_type', 32)->default('');
    $t->string('before_snapshot_id', 32)->default('0');
    $t->string('after_snapshot_type', 32)->default('');
    $t->string('after_snapshot_id', 32)->default('0');
    $t->string('outcome', 16);
    $t->string('reason_code', 64)->default('');
    $t->string('request_id', 64)->default('');
    $t->string('approval_id', 32)->default('0');
    $t->string('case_id', 32)->default('0');
    $t->integer('created_time')->default(0);
});

// CLI 下无 translation 配置；envelopeError 对非 DomainException 会调 trans() → 预置最小翻译器。
\support\Translation::instance('', [
    'locale' => 'en', 'fallback_locale' => ['en'], 'path' => [], 'loader' => [],
]);

Context::reset();

/** 构造请求并注入协程上下文（\request() 取回同一实例）。 */
function pccMakeRequest(bool $authenticated, string $body): void
{
    $tokenLine = $authenticated ? "Token: test-stub-token\r\n" : '';
    $raw = "POST /api/v1/orders HTTP/1.1\r\nHost: localhost\r\n"
        . "Content-Type: application/x-www-form-urlencoded\r\n"
        . $tokenLine
        . "\r\n" . $body;
    if ($authenticated) {
        // token stub：直接注入登录态（任务约定，不引入真实 JWT 逻辑）
        $req = new class($raw) extends \support\Request {
            public function getTokenUser($is_throw = true)
            {
                return new \stdClass(); // 已登录用户桩
            }

            public function getUserID()
            {
                return 1001;
            }
        };
    } else {
        $req = new \support\Request($raw); // 真实未登录请求：真实 getTokenUser 抛 AuthorizeException
    }
    $req->app = 'api';
    Context::set(\Webman\Http\Request::class, $req);
}

/** 真实调用 PredictionController::orderCreate()，返回 [httpStatus, envelopeArray]。 */
function pccCallOrderCreate(): array
{
    $resp = (new \app\api\controller\PredictionController())->orderCreate();
    $body = json_decode($resp->rawBody(), true);
    return [$resp->getStatusCode(), is_array($body) ? $body : []];
}

/** 断言错误 Envelope 形状 + result_code/http_status。 */
function pccExpectErrorEnvelope(array $body, int $httpStatus, string $code, string $label): void
{
    $shapeOk = array_key_exists('request_id', $body)
        && array_key_exists('result_code', $body)
        && array_key_exists('result_message', $body)
        && array_key_exists('http_status', $body)
        && array_key_exists('details', $body)
        && !array_key_exists('data_status', $body); // 成功 Envelope 专属字段不得出现
    check($shapeOk, "{$label}：错误 Envelope 五字段齐备且不含成功字段");
    check(($body['result_code'] ?? '') === $code, "{$label}：result_code={$code}（实测=" . ($body['result_code'] ?? 'null') . "）");
    check(($body['http_status'] ?? 0) === $httpStatus, "{$label}：envelope.http_status={$httpStatus}");
}

echo "=====================================================\n";
echo "Prediction orderCreate controller contract test\n";
echo "=====================================================\n\n";

$orderRows = fn (): int => Capsule::connection('mysql')->table('prediction_orders')->count();

// ======================= (a) 未登录 → 错误 Envelope =======================
echo "[a] 未登录（无 Token 头，真实 getTokenUser）→ 错误 Envelope（契约 05§7：AUTH_UNAUTHENTICATED/401）\n";
Context::set('request_id', 'REQ-PCC-A');
pccMakeRequest(false, 'market_id=M_OPEN&selection=HOME&amount_apt=100&idempotency_key=IK-PCC-A');
[$statusA, $bodyA] = pccCallOrderCreate();
check($statusA === 401, "(a) HTTP 状态=401（未认证拒绝；实测={$statusA}）");
pccExpectErrorEnvelope($bodyA, 401, ErrorDict::AUTH_UNAUTHENTICATED, '(a) 未登录');
check($orderRows() === 0, '(a) 无订单落库副作用（fail-closed）');
check(Capsule::connection('mysql')->table('sys_lang_key')->count() >= 1,
    '(a) AuthorizeException 构造路径真实执行（lang_key 翻译落库为证）');
echo "\n";

// ======================= (c) 合法参数 → fail-closed（SKIP 注明）=======================
echo "[c] 合法参数 → submit() 服务未解冻（06 TBC）无条件 fail-closed（SKIP：成功+落库路径不可构造）\n";
Context::set('request_id', 'REQ-PCC-C');
pccMakeRequest(true, 'market_id=M_OPEN&selection=HOME&amount_apt=100.000000000000000000&idempotency_key=IK-PCC-C');
[$statusC, $bodyC] = pccCallOrderCreate();
check($statusC === 503, "(c) HTTP 状态=503（实测={$statusC}）");
pccExpectErrorEnvelope($bodyC, 503, ErrorDict::DEPENDENCY_UNAVAILABLE, '(c) 合法参数 fail-closed');
check($bodyC['request_id'] === 'REQ-PCC-C', '(c) RequestContext request_id 透传到 Envelope');
check($orderRows() === 0 && Capsule::connection('mysql')->table('audit_events')->count() === 0,
    '(c) prediction_orders/audit_events 零写入（无落库副作用成立）');

try {
    (new PredictionOrderServiceProbe())->probe();
    check(false, '(c) 服务层直查应不可达');
} catch (\Throwable $e) {
    check($e instanceof DomainException && $e->resultCode() === ErrorDict::DEPENDENCY_UNAVAILABLE,
        '(c) 旁证：PredictionOrderService::submit 为服务级 unconditional fail-closed（与控制器观测一致）');
}
echo "\n";

summary();

/**
 * 旁证探针：直接调用服务层 submit，确认控制器 503 来自服务层 fail-closed 而非控制器逻辑。
 */
class PredictionOrderServiceProbe
{
    public function probe(): void
    {
        (new \library\service\prediction\PredictionOrderService())->submit(
            ['market_id' => 'M_OPEN'], 'U1001', 'END_USER'
        );
    }
}
