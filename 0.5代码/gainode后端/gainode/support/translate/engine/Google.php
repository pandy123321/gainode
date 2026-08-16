<?php

namespace support\translate\engine;

require BASE_PATH.'/vendor/autoload.php';

use Google\Cloud\Translate\V3\GcsSource;
use Google\Cloud\Translate\V3\TranslationServiceClient;
use support\exception\VerifyException;
use support\translate\TranslateInterface;


/**
 */
class Google  implements TranslateInterface{

    private $client_key = '';
    private $creds = [];

    public function __construct()
    {
        $this->client_key = getenv('GOOGLE_API_KEY') ?: '';
        $json = getenv('GOOGLE_SERVICE_ACCOUNT_JSON') ?: '';
        $this->creds = $json ? json_decode($json, true) : [];
    }

    public function getLanguageList(){
        return [
            'zh_cn'=>'zh-CN',     //中文
            'zh_hk'=>'zh-TW',   //繁体中文
            'en'=>'en',         //英语
            'ja'=>'ja',     //日语
            'ko'=>'ko',     //韩语
            'fr'=>'fr',     //法语
            'es'=>'es',     //西班牙语
            'th'=>'th',     //泰语
            'ar'=>'ar',     //阿拉伯语
            'ru'=>'ru',     //俄语
            'pt'=>'pt',     //葡萄牙语
            'de'=>'de',     //德语
            'it'=>'it',     //意大利语
            'el'=>'el',     //希腊语
            'nl'=>'nl',     //荷兰语
            'pl'=>'pl',     //波兰语
            'da'=>'da',     //丹麦语
            'fi'=>'fi',     //芬兰语
            'cs'=>'cs',     //捷克语
            'rom'=>'rom',   //罗马尼亚语
            'sw'=>'sw',     //瑞典语
            'hu'=>'hu',     //匈牙利语
            'vi'=>'vi',     //越南语
        ];
    }

    public function translateText(string $text,string $targetLanguage='en',$sourceLanguage=null){
        $langList = $this->getLanguageList();
        if(!$langList[$targetLanguage]){
            throw new VerifyException('暂不支持该翻译语言');
        }
        elseif($sourceLanguage!='auto' && !$langList[$sourceLanguage]){
            throw new VerifyException('无法识别该翻译语言');
        }
        $trans = null;
        // 创建临时文件保存凭证信息
        $filename = sys_get_temp_dir(). '/'. md5(uniqid()). '.json';
        file_put_contents($filename, json_encode($this->creds));
        // 设置环境变量
        putenv('GOOGLE_APPLICATION_CREDENTIALS='. $filename);
        try {
            $mime_type = 'text/plain';  //text/html
            $translationServiceClient = new \Google\Cloud\Translate\V3\TranslationServiceClient();
            // 调用翻译方法
            $response = $translationServiceClient->translateText(
                [$text],
                $langList[$targetLanguage],
                $translationServiceClient->locationName($this->creds['project_id'], 'global'),
                ['sourceLanguageCode' => null, 'mime_type' => $mime_type]
            );
            //$langList[$sourceLanguage]
            foreach ($response->getTranslations() as $translation) {
                $trans = $translation->getTranslatedText();
                break;
            }
        }
        catch (\Exception $e) {
            throw $e;
        }
        finally {
            $translationServiceClient->close();
            @unlink($filename);
        }
        if(empty($trans)){
            throw new VerifyException('翻译内容为空');
        }
        return $trans;
    }

    public function translateArrayText(array $texts,string $targetLanguage='en',$sourceLanguage=null){
        $langList = $this->getLanguageList();
        if(!$langList[$targetLanguage]){
            throw new VerifyException('暂不支持该翻译语言');
        }
        elseif($sourceLanguage!='auto' && !$langList[$sourceLanguage]){
            throw new VerifyException('无法识别该翻译语言');
        }
        // 创建临时文件保存凭证信息
        $filename = sys_get_temp_dir(). '/'. md5(uniqid()). '.json';
        file_put_contents($filename, json_encode($this->creds));
        // 设置环境变量
        putenv('GOOGLE_APPLICATION_CREDENTIALS='. $filename);
        $data = [];
        try {
            $mime_type = 'text/plain';  //text/html
            $translationServiceClient = new \Google\Cloud\Translate\V3\TranslationServiceClient();
            // 调用翻译方法
            $response = $translationServiceClient->translateText(
                $texts,
                $langList[$targetLanguage],
                $translationServiceClient->locationName($this->creds['project_id'], 'global'),
                ['sourceLanguageCode' => null, 'mime_type' => $mime_type]
            );
            //$langList[$sourceLanguage]
            foreach ($response->getTranslations() as $k=>$translation) {
                $key = $texts[$k];
                $trans = $translation->getTranslatedText();
                $data[$key] = $trans;
            }
        }
        catch (\Exception $e) {
            throw $e;
        }
        finally {
            $translationServiceClient->close();
            @unlink($filename);
        }
        if(empty($data)){
            throw new VerifyException('翻译内容为空');
        }
        return $data;
    }
}
