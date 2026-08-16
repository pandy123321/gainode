<?php

declare(strict_types=1);

namespace library\service\kyc;

use library\dao\kyc\KycCaseDao;
use library\model\kyc\KycCaseModel;
use support\extend\Service;

/**
 * KYC 案件 Service — kyc_cases 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer kyc_cases
 *
 * 状态机说明（05 §4 canonical KYC，复制冻结）：
 *   not_started / pending / needs_info / approved / rejected / review
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method KycCaseModel create($data)
 * @method KycCaseModel get($id, string $field = null)
 * @method KycCaseModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class KycCaseService extends Service
{
    public function __construct()
    {
        $this->dao = KycCaseDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询 KYC 案件（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
