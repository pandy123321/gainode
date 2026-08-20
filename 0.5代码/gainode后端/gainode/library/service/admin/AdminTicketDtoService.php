<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\support\TicketDao;
use support\extend\Service;

/**
 * Admin V2 工单列表 DTO 服务（A-SUPPORT-001）。
 *
 * 只读全量分页：tickets 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；时间为 UTC。
 * 供 Admin 2.0 工单队列页经 /api/v1/admin/support/tickets 对接。
 */
class AdminTicketDtoService extends Service
{
    public function __construct()
    {
        $this->dao = TicketDao::class;
        parent::__construct();
    }

    /**
     * 分页工单列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 工单状态筛选（可选）
     * @return array{tickets:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new TicketDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['ticket_id', 'user_id', 'category', 'status', 'assigned_to', 'last_activity_at', 'resolution_type', 'object_version', 'created_time']
        );

        $tickets = [];
        foreach ($paginator->items() as $t) {
            $tickets[] = [
                'ticket_id'         => (string) $t->ticket_id,
                'user_id'           => (string) $t->user_id,
                'category'          => (string) $t->category,
                'status'            => (string) $t->status,
                'assigned_to'       => $t->assigned_to !== null ? (string) $t->assigned_to : null,
                'last_activity_at'  => (int) $t->last_activity_at,
                'resolution_type'   => $t->resolution_type !== null ? (string) $t->resolution_type : null,
                'object_version'    => (int) $t->object_version,
                'created_time'      => (int) $t->getRawOriginal('created_time'),
            ];
        }

        return [
            'tickets' => $tickets,
            'total'   => (int) $paginator->total(),
            'page'    => $page,
            'size'    => $size,
        ];
    }
}
