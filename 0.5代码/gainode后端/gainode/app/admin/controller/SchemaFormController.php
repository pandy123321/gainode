<?php

namespace app\admin\controller;

use library\service\sys\TableListService;
use support\controller\Admin;
use support\Response;

/**
 * 表单配置管理
 */
class SchemaFormController extends Admin
{
    public function __construct()
    {
        $this->service = new TableListService();
        parent::__construct();
    }

    /**
     * 获取所有表格
     * @method GET
     * @url /admin/schemaForm/list
     * @return Response
     */
    public function list()
    {
        try{
            $data = $this->service->getTableList();
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取表格的字段
     * @method GET
     * @url /admin/schemaForm/fields
     * @return Response
     */
    public function fields(){

    }

    /**
     * 设置表单配置
     * @method GET
     * @url /admin/schemaForm/setting
     * @return Response
     */
    public function setting(){

    }

    /**
     * 获取搜索表单配置
     * @method GET
     * @url /admin/schemaForm/search/{code}
     * @return Response
     */
    public function getSearchConfig(string $code)
    {
        try{
            $data = $this->service->getSearchFormData($code);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取列表表单配置
     * @method GET
     * @url /admin/schemaForm/list/{code}
     * @return Response
     */
    public function getListConfig(string $code)
    {
        try{
            $data = $this->service->getListFormData($code);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取创建表单配置
     * @method GET
     * @url /admin/schemaForm/create/{code}
     * @return Response
     */
    public function getCreateConfig(string $code)
    {
        try{
            $data = $this->service->getCreateSchemaForm($code);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取修改表单配置
     * @method GET
     * @url /admin/schemaForm/update/{code}
     * @return Response
     */
    public function getUpdateConfig(string $code)
    {
        try{
            $data = $this->service->getUpdateSchemaForm($code);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
