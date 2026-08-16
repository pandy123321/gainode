<?php

declare(strict_types=1);

namespace library\service\ledger;

use library\dao\ledger\AptAccountDao;
use library\dict\ErrorDict;
use library\model\ledger\AptAccountModel;
use support\extend\Service;
use support\exception\DomainException;

/**
 * APT 数量账主账号 Service — apt_accounts 表唯一 Authoritative Writer（S02-P03）
 *
 * @authoritative_writer apt_accounts
 *
 * 本表为 scalar 余额模型（无领域状态机）。余额字段（balance_apt_* / frozen_apt_* /
 * total_*_apt）只能随账本分录在同一事务内由账本模块更新，禁止任何旁路直接改写。
 *
 * S02-P03 落地统一 Economic Mutation Lock 的账户侧 CAS：
 *   - applyEntryEffect()：CAS 乐观锁（object_version）更新余额，affected rows≠1
 *     统一抛 OBJECT_VERSION_CONFLICT(409)。
 *   - 金额一律 bcmath 字符串运算，禁 float。
 *
 * 注意：本表无 aggregate_dispute_hold 列（MC1 冻结 DDL 未含该列）。dispute_hold 为
 * 账户级聚合「投影」（Σ active disputed entries），S02-P03 中 L4/L5 dispute 均
 * FAIL_CLOSED，故恒为 0；effective_available = stored_available（balance_apt_i）。
 *
 * @method AptAccountModel create($data)
 * @method AptAccountModel get($id, string $field = null)
 * @method AptAccountModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class AptAccountService extends Service
{
    public function __construct()
    {
        $this->dao = AptAccountDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询主账号（只读透传）
     *
     * @param string $userId
     * @return AptAccountModel|null
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    /**
     * 账户级 aggregate_dispute_hold 投影（S02-P03 恒为 0）。
     *
     * MC2（IR 682 P1-2）：dispute_hold 是 ACCOUNT-LEVEL AGGREGATE
     * （Σ active disputed/reserved entries 的 hold）。S02-P03 中 L4/L5 未冻结
     * （RiskCase CONTRACT_GAP），故不存在 disputed 分录，恒为 '0'。
     *
     * @param string $accountId
     * @return string
     */
    public function getAggregateDisputeHold(string $accountId): string
    {
        return '0';
    }

    /**
     * 计算 effective_available = stored_available - aggregate_dispute_hold。
     *
     * @param AptAccountModel $account
     * @return string
     */
    public function getEffectiveAvailable(AptAccountModel $account): string
    {
        return bcsub(
            $this->normalize($account->balance_apt_i),
            $this->getAggregateDisputeHold((string) $account->account_id),
            18
        );
    }

    /**
     * 事务内应用一条分录的经济效果（CAS 乐观锁）。
     *
     * CREDIT(+1)：balance_apt_i += quantity，total_earned_apt += quantity。
     * DEBIT(-1)：balance_apt_i -= quantity，total_spent_apt += quantity。
     * DEBIT 使余额 < 0 → INSUFFICIENT_APT(422)（禁止负 stored_balance）。
     * object_version 不匹配 → OBJECT_VERSION_CONFLICT(409)。
     *
     * @param string $accountId
     * @param string $quantity          正数，decimal string
     * @param int    $direction         1=CREDIT / -1=DEBIT
     * @param int    $expectedVersion   调用方读取到的 object_version
     * @param string $lastLedgerEntryId 最近分录ID（回写 last_ledger_entry_id）
     * @return AptAccountModel 更新后的账户
     * @throws DomainException
     */
    public function applyEntryEffect(
        string $accountId,
        string $quantity,
        int $direction,
        int $expectedVersion,
        string $lastLedgerEntryId
    ): AptAccountModel {
        $account = $this->get($accountId);
        if (empty($account)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'apt_accounts not found');
        }

        $quantity = $this->normalize($quantity);
        if (bccomp($quantity, '0', 18) <= 0) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'quantity must be positive');
        }

        $balance = $this->normalize($account->balance_apt_i);
        $earned  = $this->normalize($account->total_earned_apt);
        $spent   = $this->normalize($account->total_spent_apt);

        if ($direction === -1) {
            // DEBIT：先做负余额保护
            if (bccomp($balance, $quantity, 18) < 0) {
                throw new DomainException(ErrorDict::INSUFFICIENT_APT, 'insufficient APT');
            }
            $newBalance = bcsub($balance, $quantity, 18);
            $newEarned  = $earned;
            $newSpent   = bcadd($spent, $quantity, 18);
        } elseif ($direction === 1) {
            $newBalance = bcadd($balance, $quantity, 18);
            $newEarned  = bcadd($earned, $quantity, 18);
            $newSpent   = $spent;
        } else {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'invalid entry_direction');
        }

        $affected = $this->getNewDao()->updateAll(
            ['account_id' => $accountId, 'object_version' => $expectedVersion],
            [
                'balance_apt_i'        => $newBalance,
                'total_earned_apt'     => $newEarned,
                'total_spent_apt'      => $newSpent,
                'last_ledger_entry_id' => $lastLedgerEntryId,
                'object_version'       => $expectedVersion + 1,
                'updated_time'         => time(),
            ]
        );

        if ($affected !== 1) {
            throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'apt_accounts CAS conflict');
        }

        $updated = $this->get($accountId);
        if (empty($updated)) {
            throw new DomainException(ErrorDict::INTERNAL_ERROR, 'apt_accounts reload failed');
        }
        return $updated;
    }

    /**
     * 归一化金额为 bcmath 可用字符串。
     *
     * @param mixed $value
     * @return string
     */
    private function normalize($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }
        if (is_float($value) || is_int($value)) {
            return (string) $value;
        }
        return (string) $value;
    }
}
