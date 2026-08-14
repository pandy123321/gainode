<?php

declare(strict_types=1);

namespace library\service\otc;

use library\dao\otc\OtcOrderDao;
use library\model\otc\OtcOrderModel;
use support\extend\Service;

/**
 * OTC 订单 Service — otc_orders 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer otc_orders
 *
 * 状态机说明（05 §4 canonical，MC1 冻结）：
 *   draft → review → matching → partial → completed
 *   旁路：cancelled / expired / rejected / disputed
 *   - partial + cancelled/expired 仅释放 remaining
 *   - disputed 保持冻结直到处置
 *   - 不删除/覆盖历史 Trade、APT Ledger、Power Ledger
 *
 * 本骨架不实现状态转移矩阵（属 Machine Contract 第二批范畴）。转移矩阵 FROZEN 前，
 * 任何状态流转操作 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method OtcOrderModel create($data)
 * @method OtcOrderModel get($id, string $field = null)
 * @method OtcOrderModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class OtcOrderService extends Service
{
    public function __construct()
    {
        $this->dao = OtcOrderDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询 OTC 订单（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
