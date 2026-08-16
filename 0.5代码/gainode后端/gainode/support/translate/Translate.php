<?php

namespace support\translate;

use support\Container;
use support\exception\RunException;
use support\extend\Log;


/**
 * Model
 * Strings methods
 * @method TranslateInterface getLanguageList()
 * @method TranslateInterface translateText(string $msg,string $targetLanguage='en',$fromLanguage='auto')
 * @method TranslateInterface translateArrayText(array $strings,string $targetLanguage='en',$fromLanguage='auto')
 */
class Translate
{
    /**
     * 实例化数组
     * @var array
     */
    private static $clientAry=[];

    /**
     * 翻译实例对象
     * @var TranslateInterface
     */
    private $client;

    /**
     * @param string $platform 翻译平台
     * @return \support\translate\Translate
     */
    public static function getInstance(string $platform='google')
    {
        if(empty(self::$clientAry[$platform])){
            self::$clientAry[$platform] = new Translate($platform);
        }
        return self::$clientAry[$platform];
    }

    /**
     * 实例化
     * @param string $platform 平台
     */
    private function __construct(string $platform)
    {
        $klass = 'support\\translate\\engine\\'.ucfirst($platform);
        $this->client = Container::get($klass);
    }

    /**
     * @param string $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        try {
            if(empty($this->client)){
                throw new RunException("暂未找到Translate类");
            }
            return $this->client->{$name}(... $arguments);
        }
        catch (\Throwable $e) {
            Log::channel('library')->error('Translate Call',['func'=>$name,'msg'=>$e->getMessage()]);
            throw $e;
        }
    }
}
