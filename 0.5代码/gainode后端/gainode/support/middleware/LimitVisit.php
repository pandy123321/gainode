<?php

namespace support\middleware;

use Carbon\Carbon;
use support\extend\RateLimiter;
use support\Middleware;
use Webman\Http\Request;
use Webman\Http\Response;

class LimitVisit extends Middleware
{
    /**
     * @param Request $request
     * @param callable $handler
     * @return Response
     */
    public function process(Request $request, callable $next):Response
    {
        $this->setRequestParams($request);
        if ($result = RateLimiter::traffic()) {
            return new Response($result['status'], [
                'Content-Type' => 'application/json',
                'X-Rate-Limit-Limit' => $result['limit'],
                'X-Rate-Limit-Remaining' => $result['remaining'],
                'X-Rate-Limit-Reset' => $result['reset']
            ], json_encode($result['body']));
        }
        return $next($request);
    }
}
