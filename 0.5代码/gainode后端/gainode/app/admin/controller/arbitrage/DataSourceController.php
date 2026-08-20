<?php

namespace app\admin\controller\arbitrage;

use library\service\arbitrage\DataSourceService;
use support\controller\Api;
use support\Response;

/**
 * 数据源管理（BetBurger / API-Football 凭证配置与健康测试）
 */
class DataSourceController extends Api
{
    private DataSourceService $sourceService;

    public function __construct()
    {
        $this->sourceService = new DataSourceService();
        parent::__construct();
    }

    /**
     * 数据源列表（含字段元数据 + 配置/健康状态 + 同步统计）
     * @method GET
     * @url /admin/arbitrage/datasource
     * @return Response
     */
    public function list(): Response
    {
        try {
            $data = $this->sourceService->listSources();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 保存数据源凭证（仅更新已提供的字段）
     * @param string $code 数据源编码: api_football|betburger
     * @param array  $fields 健值对 field_code => value
     * @method POST
     * @url /admin/arbitrage/datasource/save
     * @return Response
     */
    public function save(): Response
    {
        try {
            $code = (string) $this->getPost('code');
            $fields = $this->getPost('fields');
            $fields = is_array($fields) ? $fields : [];
            $data = $this->sourceService->saveSource($code, $fields);
            return $this->json($data, '保存成功');
        }
        catch (\Exception $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 凭证健康测试（发一次轻量请求验证连通性）
     * @param string $code 数据源编码: api_football|betburger
     * @param array  $fields 可选覆盖值（测试未保存的新凭证）
     * @method POST
     * @url /admin/arbitrage/datasource/test
     * @return Response
     */
    public function test(): Response
    {
        try {
            $code = (string) $this->getPost('code');
            $fields = $this->getPost('fields');
            $fields = is_array($fields) ? $fields : [];
            $data = $this->sourceService->testSource($code, $fields);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }
}
