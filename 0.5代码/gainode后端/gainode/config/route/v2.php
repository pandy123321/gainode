<?php

use support\extend\Route;

// =====================================================================
// Gainode 2.0 V2 API 路由组（OPTION_A：/api/v1，Owner 裁决 V2-ROUTE-PATH-01）
//
// 契约 05 / OpenAPI / 前端 base 均为 /api/v1。本组固定前缀 /api/v1，
// 从 sys_route 表 module='api_v2' 加载 V2 路由（url 为相对路径）。
// 中间件：Cors + RequestContext + ActionHook 已由 config/middleware.php
// 'api' 应用全局注入；DR-04：额外挂 V2Auth 统一鉴权（公开端点白名单放行，
// 其余强制 getTokenUser()），防止新增端点漏挂鉴权。
// =====================================================================
Route::group('/api/v1', function () {
    $routes = getRouteList('api_v2', true);
    foreach ($routes as $v) {
        $route = Route::add($v['methods'], $v['route_url'], [$v['path'], $v['action']]);
        // DR-04：V2 统一鉴权（V2Auth 内部按 controller::action 白名单放行公开端点）
        $route->middleware([support\middleware\V2Auth::class]);
        if (!empty($v['middleware'])) {
            $route->middleware($v['middleware']);
        }
    }
});
