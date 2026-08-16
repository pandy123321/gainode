<?php

declare(strict_types=1);

namespace library\dao\audit;

use support\extend\Dao;
use support\exception\RunException;
use library\model\audit\AuditEventModel;

/**
 * AuditEvent DAO — audit_events 表查询封装（append-only）
 *
 * 注意：append-only 审计事件禁止物理删除/覆盖。本 DAO 对继承的 delete/deleteAll/update/
 * updateAll/updateOrCreate 全部 fail-closed 覆写，从代码层面机械阻断 DAO 层的删除/覆盖路径。
 * 仅保留只读查询与追加（create/insert）。
 */
class AuditEventDao extends Dao
{
    public function __construct()
    {
        $this->model = AuditEventModel::class;
    }

    /**
     * 按目标对象查询审计事件
     *
     * @param string $targetObjectType
     * @param string $targetObjectId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByTarget(string $targetObjectType, string $targetObjectId)
    {
        return $this->fetchAll(['target_object_type' => $targetObjectType, 'target_object_id' => $targetObjectId]);
    }

    /**
     * 按操作者查询审计事件
     *
     * @param string $actorId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByActor(string $actorId)
    {
        return $this->fetchAll(['actor_id' => $actorId]);
    }

    /**
     * 按事件码查询审计事件
     *
     * @param string $eventCode
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByEventCode(string $eventCode)
    {
        return $this->fetchAll(['event_code' => $eventCode]);
    }

    /**
     * append-only 审计事件：禁止删除单条。
     *
     * @throws RunException
     */
    public function delete($id, bool $force = false)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止删除事件');
    }

    /**
     * append-only 审计事件：禁止批量删除。
     *
     * @throws RunException
     */
    public function deleteAll(array $params, bool $force = false)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止批量删除事件');
    }

    /**
     * append-only 审计事件：禁止 UPDATE。
     *
     * @throws RunException
     */
    public function update($id, array $data)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 UPDATE 事件');
    }

    /**
     * append-only 审计事件：禁止批量 UPDATE。
     *
     * @throws RunException
     */
    public function updateAll(array $params, array $data)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止批量 UPDATE 事件');
    }

    /**
     * append-only 审计事件：禁止 updateOrCreate。
     *
     * @throws RunException
     */
    public function updateOrCreate(array $params, array $data)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 updateOrCreate');
    }
}
