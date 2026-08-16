<?php

namespace library\service\sys;

use library\model\sys\DictListModel;
use library\dao\sys\DictListDao;
use support\extend\Cache;
use support\extend\Service;

/**
 * Service
 * @method DictListModel create($data)
 * @method DictListModel updateOrCreate(array $params,array $data)
 * @method DictListModel update($id,array $data){
 * @method DictListModel get($id,string $field = null)
 * @method DictListModel find($id)
 * @method DictListModel findOrFail($id)
 * @method DictListModel firstOrCreate(array $params,array $data)
 * @method DictListModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method array getDictList(string $dict_code)
 */
class DictListService extends Service
{
    public function __construct()
    {
        $this->dao = DictListDao::class;
        parent::__construct();
    }

    /**
     * 保存配置数据
     * @param string $dict_code 指定字典编码
     * @param array $data 数据
     */
    public function saveDictListValue($dict_code,array $data){
        $list = $this->fetchAll(['dict_code'=>$dict_code]);
        foreach($list as $v){
            if(isset($data[$v['field_code']])){
                $value = (is_array($data[$v['field_code']])?implode(',',$data[$v['field_code']]):$data[$v['field_code']]);
                $this->update($v['id'],[
                    'field_value'=>$value
                ]);
            }
        }
        $cache_key = 'logic.dict_configs_'.$dict_code;
        Cache::delete($cache_key);
        return true;
    }

    /**
     * 保存字典数据
     * @param string $dict_code
     * @param array $data
     * @return int
     */
    public function saveDictConfigs(string $dict_code,array $data){
        $conn = $this->connection();
        try{
            $conn->beginTransaction();
            $datalist = [];
            $list = $this->fetchAll(['dict_code'=>$dict_code],[],['id','field_code'])->toArray();
            foreach($list as $v){
                $datalist[$v['field_code']] = $v;
            }
            $ct = 0;
            foreach ($data as $v){
                $v = array_filter($v);
                $v['status'] = 1;
                if(!empty($v['id'])){
                    $this->update($v['id'],$v);
                    $ct++;
                }
                elseif(!empty($datalist[$v['field_code']])){
                    $this->update($datalist[$v['field_code']]['id'],$v);
                    $ct++;
                }
                else{
                    $v['dict_code'] = $dict_code;
                    $this->create($v);
                    $ct++;
                }
            }
            $cache_key = 'logic.dict_configs_'.$dict_code;
            Cache::delete($cache_key);
            $conn->commit();
            return $ct;
        }
        catch (\Exception $e){
            $conn->rollBack();
            throw $e;
        }
    }
}
