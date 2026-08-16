<?php

declare(strict_types=1);

namespace library\service\kyc;

use library\service\member\UserKycService;

/**
 * KYC 应用服务（S02-P02 子流程 4：submit→under_review→needs_info→resubmit→approve/reject）。
 *
 * 桥接：V2 KycCaseService（状态转移，kyc_cases）与 V1.x UserKycService（字段存档）。
 * C 端仅暴露 submit + 查询；admin 审核端点（approve/reject/needs_info）由领域服务提供，
 * 控制器侧在 Admin 流程（S02-P07 之前）不暴露。
 */
class KycApplicationService
{
    /**
     * 提交/重新提交 KYC → pending。
     */
    public function submit(string $userId, array $data): array
    {
        $service = new KycCaseService();
        $case = $service->submit(
            $userId,
            $data['kyc_level'] ?? '',
            $data['attachment_refs'] ?? [],
            $data['policy_version'] ?? '',
            $data['rule_version'] ?? '',
            $this->idempotencyKey(),
            ''
        );

        // 桥接 V1.x 字段存档（KEEP，不继承其旧状态语义；V2 状态以 kyc_cases 为准）
        $this->syncV1Kyc($userId, $data);

        return $this->toArray($case);
    }

    /**
     * 查询当前用户 KYC 案件。
     */
    public function get(string $userId): array
    {
        $service = new KycCaseService();
        $case = $service->getByUser($userId)->first();
        if (empty($case)) {
            return [
                'case_id'   => '',
                'user_id'   => $userId,
                'kyc_level' => '',
                'status'    => 'not_started',
            ];
        }
        return $this->toArray($case);
    }

    /**
     * 桥接 V1.x UserKycService 字段存档（仅存档，不影响 V2 状态）。
     */
    private function syncV1Kyc(string $userId, array $data): void
    {
        try {
            $kycService = new UserKycService();
            $kycService->saveUserKycData($userId, [
                'kyc_level' => $data['kyc_level'] ?? '',
            ]);
        } catch (\Throwable $e) {
            // V1.x 存档失败不回滚 V2 状态（桥接尽力而为）
        }
    }

    private function toArray($case): array
    {
        return [
            'case_id'         => (string) $case->case_id,
            'user_id'         => (string) $case->user_id,
            'kyc_level'       => (string) $case->kyc_level,
            'status'          => (string) $case->status,
            'submitted_at'    => (int) $case->submitted_at,
            'reviewed_at'     => (int) $case->reviewed_at,
            'reviewed_by'     => (string) $case->reviewed_by,
            'reason_code'     => (string) $case->reason_code,
            'reason_text_key' => (string) $case->reason_text_key,
            'next_action'     => (string) $case->next_action,
            'policy_version'  => (string) $case->policy_version,
            'rule_version'    => (string) $case->rule_version,
        ];
    }

    private function idempotencyKey(): string
    {
        return \support\middleware\RequestContext::getContext()['idempotency_key'] ?? '';
    }
}
