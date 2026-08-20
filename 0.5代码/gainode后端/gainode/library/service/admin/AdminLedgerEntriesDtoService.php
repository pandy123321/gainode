<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\ledger\AptLedgerEntryDao;
use support\extend\Service;

/**
 * Admin V2 APT 流水明细 DTO 服务（A-LEDGER-002 明细）。
 *
 * 只读全量分页：apt_ledger_entries（append-only）按 account_id 筛选。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 APT 账户流水页经 /api/v1/admin/ledger/entries 对接。
 */
class AdminLedgerEntriesDtoService extends Service
{
    public function __construct()
    {
        $this->dao = AptLedgerEntryDao::class;
        parent::__construct();
    }

    /**
     * 分页流水明细 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $accountId 账户 ID 筛选（可选）
     * @return array{entries:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $accountId = ''): array
    {
        $params = [];
        if ($accountId !== '') {
            $params['account_id'] = $accountId;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new AptLedgerEntryDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['ledger_entry_id', 'account_id', 'asset', 'quantity', 'entry_direction', 'entry_type', 'state', 'source_object_type', 'source_object_id', 'journal_batch_id', 'reversal_of', 'rule_version', 'snapshot_id', 'audit_event_id', 'object_version', 'created_time']
        );

        $entries = [];
        foreach ($paginator->items() as $e) {
            $entries[] = [
                'ledger_entry_id'    => (string) $e->ledger_entry_id,
                'account_id'         => (string) $e->account_id,
                'asset'              => (string) $e->asset,
                'quantity'           => (string) $e->quantity,
                'entry_direction'    => (int) $e->entry_direction,
                'entry_type'         => (string) $e->entry_type,
                'state'              => (string) $e->state,
                'source_object_type' => (string) $e->source_object_type,
                'source_object_id'   => (string) $e->source_object_id,
                'journal_batch_id'   => (string) $e->journal_batch_id,
                'reversal_of'        => (string) $e->reversal_of,
                'rule_version'       => (string) $e->rule_version,
                'snapshot_id'        => (string) $e->snapshot_id,
                'audit_event_id'     => (string) $e->audit_event_id,
                'object_version'     => (int) $e->object_version,
                'created_time'       => (int) $e->getRawOriginal('created_time'),
            ];
        }

        return [
            'entries' => $entries,
            'total'   => (int) $paginator->total(),
            'page'    => $page,
            'size'    => $size,
        ];
    }
}
