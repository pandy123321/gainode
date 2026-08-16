<?php

namespace support;
use Carbon\Carbon;
use library\dict\ErrorDict;
use library\dict\QueueDict;
use library\service\sys\RouteService;
use support\exception\VerifyException;
use support\exception\SignException;
use support\extend\Validator;
use support\utils\Data;
use support\utils\Random;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * Class Middleware
 * @package support\Middleware
 */
class Middleware  implements MiddlewareInterface
{
    public function process(Request $request, callable $next):Response
    {
        $this->setRequestParams($request);
        /** @var Response $response */
        $response = $next($request);
        return $response;
    }

    protected function setRequestParams(Request $request)
    {
//        $request->runtime = Carbon::now()->getTimestampMs();
//        $request->trace_id = Random::guid(\request());
    }

    private function getRouteKey(Request $request){
        $app = $request->app;
        $arr = explode('\\',str_replace('Controller', '', $request->controller));
        $action = $request->action;
        $controller = array_pop($arr);
        $url = '/'.$app.'/'.ucfirst($controller).'/'.$action;
        $route_key = route_key($url);
        return $route_key;
    }

    /**
     * 验证用户权限
     * @param $type
     * @throws VerifyException
     * @throws \Casbin\Exceptions\CasbinException
     * @throws \ReflectionException
     */
    public function verifyUserGrant(Request $request,$type="rbac"){
        $route_key = $this->getRouteKey($request);
        $verify = (new RouteService())->getRouteVerify($route_key);
        $method = $request->method();
        if($verify>0){
            $loginUser = getTokenUser();
            if($request->app=='admin'){ //后台权限验证

            }
        }
        elseif($verify<0){
            throw new VerifyException('接口不存在');
        }
    }

    /**
     * 验证签名
     */
    protected function verifyRequestSign(Request $request){
        $request = \request();
        $params =  validation_sign($request->app);
        if(is_array($params) && in_array("Sign",$params)){
            $url_expire = config('app.url_expire');
            $headers = [];
            foreach($params as $key){
                if($key=="Language"){
                    $headers['Language'] = $request->getLanguage();
                }
                else{
                    $headers[$key] = $request->header($key);
                }
            }
            $validator = new Validator();
            if(!empty($headers['Language'])){
                $validator->setLanguage($headers['Language']);
            }
            $res = $validator->verifyHeaderData($headers,$request->app);
            if(!$res){
                $message = $validator->getMessage();
                if(is_string($message)){
                    throw new VerifyException($message);
                }
                else{
                    $msg = '';
                    foreach($message as $k=>$v){
                        if(empty($msg)){
                            $msg = $v[0];
                        }
                    }
                    throw new VerifyException($msg);
                }
            }
            $sign = Data::sign($headers);
            if (abs(time() - $headers['Timestamp']) > $url_expire) {
                throw new VerifyException('时间戳异常');
            }
            elseif ($sign != $headers['Sign']) {
                throw new SignException('签名错误',ErrorDict::ParameterSignatureError);
            }
        }
    }

    /**
     * 写日志
     */
    protected function writeRequestLog(Request $request)
    {
        $request = \request();
        echo date('Y-m-d H:i:s').' '.$request->app.' '.$request->method().' '.$request->uri().PHP_EOL;
        if (write_operation_log($request)) {
            $requestData = $request->post();
            $data = [
                'module' => $request->app,
                'request_url' => $request->uri(),
                'request_method' => $request->method(),
                'refer_url' => $request->header("referer"),
                'client_ip' => $request->getRealIp(),
                'request_date' => date('Y-m-d'),
                'user_id' => $request->getUserID(),
                'request_data' => $requestData
            ];
            pushQueue(QueueDict::QUEUE_WRITE_LOGS, $data);
        }
    }
}
