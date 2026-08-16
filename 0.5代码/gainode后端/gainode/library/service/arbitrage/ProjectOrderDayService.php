<?php

namespace library\service\arbitrage;

use library\model\arbitrage\ProjectOrderDayModel;
use library\dao\arbitrage\ProjectOrderDayDao;
use library\model\arbitrage\ProjectOrderLogsModel;
use library\model\member\UserWalletModel;
use library\service\member\UserWalletService;
use support\exception\VerifyException;
use support\extend\Cache;
use support\extend\Log;
use support\extend\Service;

/**
 * Service
 * @method ProjectOrderDayModel create($data)
 * @method ProjectOrderDayModel updateOrCreate(array $params,array $data)
 * @method ProjectOrderDayModel update($id,array $data){
 * @method ProjectOrderDayModel get($id,string $field = null)
 * @method ProjectOrderDayModel find($id)
 * @method ProjectOrderDayModel findOrFail($id)
 * @method ProjectOrderDayModel firstOrCreate(array $params,array $data)
 * @method ProjectOrderDayModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class ProjectOrderDayService extends Service
{
    public function __construct()
    {
        $this->dao = ProjectOrderDayDao::class;
        parent::__construct();
    }

    public function getUserOrderDay($user_id,$income_day){
        return $this->fetch(['user_id'=>$user_id,'income_day'=>$income_day]);
    }

    public function saveOrderDay(ProjectOrderLogsModel $logObj){
        $userId   = (int) $logObj->income_userid;
        $incomeDay = (string) $logObj->income_day;
        if ($userId <= 0 || $incomeDay === '') {
            return null;
        }
        $amountField = $logObj->level == 0 ? 'project_amount' : 'team_amount';
        $amount      = (float) $logObj->income_amount;
        $dayObj = $this->getUserOrderDay($userId,$incomeDay);
        if (empty($dayObj)) {
            $cdata = [
                'user_id'     => $userId,
                'income_day'  => $incomeDay,
                'project_amount' => $logObj->level == 0 ? $amount : 0,
                'team_amount'    => $logObj->level == 0 ? 0 : $amount,
                'status'      => 0,
            ];
            $dayObj = $this->create($cdata);
        } else {
            // 已存在：仅当未结算时累加（用字段表达式原子累加，避免并发覆盖）
            if ($dayObj->status == 0) {
                $this->update((int) $dayObj->id, [
                    $amountField => $this->raw($amountField . '+' . $amount),
                ]);
            }
        }
        return $dayObj;
    }

    //作废
    public function settleProjectOrderDayAmount(ProjectOrderDayModel $dayObj){
        $dayObj->saveData([
            'status'=>1,
        ]);
        return true;

        if($dayObj->status==1){
            return false;
        }
        $projectOrderLogSvc = new ProjectOrderLogsService();
        $money = $projectOrderLogSvc->getUserIncomeMoneyByDay($dayObj->user_id,$dayObj->income_day);
        $day_income = ($dayObj->project_amount+$dayObj->team_amount);
        $tolerance = $day_income * 0.002;
        if ($money + $tolerance < $day_income) {
            Log::channel('library')->error('项目订单日结算金额不足',[
                'user_id'=>$dayObj->user_id,
                'income_day'=>$dayObj->income_day,
                'project_amount'=>$dayObj->project_amount,
                'team_amount'=>$dayObj->team_amount,
                'money'=>$money
            ]);
            return false;
        }
        $walletService = new UserWalletService();
        $projectOrderSvc = new ProjectOrderService();
        $conn = $this->connection();
        try {
            $conn->beginTransaction();
            $dayObj->saveData([
                'status'=>1,
            ]);
            if($dayObj->project_amount>0){
                //处理订单的结算金额
                $rows = $projectOrderLogSvc->getActiveUserSelector($dayObj->user_id,$dayObj->income_day)->groupBy('project_id')
                    ->selectRaw(['order_id','sum(income_amount) as income_amount'])->get();
                foreach($rows as $v){
                    $projectOrderSvc->updateAll(['id'=>$v['order_id']],[
                        'settle_amount'=>$projectOrderSvc->raw('settle_amount+'.$v['income_amount'])
                    ]);
                }
                //添加项目收益
                $walletService->addUserWallet(
                    (int)$dayObj->user_id,
                    UserWalletModel::TYPE_ARBITRAGE,
                    $money,
                    UserWalletService::EVENT_ARBITRAGE_SETTLE,
                    " Arbitrage day settlement  #{$dayObj->income_day}",
                    'arbitrage_project_order_day',
                    (int)$dayObj->id
                );
            }
            if($dayObj->team_amount>0){
                //计算团队收益
                $walletService->addUserWallet(
                    (int)$dayObj->user_id,
                    UserWalletModel::TYPE_FUNDING,
                    $money,
                    UserWalletService::EVENT_ARBITRAGE_PROFIT,
                    " Arbitrage day settlement commission  #{$dayObj->income_day}",
                    'arbitrage_project_order_day',
                    (int)$dayObj->id
                );
            }
            $projectOrderLogSvc->getActiveUserSelector($dayObj->user_id,$dayObj->income_day)->update(['status'=>ProjectOrderLogsModel::STATUS_SETTLED]);

            $conn->commit();
            return true;
        }
        catch (\Exception $e){
            $conn->rollBack();
            return false;
        }
    }
}
