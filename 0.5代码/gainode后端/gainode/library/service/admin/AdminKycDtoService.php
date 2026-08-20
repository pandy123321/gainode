<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\kyc\KycCaseDao;
use support\extend\Service;

/**
 * Admin V2 KYC 审核队列 DTO 服务（A-KYC-001）。
 *
 * 只读全量分页：kyc_cases 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；时间为 UTC。
 * 供 Admin 2.0 KYC 审核队列页经 /api/v1/admin/admission/kyc 对接。
 */
class AdminKycDtoService extends Service
{
    public function __construct()
    {
        $this->dao = KycCaseDao::class;
        parent::__construct();
    }

    /**
     * 分页 KYC 案件列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{cases:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $paginator = (new KycCaseDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['case_id', 'user_id', 'kyc_level', 'status', 'submitted_at', 'reviewed_at', 'reviewed_by', 'reason_code', 'next_action', 'policy_version', 'rule_version', 'object_version', 'created_time']
        );

        $cases = [];
        foreach ($paginator->items() as $c) {
            $cases[] = [
                'case_id'        => (string) $c->case_id,
                'user_id'        => (string) $c->user_id,
                'kyc_level'      => (string) $c->kyc_level,
                'status'         => (string) $c->status,
                'submitted_at'   => (int) $c->submitted_at,
                'reviewed_at'    => (int) $c->reviewed_at,
                'reviewed_by'    => $c->reviewed_by !== null ? (string) $c->reviewed_by : null,
                'reason_code'    => $c->reason_code !== null ? (string) $c->reason_code : null,
                'next_action'    => $c->next_action !== null ? (string) $c->next_action : null,
                'policy_version' => (string) $c->policy_version,
                'rule_version'   => (string) $c->rule_version,
                'object_version' => (int) $c->object_version,
                'created_time'   => (int) $c->getRawOriginal('created_time'),
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
