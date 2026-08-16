<?php

namespace app\common\controller;

use library\service\sys\LangService;
use support\extend\Controller;
use support\Request;

class LangController extends Controller
{

    public function list(Request $request)
    {
        try{
            $langService = new LangService();
            $data = $langService->getLangList();
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
