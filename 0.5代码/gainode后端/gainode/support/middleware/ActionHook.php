<?php

namespace support\middleware;

use Carbon\Carbon;
use support\Container;
use support\Middleware;
use Webman\Http\Request;
use Webman\Http\Response;
use Workerman\Coroutine\Context;

class ActionHook extends Middleware
{

    public function process(Request $request, callable $next): Response
    {
        $this->setRequestParams($request);
        if ($request->controller) {
            $this->initDomainData($request);
            // 禁止直接访问beforeAction afterAction
            if ($request->action === 'beforeAction' || $request->action === 'afterAction') {
                return response('<h1>404 Not Found</h1>', 404);
            }
            $controller = Container::get($request->controller);
            if (method_exists($controller, 'beforeAction')) {
                $before_response = call_user_func([$controller, 'beforeAction'], $request);
                if ($before_response instanceof Response) {
                    return $before_response;
                }
            }
            $response = $next($request);
            if (method_exists($controller, 'afterAction')) {
                $after_response = call_user_func([$controller, 'afterAction'], $request, $response);
                if ($after_response instanceof Response) {
                    return $after_response;
                }
            }
            return $response;
        }
        return $next($request);
    }

    private function initDomainData(Request $request)
    {
//        Context::set('eid', rand(1,5));
    }
}
