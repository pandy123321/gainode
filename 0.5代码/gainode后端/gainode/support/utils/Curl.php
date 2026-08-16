<?php

namespace support\utils;

/**
 * 数据抓取类
 * @author Kevin
 */
class Curl {

    //启用时会将头文件信息作为输出流输出
    public  $header = 0;
    public  $nobody = 0;
    public  $https = 0;
    private $http_header = array();
    private $user_agent = '';
    private $compression = 'gzip';
    private $has_cookies = false;
    private $cookie_file = null;
    private $proxy = '';
    private $referer = null;
    private static $clientObj = null;
    private $basic = [];
    public function __destruct() {
        if(!empty(self::$clientObj)){
            self::$clientObj = array();
        }
    }

    /**
     * 获取CURL对象
     * @return \support\utils\Curl
     */
    public static function getInstance(){
        if(empty(self::$clientObj)){
            self::$clientObj = new Curl();
        }
        return self::$clientObj;
    }

    /**
     * 获取内容
     * @param string $url
     * @param string $method
     * @param array $data
     * @return string
     */
    public static function getContents($url,$method = 'get',$body=[],$header=[]){
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_HEADER, 0);
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        }
        switch (strtoupper($method)) {
            case 'GET':
                curl_setopt($ch,CURLOPT_HTTPGET,1);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch,CURLOPT_POST,1);
                break;
            case 'PUT':
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'PUT');
                break;
            case 'DELETE':
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'DELETE');
                break;
        }
        if(!empty($body)){
            curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
        }
        $response = curl_exec( $ch );
        $err = curl_error($ch);
//        $code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
//        if($code >= 200 && $code < 500) {
        if (!$err) {
            return $response;
        }
        else {
            return false;
        }
    }

    /**
     * \support\utils\Curl constructor.
     * @param null $cookie_file
     * @param string $compression
     * @param string $proxy
     */
    public function __construct($cookie_file = null,$compression='gzip',$proxy='') {
        if(!empty($cookie_file)){
            $this->has_cookies = true;
            $this->setCookie($cookie_file);
        }
        $this->compression = $compression;
        $this->setProxy($proxy);
    }

    /**
     * 设置basic认证数据
     * @param $username
     * @param $password
     */
    public function setBasicAuth($username,$password){
        $this->basic = [
            'username'=>$username,
            'password'=>$password
        ];
        return $this;
    }

    /**
     * 设置代理信息
     * @param array $proxy
     */
    public function setProxy($proxy){
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * 设置请求的头部信息
     * @param array $headers
     */
    public function setHttpHeader(array $headers){
        $this->http_header = [];
        foreach($headers as $header){
            $this->http_header[] = $header;
        }
        return $this;
    }

    /**
     * 获取请求的头部信息
     */
    public function getHttpHeader(){
        if(empty($this->http_header)){
            $this->http_header[] = 'Accept: */*';
            $this->http_header[] = 'Connection: keep-alive';
        }
        return $this->http_header;
    }

    /**
     * 设置请求的客户端系统信息
     * @param array $user_agent
     */
    public function setUserAgent($user_agent){
        $this->user_agent = $user_agent;
        return $this;
    }

    /**
     * 获取请求的客户系统信息
     */
    public function getUserAgent(){
        if(empty($this->user_agent)){
            $this->user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.64 Safari/537.36';
        }
        return $this->user_agent;
    }

    /**
     * 写cookie
     * @param string $cookie_file
     */
    private function setCookie($cookie_file) {
        $this->has_cookies = true;
        if (file_exists($cookie_file)) {
            $this->cookie_file = $cookie_file;
        } else {
            fopen($cookie_file, 'w');
            $this->cookie_file = $cookie_file;
            fclose($this->cookie_file);
        }
        return $this;
    }

    /**
     * 获取referer的值
     * @return string
     */
    public function getReferer(){
        return $this->referer;
    }

    /**
     * 设置referer的值
     * @param string $url 来源URL
     */
    public function setReferer($url){
        $this->referer = $url;
        return $this;
    }

    /**
     * 获取CURL对象
     * @param $url
     * @return \CurlHandle|false|resource
     */
    private function getCurlClient($url,$header=[])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeader());
        }
        curl_setopt($ch, CURLOPT_USERAGENT,$this->getUserAgent());

        //当需要通过curl_getinfo来获取发出请求的header信息时，该选项需要设置为true
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HEADER,$this->header);
        curl_setopt($ch, CURLOPT_NOBODY,$this->nobody);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($ch, CURLOPT_ENCODING, $this->compression);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        if ($this->has_cookies){
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        if ($this->proxy){
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }
        if($this->https || strpos($url,'https')===0){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//不做服务器认证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//不做客户端认证
        }
        if(!empty($this->basic)){
            curl_setopt($ch, CURLOPT_USERPWD, $this->basic['username'].':'.$this->basic['password']);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        if($this->getReferer()){
            curl_setopt($ch, CURLOPT_REFERER, $this->getReferer());
        }
        return $ch;
    }

    /**
     * get的curl请求
     * @param string $url 请求的地址
     * @return mixed
     */
    public function get($url,$data=[],$header=[])
    {
        $ch = $this->getCurlClient($url,$header);
        if(!empty($data)){
            curl_setopt($ch,CURLOPT_HTTPGET,true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec( $ch );
        $err = curl_error($ch);
//        $code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
//        if($code >= 200 && $code < 500) {
        if (!$err) {
            return $response;
        }
        else {
            return false;
        }
    }

    /**
     * post的curl请求
     * @param string $url
     * @param array $data
     * @return bool|mixed
     */
    public function post($url,$data=[],$header=[]) {
        $ch = $this->getCurlClient($url,$header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        if(!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec( $ch );
        $err = curl_error($ch);
//        $code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
//        if($code >= 200 && $code < 500) {
        if (!$err) {
            return $response;
        }
        else {
            return false;
        }
    }

    /**
     * put的curl请求
     * @param string $url
     * @param array $data
     * @return bool|mixed
     */
    public function put($url,$data=[],$header=[]) {
        $ch = $this->getCurlClient($url,$header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if(!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec( $ch );
        $err = curl_error($ch);
//        $code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
//        if($code >= 200 && $code < 500) {
        if (!$err) {
            return $response;
        }
        else {
            return false;
        }
    }

    /**
     * delete的curl请求
     * @param string $url 请求的地址
     * @return mixed
     */
    public function delete($url,$data=[],$header=[])
    {
        $ch = $this->getCurlClient($url,$header);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'DELETE');
        if(!empty($data)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec( $ch );
        $err = curl_error($ch);
//        $code = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
//        if($code >= 200 && $code < 500) {
        if (!$err) {
            return $response;
        }
        else {
            return false;
        }
    }

    /**
     * 获取随机代理
     * @return string
     */
    public function getRandomProxyIp(){
        $list = array(
            '127.0.0.1:8889',    //代理地址
        );
        $key = array_rand($list);
        return $list[$key];
    }
}
