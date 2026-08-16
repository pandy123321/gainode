<?php

declare(strict_types=1);

namespace library\service\power;

use library\dao\power\PowerPositionDao;
use library\dict\ErrorDict;
use library\model\power\PowerPositionModel;
use support\extend\Service;
use support\exception\DomainException;

/**
 * Power 持仓 Service — power_positions 表唯一 Authoritative Writer（S02-P03）
 *
 * @authoritative_writer power_positions
 *
 * Power 为可消耗、可恢复操作资源，无领域状态机（scalar fields）。
 * Power 精确消耗/恢复规则由 Active Rule/Parameter 决定，生产参数未批准（TBC）。
 *
 * S02-P03 不实现任何 Power 变更逻辑；变更操作 MUST FAIL_CLOSED
 * （DEPENDENCY_UNAVAILABLE）直到规则 FROZEN。只读投影可用。
 *
 * @method PowerPositionModel create($data)
 * @method PowerPositionModel get($id, string $field = null)
 * @method PowerPositionModel find($id)
 * @method PowerPositionModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class PowerPositionService extends Service
{
    public function __construct()
    {
        $this->dao = PowerPositionDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询持仓（只读透传）
     *
     * @param string $userId
     * @return PowerPositionModel|null
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    /**
     * 消耗 Power（Robot 启动等）。规则未冻结 → FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function consume(string $userId, string $quantity, string $idempotencyKey = ''): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Power consume rules not frozen (Active Rule/Parameter TBC)'
        );
    }

    /**
     * 恢复/释放 Power。规则未冻结 → FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function recover(string $userId, string $quantity): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Power recover rules not frozen (Active Rule/Parameter TBC)'
        );
    }

    /**
     * Power 影响预览。规则未冻结 → FAIL_CLOSED（不回退旧值、不 mock）。
     *
     * @throws DomainException
     */
    public function previewImpact(string $userId, string $action): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'PowerImpactPreview unavailable (rules not frozen)'
        );
    }
}
