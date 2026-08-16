<?php
namespace library\event;


use library\model\arbitrage\PositionModel;
use library\model\arbitrage\ProjectOrderLogsModel;
use library\model\arbitrage\ProjectOrderModel;
use library\model\member\RechargeOrderModel;
use library\service\arbitrage\ProjectOrderLogsService;
use library\service\arbitrage\ProjectOrderService;
use library\service\member\LevelService;
use library\service\member\UserTeamService;
use library\service\sys\DictService;
use library\service\sys\Web3NetworkWalletService;

class User
{
    /**
     * 用户登陆的数据处理事件
     * @param $data
     * @param $event_name
     */
    function login($data,$event_name)
    {

    }

    /**
     * 用户退出登陆的数据处理事件
     * @param $data
     * @param $event_name
     */
    function register($data,$event_name)
    {
        if(isset($data['member_team']) && !empty($data['member_team'])){
            $memberTeamService = new UserTeamService();
            $memberTeam = $data['member_team'];
            if(!empty($memberTeam['parent_id'])){
                $parentsArr = explode(',',$memberTeam['parent_path']);
                $parentsArr = array_reverse($parentsArr);
                foreach($parentsArr as $k=>$uid) {
                    if ($k > 0) {
                        $update = [
                            'team_cnt'=>$memberTeamService->raw('team_cnt+1')
                        ];
                        if($k==1){
                            $update['invite_cnt'] = $memberTeamService->raw('invite_cnt+1');
                            $parentTeamObj = $memberTeamService->get($uid);
                            $update['invite_path'] = (empty($parentTeamObj['invite_path'])?$data['id']:($parentTeamObj['invite_path'].','.$data['id']));
                            $this->updateUserLevel($uid,$event_name);
                        }
                        $memberTeamService->update($uid,$update);
                    }
                }
            }
        }
        $web3WalletService = new Web3NetworkWalletService();
        $web3WalletService->initUserWalletAddress($data['id']);
    }

    /**
     * 用户退出登陆的数据处理事件
     * @param $data
     * @param $event_name
     */
    function logout($data,$event_name)
    {

    }

    public function updateUserLevel($user_id,$event_name){
        // 等级升级逻辑待完善；此处必须保证不抛错，避免购买成功后事件把请求打挂/留下事务悬挂假象
        try {
            $userId = (int)$user_id;
            if ($userId <= 0) {
                return;
            }
            $levelService = new LevelService();
            $levelService->getSelectList(0);
            (new UserTeamService())->count(['parent_id' => $userId]);
        } catch (\Throwable $e) {
            // 忽略等级计算失败，不影响主流程
        }
    }

    public function finishProjectOrder(ProjectOrderModel $obj,$event_name)
    {
        $memberTeamService = new UserTeamService();
        $memberTeamObj = $memberTeamService->get($obj->user_id);
        if(!empty($memberTeamObj['parent_id'])){
            $money = $obj->pay_amount;
            $parentsArr = explode(',',$memberTeamObj->parent_path);
            $parentsArr = array_reverse($parentsArr);
            foreach($parentsArr as $k=>$uid){
                if($k>0){
                    $update = [
                        'team_order_money'=>$memberTeamService->raw('team_order_money+'.$money),
                    ];
                    if($k==1){
                        $update['invite_order_money'] = $memberTeamService->raw('invite_order_money+'.$money);
                    }
                    $memberTeamService->update($uid,$update);
                }
                else{
                    $memberTeamService->update($uid,[
                        'order_cnt'=>$memberTeamObj->raw('order_cnt+1'),
                        'order_money'=>$memberTeamObj->raw('order_money+'.$money),
                    ]);
                    $this->updateUserLevel($uid,$event_name);
                }
            }
        }
    }

    public function calcProjectOrderCommission(ProjectOrderLogsModel $obj,$event_name){

        $memberTeamService = new UserTeamService();
        $memberTeamObj = $memberTeamService->get($obj->user_id);
        $api = new DictService();
        $config = $api->getDictConfigs('commission');
        $projectOrderLogService = new ProjectOrderLogsService();
        if(!empty($memberTeamObj['parent_id'])){
            $cdata = $obj->toM();
            $money = $obj->income_amount;
            $parentsArr = explode(',',$memberTeamObj->parent_path);
            $parentsArr = array_reverse($parentsArr);
            foreach($parentsArr as $k=>$uid){
                if($k>0){
                    if($config['is_open']=='Y' && $k<=$config['level_num']){
                        $income_rate = (isset($config['level'.$k])?$config['level'.$k]:0) / 100;
                        $income_money = $money * $income_rate;
                        $cdata['level'] = $k;
                        $cdata['money'] = $obj->income_amount;
                        $cdata['income_rate'] = $income_rate;
                        $cdata['income_userid'] = $uid;
                        $cdata['income_amount'] = $income_money;
                        $cdata['status'] = ProjectOrderLogsModel::STATUS_PENDING;
                        $update = [
                            'team_income_money'=>$memberTeamService->raw('team_income_money+'.$income_money),
                            'team_money'=>$memberTeamService->raw('team_money+'.$money),
                        ];
                        if($k==1){
                            $update['invite_income_money'] = $memberTeamService->raw('invite_income_money+'.$income_money);
                            $update['invite_money'] = $memberTeamService->raw('invite_money+'.$money);
                        }
                        $res = $memberTeamService->update($uid,$update);
                        if($res){
                            $projectOrderLogService->createProjectLog($cdata);
                        }
                    }
                }
            }
        }
    }

    public function finishRechargeOrder(RechargeOrderModel $obj,$event_name)
    {
        $memberTeamService = new UserTeamService();
        $memberTeamObj = $memberTeamService->get($obj->user_id);
        if(!empty($memberTeamObj['parent_id'])){
            $money = $obj->actual_amount;
            $parentsArr = explode(',',$memberTeamObj->parent_path);
            $parentsArr = array_reverse($parentsArr);
            foreach($parentsArr as $k=>$uid){
                if($k>0){
                    $update = [
                        'team_paid_money'=>$memberTeamService->raw('team_paid_money+'.$money),
                    ];
                    if($k==1){
                        $update['invite_paid_money'] = $memberTeamService->raw('invite_paid_money+'.$money);
                    }
                    $memberTeamService->update($uid,$update);
                }
            }
        }
    }
}
