<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\risk\RiskCaseDao;
use support\extend\Service;

/**
 * Admin V2 Risk Case 列表 DTO 服务（A-RISK-001）。
 *
 * 只读全量分页：risk_cases 全量 + 状态/severity 筛选。
 * 字段口径：仅返回已确认列；时间为 UTC。
 * 供 Admin 2.0 Risk Case 页经 /api/v1/admin/risk/cases 对接。
 */
class AdminRiskDtoService extends Service
{
    public function __construct()
    {
        $this->dao = RiskCaseDao::class;
        parent::__construct();
    }

    /**
     * 分页 Risk Case 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @param string $severity 严重等级筛选（可选）
     * @return array{cases:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = '', string $severity = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        if ($severity !== '') {
            $params['severity'] = $severity;
        }
        $paginator = (new RiskCaseDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['case_id', 'user_id', 'risk_type', 'severity', 'status', 'detected_at', 'detected_by', 'reviewed_by', 'disposition', 'appeal_eligible', 'object_version', 'created_time']
        );

        $cases = [];
        foreach ($paginator->items() as $c) {
            $cases[] = [
                'case_id'           => (string) $c->case_id,
                'user_id'           => (string) $c->user_id,
                'risk_type'         => (string) $c->risk_type,
                'severity'          => (string) $c->severity,
                'status'            => (string) $c->status,
                'detected_at'       => (int) $c->detected_at,
                'detected_by'       => (string) $c->detected_by,
                'reviewed_by'       => $c->reviewed_by !== null ? (string) $c->reviewed_by : null,
                'disposition'       => $c->disposition !== null ? (string) $c->disposition : null,
                'appeal_eligible'   => (int) $c->appeal_eligible,
                'object_version'    => (int) $c->object_version,
                'created_time'      => (int) $c->getRawOriginal('created_time'),
            ];
        }

        return [
            'cases' => $cases,
            'total' => (int) $paginator->total(),
            'page'  => $page,
            'size'  => $size,
        ];
    }
}
