<?php

namespace support\utils;

use support\Request;

/**
 * Http操作类
 * @author Kevin
 */
class Http {

    private static $clientObj = null;

    /**
     * @var Request
     */
    private $request;

    /**
     * 获取CURL对象
     * @return \support\utils\Http
     */
    public static function getInstance($request=null){
        if(empty($request)){
            $request = \request();
        }
        if(empty(self::$clientObj)){
            self::$clientObj = new Http();
            self::$clientObj->request = $request;
        }
        return self::$clientObj;
    }

    /**
     * 获取客户端请求IP
     */
    public function getClientIP(){
        try{
            if(empty($this->request)){
                return 'unknow';
            }
            return $this->request->getRealIp();
        }
        catch (\Throwable $e){
            return null;
        }
    }

     /**
     * 获取客户端系统信息:操作系统、浏览器等
     * @return string
     */
    public function getUserAgent() {
        return $this->request->header('User-Agent');
    }

    /**
     * 获取客户端操作系统
     * @return array[os]            操作系统名称
     * @return array[os_ver]        操作系统版本号
     * @return array[equipment]     终端设备类型
     */
    public function getClientOS($type=null)
    {
        if(empty($this->request)){
            return 'unknow';
        }
        $agent = $this->getUserAgent();
        //window系统
        if (stripos($agent, 'window')) {
            $os = 'Windows';
            $equipment = '电脑';
            if (preg_match('/nt 6.0/i', $agent)) {
                $os_ver = 'Vista';
            }
            elseif (preg_match('/nt 10.0/i', $agent)) {
                $os_ver = '10';
            }
            elseif (preg_match('/nt 6.3/i', $agent)) {
                $os_ver = '8.1';
            }
            elseif (preg_match('/nt 6.2/i', $agent)) {
                $os_ver = '8.0';
            }
            elseif (preg_match('/nt 6.1/i', $agent)) {
                $os_ver = '7';
            }
            elseif (preg_match('/nt 5.1/i', $agent)) {
                $os_ver = 'XP';
            }
            elseif (preg_match('/nt 5/i', $agent)) {
                $os_ver = '2000';
            }
            elseif (preg_match('/nt 98/i', $agent)) {
                $os_ver = '98';
            }
            elseif (preg_match('/nt/i', $agent)) {
                $os_ver = 'nt';
            }
            else {
                $os_ver = '';
            }
            if (preg_match('/x64/i', $agent)) {
                $os .= '(x64)';
            } elseif (preg_match('/x32/i', $agent)) {
                $os .= '(x32)';
            }
        }
        elseif (stripos($agent, 'linux')) {
            if (stripos($agent, 'android')) {
                preg_match('/android\s([\d\.]+)/i', $agent, $match);
                $os = 'Android';
                $equipment = 'Mobile phone';
                $os_ver = $match[1];
            } else {
                $os = 'Linux';
            }
        }
        elseif (stripos($agent, 'unix')) {
            $os = 'Unix';
        }
        elseif (preg_match('/iPhone|iPad|iPod/i', $agent)) {
            preg_match('/OS\s([0-9_\.]+)/i', $agent, $match);
            $os = 'IOS';
            $os_ver = str_replace('_', '.', $match[1]);
            if (preg_match('/iPhone/i', $agent)) {
                $equipment = 'iPhone';
            } elseif (preg_match('/iPad/i', $agent)) {
                $equipment = 'iPad';
            } elseif (preg_match('/iPod/i', $agent)) {
                $equipment = 'iPod';
            }
        }
        elseif (stripos($agent, 'mac os')) {
            preg_match('/Mac OS X\s([0-9_\.]+)/i', $agent, $match);
            $os = 'Mac OS X';
            $equipment = '电脑';
            $os_ver = str_replace('_', '.', $match[1]);
        }
        else {
            $os = 'Other';
        }
        if($type=='os'){
            return $os;
        }
        return ['os' => $os, 'os_ver' => $os_ver, 'equipment' => $equipment];
    }

