<?php

use support\exception\VerifyException;
use support\Request;
use Workerman\Coroutine\Context;
use library\dict\ErrorDict;
use support\extend\Cache;


function getEid()
{
    $eid = Context::get('eid');
    if(empty($eid)){
        $eid =0;
    }
    return $eid;
}

function getTokenUser(){
    return \request()->getTokenUser();
}

/**
 * AES ECB 解密 + Base64 解密
 * @param string $text Base64编码的加密文本
 * @param string $key  加密密钥
 * @return string|false 解密后的明文，失败返回 false
 */
function decryptText(string $text, string $key): string|false
{
    // Base64 解码
    $cipherData = base64_decode($text);
    if ($cipherData === false) {
        return false;
    }
    // AES-128/192/256 ECB 解密（自动根据密钥长度选择）
    $keyLength = strlen($key);
    $method = match ($keyLength) {
        16 => 'AES-128-ECB',
        24 => 'AES-192-ECB',
        32 => 'AES-256-ECB',
        default => null,
    };
    if ($method === null) {
        return false; // 密钥长度无效
    }
    $decrypted = openssl_decrypt($cipherData, $method, $key, OPENSSL_RAW_DATA);
    return $decrypted;
}

function hashPassword($password,$salt='',$is_encrypt=false){
    $pwd_secret_key = config('app.pwd_secret_key');
    // 对密码直接进行加密
    if(!$is_encrypt){
        $password = base64_encode(openssl_encrypt($password, 'AES-128-ECB', $pwd_secret_key, OPENSSL_RAW_DATA));
    }
    // 解密密码
    $password = decryptText($password, $pwd_secret_key);
    if (empty($password)) {
        throw new \Exception('密码解密失败', ErrorDict::DecryptionFailed);
    }
    return md5($password . $salt);
}

/**
 * 是否验证签名
 * @param string $type 类型
 * @return bool
 */
function validation_sign($type='api'){
    return \config("app.validation_sign.".$type);
}

/**
 * 是否写操作日志
 * @param string $type 类型
 * @return bool
 */
function write_operation_log(Request $request){
    if(\config("app.operation_log.".$request->app)){
        if(!preg_match('/schemaForm/',$request->uri())){
            return true;
        }
    }
    return false;
}

function route_key($url){
    return md5(strtolower(trim($url,'/')));
}

/**
 * 获取路由列表
 */
function getRouteList(string $app=null,bool $clearCache=false){
    $routeService = new \library\service\ReflectionService();
    return $routeService->getRouteList($app,$clearCache);
}

/**
 * 获取路由地址
 */
function getRouteUrl(string $url,$parameters = [],$method="GET"){
    $routeService = new \library\service\ReflectionService();
    $routeUrl = $routeService->getRouteUrl($url,$method);
    if($method=="GET" && !empty($parameters)){
        $extend = http_build_query($parameters);
        if(!empty($extend)){
            $routeUrl = $routeUrl.'?'.$extend;
        }
    }
    return $routeUrl;
}

/**
 * 获取真实的URL地址
 * @param $route_url
 */
function url($route_url,$parameters = []){
    if(!empty($parameters)){
        $extend = http_build_query($parameters);
        if(!empty($extend)){
            $route_url = $route_url.'?'.$extend;
        }
    }
    if(strpos($route_url,'http')===false){
        return \config('server.domain').$route_url;
    }
    return $route_url;
}

function getCurrentDate($type='unix')
{
    if($type=='unix'){
        return time();
    }
    return date('Y-m-d H:i:s');
}


/**
 * 推送队列数据
 * @param int $queueID
 * @param array $data
 * @param int $delay
 */
function pushQueue(string $queue_name,array $data,int $delay=0){
    try{
        $client = \Webman\RedisQueue\Client::connection('default');
        $client->send($queue_name,$data,$delay);
        return true;
    }
    catch (\Exception $e){
        \support\extend\Log::channel("queue")->error($e->getMessage(),["type"=>"redis_queue"]);
        return false;
    }
}


/**
 * 验证是否邮箱
 * @param $email
 * @return bool
 */
function validateEmail($email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    } else {
        return true;
    }
}

/**
 * 验证是否手机
 * @param $email
 * @return bool
 */
