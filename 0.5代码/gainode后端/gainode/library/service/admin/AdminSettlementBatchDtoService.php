<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\prediction\SettlementBatchDao;
use support\extend\Service;

/**
 * Admin V2 Settlement Batch 列表 DTO 服务（A-PREDICT-003 结算批）。
 *
 * 只读全量分页：settlement_batches 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 Result/Settlement 页经 /api/v1/admin/prediction/settlement-batches 对接。
 */
class AdminSettlementBatchDtoService extends Service
{
    public function __construct()
    {
        $this->dao = SettlementBatchDao::class;
        parent::__construct();
    }

    /**
     * 分页 Settlement Batch 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{batches:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new SettlementBatchDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['batch_id', 'status', 'market_count', 'order_count', 'total_principal_apt', 'total_reward_apt', 'total_service_fee_apt', 'executed_at', 'rule_version', 'object_version', 'created_time']
        );

        $batches = [];
        foreach ($paginator->items() as $b) {
            $batches[] = [
                'batch_id'              => (string) $b->batch_id,
                'status'                => (string) $b->status,
                'market_count'          => (int) $b->market_count,
                'order_count'           => (int) $b->order_count,
                'total_principal_apt'    => (string) $b->total_principal_apt,
                'total_reward_apt'       => (string) $b->total_reward_apt,
                'total_service_fee_apt'  => (string) $b->total_service_fee_apt,
                'executed_at'           => (int) $b->executed_at,
                'rule_version'          => (string) $b->rule_version,
                'object_version'        => (int) $b->object_version,
                'created_time'          => (int) $b->getRawOriginal('created_time'),
            ];
        }

        return [
            'batches' => $batches,
            'total'   => (int) $paginator->total(),
            'page'    => $page,
            'size'    => $size,
        ];
    }
}
