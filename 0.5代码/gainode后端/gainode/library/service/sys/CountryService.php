<?php

namespace library\service\sys;

use library\model\sys\CountryModel;
use library\dao\sys\CountryDao;
use support\extend\Service;

/**
 * Service
 * @method CountryModel create($data)
 * @method CountryModel updateOrCreate(array $params,array $data)
 * @method CountryModel update($id,array $data){
 * @method CountryModel get($id,string $field = null)
 * @method CountryModel find($id)
 * @method CountryModel findOrFail($id)
 * @method CountryModel firstOrCreate(array $params,array $data)
 * @method CountryModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class CountryService extends Service
{
    public function __construct()
    {
        $this->dao = CountryDao::class;
        parent::__construct();
    }

    public function getCountryList($lang='en'){
        $field = 'id,name,code,flag,three_code,dial';
        if($lang!='zh-Hans'){
            $field = 'id,name_en as name,code,flag,three_code,dial';
        }
        return $this->fetchAll(['status'=>1],['sort'=>'asc'],[$this->raw($field)]);
    }
}
