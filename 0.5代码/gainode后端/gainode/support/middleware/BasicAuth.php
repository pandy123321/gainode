<?php

namespace support\middleware;

use support\Middleware;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * HTTP Basic 认证中间件
 */
class BasicAuth extends Middleware
{
    public function process(Request $request, callable $next): Response
    {
        $username = config('plugin.basic_auth.username', 'admin');
        $password = config('plugin.basic_auth.password', '');

        $auth = $request->header('Authorization', '');
        if (preg_match('/^Basic\s+(.+)$/i', $auth, $matches)) {
            $credentials = base64_decode($matches[1]);
            if ($credentials === "$username:$password") {
                return $next($request);
            }
        }

        return new Response(401, [
            'WWW-Authenticate' => 'Basic realm="接口文档中心"',
            'Content-Type' => 'text/html; charset=utf-8',
        ], '<h2>401 Unauthorized</h2><p>需要认证才能访问接口文档</p>');
    }
}
