<?php

namespace library\service\member;

use library\dao\member\UserDao;
use library\model\member\UserModel;
use library\model\sys\AdminModel;
use library\service\sys\FlowNumbersService;
use support\exception\VerifyException;
use support\extend\Cache;
use support\extend\Service;
use support\utils\Random;
use Webman\Event\Event;

/**
 * Service
 * @method UserModel create($data)
 * @method UserModel updateOrCreate(array $params,array $data)
 * @method UserModel update($id,array $data){
 * @method UserModel get($id,string $field = null)
 * @method UserModel find($id)
 * @method UserModel findOrFail($id)
 * @method UserModel firstOrCreate(array $params,array $data)
 * @method UserModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method UserModel getUserByAccount(string $account)
 */
class UserService extends Service
{
    public function __construct()
    {
        $this->dao = UserDao::class;
        parent::__construct();
    }

    /**
     * 获取用户编号
     * @param string $suffix
     * @return mixed
     */
    public function getUserNo($suffix=''){
        $flowNumberServer = new FlowNumbersService();
        $user_no = $flowNumberServer->getFlowOrderNo($this->getNewDao()->getTable(),$suffix);
        $userObj = $this->get($user_no,'user_no');
        if(empty($userObj)){
            return $user_no;
        }
        return $this->getUserNo();
    }

    public function getUserById($userid,$clearCache=false){
        $key = 'model_user_'.$userid;
        $data = Cache::get($key);
        if(empty($data) || $clearCache){
            $userObj = $this->get($userid);
            $data = $userObj->toM();
            Cache::set($key,$data,3600*24);
        }
        return $data;
    }

    /**
     * 创建用户
     * @param $data {account,password,vcode,invite_code,account_type}
     * @return UserModel
     */
    public function createUser($data){
        $memberTeamService = new UserTeamService();
        $parentMemberTeamObj = null;
        $parent_id = 0;
        if(!empty($data['invite_code'])) {
            $parentMemberTeamObj = $memberTeamService->getUserTeamByCode($data['invite_code']);
            if(empty($parentMemberTeamObj)){
                throw new VerifyException('用户邀请码不存在');
            }
            $parent_id = $parentMemberTeamObj['user_id'];
        }
        $exists = $this->getUserByAccount($data['account']);
        if(!empty($exists)){
            throw new VerifyException('账号已存在');
        }

        $conn = $this->connection();
        try{
            $conn->beginTransaction();
            $data['user_no'] = $this->getUserNo();
            $data['eid'] = getEid();
            $data['encrypt'] = Random::getRandStr(8,6);
            if(is_null($data['password'])){
                $data['password'] = $data['encrypt'];
            }
            $data['password'] = hashPassword($data['password'],$data['encrypt']);
            if(isset($data['account_type']) && $data['account_type']=='email'){
                $data['email'] = $data['account'];
            }
            elseif(isset($data['account_type']) && $data['account_type']=='mobile'){
                $data['mobile'] = $data['account'];
            }
            $memberObj = $this->create($data);
            $userWalletService = new UserWalletService();
            $userWalletService->createUserWallet($memberObj->id);

            $parent_path = $memberObj->id;
            if(!empty($parentMemberTeamObj)){
                $parent_path = $parentMemberTeamObj->parent_path.','.$memberObj->id;
                $memberTeamObj = $memberTeamService->create([
                    'user_id'=>$memberObj->id,
                    'account'=>$memberObj->account,
                    'invite_code'=>$memberTeamService->getInviteCode(),
                    'parent_id'=>$parent_id,
                    'parent_level'=>($parentMemberTeamObj->parent_level+1),
                    'parent_path'=>$parent_path
                ]);
            }
            else{
                $memberTeamObj = $memberTeamService->create([
                    'user_id'=>$memberObj->id,
                    'account'=>$memberObj->account,
                    'invite_code'=>$memberTeamService->getInviteCode(),
                    'parent_id'=>$parent_id,
                    'parent_level'=>0,
                    'parent_path'=>$parent_path
                ]);
            }
            $member = $memberObj->toArray();
            $member['member_team'] = $memberTeamObj->toArray();

            $conn->commit();

            Event::emit('user.register',$member);

            return $memberObj;
        }
        catch (\Exception $e){
            $conn->rollBack();
            throw $e;
        }
    }

