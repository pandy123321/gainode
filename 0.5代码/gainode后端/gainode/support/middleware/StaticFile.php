<?php

namespace support\middleware;

use Carbon\Carbon;
use support\Middleware;
use Webman\Http\Request;
use Webman\Http\Response;


/**
 * Class StaticFile
 * @package app\middleware
 */
class StaticFile  extends Middleware
{
    public function process(Request $request, callable $next):Response
    {
        $this->setRequestParams($request);
        // Access to files beginning with. Is prohibited
        if (strpos($request->path(), '/.') !== false) {
            return response('<h1>403 forbidden</h1>', 403);
        }
        /** @var Response $response */
        $response = $next($request);
        // Add cross domain HTTP header
        /*$response->withHeaders([
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Credentials' => 'true',
        ]);*/
        return $response;
    }
}
