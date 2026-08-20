<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\ledger\AptAccountDao;
use library\service\ledger\AptAccountService;
use support\extend\Service;

/**
 * Admin V2 APT 账户列表 DTO 服务（A-LEDGER-002）。
 *
 * 只读全量分页：apt_accounts 全量 + effective_available 投影。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 APT 账户与流水页经 /api/v1/admin/ledger/accounts 对接。
 */
class AdminLedgerDtoService extends Service
{
    public function __construct()
    {
        $this->dao = AptAccountDao::class;
        parent::__construct();
    }

    /**
     * 分页 APT 账户列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $keyword 按 user_id 精确（可选）
     * @return array{accounts:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $keyword = ''): array
    {
        $params = [];
        if ($keyword !== '') {
            $params['user_id'] = $keyword;
        }
        $paginator = (new AptAccountDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['account_id', 'user_id', 'balance_apt_i', 'balance_apt_c', 'frozen_apt_i', 'frozen_apt_c', 'total_earned_apt', 'total_spent_apt', 'rule_version', 'object_version', 'created_time']
        );

        $aptService = new AptAccountService();
        $accounts = [];
        foreach ($paginator->items() as $a) {
            $accounts[] = [
                'account_id'             => (string) $a->account_id,
                'user_id'                => (string) $a->user_id,
                'balance_apt_i'          => (string) $a->balance_apt_i,
                'balance_apt_c'          => (string) $a->balance_apt_c,
                'frozen_apt_i'           => (string) $a->frozen_apt_i,
                'frozen_apt_c'           => (string) $a->frozen_apt_c,
                'total_earned_apt'       => (string) $a->total_earned_apt,
                'total_spent_apt'        => (string) $a->total_spent_apt,
                'aggregate_dispute_hold' => $aptService->getAggregateDisputeHold((string) $a->account_id),
                'effective_available'    => $aptService->getEffectiveAvailable($a),
                'rule_version'           => (string) $a->rule_version,
                'object_version'         => (int) $a->object_version,
                'created_time'           => (int) $a->getRawOriginal('created_time'),
            ];
        }

        return [
            'accounts' => $accounts,
            'total'    => (int) $paginator->total(),
            'page'     => $page,
            'size'     => $size,
        ];
    }
}