    public function setUserArbitrageStatus($user_id,$enabled){
        try {
            if ($enabled === 1) {
                $balance = (new UserWalletService())->getUserWalletValue((int) $user_id, 'Arbitrage');
                if ($balance <= 0) {
                    throw new VerifyException('套利本金(Arbitrage 钱包)必须大于 0');
                }
            }
            $res = $this->updateUser($user_id, ['is_arbitrage' => $enabled]);
            return $res;
        }
        catch (\Throwable $e) {
            throw $e;
        }
    }

    public function updateUser($id, array $data)
    {
        $userObj = $this->get($id);
        if(empty($userObj)){
            throw new VerifyException('用户不存在');
        }
        $userObj->setAttributes($data);
        $res =$userObj->save();
        return $this->getUserById($id,true);
    }

    /**
     * 重制用户密码
     * @param $account
     */
    public function resetUserPassword($account){
        $userObj = $this->getUserByAccount($account);
        if(empty($userObj)){
            throw new VerifyException('账号不存在');
        }
        elseif($userObj['status']!=1){
            throw new VerifyException('账号已被锁定');
        }
        $new_password = Random::getPwdRandom(6);
        $passpwd= hashPassword($new_password,$userObj->encrypt);
        $data = [
            'password'=>$passpwd,
            'modify_pwd_time'=>getCurrentDate(),
            'pwd_strong'=>0
        ];
        $res = $userObj->saveData($data);
        if(!empty($res)){
            return $new_password;
        }
        return false;
    }

    /**
     * 修改用户密码
     * @param UserModel $userObj  用户
     * @param string $new_password 新密码
     * @param string $old_password 旧密码
     * @return AdminModel
     */
    public function modifyPassword(UserModel $userObj,string $new_password,string $old_password = null,int $pwd_strong=0) {
        if(!empty($old_password) && hashPassword($old_password,$userObj->encrypt)!=$userObj->password){
            throw new VerifyException('你输入的密码错误');
        }
        $passpwd= hashPassword($new_password,$userObj->encrypt);
        $data = [
            'password'=>$passpwd,
            'modify_pwd_time'=>getCurrentDate(),
        ];
        if(!empty($pwd_strong)){
            $data['pwd_strong'] =$pwd_strong;
        }
        return $userObj->update($data);
    }

    /**
     * 修改用户密码
     * @param int $userid  用户ID
     * @param string $new_password 新密码
     * @param string $old_password 旧密码
     * @return bool
     */
    public function modifyPayPassword(int $userid,string $new_password,string $old_password=null):bool {
        $userObj = $this->get($userid);
        if (empty($userObj)) {
            throw new VerifyException('账号不存在');
        }
        elseif(!empty($old_password) && hashPassword($old_password,$userObj->encrypt)!=$userObj->pay_password){
            throw new VerifyException('你输入的密码错误');
        }
        $passpwd= hashPassword($new_password,$userObj->encrypt);
        return $userObj->saveData(['pay_password'=>$passpwd]);
    }

    /**
     * 修改用户密码
     * @param array $ids 用户ID
     * @param string $password 密码
     * @return bool
     */
    public function modifyUsersPassword(array $ids,string $password){
        $rows = $this->fetchAll(['id'=>['in',$ids]]);
        foreach($rows as $obj){
            $passpwd = hashPassword($password,$obj->encrypt);
            $obj->update(['password'=>$passpwd,'modify_pwd_time'=>getCurrentDate()]);
        }
        return true;
    }

    public function getUserFieldsByIds(array $user_ids,array $field=[]){
        $field[] = 'id';
        $rows = $this->fetchAll(['id'=>['in',$user_ids]],[],$field);
        $data = [];
        foreach($rows as $v){
            $data[$v['id']] = $v;
        }
        return $data;
    }
}
