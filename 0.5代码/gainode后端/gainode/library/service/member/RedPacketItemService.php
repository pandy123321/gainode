<?php

namespace library\service\member;

use library\model\member\RedPacketItemModel;
use library\dao\member\RedPacketItemDao;
use support\exception\VerifyException;
use support\extend\Service;
use support\utils\Random;
use Webman\Event\Event;

/**
 * Service
 * @method RedPacketItemModel create($data)
 * @method RedPacketItemModel updateOrCreate(array $params,array $data)
 * @method RedPacketItemModel update($id,array $data){
 * @method RedPacketItemModel get($id,string $field = null)
 * @method RedPacketItemModel find($id)
 * @method RedPacketItemModel findOrFail($id)
 * @method RedPacketItemModel firstOrCreate(array $params,array $data)
 * @method RedPacketItemModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class RedPacketItemService extends Service
{

    public function __construct()
    {
        $this->dao = RedPacketItemDao::class;
        parent::__construct();
    }

    public function getPacketItems($packet_id){
        return $this->fetchAll(['packet_id'=>$packet_id]);
    }

    public function getUserPacketItems($user_id){
        return $this->fetchAll(['receive_user_id'=>$user_id],['receive_time'=>'desc']);
    }
}
