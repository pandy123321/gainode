<?php

declare(strict_types=1);

namespace library\service\notice;

use library\dao\notice\NotificationDeliveryDao;
use library\model\notice\NotificationDeliveryModel;
use support\extend\Service;

/**
 * 通知投递 Service — notification_deliveries 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer notification_deliveries
 *
 * 状态机说明（05 §4 V2.4 canonical，Owner 2B2-ENUM-01）：
 *   pending / delivered / failed / cancelled
 *   - failed=失败待重试（attempt_count/next_retry_at 驱动，不新增 processing 态）；
 *   - cancelled=业务对象失效/用户已读不再投递；投递失败不回滚业务（05 §4 Notice 设计原则 1）。
 *
 * 幂等：dedupe_key（去重 key）。本骨架不实现投递流转（属 State Machine gate）。
 * 转移矩阵 FROZEN 前，任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method NotificationDeliveryModel create($data)
 * @method NotificationDeliveryModel get($id, string $field = null)
 * @method NotificationDeliveryModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class NotificationDeliveryService extends Service
{
    public function __construct()
    {
        $this->dao = NotificationDeliveryDao::class;
        parent::__construct();
    }

    /**
     * 按通知查询投递（只读透传）
     *
     * @param string $noticeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByNotice(string $noticeId)
    {
        return $this->getNewDao()->getByNotice($noticeId);
    }
}
