<?php

namespace app\common\middleware;

use Carbon\Carbon;
use support\Middleware;
use Webman\Http\Request;
use Webman\Http\Response;

class AuthMiddleware extends Middleware
{

    public function process(Request $request, callable $next):Response
    {
        try{
            $this->setRequestParams($request);
            $this->verifyRequestSign($request);
            $this->writeRequestLog($request);
            return $next($request);
        }
        catch (\Throwable $e){
            return failJson(405,[
                'data' => [],
                'code' => -1,
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
