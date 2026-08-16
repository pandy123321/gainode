<?php

declare(strict_types=1);

namespace library\service\kyc;

use library\dao\kyc\KycCaseDao;
use library\model\kyc\KycCaseModel;
use support\extend\Service;
use support\utils\Random;
use support\exception\DomainException;
use library\dict\ErrorDict;

/**
 * KYC 案件 Service — kyc_cases 表唯一 Authoritative Writer（S02-P02 状态转移落地）
 *
 * @authoritative_writer kyc_cases
 *
 * 状态机（05 §4 canonical）：not_started / pending / needs_info / approved / rejected / review
 *   submit        → pending（not_started|needs_info|rejected 可提交）
 *   startReview   → review（pending）
 *   requestInfo   → needs_info（review）
 *   resubmit      → pending（needs_info）
 *   approve       → approved（review）
 *   reject        → rejected（review）
 *
 * 转移矩阵属 2B-2 CANDIDATE。本包按 07 §S02-P02 已列转移实现；未列转移 FAIL_CLOSED。
 * 申请人不得审批本人（05 §11.1）；reviewed_by 记录 KYC_REVIEWER。
 */
class KycCaseService extends Service
{
    public function __construct()
    {
        $this->dao = KycCaseDao::class;
        parent::__construct();
    }

    /**
     * 提交/重新提交 KYC → pending。
     *
     * @param string[] $attachmentRefs 后端签发的附件对象引用
     * @return KycCaseModel
     */
    public function submit(
        string $userId,
        string $kycLevel,
        array $attachmentRefs,
        string $policyVersion,
        string $ruleVersion,
        string $idempotencyKey,
        string $auditEventId
    ): KycCaseModel {
        if ($attachmentRefs === []) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'KYC 附件不能为空');
        }

        $case = $this->getByUser($userId)->first();

        if (empty($case)) {
            $data = [
                'case_id'         => (string) Random::getSnowflakeID(),
                'user_id'         => $userId,
                'kyc_level'       => $kycLevel,
                'status'          => KycCaseModel::STATUS_PENDING,
                'submitted_at'    => time(),
                'reviewed_at'     => 0,
                'reviewed_by'     => '',
                'reason_code'     => '',
                'reason_text_key' => '',
                'next_action'     => '',
                'policy_version'  => $policyVersion,
                'rule_version'    => $ruleVersion,
                'object_version'  => 0,
                'idempotency_key' => $idempotencyKey,
                'audit_event_id'  => $auditEventId,
            ];
            return $this->create($data);
        }

        $allowed = [
            KycCaseModel::STATUS_NOT_STARTED,
            KycCaseModel::STATUS_NEEDS_INFO,
            KycCaseModel::STATUS_REJECTED,
        ];
        if (!in_array($case->status, $allowed, true)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'KYC 当前状态不可提交');
        }

        $case->update([
            'kyc_level'       => $kycLevel,
            'status'          => KycCaseModel::STATUS_PENDING,
            'submitted_at'    => time(),
            'reviewed_at'     => 0,
            'reviewed_by'     => '',
            'reason_code'     => '',
            'reason_text_key' => '',
            'next_action'     => '',
            'policy_version'  => $policyVersion,
            'rule_version'    => $ruleVersion,
        ]);
        return $case;
    }

    /**
     * 进入人工复核（pending → review）。
     */
    public function startReview(string $caseId, string $reviewerUserId): KycCaseModel
    {
        $case = $this->assertCase($caseId);
        $this->assertNotSelf($case, $reviewerUserId);
        if ($case->status !== KycCaseModel::STATUS_PENDING) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'KYC 当前状态不可进入复核');
        }
        $case->update([
            'status'      => KycCaseModel::STATUS_REVIEW,
            'reviewed_by' => $reviewerUserId,
        ]);
        return $case;
    }

    /**
     * 要求补充材料（review → needs_info）。
     */
    public function requestInfo(string $caseId, string $reviewerUserId, string $reasonCode, string $reasonTextKey): KycCaseModel
    {
        $case = $this->assertCase($caseId);
        $this->assertNotSelf($case, $reviewerUserId);
        if ($case->status !== KycCaseModel::STATUS_REVIEW) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'KYC 当前状态不可要求补充');
        }
        $case->update([
            'status'          => KycCaseModel::STATUS_NEEDS_INFO,
            'reviewed_by'     => $reviewerUserId,
            'reason_code'     => $reasonCode,
            'reason_text_key' => $reasonTextKey,
            'next_action'     => 'resubmit',
            'reviewed_at'     => time(),
        ]);
        return $case;
    }

    /**
     * 通过（review → approved）。
     */
    public function approve(string $caseId, string $reviewerUserId): KycCaseModel
    {
        $case = $this->assertCase($caseId);
        $this->assertNotSelf($case, $reviewerUserId);
        if ($case->status !== KycCaseModel::STATUS_REVIEW) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'KYC 当前状态不可通过');
        }
        $case->update([
            'status'          => KycCaseModel::STATUS_APPROVED,
            'reviewed_by'     => $reviewerUserId,
            'reason_code'     => '',
            'reason_text_key' => '',
            'next_action'     => '',
            'reviewed_at'     => time(),
        ]);
        return $case;
    }

    /**
     * 驳回（review → rejected）。
     */
    public function reject(string $caseId, string $reviewerUserId, string $reasonCode, string $reasonTextKey): KycCaseModel
    {
        $case = $this->assertCase($caseId);
        $this->assertNotSelf($case, $reviewerUserId);
        if ($case->status !== KycCaseModel::STATUS_REVIEW) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'KYC 当前状态不可驳回');
        }
        $case->update([
            'status'          => KycCaseModel::STATUS_REJECTED,
            'reviewed_by'     => $reviewerUserId,
            'reason_code'     => $reasonCode,
            'reason_text_key' => $reasonTextKey,
            'next_action'     => '',
            'reviewed_at'     => time(),
        ]);
        return $case;
    }

    /**
     * 按用户查询 KYC 案件（只读透传）。
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    private function assertCase(string $caseId): KycCaseModel
    {
        $case = $this->get($caseId);
        if (empty($case)) {
            throw new DomainException(ErrorDict::AUTH_FORBIDDEN);
        }
        return $case;
    }

    /**
     * 申请人不得审批本人（05 §11.1）。
     */
    private function assertNotSelf(KycCaseModel $case, string $reviewerUserId): void
    {
        if ($case->user_id === $reviewerUserId) {
            throw new DomainException(ErrorDict::AUTH_FORBIDDEN, '申请人不得审批本人');
        }
    }
}
