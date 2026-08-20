<?php

use support\extend\Route;

Route::group('/v1',function (){
    //数据库维护的路由数据
    $routes = getRouteList("admin",true);
    foreach ($routes as $v){
        $route = Route::add($v['methods'],$v['route_url'],[$v['path'],$v['action']]);
        if(!empty($v['middleware'])){
            $route->middleware($v['middleware']);
        }
    }
});

// =====================================================================
// Admin V2 路由组（OPTION_A：/api/v1/admin，Owner 裁决 ADMIN-V2-AUTH-01）
// $request->app='admin'（控制器位于 app/admin/controller/v2）→ getTokenUser() 走 AdminAuth。
// 从 sys_route module='admin_v2' 加载（url 为相对路径，组前缀 + url = /api/v1/admin/<url>）。
// =====================================================================
Route::group('/api/v1/admin', function () {
    $routes = getRouteList('admin_v2', true);
    foreach ($routes as $v) {
        $route = Route::add($v['methods'], $v['route_url'], [$v['path'], $v['action']]);
        if (!empty($v['middleware'])) {
            $route->middleware($v['middleware']);
        }
    }
});


