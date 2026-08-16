<?php

declare(strict_types=1);

namespace library\service\otc;

use support\extend\ProjectionService;
use library\dao\kyc\KycCaseDao;
use library\model\kyc\KycCaseModel;
use library\response\otc\OtcEligibilityResponse;

/**
 * OtcEligibility 投影服务（05 §3，非持久投影，每个请求/用户评估）。
 *
 * 读取顺序（默认 deny）：
 *   1. kyc_cases（2B-2 已建）→ KYC 状态可读，明确非 approved 可确定性 deny；
 *   2. OTC 参数（06：fee/limit/库存）→ TBC → 资格无法判定，默认 deny（MAINTENANCE）。
 *
 * reason_code 使用 05 §3 冻结七选一，不得覆盖 OtcOrder.status。
 * 越权访问返回 UNAVAILABLE 安全 reason。
 */
class OtcEligibilityProjectionService extends ProjectionService
{
    private KycCaseDao $kycCaseDao;

    public function __construct()
    {
        $this->kycCaseDao = new KycCaseDao();
    }

    /**
     * 计算 OTC 资格投影。
     *
     * @param string $viewerUserId 当前访问者
     * @param string $targetUserId 目标用户
     */
    public function getEligibility(string $viewerUserId, string $targetUserId): OtcEligibilityResponse
    {
        $response = new OtcEligibilityResponse();

        if ($viewerUserId !== $targetUserId) {
            $this->applyMetadata($response, $this->unavailableMetadata('projection.access_denied'));
            return $response;
        }

        // 读取 KYC 案件（最新一条）
        $kycCases = $this->kycCaseDao->getByUser($targetUserId);
        $kyc = $kycCases->sortByDesc('created_time')->first();

        $response->allowed = false;
        $response->buy_allowed = false;
        $response->sell_allowed = false;

        if ($kyc === null) {
            // 无 KYC 案件 → not_started → KYC_REQUIRED（确定性 deny，REALTIME）
            $response->reason_code = OtcEligibilityResponse::REASON_KYC_REQUIRED;
            $response->reason_text_key = 'otc.eligibility.kyc_required';
            $this->applyMetadata($response, $this->realtimeMetadata());
            return $response;
        }

        // KYC 明确非 approved → 确定性 deny（reason 见映射）
        $response->reason_code = $this->mapKycReason((string) $kyc->status);
        $response->reason_text_key = $this->mapKycReasonText((string) $kyc->status);
        $response->rule_version = $this->nullableString($kyc->rule_version);
        $response->policy_version = $this->nullableString($kyc->policy_version);

        // KYC 状态可读（REALTIME），但 OTC 参数 TBC → PARTIAL
        $this->applyMetadata($response, $this->realtimeMetadata($this->rawUnix($kyc, 'updated_time')));
        $response->source_status = self::SOURCE_STATUS_PARTIAL;

        return $response;
    }

    /**
     * KYC 状态 → OtcEligibility reason_code（05 §3 七选一）。
     *
     * approved 时 OTC 参数仍 TBC，无法判定资格 → MAINTENANCE（默认 deny）。
     */
    private function mapKycReason(string $status): string
    {
        switch ($status) {
            case KycCaseModel::STATUS_APPROVED:
                return OtcEligibilityResponse::REASON_MAINTENANCE;
            case KycCaseModel::STATUS_PENDING:
            case KycCaseModel::STATUS_REVIEW:
                return OtcEligibilityResponse::REASON_UNDER_REVIEW;
            case KycCaseModel::STATUS_NOT_STARTED:
            case KycCaseModel::STATUS_NEEDS_INFO:
            case KycCaseModel::STATUS_REJECTED:
            default:
                return OtcEligibilityResponse::REASON_KYC_REQUIRED;
        }
    }

    private function mapKycReasonText(string $status): string
    {
        switch ($status) {
            case KycCaseModel::STATUS_APPROVED:
                return 'otc.eligibility.maintenance';
            case KycCaseModel::STATUS_PENDING:
            case KycCaseModel::STATUS_REVIEW:
                return 'otc.eligibility.under_review';
            case KycCaseModel::STATUS_NOT_STARTED:
            case KycCaseModel::STATUS_NEEDS_INFO:
            case KycCaseModel::STATUS_REJECTED:
            default:
                return 'otc.eligibility.kyc_required';
        }
    }

    private function nullableString($value): ?string
    {
        return ($value !== null && $value !== '') ? (string) $value : null;
    }
}
