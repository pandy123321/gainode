<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\approval\ApprovalRequestDao;
use support\extend\Service;

/**
 * Admin V2 审批中心列表 DTO 服务（A-APPROVAL-001）。
 *
 * 只读全量分页：approval_requests 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；时间为 UTC。
 * 供 Admin 2.0 审批中心页经 /api/v1/admin/approval/tasks 对接。
 */
class AdminApprovalDtoService extends Service
{
    public function __construct()
    {
        $this->dao = ApprovalRequestDao::class;
        parent::__construct();
    }

    /**
     * 分页审批请求列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{tasks:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new ApprovalRequestDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['approval_id', 'request_type', 'request_object_type', 'request_object_id', 'status', 'submitted_by', 'submitter_role', 'assigned_to', 'decided_by', 'decided_at', 'reason_key', 'object_version', 'created_time']
        );

        $tasks = [];
        foreach ($paginator->items() as $a) {
            $tasks[] = [
                'approval_id'          => (string) $a->approval_id,
                'request_type'         => (string) $a->request_type,
                'request_object_type'  => (string) $a->request_object_type,
                'request_object_id'    => (string) $a->request_object_id,
                'status'               => (string) $a->status,
                'submitted_by'         => (string) $a->submitted_by,
                'submitter_role'       => (string) $a->submitter_role,
                'assigned_to'          => $a->assigned_to !== null ? (string) $a->assigned_to : null,
                'decided_by'           => $a->decided_by !== null ? (string) $a->decided_by : null,
                'decided_at'           => (int) $a->decided_at,
                'reason_key'           => $a->reason_key !== null ? (string) $a->reason_key : null,
                'object_version'       => (int) $a->object_version,
                'created_time'         => (int) $a->getRawOriginal('created_time'),
            ];
        }

        return [
            'tasks' => $tasks,
            'total' => (int) $paginator->total(),
            'page'  => $page,
            'size'  => $size,
        ];
    }
}
