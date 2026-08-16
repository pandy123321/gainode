<?php

namespace library\service\sys;

use library\model\sys\LangKeyModel;
use library\dao\sys\LangKeyDao;
use support\extend\Service;

/**
 * Service
 * @method LangKeyModel create($data)
 * @method LangKeyModel updateOrCreate(array $params,array $data)
 * @method LangKeyModel update($id,array $data){
 * @method LangKeyModel get($id,string $field = null)
 * @method LangKeyModel find($id)
 * @method LangKeyModel findOrFail($id)
 * @method LangKeyModel firstOrCreate(array $params,array $data)
 * @method LangKeyModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class LangKeyService extends Service
{
    public function __construct()
    {
        $this->dao = LangKeyDao::class;
        parent::__construct();
    }

    /**
     * @param string $name 获取翻译的对象
     * @param $parent_id
     * @return LangKeyModel
     */
    public function getDataObj($name,$parent_id=0)
    {
        return $this->fetch(['name'=>$name,'parent_id'=>$parent_id]);
    }

    /**
     * @param $name
     * @param $parent_id
     * @return array|LangKeyModel|null
     */
    public function saveTranslateValue($name,$parent_id=0,$type=null,$source=null){
        $model = $this->getDataObj($name,$parent_id);
        if(empty($model) && !empty($name)){
            $model = $this->create([
                'name'=>$name,
                'content'=>$name,
                'parent_id'=>$parent_id,
                'status'=>1,
            ]);
        }
        return $model;
    }
}
