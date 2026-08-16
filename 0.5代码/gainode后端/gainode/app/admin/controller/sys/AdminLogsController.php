<?php

namespace app\admin\controller\sys;

use library\service\sys\AdminLogsService;
use library\service\sys\TableFieldService;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 管理员日志管理
 */
class AdminLogsController extends Admin
{
    public function __construct()
    {
        $this->service = new AdminLogsService();
        parent::__construct();
    }

    /**
     * 列表
     * @param int $page 页码
     * @param int $size 显示条数
     * @param string account 账号
     * @method GET
     * @url /admin/sys/adminLogs
     * @return Response
     */
    public function list(): Response
    {
        $tableFieldService = new TableFieldService();
        $config = $tableFieldService->getSearchListData('sys_admin_logs');
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
     * @url /admin/sys/adminLogs/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $adminLogsObj = $this->service->get($id);
            if(empty($adminLogsObj)){
                throw new VerifyException('执行失败');
            }
            $data = $adminLogsObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 删除
     * @method DELETE
     * @url /admin/sys/adminLogs/{id}
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
