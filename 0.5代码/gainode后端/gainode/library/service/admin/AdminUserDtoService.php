<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\member\UserDao;
use library\service\ledger\AptAccountService;
use library\service\robot\RobotService;
use support\extend\Service;

/**
 * Admin V2 用户列表 DTO 聚合服务（A-USER-001）。
 *
 * 只读分页聚合：member_user 基础字段 + Robot 状态 + APT 余额。
 * 字段口径：仅返回已确认列（不猜测）；金额为 string decimal；时间 UTC。
 * 供 Admin 2.0 用户列表页经 /api/v1/admin/admission/users 对接。
 */
class AdminUserDtoService extends Service
{
    public function __construct()
    {
        $this->dao = UserDao::class;
        parent::__construct();
    }

    /**
     * 分页用户列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $keyword 按 user_no/account/email/phone 模糊
     * @return array{users:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $keyword = ''): array
    {
        $params = [];
        if ($keyword !== '') {
            $params['account'] = ['like', "%{$keyword}%"];
        }
        $paginator = (new UserDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['id', 'user_no', 'account', 'email', 'phone', 'nickname', 'is_verify', 'status', 'created_time']
        );

        $users = [];
        $robotService = new RobotService();
        $aptService = new AptAccountService();

        foreach ($paginator->items() as $u) {
            $userId = (string) $u->id;

            $robots = [];
            foreach ($robotService->getByUser($userId) as $r) {
                $robots[] = [
                    'robot_id' => (string) $r->robot_id,
                    'level'    => (int) $r->level,
                    'status'   => (string) $r->status,
                ];
            }

            $account = $aptService->getByUser($userId);
            $balance = $account ? (string) $account->balance_apt_i : '0';

            $users[] = [
                'user_id'     => $userId,
                'user_no'     => (string) $u->user_no,
                'account'     => (string) $u->account,
                'email'       => $u->email !== null ? (string) $u->email : null,
                'phone'       => $u->phone !== null ? (string) $u->phone : null,
                'nickname'    => $u->nickname !== null ? (string) $u->nickname : null,
                'is_verify'   => (int) $u->is_verify,
                'status'      => (int) $u->status,
                'balance_apt' => $balance,
                'robots'      => $robots,
                'created_time'=> (int) $u->getRawOriginal('created_time'),
            ];
        }

        return [
            'users' => $users,
            'total' => (int) $paginator->total(),
            'page'  => $page,
            'size'  => $size,
        ];
    }
}
