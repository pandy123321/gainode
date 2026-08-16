<?php

namespace support\mailer;


use library\service\sys\DictService;
use support\Container;
use support\extend\Log;
use support\utils\Curl;

/**
 * 短信发送接口
 * 587:tls
 * 465:ssl
 */
class Smsbao
{
    protected $error = '';
    protected $config = [];
    protected $statusStr = array(
        "0"  => "短信发送成功",
        "-1" => "参数不全",
        "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
        "30" => "密码错误",
        "40" => "账号不存在",
        "41" => "余额不足",
        "42" => "帐户已过期",
        "43" => "IP地址限制",
        "50" => "内容含有敏感词",
        "51" => "手机号码不正确"
    );

    public function __construct($options = [])
    {
        if(empty($options)){
            $dictService = new DictService();
            $configs = [];//$dictService->getDictConfigs('smsbao',true);
            if(!empty($configs)){
                $this->config['u'] = $configs['username'];
                $this->config['p'] = $configs['password'];
            }
            else{
                $this->config['u'] = env('SMS_USERNAME');
                $this->config['p'] = env('SMS_PASSWORD');
            }
        }
        else{
            $this->config = $options;
        }
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 短信发送
     * @param string $mobile
     * @param string $msg
     * @return mixed
     */
    public function sendMsg($mobile,$msg)
    {
        $this->error = '';
        $postArr = array(
            'u' => $this->config['u'],
            'p' => $this->config['p'],
            'm' => '+'.$mobile,
            'c' => $msg
        );
        $header = [
            'Content-Type: application/json; charset=utf-8'
        ];
        $url = 'http://hk.smsbao.com/sms';
        if(strpos($postArr['m'],'+86')!==0){
            $url = 'https://hk.smsbao.com/wsms';
        }
        else{
            $postArr['m'] = str_replace('+86','',$postArr['m']);
        }
        $url.='?'.http_build_query($postArr);
        Log::channel('message')->info($url,['type'=>"mobile"]);
        $res = Curl::getInstance()->get($url,[],$header);
        if(is_numeric($res)){
            if($res==='0'){
                return TRUE;
            }
            else{
                $this->error = isset($this->statusStr[$res]) ? $this->statusStr[$res] : 'InvalidResult';
                return FALSE;
            }
        }
        elseif(!empty($res)){
            $result =json_decode($res,true);
            if ($result['ret']) {
                if (isset($result['msg']) && $result['msg'] == '0')
                    return TRUE;
                $this->error = isset($this->statusStr[$result['msg']]) ? $this->statusStr[$result['msg']] : 'InvalidResult';
            }
            else {
                $this->error = $result['msg'];
            }
            return FALSE;
        }
        return null;
    }
}
