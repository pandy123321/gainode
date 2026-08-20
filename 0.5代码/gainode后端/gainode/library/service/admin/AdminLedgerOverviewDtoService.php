<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\ledger\AptAccountDao;
use support\extend\Service;

/**
 * Admin V2 资产总览 DTO 服务（A-LEDGER-001）。
 *
 * 只读聚合：APT 四账余额汇总 + 账户/流水计数。
 * 金额为 string decimal 汇总（bcmath），不涉及明细推导。
 * 供 Admin 2.0 资产总览 dashboard 经 /api/v1/admin/ledger/overview 对接。
 */
class AdminLedgerOverviewDtoService extends Service
{
    /**
     * 资产总览聚合。
     *
     * @return array{account_count:int,total_balance_apt_i:string,total_frozen_apt_i:string,total_earned_apt:string,total_spent_apt:string}
     */
    public function overview(): array
    {
        $dao = new AptAccountDao();
        $sum = static fn (string $col): string => (string) $dao->sum($col, []) ?? '0';

        return [
            'account_count'       => (int) $dao->count([]),
            'total_balance_apt_i' => bcadd($sum('balance_apt_i'), '0', 18),
            'total_frozen_apt_i'  => bcadd($sum('frozen_apt_i'), '0', 18),
            'total_earned_apt'    => bcadd($sum('total_earned_apt'), '0', 18),
            'total_spent_apt'     => bcadd($sum('total_spent_apt'), '0', 18),
        ];
    }
}
