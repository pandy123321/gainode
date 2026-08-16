<?php

namespace support\translate\engine;

use support\exception\VerifyException;
use support\translate\TranslateInterface;
use support\utils\Curl;

/**
 */
class Baidu implements TranslateInterface{

    private $client_key = '';
    private $client_secret = '';

    public function __construct()
    {
        $this->client_key = getenv('BAIDU_CLIENT_KEY') ?: '';
        $this->client_secret = getenv('BAIDU_CLIENT_SECRET') ?: '';
    }

    public function getLanguageList(){
        return [
            'zh_CN'=>'zh',     //中文
            'zh_TW'=>'cht',   //繁体中文
            'en'=>'en',     //英语
            'jp'=>'jp',     //日语
            'ko'=>'kor',   //韩语
            'fr'=>'fra',   //法语
            'de'=>'de',    //德语
            'it'=>'it',     //意大利语
            'es'=>'spa',   //西班牙语
            'pt'=>'pt',     //葡萄牙语
            'th'=>'th',     //泰语
            'ar'=>'ara',   //阿拉伯语
            'ru'=>'ru',     //俄语
            'el'=>'el',     //希腊语
            'nl'=>'nl',     //荷兰语
            'pl'=>'pl',     //波兰语
            'da'=>'dan',     //丹麦语
            'fi'=>'fin',     //芬兰语
            'cs'=>'cs',     //捷克语
            'rom'=>'rom',     //罗马尼亚语
            'sw'=>'swe',     //瑞典语
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
        $url = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
        $salt = time();
        $sign = md5($this->client_key.$text.$salt.$this->client_secret);
        $data = [
            'q'=>$text,
            'from'=>($sourceLanguage=='auto'?'auto':$langList[$sourceLanguage]),
            'to'=>$langList[$targetLanguage],
            'appid'=>$this->client_key,
            'salt'=>$salt,
            'sign'=>$sign
        ];
        $url.='?'.http_build_query($data);
        $response = Curl::getContents($url);
        $data = json_decode($response,true);
        if(!empty($data) && !empty($data['trans_result'])){
            return $data['trans_result'][0]['dst'];
        }
        elseif(isset($data['error_msg'])){
            throw new \Exception($data['error_msg']);
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
        $tranString = implode("\r\n",$texts);
        $url = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
        $salt = time();
        $sign = md5($this->client_key.$tranString.$salt.$this->client_secret);
        $data = [
            'q'=>$tranString,
            'from'=>($sourceLanguage=='auto'?'auto':$langList[$sourceLanguage]),
            'to'=>$langList[$targetLanguage],
            'appid'=>$this->client_key,
            'salt'=>$salt,
            'sign'=>$sign
        ];
        $url.='?'.http_build_query($data);
        $response = Curl::getContents($url);
        $data = json_decode($response,true);
        if(!empty($data) && !empty($data['trans_result'])){
            $cdata = [];
            foreach($data['trans_result'] as $v){
                $cdata[$v['src']] = $v['dst'];
            }
            return $cdata;
        }
        elseif(isset($data['error_msg'])){
            throw new \Exception($data['error_msg']);
        }
        throw new VerifyException('翻译接口异常');
    }
}
