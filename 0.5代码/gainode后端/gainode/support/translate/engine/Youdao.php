<?php

namespace support\translate\engine;

use support\exception\VerifyException;
use support\translate\TranslateInterface;
use support\utils\Curl;

/**
 */
class Youdao implements TranslateInterface{

    private $client_key = '';
    private $client_secret = '';

    public function __construct()
    {
        $this->client_key = getenv('YOUDAO_CLIENT_KEY') ?: '';
        $this->client_secret = getenv('YOUDAO_CLIENT_SECRET') ?: '';
    }

    public function getApiUrl($is_html = false)
    {
        if($is_html){
            return 'https://openapi.youdao.com/translate_html';
        }
        return 'https://openapi.youdao.com/api';
    }

    public function getLanguageList(){
        return [
            'zh_CN'=>'zh-CHS',     //中文
            'zh_TW'=>'zh-CHT',   //繁体中文
            'en'=>'en',     //英语
            'jp'=>'ja',     //日语
            'ko'=>'ko',   //韩语
            'fr'=>'fr',   //法语
            'de'=>'de',    //德语
            'it'=>'it',     //意大利语
            'id'=>'id',     //印度尼西亚语
            'es'=>'es',   //西班牙语
            'pt'=>'pt',     //葡萄牙语
            'th'=>'th',     //泰语
            'ar'=>'ar',   //阿拉伯语
            'ru'=>'ru',     //俄语
            'el'=>'el',     //希腊语
            'nl'=>'nl',     //荷兰语
            'pl'=>'pl',     //波兰语
            'da'=>'da',     //丹麦语
            'fi'=>'fi',     //芬兰语
            'cs'=>'cs',     //捷克语
            'rom'=>'ro',     //罗马尼亚语
            'sw'=>'sv',     //瑞典语
            'hu'=>'hu',     //匈牙利语
            'vi'=>'vie',     //越南语
        ];
    }

    public function translateText(string $text,string $targetLanguage='en',$sourceLanguage='auto'){
        $langList = $this->getLanguageList();
        if(!$langList[$targetLanguage]){
            throw new VerifyException('暂不支持该翻译语言');
        }
        elseif($sourceLanguage!='auto' && !$langList[$sourceLanguage]){
            throw new VerifyException('无法识别该翻译语言');
        }
        $salt = $this->createGuid();
        $args = [
            'q'      => $text,
            'appKey' => $this->client_key,
            'salt'   => $salt,
        ];
        $args['from']     = ($sourceLanguage=='auto'?'auto':$langList[$sourceLanguage]);
        $args['to']       = $langList[$targetLanguage];
        $args['signType'] = 'v3';
        $currentTime      = strtotime('now');
        $args['curtime']  = $currentTime;
        $signStr          = $this->client_key . $this->truncate($text) . $salt . $currentTime . $this->client_secret;
        $args['sign']     = hash('sha256', $signStr);
        $args['vocabId']  = '您的用户词表ID';
        $ret              = $this->call($this->getApiUrl(), $args);
        $data = json_decode($ret, true);
        if(!empty($data['translation']) && !empty($data['translation'][0])){
            return $data['translation'][0];
        }
        elseif(isset($data['errorCode'])){
            $errorMessage = "Error code: {$data['errorCode']}, 请查看: https://ai.youdao.com/DOCSIRMA/html/trans/api/wbfy/index.html#section-14";
            throw new \Exception($errorMessage);
        }
        throw new VerifyException('翻译接口异常');
    }

