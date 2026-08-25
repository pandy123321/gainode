<?php

declare(strict_types=1);

// CLI 契约测试：V2Auth 统一鉴权中间件（DR-04）
// 用法：php tests/Contract/V2AuthMiddlewareContractTest.php
require __DIR__ . '/_bootstrap.php';

use support\middleware\V2Auth;

check(class_exists(\support\middleware\V2Auth::class), 'V2Auth middleware exists');

// ---- 1. PUBLIC 白名单：公开端点放行（next 被调用，不抛） ----
$publicKey = V2Auth::PUBLIC;
check(is_array($publicKey) && count($publicKey) === 8, 'PUBLIC whitelist has 8 public endpoints');
check(in_array('AuthController::register', $publicKey, true), 'PUBLIC contains register');
check(in_array('AuthController::login', $publicKey, true), 'PUBLIC contains login');
check(in_array('AuthController::refresh', $publicKey, true), 'PUBLIC contains refresh');
check(in_array('AuthController::passwordReset', $publicKey, true), 'PUBLIC contains passwordReset');
check(!in_array('AuthController::logout', $publicKey, true), 'PUBLIC excludes logout (needs auth)');
check(!in_array('AuthController::sessions', $publicKey, true), 'PUBLIC excludes sessions (needs auth)');
check(!in_array('AuthController::sessionRevoke', $publicKey, true), 'PUBLIC excludes sessionRevoke (needs auth)');

// ---- 2. fail-closed：非公开端点、无有效 token → getTokenUser 抛 AuthorizeException ----
// 用真实 Request（app=api，controller=OtcController，action=orderCreate），无 Token 头
// → V2Auth.process 内部 getTokenUser() 抛 AUTH_UNAUTHENTICATED。
$raw = "POST /api/v1/otc/orders HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n";
$req = new \support\Request($raw);
$req->app = 'api';
$req->controller = 'app\\api\\controller\\OtcController';
$req->action = 'orderCreate';

$threw = false;
try {
    $mw = new V2Auth();
    $mw->process($req, function () {
        return \response('ok', 200);
    });
} catch (\support\exception\AuthorizeException $e) {
    $threw = true;
    check($e->resultCode() === \library\dict\ErrorDict::AUTH_UNAUTHENTICATED, 'no-token non-public throws AUTH_UNAUTHENTICATED');
} catch (\Throwable $e) {
    $threw = true;
}
check($threw, 'no-token non-public request is rejected (fail-closed)');

// ---- 3. 公开端点：真实 Request 无 Token → 不抛，next 被调用 ----
$pubReq = new \support\Request($raw);
$pubReq->app = 'api';
$pubReq->controller = 'app\\api\\controller\\AuthController';
$pubReq->action = 'login';
$called = false;
try {
    $mw = new V2Auth();
    $resp = $mw->process($pubReq, function () use (&$called) {
        $called = true;
        return \response('ok', 200);
    });
    check($called && $resp->getStatusCode() === 200, 'public endpoint passes through without token');
} catch (\Throwable $e) {
    check(false, 'public endpoint should NOT throw: ' . $e->getMessage());
}

summary('V2Auth');
