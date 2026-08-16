<?php

namespace app\common\controller;

use library\service\sys\ShortUrlService;
use support\exception\VerifyException;
use support\extend\Controller;
use support\Request;

class ShortUrlController extends Controller
{

    public function __construct(){
        $this->service = new ShortUrlService();
        parent::__construct();
    }

    public function link(Request $request,string $code)
    {
        try{
            $url = $this->service->toLong($code);
            if(empty($url)){
                throw new VerifyException('链接不存在');
            }
            return $this->redirect($url);
        }
        catch (\Throwable $e){
            return $this->failJson(null,$e->getMessage(),$e->getCode());
        }
    }
}
