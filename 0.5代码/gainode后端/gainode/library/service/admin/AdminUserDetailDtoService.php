<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\kyc\KycCaseDao;
use library\dao\member\UserDao;
use library\service\ledger\AptAccountService;
use library\service\power\PowerPositionService;
use library\service\robot\RobotService;
use support\extend\Service;

/**
 * Admin V2 用户 360 详情 DTO 服务（A-USER-002）。
 *
 * 只读聚合：用户基础 + Robot 列表 + APT 余额 + KYC 状态 + Power。
 * 字段口径：仅返回已确认列；金额/数量为 string decimal；时间 UTC。
 * 供 Admin 2.0 用户 360 页经 /api/v1/admin/admission/users/{id} 对接。
 */
class AdminUserDetailDtoService extends Service
{
    public function __construct()
    {
        $this->dao = UserDao::class;
        parent::__construct();
    }

    /**
     * 用户 360 详情 DTO。
     *
     * @param string $userId
     * @return array|null 不存在返回 null
     */
    public function detail(string $userId): ?array
    {
        $user = (new UserDao())->fetch(['id' => $userId]);
        if (empty($user)) {
            return null;
        }

        // Robot 列表
        $robots = [];
        foreach ((new RobotService())->getByUser($userId) as $r) {
            $robots[] = [
                'robot_id'  => (string) $r->robot_id,
                'level'     => (int) $r->level,
                'status'    => (string) $r->status,
            ];
        }

        // APT 余额
        $account = (new AptAccountService())->getByUser($userId);
        $apt = $account
            ? [
                'account_id'          => (string) $account->account_id,
                'balance_apt_i'       => (string) $account->balance_apt_i,
                'frozen_apt_i'        => (string) $account->frozen_apt_i,
                'total_earned_apt'    => (string) $account->total_earned_apt,
                'total_spent_apt'     => (string) $account->total_spent_apt,
            ]
            : null;

        // KYC 状态（kyc_cases 最新）
        $kyc = null;
        foreach ((new KycCaseDao())->getByUser($userId) as $k) {
            $kyc = [
                'case_id'   => (string) $k->case_id,
                'kyc_level' => (string) $k->kyc_level,
                'status'    => (string) $k->status,
                'submitted_at' => (int) $k->submitted_at,
            ];
        }

        // Power
        $power = null;
        $pos = (new PowerPositionService())->getByUser($userId);
        if (!empty($pos)) {
            $power = [
                'available'     => (string) $pos->available,
                'frozen'        => (string) $pos->frozen,
                'limit'         => (string) $pos->limit,
                'rule_version'  => (string) $pos->rule_version,
            ];
        }

        return [
            'user_id'    => $userId,
            'user_no'    => (string) $user->user_no,
            'account'    => (string) $user->account,
            'email'      => $user->email !== null ? (string) $user->email : null,
            'phone'      => $user->phone !== null ? (string) $user->phone : null,
            'nickname'   => $user->nickname !== null ? (string) $user->nickname : null,
            'is_verify'  => (int) $user->is_verify,
            'status'     => (int) $user->status,
            'created_time' => (int) $user->getRawOriginal('created_time'),
            'robots'     => $robots,
            'apt'        => $apt,
            'kyc'        => $kyc,
            'power'      => $power,
        ];
    }
}