    public function translateArrayText(array $texts,string $targetLanguage='en',$sourceLanguage='auto'){
        $langList = $this->getLanguageList();
        if(!$langList[$targetLanguage]){
            throw new VerifyException('暂不支持该翻译语言');
        }
        elseif($sourceLanguage!='auto' && !$langList[$sourceLanguage]){
            throw new VerifyException('无法识别该翻译语言');
        }
        $text   = implode('#', $texts);
        $salt = $this->createGuid();
        $args = [
            'q'      => $text,
            'appKey' => $this->client_key,
            'salt'   => $salt,
        ];
        $args['from']     = ($sourceLanguage=='auto'?'auto':$langList[$sourceLanguage]);
        $args['to']       = $langList[$targetLanguage];
        $args['signType'] = 'v3';
        $currentTime      = strtotime('now');
        $args['curtime']  = $currentTime;
        $signStr          = $this->client_key . $this->truncate($text) . $salt . $currentTime . $this->client_secret;
        $args['sign']     = hash('sha256', $signStr);
        $args['vocabId']  = '您的用户词表ID';
        $ret              = $this->call($this->getApiUrl(), $args);
        $data = json_decode($ret, true);
        if(!empty($data['translation']) && !empty($data['translation'][0])){
            $arr = explode('#', $data['translation'][0]);
            return array_combine($texts,$arr);
        }
        elseif(isset($data['errorCode'])){
            $errorMessage = "Error code: {$data['errorCode']}, 请查看: https://ai.youdao.com/DOCSIRMA/html/trans/api/wbfy/index.html#section-14";
            throw new \Exception($errorMessage);
        }
        throw new VerifyException('翻译接口异常');
    }
    /**
     * uuid generator
     *
     * @return string
     */
    private function createGuid(): string
    {
        $microTime       = microtime();
        [$a_dec, $a_sec] = explode(' ', $microTime);
        $dec_hex         = dechex($a_dec * 1000000);
        $sec_hex         = dechex($a_sec);
        $this->ensureLength($dec_hex, 5);
        $this->ensureLength($sec_hex, 6);
        $guid = $dec_hex;
        $guid .= $this->createGuidSection(3);
        $guid .= '-';
        $guid .= $this->createGuidSection(4);
        $guid .= '-';
        $guid .= $this->createGuidSection(4);
        $guid .= '-';
        $guid .= $this->createGuidSection(4);
        $guid .= '-';
        $guid .= $sec_hex;
        $guid .= $this->createGuidSection(6);

        return $guid;
    }

    /**
     * @param $string
     * @param $length
     * @return void
     */
    private function ensureLength(&$string, $length): void
    {
        $strlen = strlen($string);
        if ($strlen < $length) {
            $string = str_pad($string, $length, '0');
        } elseif ($strlen > $length) {
            $string = substr($string, 0, $length);
        }
    }

    /**
     * @param $characters
     * @return string
     */
    private function createGuidSection($characters): string
    {
        $return = '';
        for ($i = 0; $i < $characters; $i++) {
            $return .= dechex(mt_rand(0, 15));
        }

        return $return;
    }

    /**
     * @param $q
     * @return string
     */
    private function truncate($q): string
    {
        $len = $this->absLength($q);

        return $len <= 20 ? $q : (mb_substr($q, 0, 10) . $len . mb_substr($q, $len - 10, $len));
    }

    /**
     * @param $str
     * @return int
     */
    private function absLength($str): int
    {
        if (empty($str)) {
            return 0;
        }
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'utf-8');
        }
        preg_match_all('/./u', $str, $ar);

        return count($ar[0]);

    }

    /**
     * 发起网络请求
     *
     * @param        $url
     * @param        $args
     * @param string $method
     * @param int    $timeout
     * @param array  $headers
     * @return bool|mixed|string
     */
    private function call($url, $args = null, string $method = 'post', int $timeout = 3000, array $headers = []): mixed
    {
        $ret = false;
        $i   = 0;
        while ($ret === false) {
            if ($i > 1) {
                break;
            }
            if ($i > 0) {
                sleep(1);
            }
            $ret = $this->callOnce($url, $args, $method, false, $timeout, $headers);
            $i++;
        }

        return $ret;
    }

    /**
     * @param $url
     * @param $args
     * @param $method
     * @param $withCookie
     * @param $timeout
     * @param $headers
     * @return bool|string
     */
    private function callOnce($url, $args = null, $method = 'post', $withCookie = false, $timeout = 3000, $headers = []): bool|string
    {
        $ch   = curl_init();
        $data = $this->convert($args);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            if ($data) {
                if (stripos($url, '?') > 0) {
                    $url .= "&$data";
                } else {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (! empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($withCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);

        return $r;
    }

    /**
     * @param $args
     * @return mixed|string
     */
    private function convert(&$args): mixed
    {
        $data = '';
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $data .= $key . '[' . $k . ']=' . rawurlencode($v) . '&';
                    }
                } else {
                    $data .= "$key=" . rawurlencode($val) . '&';
                }
            }
            return trim($data, '&');
        }
        return $args;
    }
}
