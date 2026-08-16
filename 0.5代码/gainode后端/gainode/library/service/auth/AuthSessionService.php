<?php

declare(strict_types=1);

namespace library\service\auth;

use library\dao\auth\AuthSessionDao;
use library\model\auth\AuthSessionModel;
use support\extend\Service;

/**
 * 会话 Service — auth_sessions 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer auth_sessions
 *
 * 状态机说明（05 §2.2 canonical Session，复制冻结）：
 *   active / mfa_required / restricted / expired / revoked
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method AuthSessionModel create($data)
 * @method AuthSessionModel get($id, string $field = null)
 * @method AuthSessionModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class AuthSessionService extends Service
{
    public function __construct()
    {
        $this->dao = AuthSessionDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询会话（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
