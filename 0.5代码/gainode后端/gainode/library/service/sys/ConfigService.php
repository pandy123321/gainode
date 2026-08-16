<?php

namespace library\service\sys;

use library\model\sys\ConfigModel;
use library\dao\sys\ConfigDao;
use support\extend\Service;
use Webman\Http\Request;

/**
 * Service
 * @method ConfigModel create($data)
 * @method ConfigModel updateOrCreate(array $params,array $data)
 * @method ConfigModel update($id,array $data){
 * @method ConfigModel get($id,string $field = null)
 * @method ConfigModel find($id)
 * @method ConfigModel findOrFail($id)
 * @method ConfigModel firstOrCreate(array $params,array $data)
 * @method ConfigModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class ConfigService extends Service
{
    public function __construct()
    {
        $this->dao = ConfigDao::class;
        parent::__construct();
    }

    public function getConfig($key,$default=null){
        $value =  $this->value('value',['name'=>$key]);
        if(!empty($value)){
            $data = json_decode($value,true);
            return $data??$value;
        }
        elseif(!empty($default)){
            return $default;
        }
        return null;
    }

    /**
     * @param $content
     * @return bool
     */
    public function contentSafe($content,Request $request): bool
    {
        $sensitiveWords = $this->getConfig('sensitive_words');
        $bannedWords = $sensitiveWords ? explode("\n", $sensitiveWords) : [];
        $content = strtolower($content);
        $userinfo = 'ip:' . $request->getRealIp() . ' sid:' . $request->sessionId()  . "\n";
        foreach ($bannedWords as $word) {
            $word = trim($word);
            if (!$word) continue;
            $word = preg_quote($word);
            // 英文单词
            if (preg_match('/^[a-zA-Z ]+$/', $word, $match)) {
                if (preg_match('/\b' . $word . '\b/i', $content)) {
                    Log::warning(" $userinfo" . $word . " " . $match[0] . "\n" . $content . "\n");
                    return false;
                }
                continue;
            }
            // 中文词组使用正则匹配
            preg_match_all('/./u', $word, $matches);
            if (empty($matches[0])) {
                continue;
            }
            $preg = '/' . implode(".?", $matches[0]) . '/i';
            if (preg_match($preg, $content, $match)) {
                Log::warning( " $userinfo" . $word . " " . $match[0] . "\n" . $content. "\n");
                return false;
            }
        }
        return true;
    }
}
