<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\prediction\SettlementDao;
use support\extend\Service;

/**
 * Admin V2 Settlement 列表 DTO 服务（A-PREDICT-003 结算）。
 *
 * 只读全量分页：settlements 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 Result/Settlement 页经 /api/v1/admin/prediction/settlements 对接。
 */
class AdminSettlementDtoService extends Service
{
    public function __construct()
    {
        $this->dao = SettlementDao::class;
        parent::__construct();
    }

    /**
     * 分页 Settlement 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{settlements:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $paginator = (new SettlementDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['settlement_id', 'market_id', 'batch_id', 'status', 'principal_total_apt', 'reward_total_apt', 'service_fee_total_apt', 'ledger_batch_id', 'approved_by', 'executed_at', 'rule_version', 'parameter_release_id', 'snapshot_id', 'object_version', 'created_time']
        );

        $settlements = [];
        foreach ($paginator->items() as $s) {
            $settlements[] = [
                'settlement_id'         => (string) $s->settlement_id,
                'market_id'             => (string) $s->market_id,
                'batch_id'              => (string) $s->batch_id,
                'status'                => (string) $s->status,
                'principal_total_apt'    => (string) $s->principal_total_apt,
                'reward_total_apt'       => (string) $s->reward_total_apt,
                'service_fee_total_apt'  => (string) $s->service_fee_total_apt,
                'ledger_batch_id'        => (string) $s->ledger_batch_id,
                'approved_by'           => $s->approved_by !== null ? (string) $s->approved_by : null,
                'executed_at'           => (int) $s->executed_at,
                'rule_version'          => (string) $s->rule_version,
                'parameter_release_id'  => (string) $s->parameter_release_id,
                'snapshot_id'           => (string) $s->snapshot_id,
                'object_version'        => (int) $s->object_version,
                'created_time'          => (int) $s->getRawOriginal('created_time'),
            ];
        }

        return [
            'settlements' => $settlements,
            'total'       => (int) $paginator->total(),
            'page'        => $page,
            'size'        => $size,
        ];
    }
}