    /**
     * 获取请求的浏览器信息
     * @param $userAgent
     * @return type
     */
    public function getBrowser($type=null)
    {
        if(empty($this->request)){
            return 'unknow';
        }
        $userAgent = $this->getUserAgent();
        $browser = '';
        $browser_ver = '';
        if (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $userAgent, $regs)) {
            $browser = 'OmniWeb';
            $browser_ver = $regs[2];
        }
        if (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $userAgent, $regs)) {
            $browser = 'Netscape';
            $browser_ver = $regs[2];
        }
        if (preg_match('/safari\/([^\s]+)/i', $userAgent, $regs)) {
            $browser = 'Safari';
            $browser_ver = $regs[1];
        }
        if (preg_match('/MSIE\s([^\s|;]+)/i', $userAgent, $regs)) {
            $browser = 'Internet Explorer';
            $browser_ver = $regs[1];
        }
        if (preg_match('/Opera[\s|\/]([^\s]+)/i', $userAgent, $regs)) {
            $browser = 'Opera';
            $browser_ver = $regs[1];
        }
        if (preg_match('/NetCaptor\s([^\s|;]+)/i', $userAgent, $regs)) {
            $browser = '(Internet Explorer '.$browser_ver.') NetCaptor';
            $browser_ver = $regs[1];
        }
        if (preg_match('/Maxthon/i', $userAgent, $regs)) {
            $browser = '(Internet Explorer '.$browser_ver.') Maxthon';
            $browser_ver = '';
        }
        if (preg_match('/360SE/i', $userAgent, $regs)) {
            $browser = '(Internet Explorer '.$browser_ver.') 360SE';
            $browser_ver = '';
        }
        if (preg_match('/SE 2.x/i', $userAgent, $regs)) {
            $browser = '(Internet Explorer '.$browser_ver.') 搜狗';
            $browser_ver = '';
        }
        if (preg_match('/FireFox\/([^\s]+)/i', $userAgent, $regs)) {
            $browser = 'FireFox';
            $browser_ver = $regs[1];
        }
        if (preg_match('/Lynx\/([^\s]+)/i', $userAgent, $regs)) {
            $browser = 'Lynx';
            $browser_ver = $regs[1];
        }
        if (preg_match('/Chrome\/([^\s]+)/i', $userAgent, $regs)) {
            $browser = 'Chrome';
            $browser_ver = $regs[1];
        }
        if (preg_match('/MicroMessenger\/([^\s]+)/i', $userAgent, $regs)) {
            $browser = '微信浏览器';
            $browser_ver = $regs[1];
        }
        if ($browser != '') {
            $data = ['browser'=>$browser, 'browser_ver'=>$browser_ver];
        } else {
            $data = ['browser'=>'未知','browser_ver'=> ''];
        }
        if($type=='browser'){
            return $data['browser'];
        }
        return $data;
    }

    /**
     * 验证是否指定类型浏览器
     * @param $type (weixin)
     */
    public function isSpecifiedBrowser($type){
        $r_value = false;
        $user_agent = self::getUserAgent();
        switch ($type) {
            case 'weixin':
                $r_value = strpos($user_agent, 'MicroMessenger')?true:false;
                break;
        }
        return $r_value;
    }

    /**
     * 获取服务器的名称
     * @return string server name
     */
    public function getHostName() {
        return $this->request->header("host");
    }

    /**
     * 返回前一个请求页面地址
     * @return string
     */
    public function getUrlReferer($is_current = false) {
        if($is_current){
            $refer = $this->request->uri();
        }
        else{
            $refer = $this->request->header("Referer");
        }
        if(!empty($refer) && preg_match('/(login)/i', $refer)){
            $refer = '';
        }
        return $refer;
    }

    /**
     * 设置referer的地址
     */
    public function setRefererUrl($url) {
        if(!preg_match('/product\/*/ius', $url)){
            $url = '';
        }
        if(!empty($url)){
            $this->request->session()->set("LOGIN_REFER_URL",$url);
        }
    }

    /**
     * 获取referer的地址
     * @return type
     */
    public function getRefererUrl() {
        $refer = $this->request->session()->get('LOGIN_REFER_URL');
        if(preg_match('/(login|register|forgot|backend)/ius', $refer)){
            $refer = '';
        }
        if (empty($refer)) {
            $refer = strtolower($this->getUrlReferer());
        }
        return $refer;
    }
}
