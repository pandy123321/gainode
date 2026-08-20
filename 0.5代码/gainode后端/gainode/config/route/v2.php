<?php

use support\extend\Route;

// =====================================================================
// Gainode 2.0 V2 API 路由组（OPTION_A：/api/v1，Owner 裁决 V2-ROUTE-PATH-01）
//
// 契约 05 / OpenAPI / 前端 base 均为 /api/v1。本组固定前缀 /api/v1，
// 从 sys_route 表 module='api_v2' 加载 V2 路由（url 为相对路径）。
// 中间件：Cors + RequestContext + ActionHook 已由 config/middleware.php
// 'api' 应用全局注入；鉴权由控制器内部 getTokenUser() 强制。
// =====================================================================
Route::group('/api/v1', function () {
    $routes = getRouteList('api_v2', true);
    foreach ($routes as $v) {
        $route = Route::add($v['methods'], $v['route_url'], [$v['path'], $v['action']]);
        if (!empty($v['middleware'])) {
            $route->middleware($v['middleware']);
        }
    }
});
