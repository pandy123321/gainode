<?php
namespace support\middleware;

use Carbon\Carbon;
use support\Middleware;
use Webman\Http\Request;
use Webman\Http\Response;


class Cors extends Middleware
{
    public function process(Request $request, callable $next):Response
    {
        $this->setRequestParams($request);
        $response = $request->method() == 'OPTIONS' ? response('') : $next($request);
        $origin = $request->header('Origin', '*');
        $response->withHeaders([
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Max-Age' => '3600',
            'Access-Control-Allow-Headers' => 'Content-Type,X-Requested-With,Accept,Origin,Access-Control-Allow-Headers,Authorization,Token,Sign',
        ]);
        return $response;
    }
}
