<?php

namespace support\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * V2 统一鉴权中间件（DR-04）。
 *
 * 目的：防止新增 /api/v1 V2 端点漏挂 getTokenUser() 造成鉴权遗漏。
 * 对进入本中间件的请求强制调用 $request->getTokenUser()（按 $request->app
 * 自动路由到 MemberAuth(api)/AdminAuth(admin)）；公开端点（白名单）放行。
 *
 * 白名单：按 controller+action 匹配（如 AuthController::login）；
 * 未命中白名单即视为需鉴权，令牌缺失/过期抛 AuthorizeException
 * （映射 AUTH_UNAUTHENTICATED/401，由异常处理器转 envelope）。
 *
 * fail-closed：白名单外一律要求有效令牌。
 */
class V2Auth implements MiddlewareInterface
{
    /**
     * 公开端点白名单（controller::action，无需登录）。
     * 控制台/文档：C 端 register/login/otp / mfaVerify/refresh/recovery/passwordReset，共 9 个。
     * 注：仅列出 api_v2 路由中真实的公开端点（logout/sessions/sessionRevoke 需鉴权）。
     *
     * @var string[]
     */
    public const PUBLIC = [
        'AuthController::register',
        'AuthController::login',
        'AuthController::otpVerify',
        'AuthController::otpResend',
        'AuthController::mfaVerify',
        'AuthController::refresh',
        'AuthController::recovery',
        'AuthController::passwordReset',
    ];

    public function process(Request $request, callable $next): Response
    {
        $key = $this->guardKey($request);
        if (!in_array($key, self::PUBLIC, true)) {
            // 需鉴权：getTokenUser() 内部按 app 路由到对应 Auth；无效令牌抛
            // AuthorizeException(AUTH_UNAUTHENTICATED/401)。此调用会缓存当前令牌用户。
            $request->getTokenUser();
        }
        return $next($request);
    }

    /**
     * 生成 controller::action 白名单键。
     */
    private function guardKey(Request $request): string
    {
        $controller = $request->controller;
        if (strpos($controller, '\\') !== false) {
            $parts = explode('\\', $controller);
            $controller = end($parts);
        }
        return $controller . '::' . $request->action;
    }
}
