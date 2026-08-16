<?php

namespace support\controller;

use support\extend\Controller;
use support\Request;

/**
 * 接口访问模式controller 继承
 */
class Api extends Controller{

    /**
     * 初始化数据
     */
    public function beforeAction(Request $request)
    {
        try {
            $response = parent::beforeAction($request);
            if(!empty($response)){
                return $response;
            }
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(), $e->getCode());
        }
    }
}
