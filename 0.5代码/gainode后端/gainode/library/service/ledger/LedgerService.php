<?php

declare(strict_types=1);

namespace library\service\ledger;

use library\dao\ledger\AptLedgerEntryDao;
use library\model\ledger\AptLedgerEntryModel;
use support\extend\Service;

/**
 * APT 账本分录 Service — apt_ledger_entries 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer apt_ledger_entries
 *
 * append-only 与状态流转约束（MC1 Freeze §3.6）：
 *   - 经济字段（除 state / audit_event_id 外）不可 UPDATE/DELETE。
 *   - state 是唯一可变列，流转必须与「追加 append-only 审计事件 + 更新 audit_event_id」
 *     在同一 DB 事务内原子完成。
 *   - 冲正通过新增分录 + reversal_of 指向原分录，不删不覆盖原文。
 *
 * 重要（FAIL_CLOSED）：
 *   MC1 只冻结了 Ledger canonical enum、字段可变性与审计不变量，**未授权任何具体 state
 *   transition**（各 transition 触发条件、dispute 仲裁、reversal 触发条件均为 CONTRACT GAP）。
 *   在 Event Catalog / Ledger Mutation Contract 正式 FROZEN 前，任何 state 流转操作
 *   MUST FAIL_CLOSED（拒绝写入），不得自创转移规则。
 *
 * @method AptLedgerEntryModel create($data)
 * @method AptLedgerEntryModel get($id, string $field = null)
 * @method AptLedgerEntryModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class LedgerService extends Service
{
    public function __construct()
    {
        $this->dao = AptLedgerEntryDao::class;
        parent::__construct();
    }

    /**
     * 按账号查询分录（只读透传）
     *
     * @param string $accountId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByAccount(string $accountId)
    {
        return $this->getNewDao()->getByAccount($accountId);
    }
}
