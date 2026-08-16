<?php

namespace library\service\sys;

use library\dao\sys\DictListDao;
use library\model\sys\DictModel;
use library\dao\sys\DictDao;
use support\extend\Cache;
use support\extend\Service;

/**
 * Service
 * @method DictModel create($data)
 * @method DictModel updateOrCreate(array $params,array $data)
 * @method DictModel update($id,array $data){
 * @method DictModel get($id,string $field = null)
 * @method DictModel find($id)
 * @method DictModel findOrFail($id)
 * @method DictModel firstOrCreate(array $params,array $data)
 * @method DictModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method mixed[] getSelectList($type)
 */
class DictService extends Service
{
    public function __construct()
    {
        $this->dao = DictDao::class;
        parent::__construct();
    }

    /**
     * 获取页面的可选数据
     * @param int $type
     */
    public function getDictTypes($type=null){
        $data = [
            0=>'系统配置',
            1=>'推广设置',
            2=>'存储配置',
            3=>'支付配置',
            4=>'其他配置',
        ];
        if(!empty($type)){
            return isset($data[$type])?$data[$type]:[];
        }
        return $data;
    }

    /**
     * 获取字典配置
     * @param $dict_code
     * @param false $clearCache
     * @return array|mixed|null
     */
    public function getDictConfigs($dict_code,$clearCache=true){
        $cache_key = 'logic.dict_configs_'.$dict_code;
        $data = Cache::get($cache_key);
        if(empty($data) || $clearCache){
            $dictListDao = new DictListDao();
            $rows = $dictListDao->getDictList($dict_code);
            $data = [];
            foreach($rows as $v){
                $data[$v['field_code']] = $v['field_value'];
            }
            Cache::set($cache_key,$data,3600);
        }
        return $data;
    }

    /**
     * 获取某类型的配置
     * @param int $type
     */
    public function getDictListForType($type){
        $confList = $this->getSelectList($type);
        $dictListDao = new DictListDao();
        foreach($confList as &$v){
            $v['children'] =  $dictListDao->getDictList($v['code']);
        }
        return $confList;
    }
}
