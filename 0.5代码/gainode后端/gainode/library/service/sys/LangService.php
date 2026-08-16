<?php

namespace library\service\sys;

use library\model\sys\LangKeyModel;
use library\model\sys\LangModel;
use library\dao\sys\LangDao;
use support\extend\Service;

/**
 * Service
 * @method LangModel create($data)
 * @method LangModel updateOrCreate(array $params,array $data)
 * @method LangModel update($id,array $data){
 * @method LangModel get($id,string $field = null)
 * @method LangModel find($id)
 * @method LangModel findOrFail($id)
 * @method LangModel firstOrCreate(array $params,array $data)
 * @method LangModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class LangService extends Service
{
    public function __construct()
    {
        $this->dao = LangDao::class;
        parent::__construct();
    }

    public function getLangList(){
        $list = $this->fetchAll(['status'=>1],['sort'=>'asc'],['code','name','locale','image']);
//        $data = [];
//        foreach ($list as $item){
//            $data[$item->code] = $item;
//        }
        return $list;
    }

    /**
     * @example zh-CN(简体中文)、en(英语)、ja(日语)、ko(韩语)
     * @return string
     */
    public function getTranslateStr(){
        $rows = $this->fetchAll(['status'=>1]);
        $str = '';
        foreach($rows as $v){
            $str.='、'.$v['code'].'('.$v['name'].')';
        }
        return trim($str,'、');
    }
}
