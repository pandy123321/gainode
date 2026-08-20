<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\prediction\ResultDao;
use support\extend\Service;

/**
 * Admin V2 Result/Settlement 列表 DTO 服务（A-PREDICT-003）。
 *
 * 只读全量分页：results 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；时间为 UTC；scores/evidence 为 JSON 字符串透传。
 * 供 Admin 2.0 Result/Settlement 页经 /api/v1/admin/prediction/results 对接。
 */
class AdminPredictionResultDtoService extends Service
{
    public function __construct()
    {
        $this->dao = ResultDao::class;
        parent::__construct();
    }

    /**
     * 分页 Result 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{results:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $paginator = (new ResultDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['result_id', 'market_id', 'event_id', 'scores', 'outcome', 'status', 'confirmed_by', 'confirmed_at', 'dispute_reason_code', 'correction_version', 'rule_version', 'snapshot_id', 'object_version', 'created_time']
        );

        $results = [];
        foreach ($paginator->items() as $r) {
            $results[] = [
                'result_id'           => (string) $r->result_id,
                'market_id'           => (string) $r->market_id,
                'event_id'            => (string) $r->event_id,
                'scores'              => $r->scores !== null ? (string) $r->scores : null,
                'outcome'             => (string) $r->outcome,
                'status'              => (string) $r->status,
                'confirmed_by'        => $r->confirmed_by !== null ? (string) $r->confirmed_by : null,
                'confirmed_at'        => (int) $r->confirmed_at,
                'dispute_reason_code' => $r->dispute_reason_code !== null ? (string) $r->dispute_reason_code : null,
                'correction_version'  => (int) $r->correction_version,
                'rule_version'        => (string) $r->rule_version,
                'snapshot_id'         => (string) $r->snapshot_id,
                'object_version'      => (int) $r->object_version,
                'created_time'        => (int) $r->getRawOriginal('created_time'),
            ];
        }

        return [
            'results' => $results,
            'total'   => (int) $paginator->total(),
            'page'    => $page,
            'size'    => $size,
        ];
    }
}
