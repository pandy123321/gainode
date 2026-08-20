<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\otc\OtcOrderDao;
use support\extend\Service;

/**
 * Admin V2 OTC 订单列表 DTO 服务（A-OTC-001）。
 *
 * 只读全量分页：otc_orders 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 OTC 订单列表页经 /api/v1/admin/otc/orders 对接。
 */
class AdminOtcDtoService extends Service
{
    public function __construct()
    {
        $this->dao = OtcOrderDao::class;
        parent::__construct();
    }

    /**
     * 分页 OTC 订单列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 订单状态筛选（可选）
     * @return array{orders:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $paginator = (new OtcOrderDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['otc_order_id', 'user_id', 'side', 'price', 'quantity_apt', 'filled_quantity_apt', 'remaining_quantity_apt', 'fee_apt', 'power_required', 'power_frozen', 'status', 'rule_version', 'created_time']
        );

        $orders = [];
        foreach ($paginator->items() as $o) {
            $orders[] = [
                'otc_order_id'            => (string) $o->otc_order_id,
                'user_id'                 => (string) $o->user_id,
                'side'                    => (string) $o->side,
                'price'                   => (string) $o->price,
                'quantity_apt'            => (string) $o->quantity_apt,
                'filled_quantity_apt'     => (string) $o->filled_quantity_apt,
                'remaining_quantity_apt'  => (string) $o->remaining_quantity_apt,
                'fee_apt'                 => (string) $o->fee_apt,
                'power_required'          => (string) $o->power_required,
                'power_frozen'            => (string) $o->power_frozen,
                'status'                  => (string) $o->status,
                'rule_version'            => (string) $o->rule_version,
                'created_time'            => (int) $o->getRawOriginal('created_time'),
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
