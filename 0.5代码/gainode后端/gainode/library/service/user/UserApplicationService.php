<?php

declare(strict_types=1);

namespace library\service\user;

use library\dict\ErrorDict;
use library\model\member\UserModel;
use library\service\auth\LoginAuditProjectionService;
use library\service\auth\SecurityProfileProjectionService;
use support\exception\DomainException;

/**
 * 用户侧读应用服务（S02-P02 子流程 5/6：me / security-profile / login-audit）。
 *
 * 全为投影/读取，遵循数据新鲜度契约与 fail-closed：
 * V1.x member_user 未含 V2 User 新增字段（locale/timezone/global_p_level/*_eligibility），
 * 一律返回 null（不编造、不 mock）；资格聚合由 EligibilityApplicationService 单独提供。
 *
 * V1.x status（1:可用/0:停用/-1:删除）映射到 V2 User.status（active/suspended/closed）；
 * restricted 态依赖 is_frozen_withdraw，V1.x 未在 User.status 中区分 → Contract Gap，暂不回退。
 */
class UserApplicationService
{
    public function me(string $userId): array
    {
        $user = UserModel::find($userId);
        if (empty($user)) {
            throw new DomainException(ErrorDict::AUTH_UNAUTHENTICATED);
        }
        return [
            'user_id'                => (string) $user->id,
            'status'                 => $this->mapStatus((int) $user->status),
            'display_name'           => (string) ($user->nickname ?: $user->account),
            'locale'                 => null,   // V1.x 未冻结字段，fail-closed
            'timezone'               => null,
            'global_p_level'         => null,   // 06 参数未冻结 → fail-closed
            'ai_reward_eligibility'  => null,
            'prediction_eligibility' => null,
            'created_at'             => $this->rawUnix($user, 'created_time'),
            'updated_at'             => $this->rawUnix($user, 'updated_time'),
        ];
    }

    public function securityProfile(string $viewerUserId, string $targetUserId): array
    {
        $service = new SecurityProfileProjectionService();
        return $service->getProfile($viewerUserId, $targetUserId)->toArray();
    }

    public function loginAudit(string $viewerUserId, string $targetUserId): array
    {
        $service = new LoginAuditProjectionService();
        return $service->getAudit($viewerUserId, $targetUserId)->toArray();
    }

    private function mapStatus(int $status): string
    {
        return match ($status) {
            1       => 'active',
            0       => 'suspended',
            -1      => 'closed',
            default => 'suspended',
        };
    }

    private function rawUnix($model, string $field): ?int
    {
        $raw = $model->getRawOriginal($field);
        if ($raw === null || $raw === '') {
            return null;
        }
        if ($raw instanceof \DateTimeInterface) {
            return $raw->getTimestamp();
        }
        return (int) $raw;
    }
}
