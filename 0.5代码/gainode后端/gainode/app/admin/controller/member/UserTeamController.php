<?php

namespace app\admin\controller\member;

use library\service\member\UserTeamService;
use library\validator\member\UserTeamValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 用户团队管理
 */
class UserTeamController extends Admin
{
    public function __construct()
    {
        $this->service = new UserTeamService();
        $this->validation = new UserTeamValidation();
        parent::__construct();
    }

    /**
     * 获取用户团队所有数据
     * @method GET
     * @param integer $user_id 父级用户ID
     * @param string $user_no 用户编号
     * @url /admin/member/userTeamAll
     * @return Response
     */
    public function all(): Response
    {
        try {
            $params['user_id'] = $this->getParams('user_id');
            $params['user_no'] = $this->getParams('user_no');
            $data = $this->service->getSelectList($params);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 用户团队列表
     * @method GET
     * @url /admin/member/userTeam
     * @return Response
     */
    public function list(): Response
    {
        try {
            $params = $this->getAllRequest();
            $data = $this->service->paginateArray($params);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 用户团队详情
     * @method GET
     * @url /admin/member/userTeam/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $userTeamObj = $this->service->get($id);
            if(empty($userTeamObj)){
                throw new VerifyException('执行失败');
            }
            $data = $userTeamObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
