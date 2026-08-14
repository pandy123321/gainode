<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\PredictionMarketDao;
use library\model\prediction\PredictionMarketModel;
use support\extend\Service;

/**
 * 预测市场 Service — prediction_markets 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer prediction_markets
 *
 * 状态机说明（05 §4 canonical，MC1 冻结）：
 *   draft → open → closing → locked → awaiting_result → settlement → settled
 *   旁路：void（赛事取消等原因作废）/ exception（异常）
 *
 * 本骨架不实现状态转移矩阵（属 Machine Contract 第二批范畴）。转移矩阵 FROZEN 前，
 * 任何状态流转操作 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method PredictionMarketModel create($data)
 * @method PredictionMarketModel get($id, string $field = null)
 * @method PredictionMarketModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class PredictionMarketService extends Service
{
    public function __construct()
    {
        $this->dao = PredictionMarketDao::class;
        parent::__construct();
    }

    /**
     * 按赛事查询市场（只读透传）
     *
     * @param string $eventId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByEvent(string $eventId)
    {
        return $this->getNewDao()->getByEvent($eventId);
    }
}
