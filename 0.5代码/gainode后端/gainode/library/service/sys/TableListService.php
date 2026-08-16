<?php

namespace library\service\sys;

use library\model\sys\TableListModel;
use library\dao\sys\TableListDao;
use support\exception\VerifyException;
use support\extend\Service;

/**
 * Service
 * @method TableListModel create($data)
 * @method TableListModel updateOrCreate(array $params,array $data)
 * @method TableListModel update($id,array $data){
 * @method TableListModel get($id,string $field = null)
 * @method TableListModel find($id)
 * @method TableListModel findOrFail($id)
 * @method TableListModel firstOrCreate(array $params,array $data)
 * @method TableListModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method TableListModel getTableByCode($code)
 * @method TableListModel getTableByName($name)
 */
class TableListService extends Service
{
    private $fieldService;
    public function __construct()
    {
        $this->dao = TableListDao::class;
        $this->fieldService = new TableFieldService();
        parent::__construct();
    }

    public function getTableList(){
        return $this->getNewDao()->fetchAll();
    }

    public function getSearchFormData($code){
        $tableConfig = $this->getTableByCode($code);
        if(empty($tableConfig)){
            throw new VerifyException('配置表不存在');
        }
        $fieldService = new TableFieldService();
        return $fieldService->buildQuerySchemaJson($tableConfig);
    }

    public function getListFormData($code){
        $tableConfig = $this->getTableByCode($code);
        if(empty($tableConfig)){
            throw new VerifyException('配置表不存在');
        }
        $fieldService = new TableFieldService();
        return $fieldService->buildListSchemaJson($tableConfig);
    }

    public function getCreateSchemaForm($code){
        $tableConfig = $this->getTableByCode($code);
        if(empty($tableConfig)){
            throw new VerifyException('配置表不存在');
        }
        $fieldService = new TableFieldService();
        return $fieldService->buildCreateSchemaJson($tableConfig);
    }

    public function getUpdateSchemaForm($code){
        $tableConfig = $this->getTableByCode($code);
        if(empty($tableConfig)){
            throw new VerifyException('配置表不存在');
        }
        $fieldService = new TableFieldService();
        return $fieldService->buildUpdateSchemaJson($tableConfig);
    }
}
