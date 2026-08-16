<?php

namespace library\service\arbitrage;

use library\dao\arbitrage\AttemptDao;
use library\model\arbitrage\AttemptModel;
use support\extend\Service;

/**
 * @method AttemptModel create($data)
 * @method AttemptModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class AttemptService extends Service
{
    public function __construct()
    {
        $this->dao = AttemptDao::class;
        parent::__construct();
    }

    /**
     * @param array<string,mixed> $detail
     */
    public function record(
        int $projectId,
        int $planId,
        int $signalId,
        int $fixtureId,
        int $windowIdx,
        string $execStatus,
        float $stake,
        float $profitRate,
        array $detail = []
    ): AttemptModel {
        return $this->create([
            'user_id'      => $projectId,
            'plan_id'      => $planId,
            'signal_id'    => $signalId,
            'fixture_id'   => $fixtureId,
            'window_idx'   => $windowIdx,
            'exec_status'  => $execStatus,
            'stake'        => round($stake, 2),
            'profit_rate'  => round($profitRate, 4),
            'detail'       => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'created_time' => time(),
        ]);
    }
}
