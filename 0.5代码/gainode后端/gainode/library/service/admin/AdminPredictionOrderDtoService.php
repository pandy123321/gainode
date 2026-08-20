<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\prediction\PredictionOrderDao;
use support\extend\Service;

/**
 * Admin V2 Prediction Order 列表 DTO 服务（A-PREDICT-001 补充）。
 *
 * 只读全量分页：prediction_orders 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 Market/Event 页经 /api/v1/admin/prediction/orders 对接。
 */
class AdminPredictionOrderDtoService extends Service
{
    public function __construct()
    {
        $this->dao = PredictionOrderDao::class;
        parent::__construct();
    }

    /**
     * 分页 Prediction Order 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{orders:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['order_status'] = $status;
        }
        $paginator = (new PredictionOrderDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['order_id', 'user_id', 'market_id', 'selection', 'amount_apt', 'order_status', 'consent_receipt_id', 'submit_snapshot_id', 'parameter_release_id', 'policy_version', 'object_version', 'created_time']
        );

        $orders = [];
        foreach ($paginator->items() as $o) {
            $orders[] = [
                'order_id'            => (string) $o->order_id,
                'user_id'             => (string) $o->user_id,
                'market_id'           => (string) $o->market_id,
                'selection'           => (string) $o->selection,
                'amount_apt'          => (string) $o->amount_apt,
                'order_status'        => (string) $o->order_status,
                'consent_receipt_id'  => (string) $o->consent_receipt_id,
                'submit_snapshot_id'  => (string) $o->submit_snapshot_id,
                'parameter_release_id'=> (string) $o->parameter_release_id,
                'policy_version'      => (string) $o->policy_version,
                'object_version'      => (int) $o->object_version,
                'created_time'        => (int) $o->getRawOriginal('created_time'),
            ];
        }

        return [
            'orders' => $orders,
            'total'  => (int) $paginator->total(),
            'page'   => $page,
            'size'   => $size,
        ];
    }
}
