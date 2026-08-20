<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\parameter\ParameterReleaseDao;
use support\extend\Service;

/**
 * Admin V2 Parameter Center 列表 DTO 服务（A-CONFIG-001）。
 *
 * 只读全量分页：parameter_releases 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；时间为 UTC；parameter_keys 为 JSON 字符串透传。
 * 供 Admin 2.0 Parameter Center 页经 /api/v1/admin/parameter/definitions 对接。
 */
class AdminConfigDtoService extends Service
{
    public function __construct()
    {
        $this->dao = ParameterReleaseDao::class;
        parent::__construct();
    }

    /**
     * 分页 Parameter Release 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{releases:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new ParameterReleaseDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['release_id', 'parameter_keys', 'status', 'draft_version', 'approved_by', 'scheduled_at', 'activated_at', 'snapshot_id', 'object_version', 'created_time']
        );

        $releases = [];
        foreach ($paginator->items() as $r) {
            $releases[] = [
                'release_id'     => (string) $r->release_id,
                'parameter_keys' => $r->parameter_keys !== null ? (string) $r->parameter_keys : null,
                'status'         => (string) $r->status,
                'draft_version'  => (string) $r->draft_version,
                'approved_by'    => $r->approved_by !== null ? (string) $r->approved_by : null,
                'scheduled_at'   => (int) $r->scheduled_at,
                'activated_at'   => (int) $r->activated_at,
                'snapshot_id'    => (string) $r->snapshot_id,
                'object_version' => (int) $r->object_version,
                'created_time'   => (int) $r->getRawOriginal('created_time'),
            ];
        }

        return [
            'releases' => $releases,
            'total'    => (int) $paginator->total(),
            'page'     => $page,
            'size'     => $size,
        ];
    }
}
