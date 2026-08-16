<?php

namespace library\service\member;

use library\model\member\UserKycModel;
use library\dao\member\UserKycDao;
use support\exception\VerifyException;
use support\extend\Service;

/**
 * Service
 * @method UserKycModel create($data)
 * @method UserKycModel updateOrCreate(array $params,array $data)
 * @method UserKycModel update($id,array $data){
 * @method UserKycModel get($id,string $field = null)
 * @method UserKycModel find($id)
 * @method UserKycModel findOrFail($id)
 * @method UserKycModel firstOrCreate(array $params,array $data)
 * @method UserKycModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class UserKycService extends Service
{
    public function __construct()
    {
        $this->dao = UserKycDao::class;
        parent::__construct();
    }

    /**
     * @param $userid
     * @return UserKycModel|null
     */
    public function getUserKycObj($userid){
        return $this->fetch(['user_id'=>$userid]);
    }

    public function getGroupAllStatusCnt(array $params = []): array
    {
        if(isset($params['size'])) unset($params['size']);
        if(isset($params['page'])) unset($params['page']);
        $rows = $this->groupBySelector(['review_status'],$params)->select($this->raw('review_status, COUNT(*) AS ct'))->get()->toArray();
        $data = ['all' => 0];
        foreach ($rows as $v) {
            $data[$v['review_status']] = $v['ct'];
            $data['all']    += $v['ct'];
        }
        return $data;
    }

    /**
     * @param $user_id
     * @param $data
     * @return UserKycModel|null
     */
    public function saveUserKycData($userid,$data){
        $kycObj = $this->getUserKycObj($userid);
        if(empty($kycObj)){
            $data['user_id'] = $userid;
            $data['status'] = 1;
            $data['review_status'] = UserKycModel::STATUS_CREATED;
            $kycObj = $this->create($data);
        }
        else{
            $data['status'] = 1;
            $data['review_status'] = UserKycModel::STATUS_CREATED;
            $data['reject_reason'] = '';
            $data['review_admin_id'] = 0;
            $data['review_time'] = 0;
            $kycObj->saveData($data);
        }
        $userService = new UserService();
        $userService->updateUser($kycObj->user_id,['is_verify'=>1]);
        return $kycObj;
    }

    public function verifyUserKyc($id,$review_status,$reject_reason=null,$admin_id=0){
        $kycObj = $this->get($id);
        if (empty($kycObj) || $kycObj->review_status === 'approved') {
            throw new VerifyException('实名认证不存在或状态不可审核');
        }
        $conn = $this->connection();
        try {
            $conn->beginTransaction();
            $kycObj->review_status = $review_status;
            $kycObj->reject_reason = $reject_reason;
            $kycObj->review_admin_id = $admin_id;
            $kycObj->review_time = time();
            $res = $kycObj->save();
            if($res){
                $status = ($review_status == 'approved'?UserKycModel::STATUS_APPROVED:UserKycModel::STATUS_REJECTED);
                $userService = new UserService();
                $userService->updateUser($kycObj->user_id,['is_verify'=>$status]);
            }
            $conn->commit();
            return $res;
        }
        catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
