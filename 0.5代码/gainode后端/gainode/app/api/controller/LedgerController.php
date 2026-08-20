<?php

declare(strict_types=1);

namespace app\api\controller;

use library\model\ledger\AptLedgerEntryModel;
use library\service\ledger\AptAccountService;
use library\service\ledger\LedgerService;
use library\service\power\PowerPositionService;
use support\controller\ApiV2;
use support\Response;

/**
 * APT 账本 / Power 基础只读控制器（05 §6；S02-P03 落地）。
 *
 * 只读 C 端：
 *   GET /api/v1/me/asset            → AssetBalance（含 effective_available 投影）
 *   GET /api/v1/me/ledger-entries    → LedgerEntry 列表（append-only，时间倒序）
 *   GET /api/v1/me/power             → PowerPosition（规则未冻结，仅只读投影）
 *
 * 经济写路径（post/cancel/reverse/dispute）由内部 Authoritative Writer 触发，
 * 不对外暴露直接改写接口（超级管理员无旁路）。
 */
class LedgerController extends ApiV2
{
    /** GET /api/v1/me/asset */
    public function asset(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $account = (new AptAccountService())->getByUser($userId);
            if (empty($account)) {
                return $this->envelope([]);
            }
            $aptService = new AptAccountService();
            return $this->envelope([
                'account_id'             => (string) $account->account_id,
                'balance_apt_i'          => (string) $account->balance_apt_i,
                'balance_apt_c'          => (string) ($account->balance_apt_c ?? '0'),
                'frozen_apt_i'           => (string) ($account->frozen_apt_i ?? '0'),
                'frozen_apt_c'           => (string) ($account->frozen_apt_c ?? '0'),
                'total_earned_apt'       => (string) ($account->total_earned_apt ?? '0'),
                'total_spent_apt'        => (string) ($account->total_spent_apt ?? '0'),
                'aggregate_dispute_hold' => $aptService->getAggregateDisputeHold((string) $account->account_id),
                'effective_available'    => $aptService->getEffectiveAvailable($account),
                'rule_version'           => (string) ($account->rule_version ?? ''),
                'snapshot_id'            => (string) ($account->snapshot_id ?? '0'),
                'object_version'         => (int) ($account->object_version ?? 0),
            ]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/me/ledger-entries */
    public function ledgerEntries(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $account = (new AptAccountService())->getByUser($userId);
            $items = [];
            if (!empty($account)) {
                $rows = (new LedgerService())->getByAccount((string) $account->account_id);
                foreach ($rows as $e) {
                    $items[] = [
                        'ledger_entry_id'   => (string) $e->ledger_entry_id,
                        'account_id'        => (string) $e->account_id,
                        'asset'             => (string) ($e->asset ?? AptLedgerEntryModel::ASSET_APT_I),
                        'quantity'          => (string) $e->quantity,
                        'entry_direction'   => (int) $e->entry_direction,
                        'entry_type'        => (string) ($e->entry_type ?? ''),
                        'state'             => (string) $e->state,
                        'source_object_type'=> (string) ($e->source_object_type ?? ''),
                        'source_object_id'  => (string) ($e->source_object_id ?? '0'),
                        'journal_batch_id'  => (string) ($e->journal_batch_id ?? '0'),
                        'reversal_of'       => (string) ($e->reversal_of ?? '0'),
                        'idempotency_key'   => $e->idempotency_key !== null ? (string) $e->idempotency_key : null,
                        'rule_version'      => (string) ($e->rule_version ?? ''),
                        'snapshot_id'       => (string) ($e->snapshot_id ?? '0'),
                        'audit_event_id'    => (string) ($e->audit_event_id ?? '0'),
                        'object_version'    => (int) ($e->object_version ?? 0),
                        'created_time'      => (int) $e->getRawOriginal('created_time'),
                    ];
                }
            }
            return $this->envelope(['entries' => $items]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/me/power */
    public function power(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $pos = (new PowerPositionService())->getByUser($userId);
            if (empty($pos)) {
                return $this->envelope([]);
            }
            return $this->envelope([
                'user_id'                  => (string) $pos->user_id,
                'available'                => (string) $pos->available,
                'frozen'                   => (string) ($pos->frozen ?? '0'),
                'consumed_period'          => (string) ($pos->consumed_period ?? '0'),
                'released_period'          => (string) ($pos->released_period ?? '0'),
                'recovering'               => (string) ($pos->recovering ?? '0'),
                'limit'                    => (string) ($pos->limit ?? '0'),
                'power_cap_source_robot_level' => isset($pos->power_cap_source_robot_level) ? (int) $pos->power_cap_source_robot_level : null,
                'last_restore_at'          => isset($pos->last_restore_at) ? (int) $pos->last_restore_at : null,
                'next_restore_at'          => isset($pos->next_restore_at) ? (int) $pos->next_restore_at : null,
                'rule_version'             => (string) ($pos->rule_version ?? ''),
                'object_version'           => (int) ($pos->object_version ?? 0),
            ]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
