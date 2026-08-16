<?php

namespace support\extend;

use Carbon\Carbon;
use library\dict\ErrorDict;
use library\dict\QueueDict;
use support\exception\VerifyException;
use support\Request;
use support\Response;

class Controller
{
    /**
     * 验证对象
     * @var Validator
     */
    protected $validation;

    /**
     * 服务层对象
     * @var Service
     */
    protected $service;

    /**
     * @var Request
     */
    protected $request;

    public function __construct()
    {
        $this->request = \request();
    }

    private function writeLogs()
    {
        if(write_operation_log($this->request)){
            $requestData = $this->request->post();
            $data = [
                'app'=>$this->request->app,
                'request_url'=>$this->request->uri(),
                'request_method'=>$this->request->method(),
                'refer_url'=> $this->request->header("referer"),
                'client_ip'=> $this->request->getRealIp(),
                'request_date'=>date('Y-m-d'),
                'user_id'=>$this->request->getUserID(),
                'request_data'=> $requestData
            ];
            pushQueue(QueueDict::QUEUE_WRITE_LOGS,$data);
        }
    }

    public function beforeAction(Request $request){
        try{
            $language = $request->header('Language');
            if(!empty($this->validation)){
                if(!empty($language)){
                    $this->validation->setLanguage($language);
                }
                $res = $this->validation->verifyRequestData($request->action,$request->all());
                if(!$res){
                    $message = $this->validation->getMessage();
                    if(is_string($message)){
                        return $this->failJson([],$message,ErrorDict::ParameterInformationError);
                    }
                    else{
                        $msg = '';
                        foreach($message as $k=>$v){
                            if(empty($msg)){
                                $msg = $v[0];
                            }
                        }
                        return $this->failJson($message,$msg,ErrorDict::ParameterInformationError);
                    }
                }
            }
            locale($language);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    public function afterAction(Request $request){}

    /**
     * 获取所有的请求数据
     * @param $name
     * @param null $default
     */
    protected function getAllRequest(string $type='search',array $filter=[]){
        $params = $this->request->all();
        if($type=='search'){
            if(!empty($params)){
                foreach($params as $k=>$v){
                    if(!empty($filter) && in_array($k,$filter)){
                        unset($params[$k]);
                    }
                    elseif($v=='' || is_null($v)){
                        $params[$k] = $v;
                    }
                }
            }
            if(!empty($params['searchType']) && isset($params['searchValue'])){
                $params[$params['searchType']] = $params['searchValue'];
            }
            if(!isset($params['page'])){
                $params['page'] = 1;
            }
            if(!isset($params['size'])){
                $params['size'] = 10;
            }
        }
        else{
            foreach($params as $k=>$v){
                if($v===''){
                    unset($params[$k]);
                }
            }
        }
        return $params;
    }

    /**
     * 获取排序字段
     * @return array
     */
    protected function getSortArray($name='sort'){
        $sort = $this->getParams($name);
        if(empty($sort)){
            $sort = $this->getPost($name);
        }
        if(!empty($sort)){
            $arr = explode('-',$sort);
            if(count($arr)==2 && in_array($arr[1],['asc','desc'])){
                return [$arr[0]=>$arr[1]];
            }
        }
        return [];
    }

    protected function createSearchListArray($fields=[],$rules=[]){
        $fields = array_merge($fields,['page','size']);
        if($this->request->isPost()){
            $params = $this->getPost($fields);
        }
        else{
            $params = $this->getParams($fields);
        }
        foreach($params as $key=>$val){
            if(isset($rules[$key])){
                $params[$key] = [$rules[$key],$val];
            }
        }
        return $params;
    }

    /**
     * 获取GET请求的数据
     * @param string $name 指定字段
     * @param string $default 默认值
     */
    protected function getParams($name = null, $default = null)
    {
        if(is_array($name)){
            $params = [];
            foreach($name as $key=>$v){
                if(is_string($key)){
                    $value = $this->request->get($key,$v);
                    if(!is_null($value) && $value!=''){
                        $params[$key] = $value;
                    }
                }
                else{
                    $value = $this->request->get($v);
                    if(!is_null($value) && $value!=''){
                        $params[$v] = $value;
                    }
                }
            }
        }
        elseif(is_string($name)){
            $params = $this->request->get($name,$default);
        }
        else{
            $params = $this->request->get();
            if(!empty($params['searchType']) && isset($post['searchValue'])){
                $params[$params['searchType']] = $params['searchValue'];
            }
        }
        return $params;
    }

    /**
     * 获取请求的POST数据（自动XSS过滤）
     */
    protected function getPost($name = null,$default=null){
        if(is_array($name)){
            $post = [];
            foreach($name as $key=>$v){
                if(is_string($key)){
                    $value = $this->request->post($key,$v);
                    if(!is_null($value)){
                        $post[$key] = $this->xssClean($value);
                    }
                }
                else{
                    $value = $this->request->post($v);
                    if(!is_null($value)){
                        $post[$v] = $this->xssClean($value);
                    }
                }
            }
        }
        elseif(is_string($name)){
            $post = $this->request->post($name,$default);
            $post = $this->xssClean($post);
        }
        else{
            $post = $this->request->post();
            if(!empty($post['searchType']) && isset($post['searchValue'])){
                $post[$post['searchType']] = $post['searchValue'];
            }
            $post = $this->xssClean($post);
        }
        return $post;
    }

    /**
     * XSS 过滤
     */
    protected function xssClean($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'xssClean'], $data);
        }
        if (!is_string($data)) {
            return $data;
        }
        $data = preg_replace(
            '/<\s*(script|iframe|object|embed|form|link|style|applet|meta|frame|bgsound|title|base)\b[^>]*>.*?<\s*\/\s*\1\s*>/is',
            '', $data
        );
        $data = preg_replace(
            '/<\s*(script|iframe|object|embed|link|meta)\b[^>]*\/?\s*>/is',
            '', $data
        );
        $data = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', $data);
        $data = preg_replace('/(javascript|vbscript)\s*:/is', '', $data);
        return trim($data);
    }

    /**
     * 添加请求锁，避免重复提交
     * @param array $data
     */
    protected function addRequestLock(array $data){
        $json = json_encode($data);
        $cache_key = 'lock_'.md5($json);
        $is_lock = Redis::addLock($cache_key);
        if(!$is_lock){
            throw new VerifyException('请不要重复提交');
        }
    }

    /**
     * 删除请求锁
     * @param array $data
     */
    protected function deleteRequestLock(array $data,$msg=null){
        if($msg=='delete' || $msg!='请不要重复提交'){
            $json = json_encode($data);
            $cache_key = 'lock_'.md5($json);
            Redis::del($cache_key);
        }
    }

    /**
     * @param string $body
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function output($body = '', $status = 200, $headers = []):Response
    {
        return \response($body,$status,$headers);
    }

    /**
     * 获取响应JSON数据
     * @param mixed $success
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return Response
     */
    protected function json($data = [], $msg = 'success', $code = 0):Response
    {
        if (!is_array($data) && empty($data)) {
            $data = new \stdClass();
        }
        $result = [
            'success'=>true,
            'data' => $data,
            'code' => $code,
            'msg' => trans($msg),
        ];
        $request = $this->request;
        if(!empty($request)){
            if($request->runtime){
                $result['runtime'] = (Carbon::now()->getTimestampMs() - $request->runtime)/1000;
            }
            if($request->trace_id){
                $result['trace_id'] = $request->trace_id;
            }
        }
        return \json($result);
    }

    /**
     * 获取响应JSON数据
     * @param mixed $success
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return Response
     */
    protected function failJson($data = [], $msg = 'error', $code = -1,$http_code=200):Response
    {
        if (!is_array($data) && empty($data)) {
            $data = new \stdClass();
        }
        if($code==0){
            $code = -1;
        }
        // 生产环境隐藏详细错误信息，只显示通用错误提示
        if (!config('app.debug', false) && !empty($msg)) {
            $msg = '系统繁忙，请稍后再试';
        }
        $result = [
            'success'=>false,
            'data' => $data,
            'code' => $code,
            'msg' => trans($msg),
        ];
        $request = $this->request;
        if(!empty($request)){
            if($request->runtime){
                $result['runtime'] = (Carbon::now()->getTimestampMs() - $request->runtime)/1000;
            }
            if($request->trace_id){
                $result['trace_id'] = $request->trace_id;
            }
        }
        return new Response($http_code, ['Content-Type' => 'application/json'], json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array $data
     * @param string $msg
     * @param int $code
     * @param string $callbackName
     * @return Response
     */
    protected function jsonp(array $data,string $msg = 'ok',int $code = 0, string $callbackName = 'callback'):Response
    {
        $res = [
            'data' => $data,
            'code' => $code,
            'msg' => trans($msg),
        ];
        return \jsonp($res,$callbackName);
    }

    /**
     * @param $template
     * @param array $vars
     * @param null $app
     * @return Response
     */
    protected function view($template,array $vars = [],string $app = null, string $plugin = null):Response
    {
        return \view($template,$vars,$app,$plugin);
    }

    /**
     * @param $location
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function redirect($location, $status = 302, $headers = []):Response
    {
        return \redirect($location,$status,$headers);
    }
}
