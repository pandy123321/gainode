<?php

declare(strict_types=1);

namespace library\service\settlement;

use library\dao\settlement\SettlementMethodDao;
use library\model\settlement\SettlementMethodModel;
use support\extend\Service;

/**
 * 结算方式 Service — settlement_methods 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer settlement_methods
 *
 * 值对象/只读聚合（05 §3 SettlementMethod）：
 *   verification_status 为可变字段（验证状态流转在 State Machine gate 冻结后实现）。
 *
 * 本骨架不实现验证状态流转（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method SettlementMethodModel create($data)
 * @method SettlementMethodModel get($id, string $field = null)
 * @method SettlementMethodModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class SettlementMethodService extends Service
{
    public function __construct()
    {
        $this->dao = SettlementMethodDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询结算方式（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