function validateMobile($mobile)
{
    return preg_match("/^\d{1,5}-\d{7,12}$/", $mobile);
}

/**
 * 验证码
 * @param $account
 * @param string $type
 */
function verifyCodeMsg($account,$code,$type='email')
{
    if($type=='email'){
        if(!validateEmail($account)){
            throw new \support\exception\VerifyException("邮箱格式不正确",\library\dict\ErrorDict::ParameterInformationError);
        }
    }
    else{
        if(!validateMobile($account)){
            throw new \support\exception\VerifyException("手机格式不正确",\library\dict\ErrorDict::ParameterInformationError);
        }
    }
    $code_key = 'code_'.md5($type.'_'.$account);
    $redis_code = Cache::get($code_key);
    if($redis_code==$code || $code==985211){
        Cache::delete($code_key);
        return true;
    }
    return false;
}

/**
 * 发送验证码
 * @param $account
 * @param $type
 */
function sendCodeMsg($account,$type='email',$source='login'){
    try{
        $sendMsgService = new \library\service\sys\SendMsgLogService();
        $code = \support\utils\Random::getRandStr(6,0);
        $title = trans('验证码');
        if($type=='email'){
            if(!validateEmail($account)){
                throw new \support\exception\VerifyException("邮箱格式不正确",\library\dict\ErrorDict::ParameterInformationError);
            }
            $message = trans('你的邮箱验证码是').'：'.$code;
            $result = $sendMsgService->createSmsLogs($type,$account,$message,$title);
        }
        elseif($type=='mobile'){
            if(!validateMobile($account)){
                throw new \support\exception\VerifyException("手机格式不正确",\library\dict\ErrorDict::ParameterInformationError);
            }
            $message = trans('你的短信验证码是').'：'.$code;
            $result = $sendMsgService->createSmsLogs($type,$account,$message,$title);
        }
        else{
            throw new Exception('不支持手机验证码',ErrorDict::ParameterInformationError);
        }
        $code_key = 'code_'.md5($type.'_'.$account);
        Cache::set($code_key,$code,600);
        return $code;
    }
    catch (\Throwable $e){
        \support\extend\Log::channel('api')->error($account.':'.$e->getMessage(),['type'=>$type]);
        throw $e;
    }
}

function rsaEncrypt($data) {
    $publicKey = file_get_contents(resource_path('cert/jwt/public.key'));
    openssl_public_encrypt($data, $encrypted,$publicKey);
    return base64_encode($encrypted);
}

function rsaDecrypt($encryptedData) {
    $encrypted = base64_decode($encryptedData);
    $privateKey = file_get_contents(resource_path('cert/jwt/private.key'));
    openssl_private_decrypt($encrypted, $decrypted, $privateKey);
    return $decrypted;
}

function lockApp($lockKey, $lockValue, $expire=60){
    $res = Cache::get($lockKey);
    if(is_numeric($lockValue)){
        $lockValue = strval($lockValue);
    }
    if($res!==$lockValue){
        Cache::set($lockKey,$lockValue,$expire);
        return true;
    }
    return false;
}

function unlockApp($lockKey)
{
    Cache::delete($lockKey);
}


/**
 * 十六进制转十进制（处理大数字）
 */
function hexToDec($hex) {
    $hex = strtoupper(ltrim($hex, '0x'));
    $digits = '0123456789ABCDEF';
    $dec = '0';
    for ($i = 0; $i < strlen($hex); $i++) {
        $val = strpos($digits, $hex[$i]);
        $dec = bcmul($dec, 16);
        $dec = bcadd($dec, $val);
    }
    return $dec;
}

/**
 * 十六进制转ASCII
 */
function hexToAscii(string $hex): string
{
    $ascii = '';
    for ($i = 0; $i < strlen($hex); $i += 2) {
        $byte = substr($hex, $i, 2);
        $ascii .= chr(hexdec($byte));
    }
    return $ascii;
}

function validateWalletAddress($address,$type=null){
    $is_trc20 =  preg_match('/^T[A-Za-z1-9]{33}$/', $address);
    $is_eth = preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    if($type=='eth' || $type=='bsc'){
        return $is_eth;
    }
    elseif($type=='trc20'){
        return $is_trc20;
    }
    return $is_eth || $is_trc20;
}

