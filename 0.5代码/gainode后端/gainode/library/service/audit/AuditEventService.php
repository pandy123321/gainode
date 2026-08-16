<?php

declare(strict_types=1);

namespace library\service\audit;

use library\dao\audit\AuditEventDao;
use library\model\audit\AuditEventModel;
use support\extend\Service;

/**
 * 审计事件 Service — audit_events 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer audit_events
 *
 * append-only 约束（MC2 Freeze §6）：
 *   - 一事件一行，顺序可重建；无 UPDATE/DELETE。
 *   - before/after_snapshot 采用 snapshot_type + snapshot_id 类型化引用（IR 629 P1-6）。
 *   - 机械强制见 AuditEventModel / AuditEventAppendOnlyBuilder / AuditEventDao。
 *
 * 本骨架仅允许追加（INSERT）审计事件；不实现任何覆盖/删除路径。
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

    /**
     * 按目标对象查询审计事件（只读透传）
     *
     * @param string $targetObjectType
     * @param string $targetObjectId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByTarget(string $targetObjectType, string $targetObjectId)
    {
        return $this->getNewDao()->getByTarget($targetObjectType, $targetObjectId);
    }
}
