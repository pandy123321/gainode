<?php

use support\extend\Route;

//数据库维护的路由数据
$routes = getRouteList("api",true);

Route::group('/v1',function (){
    //数据库维护的路由数据
    $routes = getRouteList("api",true);
    foreach ($routes as $v){
        $route = Route::add($v['methods'],$v['route_url'],[$v['path'],$v['action']]);
        if(!empty($v['middleware'])){
            $route->middleware($v['middleware']);
        }
    }
});

// 接口文档中心（Basic 认证保护）
Route::group('/v1/doc', function () {
    Route::get('', [app\api\controller\DocController::class, 'index']);
    Route::get('/index', [app\api\controller\DocController::class, 'index']);
    Route::get('/modules', [app\api\controller\DocController::class, 'modules']);
    Route::get('/list', [app\api\controller\DocController::class, 'list']);
    Route::get('/detail/{id}', [app\api\controller\DocController::class, 'detail']);
    Route::post('/sign', [app\api\controller\DocController::class, 'sign']);
    Route::get('/{key}', [app\api\controller\DocController::class, 'detailPage']);
})->middleware([support\middleware\BasicAuth::class]);
