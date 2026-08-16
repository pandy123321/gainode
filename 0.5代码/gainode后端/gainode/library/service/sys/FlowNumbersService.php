<?php

namespace library\service\sys;

use library\model\sys\FlowNumbersModel;
use library\dao\sys\FlowNumbersDao;
use support\extend\Redis;
use support\extend\Service;
use support\utils\Random;

/**
 * Service
 * @method FlowNumbersModel create($data)
 * @method FlowNumbersModel updateOrCreate(array $params,array $data)
 * @method FlowNumbersModel update($id,array $data){
 * @method FlowNumbersModel get($id,string $field = null)
 * @method FlowNumbersModel find($id)
 * @method FlowNumbersModel findOrFail($id)
 * @method FlowNumbersModel firstOrCreate(array $params,array $data)
 * @method FlowNumbersModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class FlowNumbersService extends Service
{
    public function __construct()
    {
        $this->dao = FlowNumbersDao::class;
        parent::__construct();
    }

    /**
     * 获取订单的流水订单编号
     * @param string $table 表单名
     */
    public function getFlowOrderNo($table,$suffix=''){
        $order_no = '';
        $flowObj = $this->fetch(['table'=>$table]);
        if(!empty($flowObj) && $flowObj['status']>0){
            $cache_key = 'service.flow:'.$table.(!$suffix?'':('.'.$suffix));
            $lock_key = 'lock:'.md5($cache_key);
            // 使用 setNx 获取锁，并设置过期时间，避免死锁
            $is_lock = Redis::setNx($lock_key, 1, 3);
            if($is_lock){
                try {
                    $num = Redis::get($cache_key);
                    if(empty($num)){
                        $num = $flowObj['start_val'];
                        Redis::set($cache_key, $flowObj['start_val']);
                    }
                    else{
                        Redis::incr($cache_key);
                        $num += 1;
                    }
                    if(!empty($flowObj['prefix'])){
                        $order_no .= $flowObj['prefix'];
                    }
                    if($flowObj['rule']==1){
                        $order_no.= date('Y');
                    }
                    elseif($flowObj['rule']==2){
                        $order_no.= date('Ym');
                    }
                    elseif($flowObj['rule']==3){
                        $order_no.= date('Ymd');
                    }
                    elseif($flowObj['rule']==4){
                        $order_no.= date('YmdH');
                    }
                    elseif($flowObj['rule']==5){
                        $order_no.= date('YmdHi');
                    }
                    elseif($flowObj['rule']==6){
                        $order_no.= date('YmdHis');
                    }
                    if($flowObj['random']==0){
                        $order_num = sprintf('%0'.$flowObj['digit'].'s', $num);
                    }
                    else{
                        $order_num = Random::getRandStr($flowObj['digit'],0);
                    }
                    $order_no .= $order_num;
                    if(!empty($suffix)){
                        $order_no .= $suffix;
                    }
                    elseif(!empty($flowObj['suffix'])){
                        $order_no .= $flowObj['suffix'];
                    }
                }
                finally {
                    Redis::del($lock_key);
                }
            }
            else{
                // 锁获取失败，短暂等待后重试一次
                usleep(100000); // 100ms
                $is_lock2 = Redis::setNx($lock_key, 1, 3);
                if($is_lock2){
                    try {
                        $num = Redis::incr($cache_key);
                        if(!empty($flowObj['prefix'])){
                            $order_no .= $flowObj['prefix'];
                        }
                        if($flowObj['rule']==6){
                            $order_no.= date('YmdHis');
                        }
                        $order_num = sprintf('%0'.$flowObj['digit'].'s', $num);
                        $order_no .= $order_num;
                        if(!empty($suffix)){
                            $order_no .= $suffix;
                        }
                        elseif(!empty($flowObj['suffix'])){
                            $order_no .= $flowObj['suffix'];
                        }
                    }
                    finally {
                        Redis::del($lock_key);
                    }
                }
            }
        }
        return $order_no;
    }
}
