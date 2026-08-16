<?php

declare(strict_types=1);

namespace library\service\audit;

use library\dao\audit\AuditEventDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use support\extend\Service;
use support\exception\DomainException;

/**
 * 审计事件 Service — audit_events 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer audit_events
 *
 * append-only 约束（MC2 Freeze §6）：
 *   - 一事件一行，顺序可重建；无 UPDATE/DELETE。
 *   - before/after_snapshot 采用 snapshot_type + snapshot_id 类型化引用（IR 629 P1-6）。
 *   - 机械强制见 AuditEventModel / AuditEventAppendOnlyBuilder / AuditEventDao
 *     （save/delete/update 抛 RunException）。
 *
 * 本 Service 仅允许追加（INSERT）与只读查询（AUDITOR 角色）：
 *   - create 透传 append-only DAO（各 Authoritative Writer 调用）。
 *   - listAdmin / detail 返回脱敏白名单字段，不暴露 before/after snapshot 内容。
 *
 * @method AuditEventModel create($data)
 * @method AuditEventModel get($id, string $field = null)
 * @method AuditEventModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class AuditEventService extends Service
{
    public function __construct()
    {
        $this->dao = AuditEventDao::class;
        parent::__construct();
    }

    /** 按目标对象查询审计事件（只读透传） */
    public function getByTarget(string $targetObjectType, string $targetObjectId)
    {
        return $this->getNewDao()->getByTarget($targetObjectType, $targetObjectId);
    }

    /** 按操作者查询审计事件（只读透传） */
    public function getByActor(string $actorId)
    {
        return $this->getNewDao()->getByActor($actorId);
    }

    /** 按事件码查询审计事件（只读透传） */
    public function getByEventCode(string $eventCode)
    {
        return $this->getNewDao()->getByEventCode($eventCode);
    }

    /**
     * Admin/AUDITOR 审计查询（脱敏投影）。
     *
     * @param array $filters 等值过滤条件（actor_id / event_code / target_object_type /
     *                        target_object_id / outcome / request_id）
     * @return array
     */
    public function listAdmin(array $filters = []): array
    {
        $allowed = array_intersect_key($filters, array_flip([
            'actor_id', 'event_code', 'target_object_type', 'target_object_id', 'outcome', 'request_id',
        ]));
        $items = [];
        foreach ($this->fetchAll($allowed, ['created_time' => 'desc']) as $e) {
            $items[] = $this->redact($e);
        }
        return ['audit_events' => $items];
    }

    public function detail(string $auditEventId): array
    {
        $e = $this->get($auditEventId);
        if (empty($e)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'audit event not found');
        }
        return $this->redact($e);
    }

    /** 脱敏白名单：仅返回审计元数据，不暴露 before/after snapshot 内容 */
    private function redact(AuditEventModel $e): array
    {
        return [
            'audit_event_id'     => (string) $e->audit_event_id,
            'event_code'         => (string) $e->event_code,
            'actor_id'           => (string) $e->actor_id,
            'actor_role'         => (string) $e->actor_role,
            'target_object_type' => (string) $e->target_object_type,
            'target_object_id'   => (string) $e->target_object_id,
            'outcome'            => (string) $e->outcome,
            'reason_code'        => (string) $e->reason_code,
            'request_id'         => (string) $e->request_id,
            'approval_id'        => (string) $e->approval_id,
            'case_id'            => (string) $e->case_id,
            'created_time'       => (int) $e->getRawOriginal('created_time'),
        ];
    }
}
