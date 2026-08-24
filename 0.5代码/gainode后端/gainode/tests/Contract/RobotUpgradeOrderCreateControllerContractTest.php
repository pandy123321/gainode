<?php

declare(strict_types=1);

/**
 * NEXT-01 步骤④：RobotController::upgradeOrderCreate 控制器级真实调用契约测试。
 *
 * 覆盖分支：
 *   (a) 未登录（无 Token 头，真实 getTokenUser 抛 AuthorizeException）→ 断言 error Envelope；
 *       BE-11 已修复：ApiV2::envelopeError 为 AuthorizeException 经继承由 DomainException 首分支承接，
 *       映射为契约 05§7 的 AUTH_UNAUTHENTICATED/401。
 *   (c) 合法参数（S02-P04 升级成本 AI.upgrade_apt_requirement / allocation_profile 未冻结）
 *       → 预期 fail-closed：断言 DEPENDENCY_UNAVAILABLE（POLICY_DENIED/DEPENDENCY_UNAVAILABLE 类，
 *       任务指定）503 Envelope 且 robot_upgrade_orders 零落库副作用。
 *   注：(b)（robot 不存在等不利输入）与 (c) 同路径——服务层 submit 在任何存在性校验前即
 *   fail-closed，TBC 冻结后补真分支。
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

$mk('robot_upgrade_orders', function ($t) {
    $t->string('upgrade_order_id', 32)->primary();
    $t->string('robot_id', 32);
    $t->string('user_id', 32);
    $t->integer('from_level')->default(1);
    $t->integer('to_level')->default(1);
    $t->string('apt_cost', 64)->default('0');
    $t->string('status', 16);
    $t->string('power_cap_after', 64)->default('0');
    $t->text('capacities_after')->nullable();
    $t->integer('cooling_end_at')->default(0);
    $t->string('review_case_id', 32)->default('0');
    $t->string('approval_id', 32)->default('0');
    $t->string('ledger_entry_id', 32)->default('0');
    $t->string('rule_version', 64)->default('');
    $t->string('parameter_release_id', 32)->default('0');
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

function robMakeRequest(bool $authenticated, string $body): void
{
    $tokenLine = $authenticated ? "Token: test-stub-token\r\n" : '';
    $raw = "POST /api/v1/ai/users/U3001/upgrade-orders HTTP/1.1\r\nHost: localhost\r\n"
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
                return 3001;
            }
        };
    } else {
        $req = new \support\Request($raw);
    }
    $req->app = 'api';
    Context::set(\Webman\Http\Request::class, $req);
}

/**
 * 真实调用 RobotController::upgradeOrderCreate(string $id)，返回 [httpStatus, envelopeArray]。
 */
function robCallUpgradeOrderCreate(string $robotId): array
{
    $resp = (new \app\api\controller\RobotController())->upgradeOrderCreate($robotId);
    $body = json_decode($resp->rawBody(), true);
    return [$resp->getStatusCode(), is_array($body) ? $body : []];
}

function robExpectErrorEnvelope(array $body, int $httpStatus, string $code, string $label): void
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
echo "Robot upgradeOrderCreate controller contract test\n";
echo "=====================================================\n\n";

$orderRows = fn (): int => Capsule::connection('mysql')->table('robot_upgrade_orders')->count();

// ======================= (a) 未登录 → 错误 Envelope =======================
echo "[a] 未登录 → 错误 Envelope（契约 05§7：AUTH_UNAUTHENTICATED/401）\n";
Context::set('request_id', 'REQ-ROB-A');
robMakeRequest(false, 'to_level=2&idempotency_key=IK-ROB-A');
[$statusA, $bodyA] = robCallUpgradeOrderCreate('ROBOT_3001');
check($statusA === 401, "(a) HTTP 状态=401（未认证拒绝；实测={$statusA}）");
robExpectErrorEnvelope($bodyA, 401, ErrorDict::AUTH_UNAUTHENTICATED, '(a) 未登录');
check($orderRows() === 0, '(a) 无升级单落库副作用');
echo "\n";

// ======================= (c) S02-P04 未冻结 → 预期 fail-closed（(b)(c) 同路径）=======================
echo "[c] 合法参数（S02-P04 成本/资金去向 TBC 未冻结）→ 预期 fail-closed DEPENDENCY_UNAVAILABLE/503\n";
echo "    （(b)(c) 同路径，TBC 冻结后补真分支）\n";
Context::set('request_id', 'REQ-ROB-C');
robMakeRequest(true, 'to_level=2&idempotency_key=IK-ROB-C');
[$statusC, $bodyC] = robCallUpgradeOrderCreate('ROBOT_3001');
check($statusC === 503, "(c) HTTP 状态=503（实测={$statusC}）");
robExpectErrorEnvelope($bodyC, 503, ErrorDict::DEPENDENCY_UNAVAILABLE, '(c) 未冻结 fail-closed');
check($bodyC['request_id'] === 'REQ-ROB-C', '(c) RequestContext request_id 透传到 Envelope');
check($orderRows() === 0, '(c) robot_upgrade_orders 零写入（无落库副作用负向断言成立）');

try {
    (new RobotUpgradeOrderServiceProbe())->probe();
    check(false, '(c) 服务层直查应不可达');
} catch (\Throwable $e) {
    check($e instanceof DomainException && $e->resultCode() === ErrorDict::DEPENDENCY_UNAVAILABLE,
        '(c) 旁证：RobotUpgradeOrderService::submit 为服务级 unconditional fail-closed（与控制器观测一致）');
}
echo "\n";

summary();

class RobotUpgradeOrderServiceProbe
{
    public function probe(): void
    {
        (new \library\service\robot\RobotUpgradeOrderService())->submit(
            'ROBOT_3001', 2, 'U3001', 'END_USER'
        );
    }
}
