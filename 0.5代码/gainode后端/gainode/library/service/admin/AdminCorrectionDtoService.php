<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\prediction\CorrectionCaseDao;
use support\extend\Service;

/**
 * Admin V2 Correction 列表 DTO 服务（A-PREDICT-004 更正）。
 *
 * 只读全量分页：correction_cases 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；时间为 UTC。
 * 供 Admin 2.0 Refund/Correction 页经 /api/v1/admin/prediction/corrections 对接。
 */
class AdminCorrectionDtoService extends Service
{
    public function __construct()
    {
        $this->dao = CorrectionCaseDao::class;
        parent::__construct();
    }

    /**
     * 分页 Correction Case 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{corrections:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new CorrectionCaseDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['correction_id', 'market_id', 'result_id_old', 'result_id_new', 'status', 'approved_by', 'executed_at', 'approval_id', 'rule_version', 'snapshot_id', 'object_version', 'created_time']
        );

        $corrections = [];
        foreach ($paginator->items() as $c) {
            $corrections[] = [
                'correction_id'  => (string) $c->correction_id,
                'market_id'      => (string) $c->market_id,
                'result_id_old'  => (string) $c->result_id_old,
                'result_id_new'  => (string) $c->result_id_new,
                'status'         => (string) $c->status,
                'approved_by'    => $c->approved_by !== null ? (string) $c->approved_by : null,
                'executed_at'    => (int) $c->executed_at,
                'approval_id'    => (string) $c->approval_id,
                'rule_version'   => (string) $c->rule_version,
                'snapshot_id'    => (string) $c->snapshot_id,
                'object_version' => (int) $c->object_version,
                'created_time'   => (int) $c->getRawOriginal('created_time'),
            ];
        }

        return [
            'corrections' => $corrections,
            'total'       => (int) $paginator->total(),
            'page'        => $page,
            'size'        => $size,
        ];
    }
}
