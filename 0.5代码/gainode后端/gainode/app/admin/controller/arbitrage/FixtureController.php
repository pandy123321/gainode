<?php

namespace app\admin\controller\arbitrage;

use library\service\arbitrage\FixtureService;
use library\validator\arbitrage\FixtureValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 比赛数据管理（API-Football 同步入库的比赛主数据）
 */
class FixtureController extends Api
{
    public function __construct()
    {
        $this->service = new FixtureService();
        $this->validation = new FixtureValidation();
        parent::__construct();
    }

    /**
     * 比赛列表
     * @param string $keyword 联赛/主队/客队模糊搜索
     * @param string $status 比赛状态: scheduled=待开赛 live=进行中 finished=已完赛
     * @param string $time 数据时间范围(YYYY-MM-DD - YYYY-MM-DD)
     * @method GET
     * @url /admin/arbitrage/fixture
     * @return Response
     */
    public function list(): Response
    {
        try {
            $params = $this->getAllRequest();
            $data = $this->service->paginateArray($params, ['kickoff_at' => 'desc']);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 比赛详情
     * @method GET
     * @url /admin/arbitrage/fixture/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $fixtureObj = $this->service->get($id);
            if (empty($fixtureObj)) {
                throw new VerifyException('执行失败');
            }
            return $this->json($fixtureObj->toArray());
        }
        catch (\Exception $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }
}
