<?php

namespace library\service\sys;

use library\model\sys\ShortUrlModel;
use library\dao\sys\ShortUrlDao;
use support\extend\Redis;
use support\extend\Service;
use support\utils\Data;

/**
 * Service
 * @method ShortUrlModel create($data)
 * @method ShortUrlModel updateOrCreate(array $params,array $data)
 * @method ShortUrlModel update($id,array $data){
 * @method ShortUrlModel get($id,string $field = null)
 * @method ShortUrlModel find($id)
 * @method ShortUrlModel findOrFail($id)
 * @method ShortUrlModel firstOrCreate(array $params,array $data)
 * @method ShortUrlModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class ShortUrlService extends Service
{
    public function __construct()
    {
        $this->dao = ShortUrlDao::class;
        parent::__construct();
    }

    public function buildShortUrl(string $code){
        return  url($code);
    }

    public function getNewShortCode()
    {
        $short_num = Redis::incr('short_url_num');
        return Data::base10To62($short_num);
    }

    /**
     * @param $longUrl
     * @return string
     */
    public function toShort($longUrl,$client_ip=null)
    {
        $retries = 0;
        $max_retries = 3;

        while ($retries < $max_retries) {
            try {
                $is_lock = Redis::addLock('short_url_num_lock', 3);
                if (!$is_lock) {
                    sleep(1);
                }
                $long_url_hash = md5($longUrl);
                $shortUrlObj = $this->fetch(['long_url_hash' => $long_url_hash]);
                if (!empty($shortUrlObj)) {
                    return $this->buildShortUrl($shortUrlObj['code']);
                }
                if (empty($client_ip)) {
                    $client_ip = \request()->getRemoteIp();
                }
                $code = $this->getNewShortCode();
                $this->create([
                    'code' => $code,
                    'long_url' => $longUrl,
                    'long_url_hash' => $long_url_hash,
                    'ip' => $client_ip,
                    'request_num' => 0
                ]);
                Redis::del('short_url_num_lock');
                return $this->buildShortUrl($code);
            }
            catch (\Exception $e) {
                $retries++;
                if ($retries >= $max_retries) {
                    throw $e;
                }
                $max_id = $this->max('id');
                Redis::set('short_url_num', $max_id);
                // 短暂延迟后重试
                usleep(50000 * $retries);
            }
        }

        throw new \Exception('短链接生成失败');
    }

    /**
     * @param $code
     * @return false|mixed
     */
    public function toLong($code)
    {
        $shortUrlObj = $this->fetch(['code' => $code]);
        if (empty($shortUrlObj)) {
            return false;
        }
        $shortUrlObj->saveData([
            'request_num' => ($shortUrlObj['request_num'] + 1)
        ]);
        return $shortUrlObj['long_url'];
    }
}
