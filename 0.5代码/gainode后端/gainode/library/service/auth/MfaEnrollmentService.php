<?php

declare(strict_types=1);

namespace library\service\auth;

use library\dao\auth\MfaEnrollmentDao;
use library\model\auth\MfaEnrollmentModel;
use support\extend\Service;

/**
 * MFA 注册 Service — mfa_enrollments 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer mfa_enrollments
 *
 * 状态机说明（05 §4 V2.4 canonical，Owner 2B2-ENUM-02）：
 *   pending / active / revoked
 *   - pending=已发起注册、尚未验证（enrolled_at 与 last_verified_at 分离表达）；
 *   - backup_codes_active 为字段非状态。
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method MfaEnrollmentModel create($data)
 * @method MfaEnrollmentModel get($id, string $field = null)
 * @method MfaEnrollmentModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class MfaEnrollmentService extends Service
{
    public function __construct()
    {
        $this->dao = MfaEnrollmentDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询 MFA 注册（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
