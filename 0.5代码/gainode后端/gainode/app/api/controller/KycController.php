<?php

declare(strict_types=1);

namespace app\api\controller;

use library\service\kyc\KycApplicationService;
use library\validator\KycValidation;
use support\controller\ApiV2;
use support\Response;

/**
 * KYC 控制器（05 §3/§4）。
 *
 * C 端仅暴露提交 + 查询；admin 审核（approve/reject/needs_info）由领域服务提供，
 * 本阶段不在 C 端控制器暴露。
 */
class KycController extends ApiV2
{
    /** GET /api/v1/me/kyc */
    public function get(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new KycApplicationService())->get($userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/me/kyc/submit */
    public function submit(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $data = $this->getPost();
            $this->validate('submit', $data, new KycValidation());
            $result = (new KycApplicationService())->submit($userId, $data);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
