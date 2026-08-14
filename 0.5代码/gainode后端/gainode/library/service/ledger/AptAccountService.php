<?php

declare(strict_types=1);

namespace library\service\ledger;

use library\dao\ledger\AptAccountDao;
use library\model\ledger\AptAccountModel;
use support\extend\Service;

/**
 * APT 数量账主账号 Service — apt_accounts 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer apt_accounts
 *
 * 本表为 scalar 余额模型（无领域状态机）。余额字段（balance_apt_* / frozen_apt_* /
 * total_*_apt）只能随账本分录在同一事务内由账本模块更新，禁止任何旁路直接改写。
 *
 * 本骨架不实现任何余额变更逻辑（属 Ledger Mutation Contract / 记账规则范畴）。
 * 记账规则 FROZEN 前，任何余额变更操作 MUST FAIL_CLOSED。
 *
 * @method AptAccountModel create($data)
 * @method AptAccountModel get($id, string $field = null)
 * @method AptAccountModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class AptAccountService extends Service
{
    public function __construct()
    {
        $this->dao = AptAccountDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询主账号（只读透传）
     *
     * @param string $userId
     * @return AptAccountModel|null
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
