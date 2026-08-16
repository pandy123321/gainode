<?php
namespace support\middleware;

use Carbon\Carbon;
use support\exception\AuthorizeException;
use support\extend\Validator;
use support\Middleware;
use support\utils\Data;
use Webman\Http\Request;
use Webman\Http\Response;


class VerifySign extends Middleware
{
    public function process(Request $request, callable $next):Response
    {
        try{
            $this->setRequestParams($request);
            $this->verifyRequestSign($request);
            return $next($request);
        }
        catch (\Throwable $e){
            return failJson(405,[
                'data' => [],
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
