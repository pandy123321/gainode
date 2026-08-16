<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\SettlementBatchDao;
use library\model\prediction\SettlementBatchModel;
use support\extend\Service;

/**
 * 结算批 Service — settlement_batches 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer settlement_batches
 *
 * 状态机说明（05 §4 V2.3 canonical，Owner 2B1-ENUM-01）：
 *   created → processing → completed / processing → partially_failed（可重试回 processing）/ * → failed
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method SettlementBatchModel create($data)
 * @method SettlementBatchModel get($id, string $field = null)
 * @method SettlementBatchModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class SettlementBatchService extends Service
{
    public function __construct()
    {
        $this->dao = SettlementBatchDao::class;
        parent::__construct();
    }
}
