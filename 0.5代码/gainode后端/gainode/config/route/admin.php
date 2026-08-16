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


