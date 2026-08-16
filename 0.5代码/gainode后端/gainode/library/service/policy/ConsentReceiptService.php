<?php

declare(strict_types=1);

namespace library\service\policy;

use library\dao\policy\ConsentReceiptDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\policy\ConsentReceiptModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 同意回执 Service — consent_receipts 表唯一 Authoritative Writer（S02-P05）
 *
 * @authoritative_writer consent_receipts
 *
 * 状态机（05 §4 V2.3 canonical，Owner 2B1-ENUM-06）：
 *   active → expired（两态）
 *   - 撤回/取代不新增状态值，由新版本 receipt + consent_version 表达
 *   - expired：到期为唯一终态
 *
 * 实现策略：
 *   - grant（同意登记）完整实现：content_hash/consent_version 由调用方传入，
 *     无外部数据源依赖，幂等去重（同 user + consent_type + consent_version 仅一条 active）。
 *   - expire（active → expired）纯状态转移（审计 + object_version CAS）。
 *
 * @method ConsentReceiptModel create($data)
 * @method ConsentReceiptModel get($id, string $field = null)
 * @method ConsentReceiptModel find($id)
 * @method ConsentReceiptModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ConsentReceiptService extends Service
{
    // Event Catalog（MC2 §5，CONSENT_*）
    public const EVENT_GRANTED = 'CONSENT_GRANTED';
    public const EVENT_EXPIRED = 'CONSENT_EXPIRED';

    public function __construct()
    {
        $this->dao = ConsentReceiptDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询回执（只读透传）
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    // =========================================================================
    // 同意登记（完整实现，无外部依赖）
    // =========================================================================

    /**
     * 同意登记 grant：写入 active 回执，幂等去重（同 user + consent_type + consent_version）。
     * content_hash / consent_version 由调用方传入，不依赖外部数据源。
     */
    public function grant(
        string $userId,
        string $consentType,
        string $consentVersion,
        string $contentHash,
        int $expiresAt,
        string $policyVersion,
        string $actorId,
        string $actorRole
    ): ConsentReceiptModel {
        return (new TransactionBoundary())->run(function () use (
            $userId, $consentType, $consentVersion, $contentHash, $expiresAt, $policyVersion, $actorId, $actorRole
        ) {
            $idemKey = $this->idempotencyKey($userId, $consentType, $consentVersion);

            // 幂等去重：同 user + type + version 已存在 active 回执，直接返回（不重复创建）
            $existing = $this->getNewDao()->getByIdempotencyKey($idemKey);
            if (!empty($existing) && (string) $existing->status === ConsentReceiptModel::STATUS_ACTIVE) {
                return $existing;
            }

            $receiptId = (string) Random::getSnowflakeID();
            $auditId   = $this->appendAudit(self::EVENT_GRANTED, $actorId, $actorRole, $receiptId);

            $this->create([
                'receipt_id'      => $receiptId,
                'user_id'         => $userId,
                'consent_type'    => $consentType,
                'consent_version' => $consentVersion,
                'content_hash'    => $contentHash,
                'status'          => ConsentReceiptModel::STATUS_ACTIVE,
                'agreed_at'       => time(),
                'expires_at'      => $expiresAt,
                'policy_version'  => $policyVersion,
                'idempotency_key' => $idemKey,
                'audit_event_id'  => $auditId,
                'object_version'  => 0,
                'created_time'    => time(),
                'updated_time'    => time(),
            ]);

            return $this->get($receiptId);
        });
    }

    // =========================================================================
    // 只读投影
    // =========================================================================

    /**
     * 单个回执详情投影。
     *
     * @return array<string,mixed>
     */
    public function detail(string $receiptId): array
    {
        $r = $this->get($receiptId);
        if (empty($r)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'consent receipt not found');
        }
        return [
            'receipt_id'      => (string) $r->receipt_id,
            'user_id'         => (string) $r->user_id,
            'consent_type'    => (string) $r->consent_type,
            'consent_version' => (string) $r->consent_version,
            'content_hash'    => (string) $r->content_hash,
            'status'          => (string) $r->status,
            'agreed_at'       => (int) $r->agreed_at,
            'expires_at'      => (int) $r->expires_at,
            'policy_version'  => (string) $r->policy_version,
        ];
    }

    // =========================================================================
    // 纯状态转移
    // =========================================================================

    /**
     * active → expired（到期，唯一终态）。
     */
    public function expire(string $receiptId, string $actorId, string $actorRole): ConsentReceiptModel
    {
        return (new TransactionBoundary())->run(function () use ($receiptId, $actorId, $actorRole) {
            $receipt = $this->get($receiptId);
            if (empty($receipt)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'consent receipt not found');
            }
            $current = (string) $receipt->status;
            if ($current !== ConsentReceiptModel::STATUS_ACTIVE) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid consent receipt state transition');
            }

            $auditId = $this->appendAudit(self::EVENT_EXPIRED, $actorId, $actorRole, $receiptId);

            $affected = Db::connection('mysql')
                ->table('consent_receipts')
                ->where('receipt_id', $receiptId)
                ->where('status', $current)
                ->where('object_version', (int) $receipt->object_version)
                ->update([
                    'status'         => ConsentReceiptModel::STATUS_EXPIRED,
                    'audit_event_id' => $auditId,
                    'object_version' => (int) $receipt->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'consent receipt state transition CAS conflict');
            }

            return $this->get($receiptId);
        });
    }

    // =========================================================================
    // 私有辅助
    // =========================================================================

    /**
     * 构造幂等键：user_id + consent_type + consent_version。
     */
    private function idempotencyKey(string $userId, string $consentType, string $consentVersion): string
    {
        return $userId . ':' . $consentType . ':' . $consentVersion;
    }

    /**
     * 追加 append-only 审计事件，返回 audit_event_id。
     */
    private function appendAudit(
        string $eventCode,
        string $actorId,
        string $actorRole,
        string $targetObjectId
    ): string {
        $auditId = (string) Random::getSnowflakeID();
        (new AuditEventService())->create([
            'audit_event_id'       => $auditId,
            'event_code'           => $eventCode,
            'actor_id'             => $actorId,
            'actor_role'           => $actorRole,
            'target_object_type'   => 'consent_receipts',
            'target_object_id'     => $targetObjectId,
            'before_snapshot_type' => '',
            'before_snapshot_id'   => '0',
            'after_snapshot_type'  => '',
            'after_snapshot_id'    => '0',
            'outcome'              => AuditEventModel::OUTCOME_SUCCESS,
            'reason_code'          => '',
            'request_id'           => '',
            'approval_id'          => '0',
            'case_id'              => '0',
            'created_time'         => time(),
        ]);
        return $auditId;
    }
}
