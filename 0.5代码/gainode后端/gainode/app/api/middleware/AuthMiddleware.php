<?php

namespace app\api\middleware;

use Carbon\Carbon;
use support\exception\VerifyException;
use support\Middleware;
use Webman\Http\Request;
use Webman\Http\Response;

class AuthMiddleware extends Middleware
{
    public function process(Request $request, callable $next):Response
    {
        try{
            $this->writeRequestLog($request);
            $this->setRequestParams($request);
//            $this->verifyRequestSign($request);
//            $this->verifyUserGrant($request,'restful');
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
