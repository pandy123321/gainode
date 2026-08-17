<?php

declare(strict_types=1);

namespace library\service\notice;

use library\dao\notice\NoticeDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\notice\NoticeModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\middleware\RequestContext;
use support\utils\Random;

/**
 * 通知 Service — notices 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer notices
 *
 * 只读聚合（05 §3 Notice，无状态机）；read_state（unread/read）为通知唯一可变字段。
 *   - 正文 I18N key 映射，不暴露 raw reason_code（05 §4 Notice 安全 reason mapping）。
 *   - Notice 与业务事务解耦；NotificationDelivery 失败不回滚业务（05 §4 设计原则 1）。
 *
 * 实现策略（fail-closed，与 S02-P05/P06 一致）：
 *   - create 为纯 append（通知创建业务由具体业务 Writer 触发，透传 append-only 语义）。
 *   - markRead（unread → read）为字段流转：审计 + object_version CAS + audit_event_id 回写。
 *
 * @method NoticeModel create($data)
 * @method NoticeModel get($id, string $field = null)
 * @method NoticeModel find($id)
 * @method NoticeModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class NoticeService extends Service
{
    public const EVENT_READ = 'NOTICE_READ';

    // ---- 05 §8 最小角色（本包仅引用这 1 个，canonical 冻结）----
    public const ROLE_END_USER = 'END_USER';

    public function __construct()
    {
        $this->dao = NoticeDao::class;
        parent::__construct();
    }

    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->getNewDao()->getByIdempotencyKey($idempotencyKey);
    }

    public function listByUser(string $userId): array
    {
        $items = [];
        foreach ($this->getByUser($userId) as $n) {
            $items[] = [
                'notice_id'     => (string) $n->notice_id,
                'notice_type'   => (string) $n->notice_type,
                'priority'      => (string) $n->priority,
                'read_state'    => (string) $n->read_state,
                'created_time'  => (int) $n->getRawOriginal('created_time'),
            ];
        }
        return ['notices' => $items];
    }

    public function detail(string $noticeId): array
    {
        $n = $this->get($noticeId);
        if (empty($n)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'notice not found');
        }
        return [
            'notice_id'           => (string) $n->notice_id,
            'user_id'             => (string) $n->user_id,
            'notice_type'         => (string) $n->notice_type,
            'title_key'           => (string) $n->title_key,
            'body_key'            => (string) $n->body_key,
            'priority'            => (string) $n->priority,
            'related_object_type' => (string) $n->related_object_type,
            'related_object_id'   => (string) $n->related_object_id,
            'read_state'          => (string) $n->read_state,
            'expires_at'          => (int) $n->expires_at,
            'object_version'      => (int) $n->object_version,
        ];
    }

    /** read_state：unread → read（幂等：已读再标记不报错，直接返回；END_USER 本人） */
    public function markRead(string $noticeId, string $actorId, string $actorRole): NoticeModel
    {
        return (new TransactionBoundary())->run(function () use ($noticeId, $actorId, $actorRole) {
            $notice = $this->get($noticeId);
            if (empty($notice)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'notice not found');
            }
            if ($actorRole !== self::ROLE_END_USER) {
                throw new DomainException(ErrorDict::AUTH_FORBIDDEN, 'notice markRead actor role forbidden');
            }
            if ((string) $notice->user_id !== $actorId) {
                throw new DomainException(ErrorDict::AUTH_FORBIDDEN, 'notice owner mismatch');
            }
            if ((string) $notice->read_state === NoticeModel::READ_READ) {
                return $notice;
            }
            if ((string) $notice->read_state !== NoticeModel::READ_UNREAD) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid notice read state transition');
            }

            $auditId = $this->appendAudit(self::EVENT_READ, $actorId, $actorRole, $noticeId);

            $affected = Db::connection('mysql')
                ->table('notices')
                ->where('notice_id', $noticeId)
                ->where('read_state', NoticeModel::READ_UNREAD)
                ->where('object_version', (int) $notice->object_version)
                ->update([
                    'read_state'     => NoticeModel::READ_READ,
                    'audit_event_id' => $auditId,
                    'object_version' => (int) $notice->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'notice read state CAS conflict');
            }

            return $this->get($noticeId);
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
            'target_object_type'   => 'notices',
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
