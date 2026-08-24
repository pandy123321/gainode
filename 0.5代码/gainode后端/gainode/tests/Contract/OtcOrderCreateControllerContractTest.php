<?php

declare(strict_types=1);

/**
 * NEXT-01 步骤④：OtcController::orderCreate 控制器级真实调用契约测试。
 *
 * 覆盖分支：
 *   (a) 未登录（无 Token 头，真实 getTokenUser 抛 AuthorizeException）→ 断言 error Envelope；
 *       BE-11 已修复：ApiV2::envelopeError 为 AuthorizeException 经继承由 DomainException 首分支承接，
 *       映射为契约 05§7 的 AUTH_UNAUTHENTICATED/401。
 *   (c) 合法参数 → 服务层 fail-closed：OtcOrderService::createOrder() 对
 *       order_min/max_amount+fee_rate+inventory_limit+Power freeze（06 TBC）无条件抛
 *       DEPENDENCY_UNAVAILABLE → 断言 503 Envelope 且 otc_orders 零落库副作用。
 *   注：(b)（资格缺失）与 (c) 同路径——服务层在任何资格校验前即 fail-closed，
 *   TBC 冻结后补真分支。
 *
 * 认证上下文：token stub 匿名类直接注入登录态（任务约定，不引入真实 JWT）。
 */

require __DIR__ . '/_bootstrap.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use library\dict\ErrorDict;
use support\exception\DomainException;
use Workerman\Coroutine\Context;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}
require_once BASE_PATH . '/support/Request.php';
require_once BASE_PATH . '/support/Response.php';

$capsule = new Capsule(Container::getInstance());
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'mysql');
$capsule->setAsGlobal();
$capsule->bootEloquent();

$schema = Capsule::connection('mysql')->getSchemaBuilder();
$mk = function (string $table, callable $def) use ($schema) {
    if (!$schema->hasTable($table)) {
        $schema->create($table, $def);
    }
};

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

$mk('otc_orders', function ($t) {
    $t->string('otc_order_id', 32)->primary();
    $t->string('user_id', 32);
    $t->string('side', 8)->default('BUY');
    $t->string('price', 32)->default('0');
    $t->string('quantity_apt', 64)->default('0');
    $t->string('filled_quantity_apt', 64)->default('0');
    $t->string('remaining_quantity_apt', 64)->default('0');
    $t->string('fee_apt', 64)->default('0');
    $t->string('power_required', 32)->default('0');
    $t->string('power_consumed', 32)->default('0');
    $t->string('power_frozen', 32)->default('0');
    $t->string('status', 24)->default('draft');
    $t->integer('review_required')->default(0);
    $t->string('quote_id', 32)->default('0');
    $t->string('snapshot_id', 32)->default('0');
    $t->string('rule_version', 64)->default('');
    $t->string('parameter_release_id', 32)->default('0');
    $t->string('policy_version', 64)->default('');
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

\support\Translation::instance('', [
    'locale' => 'en', 'fallback_locale' => ['en'], 'path' => [], 'loader' => [],
]);

Context::reset();

function otcMakeRequest(bool $authenticated, string $body): void
{
    $tokenLine = $authenticated ? "Token: test-stub-token\r\n" : '';
    $raw = "POST /api/v1/otc/orders HTTP/1.1\r\nHost: localhost\r\n"
        . "Content-Type: application/x-www-form-urlencoded\r\n"
        . $tokenLine
        . "\r\n" . $body;
    if ($authenticated) {
        $req = new class($raw) extends \support\Request {
            public function getTokenUser($is_throw = true)
            {
                return new \stdClass();
            }

            public function getUserID()
            {
                return 2002;
            }
        };
    } else {
        $req = new \support\Request($raw);
    }
    $req->app = 'api';
    Context::set(\Webman\Http\Request::class, $req);
}

function otcCallOrderCreate(): array
{
    $resp = (new \app\api\controller\OtcController())->orderCreate();
    $body = json_decode($resp->rawBody(), true);
    return [$resp->getStatusCode(), is_array($body) ? $body : []];
}

function otcExpectErrorEnvelope(array $body, int $httpStatus, string $code, string $label): void
{
    $shapeOk = array_key_exists('request_id', $body)
        && array_key_exists('result_code', $body)
        && array_key_exists('result_message', $body)
        && array_key_exists('http_status', $body)
        && array_key_exists('details', $body)
        && !array_key_exists('data_status', $body);
    check($shapeOk, "{$label}：错误 Envelope 五字段齐备且不含成功字段");
    check(($body['result_code'] ?? '') === $code, "{$label}：result_code={$code}（实测=" . ($body['result_code'] ?? 'null') . "）");
    check(($body['http_status'] ?? 0) === $httpStatus, "{$label}：envelope.http_status={$httpStatus}");
}

echo "=====================================================\n";
echo "OTC orderCreate controller contract test\n";
echo "=====================================================\n\n";

$orderRows = fn (): int => Capsule::connection('mysql')->table('otc_orders')->count();

// ======================= (a) 未登录 → 错误 Envelope =======================
echo "[a] 未登录 → 错误 Envelope（契约 05§7：AUTH_UNAUTHENTICATED/401）\n";
Context::set('request_id', 'REQ-OTC-A');
otcMakeRequest(false, 'side=BUY&price=10&quantity_apt=5&idempotency_key=IK-OTC-A');
[$statusA, $bodyA] = otcCallOrderCreate();
check($statusA === 401, "(a) HTTP 状态=401（未认证拒绝；实测={$statusA}）");
otcExpectErrorEnvelope($bodyA, 401, ErrorDict::AUTH_UNAUTHENTICATED, '(a) 未登录');
check($orderRows() === 0, '(a) 无挂单落库副作用');
echo "\n";

// ======================= (c) 合法参数 → fail-closed（SKIP 注明；(b)(c) 同路径）=======================
echo "[c] 合法参数 → createOrder() TBC 未冻结无条件 fail-closed（SKIP：成功+落库路径不可构造；\n";
echo "    (b)(c) 同路径，TBC 冻结后补真分支）\n";
Context::set('request_id', 'REQ-OTC-C');
otcMakeRequest(true, 'side=BUY&price=10&quantity_apt=5&idempotency_key=IK-OTC-C');
[$statusC, $bodyC] = otcCallOrderCreate();
check($statusC === 503, "(c) HTTP 状态=503（实测={$statusC}）");
otcExpectErrorEnvelope($bodyC, 503, ErrorDict::DEPENDENCY_UNAVAILABLE, '(c) 合法参数 fail-closed');
check($bodyC['request_id'] === 'REQ-OTC-C', '(c) RequestContext request_id 透传到 Envelope');
check($orderRows() === 0, '(c) otc_orders 零写入（无落库副作用负向断言成立）');

try {
    (new OtcOrderServiceProbe())->probe();
    check(false, '(c) 服务层直查应不可达');
} catch (\Throwable $e) {
    check($e instanceof DomainException && $e->resultCode() === ErrorDict::DEPENDENCY_UNAVAILABLE,
        '(c) 旁证：OtcOrderService::createOrder 为服务级 unconditional fail-closed（与控制器观测一致）');
}
echo "\n";

summary();

class OtcOrderServiceProbe
{
    public function probe(): void
    {
        (new \library\service\otc\OtcOrderService())->createOrder(
            ['side' => 'BUY', 'price' => '10'], 'U2002', 'END_USER'
        );
    }
}
