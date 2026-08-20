<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\prediction\RefundCaseDao;
use support\extend\Service;

/**
 * Admin V2 Refund/Correction 列表 DTO 服务（A-PREDICT-004）。
 *
 * 只读全量分页：refund_cases 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 Refund/Correction 页经 /api/v1/admin/prediction/refunds 对接。
 */
class AdminRefundDtoService extends Service
{
    public function __construct()
    {
        $this->dao = RefundCaseDao::class;
        parent::__construct();
    }

    /**
     * 分页 Refund Case 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{refunds:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new RefundCaseDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['refund_id', 'market_id', 'batch_size', 'principal_total_apt', 'service_fee_total_apt', 'status', 'approved_by', 'executed_at', 'reason_code', 'approval_id', 'rule_version', 'snapshot_id', 'object_version', 'created_time']
        );

        $refunds = [];
        foreach ($paginator->items() as $r) {
            $refunds[] = [
                'refund_id'             => (string) $r->refund_id,
                'market_id'             => (string) $r->market_id,
                'batch_size'            => (int) $r->batch_size,
                'principal_total_apt'    => (string) $r->principal_total_apt,
                'service_fee_total_apt'  => (string) $r->service_fee_total_apt,
                'status'                => (string) $r->status,
                'approved_by'           => $r->approved_by !== null ? (string) $r->approved_by : null,
                'executed_at'           => (int) $r->executed_at,
                'reason_code'           => (string) $r->reason_code,
                'approval_id'           => (string) $r->approval_id,
                'rule_version'          => (string) $r->rule_version,
                'snapshot_id'           => (string) $r->snapshot_id,
                'object_version'        => (int) $r->object_version,
                'created_time'          => (int) $r->getRawOriginal('created_time'),
            ];
        }

        return [
            'refunds' => $refunds,
            'total'   => (int) $paginator->total(),
            'page'    => $page,
            'size'    => $size,
        ];
    }
}
