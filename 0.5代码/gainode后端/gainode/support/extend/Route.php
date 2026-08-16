<?php

namespace support\extend;


class Route extends \Webman\Route
{
    public static function getRouteList()
    {
        $list = [];
        $routeList = self::$_collector->getData();
        foreach($routeList as $key=>$route){
            foreach($route as $k=>$v){
                foreach ($v as $k1=>$v1){
                    if(isset($v1['route'])){
                        $list[] = [
                            'path'=>$v1['route']->getPath(),
                            'method'=>$v1['route']->getMethods(),
                            'callback'=>$v1['route']->getCallback(),
                            'middleware'=>$v1['route']->getMiddleware()
                        ];
                    }
                }
            }
        }
        return $list;
    }
}