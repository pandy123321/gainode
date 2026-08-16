<?php

namespace library\service\member;

use library\dao\member\UserDao;
use library\model\member\UserTeamModel;
use library\dao\member\UserTeamDao;
use support\extend\Service;
use support\utils\Data;
use support\utils\Random;

/**
 * Service
 * @method UserTeamModel create($data)
 * @method UserTeamModel updateOrCreate(array $params,array $data)
 * @method UserTeamModel update($id,array $data){
 * @method UserTeamModel get($id,string $field = null)
 * @method UserTeamModel find($id)
 * @method UserTeamModel findOrFail($id)
 * @method UserTeamModel firstOrCreate(array $params,array $data)
 * @method UserTeamModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method UserTeamModel getUserTeamByCode(string $code)
 */
class UserTeamService extends Service
{
    public function __construct()
    {
        $this->dao = UserTeamDao::class;
        parent::__construct();
    }

    public function getSelectList(array $params = []){
        $where = [];
        if(!empty($params['user_id'])){
            $teamObj = $this->get($params['user_id']);
            $where['parent_path'] = ['like_left',$teamObj->parent_path];
        }
        elseif(!empty($params['user_no'])){
            $userDao = new UserDao();
            $userObj = $userDao->fetch(['user_no'=>$params['user_no']]);
            $where['parent_path'] = ['like_left',$userObj->team->parent_path];
        }
        return $this->fetchAll($where,['user_id'=>'asc'],['user_id','account','parent_id','parent_level','invite_cnt','team_cnt']);
    }

    public function getInviteCode(){
        $str = Random::getRandStr(6);
        $inviteObj = $this->getUserTeamByCode($str);
        if(!empty($inviteObj)){
            return $this->getInviteCode();
        }
        return $str;
    }

    /**
     * 获取用的的级别
     * @param array $user_ids
     */
    public function getUserInviteCount(array $user_ids){
        $rows = $this->fetchAll(['user_id'=>['in',$user_ids]],[],['user_id','invite_cnt'])->toArray();
        return Data::toKVArray($rows,'user_id','invite_cnt');
    }

    public function getInviteUserCnt($user_id)
    {
        return $this->count(['parent_id'=>$user_id]);
    }

    /**
     * 获取用的邀请列表
     * @param array $user_ids
     */
    public function getTeamListByIds(array $user_ids){
        $rows = $this->fetchAll(['user_id'=>['in',$user_ids]])->toArray();
        return Data::toKVArray($rows,'user_id');
    }
}
