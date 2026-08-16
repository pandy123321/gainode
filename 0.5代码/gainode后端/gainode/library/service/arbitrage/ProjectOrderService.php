<?php

namespace library\service\arbitrage;

use library\dao\arbitrage\ProjectOrderDao;
use library\model\arbitrage\DayPlanModel;
use library\model\arbitrage\PositionModel;
use library\model\arbitrage\ProjectModel;
use library\model\arbitrage\ProjectOrderLogsModel;
use library\model\arbitrage\ProjectOrderModel;
use library\model\member\WithdrawOrderModel;
use library\service\member\OrderRecordService;
use library\service\member\UserService;
use library\service\member\UserTeamService;
use library\service\member\UserWalletService;
use library\service\sys\DictService;
use library\service\sys\FlowNumbersService;
use support\arbitrage\math\Money;
use support\exception\VerifyException;
use support\extend\Log;
use support\extend\Service;
use support\utils\Data;
use Webman\Event\Event;

/**
 * 矿机订单
 *
 * @method ProjectOrderModel create($data)
 * @method ProjectOrderModel updateOrCreate(array $params, array $data)
 * @method ProjectOrderModel update($id, array $data)
 * @method ProjectOrderModel get($id, string $field = null)
 * @method ProjectOrderModel find($id)
 * @method ProjectOrderModel findOrFail($id)
 * @method ProjectOrderModel firstOrCreate(array $params, array $data)
 * @method ProjectOrderModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class ProjectOrderService extends Service
{
    /** 提现来源账户（从资金账户扣减） */
    const DEBIT_WALLET = 'Funding';

    public function __construct()
    {
        $this->dao = ProjectOrderDao::class;
        parent::__construct();
    }

    /**
     * 获取订单编号
     * @param string $suffix
     * @return mixed
     */
    public function getOrderNo($suffix=''){
        $flowNumberServer = new FlowNumbersService();
        $order_no = $flowNumberServer->getFlowOrderNo($this->getNewDao()->getTable(),$suffix);
        $orderObj = $this->get($order_no,'order_no');
        if(empty($orderObj)){
            return $order_no;
        }
        return $this->getOrderNo();
    }

    public function getOrderStatusList($num=null){
        $data = [
            'all'=> '全部',
            'unpaid'=> '待支付',
            'pending'=> '支付中',
            'paid'=>'已支付',
            'refunded'=>'已拒绝',
            'completed'=>'已完成',
            'closed'=>'已关闭'
        ];
        if(!empty($num) && isset($data[$num])){
            return $data[$num];
        }
        return $data;
    }

    public function getOrderByNo(string $order_no): ?ProjectOrderModel
    {
        return $this->fetch(['order_no'=>$order_no]);
    }

    public function getGroupAllStatusCnt(array $params = []): array
    {
        if(isset($params['size'])) unset($params['size']);
        if(isset($params['page'])) unset($params['page']);
        $rows = $this->groupBySelector(['order_status'],$params)->select($this->raw('order_status, COUNT(*) AS ct, SUM(amount) AS money'))->get()->toArray();
        $data = ['all' => ['ct' => 0, 'money' => 0]];
        foreach ($rows as $v) {
            $data[$v['order_status']] = $v;
            $data['all']['ct']    += $v['ct'];
            $data['all']['money'] += $v['money'];
        }
        return $data;
    }

    public function getUserProjectOrderList(int $user_id, ?array $params=[])
    {
        $where = ['user_id'=>$user_id];
        if(!empty($params['order_status'])){
            if(is_string($params['order_status'])){
                $where['order_status'] = $params['order_status'];
            }
            elseif(is_array($params['order_status'])){
                $where = ['in',$params['order_status']];
            }
        }
        return $this->paginateArray($where,['id'=>'desc']);
    }

    public function verifyUserCanBuyProject($userId,$projectId,$is_throw=true){
        // 金额范围校验
        $projectService = new ProjectService();
        $projectObj = $projectService->get($projectId);
        if(empty($projectObj) || $projectObj->status != ProjectModel::STATUS_ONLINE){
            if($is_throw){
                throw new VerifyException('项目不存在或已关闭');
            }
            return null;
        }
        elseif(!empty($projectObj->start_date) && strtotime($projectObj->start_date)>time()){
            if($is_throw) {
                throw new VerifyException('项目未开始');
            }
            return null;
        }
        elseif($projectObj->total_cnt<=$projectObj->sales_cnt){
            if($is_throw) {
                throw new VerifyException('项目已售完');
            }
            return null;
        }
        $res = $this->selector(['user_id'=>$userId,'project_id'=>$projectId])
            ->selectRaw('count(*) count,sum(amount) amount')->first()->toArray();
        if($projectObj->limit_num>0 && ($res['count']??0 )>= $projectObj->limit_num){
            if($is_throw) {
                throw new VerifyException('超过购买限制');
            }
            return null;
        }
        if($projectObj->user_amount>0 && ($res['amount']??0) < $projectObj->user_amount){
            if($is_throw) {
                throw new VerifyException('业绩未达标');
            }
            return null;
        }
        $userTeamSvc = new UserTeamService();
        $invite_cnt = $userTeamSvc->getInviteUserCnt($userId);
        if($projectObj->user_invite>0 && $invite_cnt < $projectObj->user_invite){
            if($is_throw) {
                throw new VerifyException('邀请人数不足');
            }
            return null;
        }
        return $projectObj;
    }

    /**
     * 创建矿机订单
     * @param int   $user_id
     * @param int $project_id
     */
    public function createOrder(int $user_id, int $project_id): ProjectOrderModel
    {
        $projectObj= $this->verifyUserCanBuyProject($user_id,$project_id);
        // 余额校验
        $walletService = new UserWalletService();
        $project_price = (float)$projectObj->project_price;
        $available = $walletService->getUserWalletValue($user_id, self::DEBIT_WALLET);
        if ($available < $project_price) {
            throw new VerifyException('余额不足');
        }
        $buy_order_cnt = $this->count(['user_id'=>$user_id,'project_id'=>$project_id,'status'=>['gt',1]]);
        if($projectObj->limit_num>0 && $buy_order_cnt>=$projectObj->limit_num){
            throw new VerifyException('您购买已满额，不能再购买');
        }
        $total_order_cnt = $this->count(['user_id'=>$user_id,'status'=>['gt',0]]);
        $expires_at = date('Y-m-d',strtotime('+'.($projectObj->project_day+1).' day'));
        $orderData = [
            'user_id'       => $user_id,
            'project_id'    => $project_id,
            'order_no'      => $this->getOrderNo(),
            'project_name'  => $projectObj->name,
            'min_day_rate'  => $projectObj->min_day_rate,
            'max_day_rate'  => $projectObj->max_day_rate,
            'amount'        => $project_price,
            'fee'           => 0,
            'order_status'  => 'paid',
            'pay_method'    => 'Balance',
            'pay_amount'    => $project_price,
            'paid_at'       => date('Y-m-d H:i:s'),
            'is_calc_money' => 1,
            'is_default' => ($total_order_cnt==0?1:0),
            'expires_at'    => $expires_at,
            'status'        => ProjectOrderModel::STATUS_PENDING,
        ];
        $conn = $this->connection();
        $projectOrderObj = null;
        try {
            $conn->beginTransaction();
            $projectOrderObj = $this->create($orderData);
            if (empty($projectOrderObj)) {
                throw new VerifyException('创建订单失败');
            }
            // 已在外层事务中：钱包扣款加入当前事务，minusUserWallet/addUserFrozen
            $walletService->addUserFrozen(
                (int)$user_id,
                self::DEBIT_WALLET,
                $project_price,
                UserWalletService::EVENT_ARBITRAGE_LOCK,
                "Project order freeze #{$projectOrderObj->order_no}",
                'arbitrage_project_order',
                (int)$projectOrderObj->id
            );
            $projectObj->saveData([
                'sales_cnt' => (int)$projectObj->sales_cnt + 1,
            ]);
            (new OrderRecordService())->writeProject($projectOrderObj);
            $conn->commit();
        }
        catch (\Throwable $e) {
            if ($conn->transactionLevel() > 0) {
                $conn->rollBack();
            }
            throw $e;
        }
        Event::emit('user.finishProjectOrder', $projectOrderObj);
        return $projectOrderObj;
    }


    public function getActiveOrdersByProject(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }
        return $this->fetchAll([
            'project_id' => $projectId,
            'status'     => ProjectOrderModel::STATUS_RUNNING,
            'expires_at'=> ['gt',date('Y-m-d H:i:s')],
            'size'       => 5000,
        ])->all();
    }

    public function sumActiveOrderAmount(int $projectId): float
    {
        if ($projectId <= 0) {
            return 0;
        }
        return $this->sum('amount',[
            'project_id' => $projectId,
            'status'     => ProjectOrderModel::STATUS_RUNNING,
            'expires_at'=> ['gt',date('Y-m-d H:i:s')]
        ]);
    }

    public function getUserActiveProjectOrderList(int $userId,$field=[])
    {
        if ($userId <= 0) {
            return [];
        }
        return $this->fetchAll([
            'user_id' => $userId,
            'status'  => ProjectOrderModel::STATUS_RUNNING,
            'expires_at'=> ['gt',date('Y-m-d H:i:s')],
            'size'    => 5000,
        ],['id'=>'asc'],$field);
    }

    /**
     * @return array
     */
    public function getUserActiveProjectIds(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $rows = $this->getUserActiveProjectOrderList($userId, ['project_id']);
        return Data::toFlatArray($rows,'project_id');
    }

    public function getUserActiveProjectOrder(int $userId, int $projectId): ?ProjectOrderModel
    {
        if ($userId <= 0 || $projectId <= 0) {
            return null;
        }
        return $this->fetch([
            'user_id'    => $userId,
            'project_id' => $projectId,
            'expires_at'=> ['gt',date('Y-m-d H:i:s')],
            'status'     => ProjectOrderModel::STATUS_RUNNING,
        ]);
    }

    public function setDefaultOrder(int $id,int $userId){
        $this->updateAll(['user_id'=>$userId,'is_default'=>1],['is_default'=>0]);
        return $this->updateAll(['id'=>$id,'user_id'=>$userId],['is_default'=>1]);
    }

    public function getUserProjectOrderDetail(int $id,int $userId){
        if ($userId <= 0 || $id <= 0) {
            return null;
        }
        return $this->fetch([
            'id' => $id,
            'user_id'    => $userId,
        ]);
    }

    /**
     * 仓位结算后，按订单金额比例分摊收益到当日日志（同订单同日累加）。
     * @param DayPlanModel $plan
     * @param PositionModel $position
     * @return void
     */
    public function allocatePositionIncome(DayPlanModel $plan, PositionModel $position):bool
    {
        $profit = (float) $position->actual_profit;
        if ($profit <= 0) {
            return false;
        }
        elseif($position->status==2){
            return false;
        }
        $orderList = $this->getActiveOrdersByProject((int) $plan->project_id);
        if ($orderList === []) {
            return false;
        }
        $orderLogService = new ProjectOrderLogsService();
        $process = $position->total_stake/$plan->target_amount;
        foreach($orderList as $order){
            $amount = $order->amount * $process;
            $income_amount = (float)($amount*$position->actual_rate);
            $cdata = [
                'order_id'      =>$order->id,
                'project_id'    => $plan->project_id,
                'user_id'       => $order->user_id,
                'plan_id'       => $plan->id,
                'position_id'   => $position->id,
                'level'         => 0,
                'to_day'        => (int) $order->settle_cnt + 1,
                'money'         => $amount,
                'income_rate'   => $position->actual_rate*100,
                'income_userid' => (int) $order->user_id,
                'income_day'    => $plan->day,
                'income_amount' => $income_amount,
                'descr'         => $position->event_name,
                'status'        => ProjectOrderLogsModel::STATUS_PENDING
            ];
            $orderLogObj = $orderLogService->createProjectLog($cdata);
            if(!empty($orderLogObj)){
                Event::emit('user.calcProjectOrderCommission', $orderLogObj);
            }
        }
        $position->status = 2;
        return $position->save();
    }

    /**
     * 设置已完成
     * @param ProjectOrderModel $orderObj
     * @return bool
     * @throws \Throwable
     */
    public function releaseProjectOrders(ProjectOrderModel $orderObj){
        if($orderObj->status==3 || $orderObj->order_status=='completed'){
            return false;
        }
        elseif(strtotime($orderObj->expires_at)>time()){
            return false;
        }
        $walletService = new UserWalletService();
        $conn = $this->connection();
        try {
            $conn->beginTransaction();
            $orderObj->update([
                'status'      => 3,
                'order_status'=>'completed',
            ]);
            $walletService->minuUserFrozen(
                (int)$orderObj->user_id,
                self::DEBIT_WALLET,
                $orderObj->amount,
                UserWalletService::EVENT_ARBITRAGE_UNLOCK,
                "Project order release freeze #{$orderObj->order_no}",
                'arbitrage_project_order',
                (int)$orderObj->id
            );
            $conn->commit();
            return true;
        }
        catch (\Exception $e){
            $conn->rollBack();
            return false;
        }
    }
}
