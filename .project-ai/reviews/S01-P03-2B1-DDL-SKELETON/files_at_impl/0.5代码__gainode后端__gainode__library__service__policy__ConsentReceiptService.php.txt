<?php

declare(strict_types=1);

namespace library\service\policy;

use library\dao\policy\ConsentReceiptDao;
use library\model\policy\ConsentReceiptModel;
use support\extend\Service;

/**
 * 同意回执 Service — consent_receipts 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer consent_receipts
 *
 * 状态机说明（05 §4 V2.3 canonical，Owner 2B1-ENUM-06）：
 *   active → expired（两态）
 *   - 撤回/取代不新增状态值，由新版本 receipt + consent_version 表达
 *   - expired：到期为唯一终态
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method ConsentReceiptModel create($data)
 * @method ConsentReceiptModel get($id, string $field = null)
 * @method ConsentReceiptModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ConsentReceiptService extends Service
{
    public function __construct()
    {
        $this->dao = ConsentReceiptDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询回执（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
