<?php

declare(strict_types=1);

namespace library\service\ledger;

use library\dao\ledger\AptLedgerEntryDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\ledger\AptLedgerEntryModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * APT 账本分录 Service — apt_ledger_entries 表唯一 Authoritative Writer（S02-P03）
 *
 * @authoritative_writer apt_ledger_entries
 *
 * 落地统一 Economic Mutation Lock（11 步）与 Ledger 状态机 L1/L2/L3：
 *   L1  pending → posted   日记账过账（经济效果 + 审计，同事务）
 *   L2  pending → reversed 入账前取消（ACCOUNT_DELTA=0，无经济 reversal）
 *   L3  posted → reversed  入账后冲正（追加 LEDGER_REVERSAL 反向分录 + 反向余额）
 *   L4/L5/L6/L7  dispute/resolveDispute  FAIL_CLOSED（RiskCase 未冻结）
 *
 * append-only 机械强制（MC1 Freeze §3.6，未放宽）：
 *   - AptLedgerEntryModel::save()/delete() 在已落盘实例抛异常；
 *   - AptLedgerEntryAppendOnlyBuilder 阻断 Eloquent Builder 层 update/delete；
 *   - AptLedgerEntryDao 覆写 delete/deleteAll/update/updateAll/updateOrCreate。
 *
 * 受控 state 转移（Ledger Mutation Field Contract，白名单三列 + 乐观锁）：
 *   仅 state / audit_event_id / object_version 三列可经本服务 transitionState()
 *   显式 raw Query Builder 更新；其余经济字段永久 immutable。
 *
 * @method AptLedgerEntryModel create($data)
 * @method AptLedgerEntryModel get($id, string $field = null)
 * @method AptLedgerEntryModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class LedgerService extends Service
{
    // Event Catalog（MC2 §5，对齐 entry_type/entry_direction）
    public const EVENT_POSTED          = 'LEDGER_ENTRY_POSTED';
    public const EVENT_REVERSED        = 'LEDGER_ENTRY_REVERSED';
    public const EVENT_DISPUTED        = 'LEDGER_ENTRY_DISPUTED';
    public const EVENT_DISPUTE_RESOLVED = 'LEDGER_ENTRY_DISPUTE_RESOLVED';
    public const EVENT_DISPUTE_REVERSED = 'LEDGER_ENTRY_DISPUTE_REVERSED';

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

    /**
     * 按幂等键查询分录（写操作去重）
     *
     * @param string $idempotencyKey
     * @return AptLedgerEntryModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->getNewDao()->getByIdempotencyKey($idempotencyKey);
    }

    /**
     * 追加一条 pending 分录（INSERT，无经济效果）。
     *
     * 幂等：非空 idempotency_key 命中已存在分录 → IDEMPOTENCY_CONFLICT。
     * 校验：asset 仅 APT-I；quantity > 0；entry_direction ∈ {1,-1}。
     *
     * @param array $data
     * @return AptLedgerEntryModel
     * @throws DomainException
     */
    public function append(array $data): AptLedgerEntryModel
    {
        $this->validateAppend($data);

        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = $this->getNewDao()->getByIdempotencyKey($idempotencyKey);
            if (!empty($existing)) {
                throw new DomainException(ErrorDict::IDEMPOTENCY_CONFLICT, 'duplicate idempotency_key');
            }
        }

        $row = [
            'ledger_entry_id'    => (string) Random::getSnowflakeID(),
            'account_id'         => $data['account_id'],
            'asset'              => $data['asset'] ?? AptLedgerEntryModel::ASSET_APT_I,
            'quantity'           => $this->normalize($data['quantity']),
            'entry_direction'    => (int) $data['entry_direction'],
            'entry_type'         => $data['entry_type'] ?? '',
            'state'              => AptLedgerEntryModel::STATE_PENDING,
            'source_object_type' => $data['source_object_type'] ?? '',
            'source_object_id'   => (string) ($data['source_object_id'] ?? '0'),
            'journal_batch_id'   => (string) ($data['journal_batch_id'] ?? '0'),
            'reversal_of'        => (string) ($data['reversal_of'] ?? '0'),
            'idempotency_key'    => $idempotencyKey !== '' ? $idempotencyKey : null,
            'rule_version'       => $data['rule_version'] ?? '',
            'snapshot_id'        => (string) ($data['snapshot_id'] ?? '0'),
            'audit_event_id'     => (string) ($data['audit_event_id'] ?? '0'),
            'object_version'     => 0,
            'created_time'       => time(),
        ];

        return $this->create($row);
    }

    /**
     * L1：pending → posted。日记账过账（经济效果 + 审计，同事务原子）。
     *
     * 经济效果经 AptAccountService::applyEntryEffect()（object_version CAS）。
     *
     * @param string $entryId
     * @param string $actorId
     * @param string $actorRole
     * @return AptLedgerEntryModel
     * @throws DomainException
     */
    public function post(string $entryId, string $actorId, string $actorRole): AptLedgerEntryModel
    {
        return (new TransactionBoundary())->run(function () use ($entryId, $actorId, $actorRole) {
            $entry = $this->mustGetPending($entryId);

            $accountSvc = new AptAccountService();
            $account = $accountSvc->get((string) $entry->account_id);
            if (empty($account)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'apt_accounts not found');
            }

            // 经济效果（guard：负余额 + CAS）
            $accountSvc->applyEntryEffect(
                (string) $entry->account_id,
                (string) $entry->quantity,
                (int) $entry->entry_direction,
                (int) $account->object_version,
                $entryId
            );

            // 审计 + 受控 state 转移
            $auditId = $this->appendAudit(
                self::EVENT_POSTED, $actorId, $actorRole, $entryId,
                AuditEventModel::OUTCOME_SUCCESS, ''
            );
            $this->transitionState($entryId, AptLedgerEntryModel::STATE_PENDING, AptLedgerEntryModel::STATE_POSTED, (int) $entry->object_version, $auditId);

            return $this->get($entryId);
        });
    }

    /**
     * L2：pending → reversed（入账前取消，无经济效果，无经济 reversal 分录）。
     *
     * @param string $entryId
     * @param string $actorId
     * @param string $actorRole
     * @return AptLedgerEntryModel
     * @throws DomainException
     */
    public function cancel(string $entryId, string $actorId, string $actorRole): AptLedgerEntryModel
    {
        return (new TransactionBoundary())->run(function () use ($entryId, $actorId, $actorRole) {
            $entry = $this->mustGetPending($entryId);

            $auditId = $this->appendAudit(
                self::EVENT_REVERSED, $actorId, $actorRole, $entryId,
                AuditEventModel::OUTCOME_SUCCESS, ''
            );
            $this->transitionState($entryId, AptLedgerEntryModel::STATE_PENDING, AptLedgerEntryModel::STATE_REVERSED, (int) $entry->object_version, $auditId);

            return $this->get($entryId);
        });
    }

    /**
     * L3：posted → reversed（入账后冲正）。
     *
     * 追加 LEDGER_REVERSAL 反向分录（entry_direction=-(原)、quantity=原、reversal_of=原），
     * 并反向更新余额。禁止删除/覆盖原文。
     *
     * @param string $entryId
     * @param string $actorId
     * @param string $actorRole
     * @return AptLedgerEntryModel 返回 reversal 分录
     * @throws DomainException
     */
    public function reverse(string $entryId, string $actorId, string $actorRole): AptLedgerEntryModel
    {
        return (new TransactionBoundary())->run(function () use ($entryId, $actorId, $actorRole) {
            $entry = $this->get($entryId);
            if (empty($entry)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'ledger entry not found');
            }
            if ((string) $entry->state !== AptLedgerEntryModel::STATE_POSTED) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'entry not in posted state');
            }

            $accountSvc = new AptAccountService();
            $account = $accountSvc->get((string) $entry->account_id);
            if (empty($account)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'apt_accounts not found');
            }

            $reversalDirection = (int) $entry->entry_direction === AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT
                ? AptLedgerEntryModel::ENTRY_DIRECTION_DEBIT
                : AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT;

            // 审计（原分录）
            $auditId = $this->appendAudit(
                self::EVENT_REVERSED, $actorId, $actorRole, $entryId,
                AuditEventModel::OUTCOME_SUCCESS, ''
            );
            // 原分录 posted → reversed
            $this->transitionState($entryId, AptLedgerEntryModel::STATE_POSTED, AptLedgerEntryModel::STATE_REVERSED, (int) $entry->object_version, $auditId);

            // 追加 reversal 分录（state=posted，立即生效）
            $reversal = $this->create([
                'ledger_entry_id'    => (string) Random::getSnowflakeID(),
                'account_id'         => (string) $entry->account_id,
                'asset'              => (string) $entry->asset,
                'quantity'           => $this->normalize($entry->quantity),
                'entry_direction'    => $reversalDirection,
                'entry_type'         => AptLedgerEntryModel::ENTRY_TYPE_LEDGER_REVERSAL,
                'state'              => AptLedgerEntryModel::STATE_POSTED,
                'source_object_type' => (string) $entry->source_object_type,
                'source_object_id'   => (string) $entry->source_object_id,
                'journal_batch_id'   => (string) $entry->journal_batch_id,
                'reversal_of'        => $entryId,
                'idempotency_key'    => 'reversal:' . $entryId,
                'rule_version'       => (string) $entry->rule_version,
                'snapshot_id'        => (string) $entry->snapshot_id,
                'audit_event_id'     => $auditId,
                'object_version'     => 0,
                'created_time'       => time(),
            ]);

            // 反向经济效果（reversal 分录的 direction 即反向）
            $accountSvc->applyEntryEffect(
                (string) $entry->account_id,
                (string) $entry->quantity,
                $reversalDirection,
                (int) $account->object_version,
                (string) $reversal->ledger_entry_id
            );

            return $reversal;
        });
    }

    /**
     * L4/L5：dispute（pending/posted → disputed）。
     * RiskCase machine contract 未冻结（2B-2 CONTRACT_GAP）→ FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function dispute(string $entryId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'L4/L5 dispute depends on RiskCase contract (2B-2) — not frozen'
        );
    }

    /**
     * L6/L7：dispute 仲裁（disputed → posted/reversed）。
     * RiskCase machine contract 未冻结 → FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function resolveDispute(string $entryId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'L6/L7 dispute resolution depends on RiskCase contract (2B-2) — not frozen'
        );
    }

    // =========================================================================
    // 私有辅助
    // =========================================================================

    /**
     * 读取并断言分录处于 pending（供 L1/L2 使用）。
     */
    private function mustGetPending(string $entryId): AptLedgerEntryModel
    {
        $entry = $this->get($entryId);
        if (empty($entry)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'ledger entry not found');
        }
        if ((string) $entry->state !== AptLedgerEntryModel::STATE_PENDING) {
            throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'entry not in pending state');
        }
        return $entry;
    }

    /**
     * 受控 state 转移（Ledger Mutation Field Contract：白名单三列 + 乐观锁）。
     *
     * 使用 raw Query Builder 显式更新，仅改 state / audit_event_id / object_version。
     * affected rows≠1 → OBJECT_VERSION_CONFLICT（fail-closed）。
     */
    private function transitionState(
        string $entryId,
        string $fromState,
        string $toState,
        int $expectedVersion,
        string $auditEventId
    ): void {
        $affected = Db::connection('mysql')
            ->table('apt_ledger_entries')
            ->where('ledger_entry_id', $entryId)
            ->where('state', $fromState)
            ->where('object_version', $expectedVersion)
            ->update([
                'state'          => $toState,
                'audit_event_id' => $auditEventId,
                'object_version' => $expectedVersion + 1,
            ]);

        if ($affected !== 1) {
            throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'ledger state transition CAS conflict');
        }
    }

    /**
     * 追加 append-only 审计事件，返回 audit_event_id。
     */
    private function appendAudit(
        string $eventCode,
        string $actorId,
        string $actorRole,
        string $targetObjectId,
        string $outcome,
        string $reasonCode
    ): string {
        $auditId = (string) Random::getSnowflakeID();
        (new AuditEventService())->create([
            'audit_event_id'        => $auditId,
            'event_code'            => $eventCode,
            'actor_id'              => $actorId,
            'actor_role'            => $actorRole,
            'target_object_type'    => 'apt_ledger_entries',
            'target_object_id'      => $targetObjectId,
            'before_snapshot_type'  => '',
            'before_snapshot_id'    => '0',
            'after_snapshot_type'   => '',
            'after_snapshot_id'     => '0',
            'outcome'               => $outcome,
            'reason_code'           => $reasonCode,
            'request_id'            => '',
            'approval_id'           => '0',
            'case_id'               => '0',
            'created_time'          => time(),
        ]);
        return $auditId;
    }

    /**
     * 校验 append 输入。
     */
    private function validateAppend(array $data): void
    {
        if (empty($data['account_id'])) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'account_id required');
        }
        if (empty($data['quantity']) || bccomp($this->normalize($data['quantity']), '0', 18) <= 0) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'quantity must be positive');
        }
        $direction = (int) ($data['entry_direction'] ?? 0);
        if (!in_array($direction, [AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT, AptLedgerEntryModel::ENTRY_DIRECTION_DEBIT], true)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'entry_direction must be 1 or -1');
        }
        $asset = $data['asset'] ?? AptLedgerEntryModel::ASSET_APT_I;
        if ($asset !== AptLedgerEntryModel::ASSET_APT_I) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'asset must be APT-I');
        }
    }

    /**
     * 归一化金额为 bcmath 可用字符串。
     */
    private function normalize($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }
        return (string) $value;
    }
}
