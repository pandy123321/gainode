<?php

declare(strict_types=1);

namespace library\service\otc;

use library\dao\otc\OtcTradeDao;
use library\model\otc\OtcTradeModel;
use support\extend\Service;

/**
 * OTC 成交事实 Service — otc_trades 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer otc_trades
 *
 * 状态机说明（05 §4 V2.3 canonical，Owner 2B1-ENUM-04）：
 *   completed（单态，append-only 成交事实）
 *   - 争议/冲正不覆盖 Trade，走 RiskCase + ledger reversal（作用于 otc_orders / apt_ledger_entries）
 *
 * append-only 约束（MC2 Freeze §3.6）：
 *   - 成交事实一经写入永不覆盖，物理删除禁止。
 *   - 机械强制见 OtcTradeModel / OtcTradeAppendOnlyBuilder / OtcTradeDao。
 *
 * 本骨架不实现撮合业务（属 Machine Contract 第二批范围）。撮合与成交流转 FROZEN 前，
 * 任何写入必须符合 append-only 约束，不得自创覆盖/删除路径。
 *
 * @method OtcTradeModel create($data)
 * @method OtcTradeModel get($id, string $field = null)
 * @method OtcTradeModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class OtcTradeService extends Service
{
    public function __construct()
    {
        $this->dao = OtcTradeDao::class;
        parent::__construct();
    }

    /**
     * 按订单查询成交（只读透传）
     *
     * @param string $otcOrderId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByOrder(string $otcOrderId)
    {
        return $this->getNewDao()->getByOrder($otcOrderId);
    }
}
