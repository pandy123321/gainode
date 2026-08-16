<?php

declare(strict_types=1);

namespace library\dao\notice;

use support\extend\Dao;
use library\model\notice\NotificationDeliveryModel;

/**
 * NotificationDelivery DAO — notification_deliveries 表查询封装
 *
 * 幂等：dedupe_key（去重 key），不设 idempotency_key。
 */
class NotificationDeliveryDao extends Dao
{
    public function __construct()
    {
        $this->model = NotificationDeliveryModel::class;
    }

    /**
     * 按去重 key 查询（幂等）
     *
     * @param string $dedupeKey
     * @return NotificationDeliveryModel|null
     */
    public function getByDedupeKey(string $dedupeKey)
    {
        return $this->fetch(['dedupe_key' => $dedupeKey]);
    }

    /**
     * 按通知查询投递
     *
     * @param string $noticeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByNotice(string $noticeId)
    {
        return $this->fetchAll(['notice_id' => $noticeId]);
    }
}
