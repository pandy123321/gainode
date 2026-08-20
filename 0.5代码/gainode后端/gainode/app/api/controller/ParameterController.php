<?php

declare(strict_types=1);

namespace app\api\controller;

use library\service\parameter\ParameterReleaseService;
use library\service\parameter\ParameterSnapshotService;
use support\controller\ApiV2;
use support\Response;

/**
 * Policy / Parameter 只读控制器（05 §6；S02-P07 骨架）。
 *
 * 参数生命周期（Definition→Candidate→Approved→Active）由内部 Authoritative Writer +
 * Admin 角色驱动，本控制器只暴露只读投影（active release / snapshot detail）。
 * 写路径（candidate create / release create / activate）不在 C 端暴露。
 */
class ParameterController extends ApiV2
{
    /** GET /api/v1/parameter-releases (active) — 只读投影 */
    public function activeRelease(): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new ParameterReleaseService())->getActive();
            if (empty($result)) {
                return $this->envelope([]);
            }
            return $this->envelope((new ParameterReleaseService())->detail((string) $result->release_id));
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/parameters/snapshots/{id} */
    public function snapshot(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new ParameterSnapshotService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
