<?php

declare(strict_types=1);

namespace library\service\risk;

use library\dao\risk\RiskCaseDao;
use library\model\risk\RiskCaseModel;
use support\extend\Service;

/**
 * 风控案件 Service — risk_cases 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer risk_cases
 *
 * 状态机说明（05 §4 V2.4 canonical，Owner 2B2-ENUM-03）：
 *   open / investigating / under_review / resolved / closed
 *   - under_review=RISK_APPROVER 审批处置（RISK_ANALYST != RISK_APPROVER）；
 *   - appeal_eligible 为字段非状态。
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method RiskCaseModel create($data)
 * @method RiskCaseModel get($id, string $field = null)
 * @method RiskCaseModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RiskCaseService extends Service
{
    public function __construct()
    {
        $this->dao = RiskCaseDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询风控案件（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
