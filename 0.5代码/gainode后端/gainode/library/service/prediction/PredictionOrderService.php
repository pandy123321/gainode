<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\PredictionOrderDao;
use library\model\prediction\PredictionOrderModel;
use support\extend\Service;

/**
 * 预测订单 Service — prediction_orders 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer prediction_orders
 *
 * 状态机说明（05 §4 canonical，MC1 冻结）：
 *   submitted → locked → awaiting_result → settling → settled
 *   旁路：refunding → refunded（退款）/ correcting → corrected（仅 settlement error 触发）
 *   RESULT_UNKNOWN 不得混入订单状态（Result 是独立对象）。
 *
 * 本骨架不实现状态转移矩阵（属 Machine Contract 第二批范畴）。转移矩阵 FROZEN 前，
 * 任何状态流转操作 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method PredictionOrderModel create($data)
 * @method PredictionOrderModel get($id, string $field = null)
 * @method PredictionOrderModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class PredictionOrderService extends Service
{
    public function __construct()
    {
        $this->dao = PredictionOrderDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询订单（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
