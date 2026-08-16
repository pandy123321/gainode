<?php

namespace app\admin\controller\sys;

use library\service\sys\OperationLogsService;
use library\service\sys\TableFieldService;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 操作日志管理
 */
class OperationLogsController extends Admin
{
    public function __construct()
    {
        $this->service = new OperationLogsService();
        parent::__construct();
    }

    /**
     * 列表
     * @param int $page 页码
     * @param int $size 显示条数
     * @param int $user_id 用户ID
     * @param string $request_url 请求地址
     * @method GET
     * @url /admin/sys/operationLogs
     * @return Response
     */
    public function list(): Response
    {
        $tableFieldService = new TableFieldService();
        $config = $tableFieldService->getSearchListData('sys_operation_logs');
        if(!empty($config)){
            $params = $this->createSearchListArray($config['query'],$config['where']);
            $sort = $this->getSortArray('sort');
            $fields = $config['list']??[];
            $data = $this->service->paginateArray($params,$sort,$fields);
        }
        else{
            $params = $this->getAllRequest();
            $data = $this->service->paginateArray($params);
        }
        return $this->json($data);
    }

    /**
     * 详情
     * @method GET
     * @url /admin/sys/operationLogs/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $operationLogsObj = $this->service->get($id);
            if(empty($operationLogsObj)){
                throw new VerifyException('执行失败');
            }
            $data = $operationLogsObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 删除
     * @method DELETE
     * @url /admin/sys/operationLogs/{id}
     * @return Response
     */
    public function delete(int $id): Response
    {
        try {
            $res = $this->service->delete($id);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'删除成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
