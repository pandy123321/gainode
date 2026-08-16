<?php

namespace library\service\arbitrage;

use library\dao\arbitrage\ProjectOrderLogsDao;
use library\model\arbitrage\DayPlanModel;
use library\model\arbitrage\PositionModel;
use library\model\arbitrage\ProjectOrderLogsModel;
use library\model\arbitrage\ProjectOrderModel;
use library\model\member\UserWalletModel;
use library\service\member\UserWalletService;
use library\service\sys\DictService;
use support\arbitrage\math\Money;
use support\extend\Service;
use support\exception\VerifyException;

/**
 * 矿机订单收益日志
 *
 * @method ProjectOrderLogsModel create($data)
 * @method ProjectOrderLogsModel updateOrCreate(array $params, array $data)
 * @method ProjectOrderLogsModel update($id, array $data)
 * @method ProjectOrderLogsModel get($id, string $field = null)
 * @method ProjectOrderLogsModel find($id)
 * @method ProjectOrderLogsModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class ProjectOrderLogsService extends Service
{

    public function __construct()
    {
        $this->dao = ProjectOrderLogsDao::class;
        parent::__construct();
    }

    public function createProjectLog(array $data){
        $obj = $this->create($data);
        if(!empty($obj)){
            $orderDaySvc = new ProjectOrderDayService();
            $orderDaySvc->saveOrderDay($obj);
        }
        return $obj;
    }

    public function receive($user_id, $order_id)
    {
        $projectOrderService = new ProjectOrderService();
        $projectOrderObj = $projectOrderService->get($order_id);
        if(empty($projectOrderObj) || $projectOrderObj->user_id!=$user_id){
            throw new VerifyException("数据异常");
        }
        $conn = $this->connection();
        try{
            $conn->beginTransaction();
            $selector = $this->selector([
                'income_userid'=>$user_id,
                'level'=>0,
                'order_id'=>$order_id,
                'status'=>ProjectOrderLogsModel::STATUS_SETTLED,
            ]);
            $count = $selector->update(['status'=>ProjectOrderLogsModel::STATUS_RECEIVED]);
            $res = $selector->selectRaw('sum(income_amount-(income_amount*platform_rate/100)) as income_amount')->first()->toArray();
            $amount = $res['income_amount']??0;
            if(empty($amount)){
                throw new VerifyException("金额数据异常");
            }
            $walletService = new UserWalletService();
            $walletService->minusUserWallet(
                (int)$user_id,
                UserWalletModel::TYPE_ARBITRAGE,
                $amount,
                UserWalletService::EVENT_ARBITRAGE_VOID,
                " Arbitrage settlement and payment  #{$projectOrderObj->project_name}",
                'arbitrage_project_order_logs',
                0
            );
            $walletService->addUserWallet(
                (int)$user_id,
                UserWalletModel::TYPE_FUNDING,
                $amount,
                UserWalletService::EVENT_ARBITRAGE_VOID,
                " Arbitrage settlement extraction  #{$projectOrderObj->project_name}",
                'arbitrage_project_order_logs',
                0
            );
            $conn->commit();;
            return ['count'=>$count,'amount'=>$amount];
        }
        catch (\Exception $e){
            $conn->rollBack();
            throw $e;
        }
    }

    public function getGroupUserOrderIncomeMoney($user_id,$order_id): array
    {
        $rows = $this->groupBySelector(['status'],[
            'income_userid'=>$user_id,
            'level'=>0,
            'order_id'=>$order_id,
        ])->select($this->raw('status,SUM(income_amount-(income_amount*platform_rate/100)) as money'))->get()->toArray();
        $data = ['all' => 0];
        foreach ($rows as $v) {
            $data[$v['status']] = $v['money'];
            $data['all'] += $v['money'];
        }
        return $data;
    }

    /**
     * @param $user_id
     * @param $income_day
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getActiveUserSelector($user_id,$income_day)
    {
        return $this->selector([
            'income_userid'=>$user_id,
            'income_day'=>$income_day,
            'status'=>ProjectOrderLogsModel::STATUS_PENDING
        ]);
    }

    public function getUserIncomeMoneyByDay($user_id,$income_day): float
    {
        $money = $this->getActiveUserSelector($user_id,$income_day)
            ->sum('income_amount');
        return $money??0;
    }

    public function settleProjectOrderLog(ProjectOrderLogsModel $obj){
        $projectOrderService = new ProjectOrderService();
        $orderObj = $projectOrderService->get($obj->order_id);
        if($orderObj->status!=ProjectOrderModel::STATUS_RUNNING){
            return false;
        }
        $conn = $this->connection();
        try{
            $conn->beginTransaction();
            //计算收益
            $walletService = new UserWalletService();
            if($obj->level==0){
                //添加项目收益
                $dictService = new DictService();
                $config = $dictService->getDictConfigs('commission');
                $platform_rate = $config['platform_rate']??20;
                //修改订单统计数据
                $amount = $obj->income_amount - $obj->income_amount*($platform_rate/100);
                $update = [
                    'last_settle_time'=>date('Y-m-d H:i:s'),
                    'settle_amount'=>$this->raw('settle_amount+'.$amount)
                ];
                if(empty($orderObj->last_settle_time) || strtotime($orderObj->last_settle_time)< strtotime(date('Y-m-d'))){
                    $update['settle_cnt'] = (int) $orderObj->settle_cnt + 1;
                }
                $orderObj->update($update);
                $walletService->addUserWallet(
                    (int)$obj->income_userid,
                    UserWalletModel::TYPE_ARBITRAGE,
                    $amount,
                    UserWalletService::EVENT_ARBITRAGE_SETTLE,
                    $orderObj->project_name." settlement  #{$obj->descr}",
                    'arbitrage_project_order_logs',
                    (int)$obj->id
                );
                //修改执行状态
                $obj->saveData([
                    'status'=>ProjectOrderLogsModel::STATUS_SETTLED,
                    'platform_rate'=>$platform_rate
                ]);
            }
            else{
                //计算团队收益
                $walletService->addUserWallet(
                    (int)$obj->income_userid,
                    UserWalletModel::TYPE_FUNDING,
                    $obj->income_amount,
                    UserWalletService::EVENT_ARBITRAGE_PROFIT,
                    $orderObj->project_name." settlement commission",
                    'arbitrage_project_order_logs',
                    (int)$obj->id
                );
                //修改执行状态
                $obj->saveData([
                    'status'=>ProjectOrderLogsModel::STATUS_SETTLED
                ]);
            }
            $conn->commit();;
            return $obj;
        }
        catch (\Exception $e){
            $conn->rollBack();
            $obj->saveData(['status'=>-1]);
            throw $e;
        }
    }
}
