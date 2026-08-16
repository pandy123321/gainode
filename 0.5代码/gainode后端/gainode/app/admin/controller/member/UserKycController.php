<?php

namespace app\admin\controller\member;

use library\service\member\UserKycService;
use library\service\member\UserService;
use library\validator\member\UserKycValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;
use support\utils\Data;

/**
 * 实名认证管理
 */
class UserKycController extends Admin
{
    public function __construct()
    {
        $this->service = new UserKycService();
        $this->validation = new UserKycValidation();
        parent::__construct();
    }

    /**
     * 实名认证列表
     * @method GET
     * @url /admin/member/userKyc
     * @return Response
     */
    public function list(): Response
    {
        try {
            $params = $this->getAllRequest();
            if(!empty($params['review_status']) && $params['review_status']=='all') unset($params['review_status']);
            $data = $this->service->paginateArray($params);
            $userService = new UserService();
            $ids = Data::toFlatArray($data['data'],'user_id');
            if(!empty($ids)){
                $userList = $userService->getUserFieldsByIds($ids,['user_no','account']);
                foreach($data['data'] as $k=>$v){
                    $data['data'][$k]['user_no'] = $userList[$v['user_id']]['user_no']??'';
                    $data['data'][$k]['account'] = $userList[$v['user_id']]['account']??'';
                }
            }
            $data['report'] = $this->service->getGroupAllStatusCnt($params);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 实名认证详情
     * @method GET
     * @url /admin/member/userKyc/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $userKycObj = $this->service->get($id);
            if(empty($userKycObj)){
                throw new VerifyException('执行失败');
            }
            $data = $userKycObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 审核实名认证
     * @method PUT
     * @url /admin/member/userKyc/verify/{id}
     * @return Response
     */
    public function verify(int $id): Response
    {
        try {
            $status = $this->getPost('review_status');
            $reject_reason = $this->getPost('reject_reason');
            $res = $this->service->verifyUserKyc($id,$status,$reject_reason,$this->request->getUserID());
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 删除实名认证
     * @method DELETE
     * @url /admin/member/userKyc/{id}
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
