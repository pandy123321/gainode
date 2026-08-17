<?php

declare(strict_types=1);

namespace library\service\notice;

use library\dao\notice\NotificationDeliveryDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\notice\NotificationDeliveryModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\middleware\RequestContext;
use support\utils\Random;

/**
 * 通知投递 Service — notification_deliveries 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer notification_deliveries
 *
 * 状态机（05 §4 V2.4 canonical，Owner 裁决 2B2-ENUM-01；转移矩阵 2B-2 §8.1，CANDIDATE 未 FROZEN）：
 *   pending → delivered / failed / cancelled
 *   failed → pending（重试，attempt_count + next_retry_at 驱动）
 *   - 失败态 failed 不新增 processing 态；投递失败不回滚业务（05 §4 Notice 设计原则 1）。
 *   - 幂等：dedupe_key（去重 key，不设 idempotency_key）。
 *
 * 实现策略（fail-closed，与 S02-P05/P06 一致）：
 *   - 纯状态转移（pending→delivered/failed/cancelled，failed→pending）完整实现
 *     （审计 + object_version CAS + audit_event_id 回写）。
 *   - deliver（实际调用 PUSH/EMAIL/SMS/IN_APP 渠道）依赖通知渠道服务（TBC）→ FAIL_CLOSED。
 *
 * @method NotificationDeliveryModel create($data)
 * @method NotificationDeliveryModel get($id, string $field = null)
 * @method NotificationDeliveryModel find($id)
 * @method NotificationDeliveryModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class NotificationDeliveryService extends Service
{
    public const EVENT_DELIVERED = 'NOTIFICATION_DELIVERED';
    public const EVENT_FAILED    = 'NOTIFICATION_DELIVERY_FAILED';
    public const EVENT_RETRY     = 'NOTIFICATION_DELIVERY_RETRY';
    public const EVENT_CANCELLED = 'NOTIFICATION_DELIVERY_CANCELLED';

    public function __construct()
    {
        $this->dao = NotificationDeliveryDao::class;
        parent::__construct();
    }

    public function getByNotice(string $noticeId)
    {
        return $this->getNewDao()->getByNotice($noticeId);
    }

    public function getByDedupeKey(string $dedupeKey)
    {
        return $this->getNewDao()->getByDedupeKey($dedupeKey);
    }

    public function listByNotice(string $noticeId): array
    {
        $items = [];
        foreach ($this->getByNotice($noticeId) as $d) {
            $items[] = [
                'delivery_id'      => (string) $d->delivery_id,
                'channel'          => (string) $d->channel,
                'delivery_status'  => (string) $d->delivery_status,
                'attempt_count'    => (int) $d->attempt_count,
                'created_time'     => (int) $d->getRawOriginal('created_time'),
            ];
        }
        return ['deliveries' => $items];
    }

    public function detail(string $deliveryId): array
    {
        $d = $this->get($deliveryId);
        if (empty($d)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'notification delivery not found');
        }
        return [
            'delivery_id'         => (string) $d->delivery_id,
            'notice_id'           => (string) $d->notice_id,
            'channel'             => (string) $d->channel,
            'delivery_status'     => (string) $d->delivery_status,
            'dedupe_key'          => (string) $d->dedupe_key,
            'attempt_count'       => (int) $d->attempt_count,
            'last_attempt_at'     => (int) $d->last_attempt_at,
            'next_retry_at'       => (int) $d->next_retry_at,
            'delivered_at'        => (int) $d->delivered_at,
            'failure_reason_code' => (string) $d->failure_reason_code,
            'object_version'      => (int) $d->object_version,
        ];
    }

    /**
     * 实际投递。依赖通知渠道服务（PUSH/EMAIL/SMS/IN_APP，TBC）→ FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function deliver(string $deliveryId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'NotificationDelivery deliver depends on notification channel services (PUSH/EMAIL/SMS/IN_APP, TBC) — not frozen'
        );
    }

    /** pending → delivered（投递成功；attempt_count 递增） */
    public function markDelivered(string $deliveryId, string $actorId, string $actorRole): NotificationDeliveryModel
    {
        return $this->transition(
            $deliveryId, [NotificationDeliveryModel::STATUS_PENDING], NotificationDeliveryModel::STATUS_DELIVERED,
            self::EVENT_DELIVERED, $actorId, $actorRole,
            ['delivered_at' => time(), 'last_attempt_at' => time()],
            true
        );
    }

    /** pending → failed（投递失败，attempt_count 递增并排期重试） */
    public function markFailed(
        string $deliveryId,
        string $actorId,
        string $actorRole,
        string $failureReasonCode = '',
        int $nextRetryAt = 0
    ): NotificationDeliveryModel {
        $extra = [
            'failure_reason_code' => $failureReasonCode,
            'last_attempt_at'     => time(),
        ];
        if ($nextRetryAt > 0) {
            $extra['next_retry_at'] = $nextRetryAt;
        }
        return $this->transition(
            $deliveryId, [NotificationDeliveryModel::STATUS_PENDING], NotificationDeliveryModel::STATUS_FAILED,
            self::EVENT_FAILED, $actorId, $actorRole, $extra, true
        );
    }

    /** failed → pending（重试） */
    public function retry(string $deliveryId, string $actorId, string $actorRole): NotificationDeliveryModel
    {
        return $this->transition(
            $deliveryId, [NotificationDeliveryModel::STATUS_FAILED], NotificationDeliveryModel::STATUS_PENDING,
            self::EVENT_RETRY, $actorId, $actorRole
        );
    }

    /** pending → cancelled（业务对象失效/用户已读，不再投递） */
    public function cancel(string $deliveryId, string $actorId, string $actorRole): NotificationDeliveryModel
    {
        return $this->transition(
            $deliveryId, [NotificationDeliveryModel::STATUS_PENDING], NotificationDeliveryModel::STATUS_CANCELLED,
            self::EVENT_CANCELLED, $actorId, $actorRole
        );
    }

    private function transition(
        string $deliveryId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole,
        array $extraFields = [],
        bool $incrementAttempt = false
    ): NotificationDeliveryModel {
        return (new TransactionBoundary())->run(function () use (
            $deliveryId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole, $extraFields, $incrementAttempt
        ) {
            $delivery = $this->get($deliveryId);
            if (empty($delivery)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'notification delivery not found');
            }
            $current = (string) $delivery->delivery_status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid notification delivery state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $deliveryId);

            $fields = array_merge([
                'delivery_status' => $toStatus,
                'audit_event_id'  => $auditId,
                'object_version'  => (int) $delivery->object_version + 1,
                'updated_time'    => time(),
            ], $extraFields);

            if ($incrementAttempt) {
                $fields['attempt_count'] = (int) $delivery->attempt_count + 1;
            }

            $affected = Db::connection('mysql')
                ->table('notification_deliveries')
                ->where('delivery_id', $deliveryId)
                ->where('delivery_status', $current)
                ->where('object_version', (int) $delivery->object_version)
                ->update($fields);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'notification delivery state transition CAS conflict');
            }

            return $this->get($deliveryId);
        });
    }

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
            'target_object_type'   => 'notification_deliveries',
            'target_object_id'     => $targetObjectId,
            'before_snapshot_type' => '',
            'before_snapshot_id'   => '0',
            'after_snapshot_type'  => '',
            'after_snapshot_id'    => '0',
            'outcome'              => AuditEventModel::OUTCOME_SUCCESS,
            'reason_code'          => '',
            'request_id'           => RequestContext::getRequestId(),
            'approval_id'          => '0',
            'case_id'              => '0',
            'created_time'         => time(),
        ]);
        return $auditId;
    }
}
