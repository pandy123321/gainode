<?php

namespace library\service\member;

use library\model\member\RedPacketModel;
use library\dao\member\RedPacketDao;
use library\service\sys\FlowNumbersService;
use support\exception\VerifyException;
use support\extend\Service;
use support\utils\Data;

/**
 * Service
 * @method RedPacketModel create($data)
 * @method RedPacketModel updateOrCreate(array $params,array $data)
 * @method RedPacketModel update($id,array $data){
 * @method RedPacketModel get($id,string $field = null)
 * @method RedPacketModel find($id)
 * @method RedPacketModel findOrFail($id)
 * @method RedPacketModel firstOrCreate(array $params,array $data)
 * @method RedPacketModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class RedPacketService extends Service
{

    const CREDIT_WALLET = 'Funding';
    public function __construct()
    {
        $this->dao = RedPacketDao::class;
        parent::__construct();
    }

    /**
     * 获取红包编号
     * @param string $suffix
     * @return string
     */
    public function getPacketNo($suffix=''){
        $flowNumberServer = new FlowNumbersService();
        $packet_no = $flowNumberServer->getFlowOrderNo($this->getNewDao()->getTable(),$suffix);
        $packetObj = $this->get($packet_no,'packet_no');
        if(empty($packetObj)){
            return $packet_no;
        }
        return $this->getPacketNo();
    }

    public function createPacketData(array $data){
        $data['packet_no'] = $this->getPacketNo();
        $data['remain_count'] = $data['packet_count'];
        $data['remain_amount'] = $data['total_amount'];
        $data['status'] = RedPacketModel::STATUS_PENDING;
        if(empty($data['start_time'])){
            $data['start_time'] = null;
        }
        if(empty($data['expire_time'])){
            $data['expire_time'] = null;
        }
        $packetObj = $this->create($data);
        if(!empty($packetObj)){
            $list = [];
            if($packetObj->packet_type==1){
                $result = Data::randomPackets($packetObj->total_amount,$packetObj->packet_count);
            }
            else{
                $result = Data::fixedPackets($packetObj->total_amount,$packetObj->packet_count);
            }
            $len = strlen(count($result));
            $numbers = Data::generateUniqueNumbers(count($result),($len+1));
            foreach($result as $k=>$money){
                $list[] = [
                    'packet_id'=>$packetObj->id,
                    'item_no'=>$packetObj->packet_no.$numbers[$k],
                    'amount'=>$money
                ];
            }
            $packetItemsService = new RedPacketItemService();
            $packetItemsService->insert($list);
        }
        return $packetObj;
    }

    public function receivePacket($user_id,$item_no){
        $packetItemService = new RedPacketItemService();
        $packetItemObj = $packetItemService->fetch(['item_no'=>$item_no]);
        if(empty($packetItemObj)){
            throw new VerifyException("记录不存在");
        }
        elseif($packetItemObj->status==1){
            throw new VerifyException("已经被领取");
        }
        $packetObj = $this->get($packetItemObj->packet_id);
        if(empty($packetObj) || $packetObj->status<0){
            throw new VerifyException("数据不存在");
        }
        elseif(!empty($packetObj->start_time) && strtotime($packetObj->start_time)>time()){
            throw new VerifyException("领取时间暂未开始");
        }
        elseif(!empty($packetObj->expire_time) && strtotime($packetObj->expire_time)<time()){
            throw new VerifyException("领取时间暂已结束");
        }
        $conn = $this->connection();
        try{
            $conn->beginTransaction();
            $packetItemObj->saveData([
                'receive_user_id'=>$user_id,
                'receive_time'=>date('Y-m-d H:i:s'),
                'status'=>1
            ]);
            $update = [
                'remain_count'=>$this->raw('remain_count-1'),
                'remain_amount'=>$this->raw('remain_amount-'.$packetItemObj->amount),
            ];
            if($packetObj->status==RedPacketModel::STATUS_PENDING){
                $update['status'] = RedPacketModel::STATUS_CLAIMING;
            }
            elseif($packetObj->status==RedPacketModel::STATUS_CLAIMING){
                if($packetObj->remain_amount<=$packetItemObj->amount){
                    $update['status'] = RedPacketModel::STATUS_FINISHED;
                }
            }
            $packetObj->update($update);
            $walletService = new UserWalletService();
            $walletService->addUserWallet(
                (int)$user_id,
                self::CREDIT_WALLET,
                $packetItemObj->amount,
                UserWalletService::EVENT_ACCOUNT_PACKET,
                "Red envelope amount #{$packetItemObj->item_no}",
                'member_red_packet_item',
                (int)$packetItemObj->id
            );
            $conn->commit();
            return $packetItemObj;
        }
        catch (\Exception $e){
            $conn->rollBack();
            throw $e;
        }
    }


}
