<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\prediction\PredictionMarketDao;
use support\extend\Service;

/**
 * Admin V2 Market 列表 DTO 服务（A-PREDICT-001）。
 *
 * 只读全量分页：prediction_markets 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；时间为 UTC。
 * 供 Admin 2.0 Market/Event 列表页经 /api/v1/admin/prediction/markets 对接。
 */
class AdminPredictionDtoService extends Service
{
    public function __construct()
    {
        $this->dao = PredictionMarketDao::class;
        parent::__construct();
    }

    /**
     * 分页 Market 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 市场状态筛选（可选）
     * @return array{markets:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['market_status'] = $status;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new PredictionMarketDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['market_id', 'event_id', 'template_id', 'market_status', 'lock_at', 'selections', 'result_status', 'rule_version', 'parameter_release_id', 'object_version', 'created_time']
        );

        $markets = [];
        foreach ($paginator->items() as $m) {
            $markets[] = [
                'market_id'            => (string) $m->market_id,
                'event_id'             => (string) $m->event_id,
                'template_id'          => (string) $m->template_id,
                'market_status'        => (string) $m->market_status,
                'lock_at'              => (int) $m->lock_at,
                'selections'           => $m->selections !== null ? (string) $m->selections : null,
                'result_status'        => $m->result_status !== null ? (string) $m->result_status : null,
                'rule_version'         => (string) $m->rule_version,
                'parameter_release_id' => (string) $m->parameter_release_id,
                'object_version'       => (int) $m->object_version,
                'created_time'         => (int) $m->getRawOriginal('created_time'),
            ];
        }

        return [
            'markets' => $markets,
            'total'   => (int) $paginator->total(),
            'page'    => $page,
            'size'    => $size,
        ];
    }
}
