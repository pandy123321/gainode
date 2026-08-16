<?php

namespace app\admin\controller\sys;

use library\service\sys\RouteService;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 路由地址管理
 */
class RouteController extends Admin
{
    public function __construct()
    {
        $this->service = new RouteService();
        parent::__construct();
    }

    /**
     * 获取所有路由地址
     * @param string $url 路由地址
     * @param string $descr 描述
     * @method GET
     * @url /admin/sys/routeAll
     * @return Response
     */
    public function all(): Response
    {
        try {
            $params = [
                'module' => 'admin'
            ];
            $params['url'] = $this->getParams('url');
            $params['descr'] = $this->getParams('descr');
            $data = $this->service->getSelectList($params);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 路由地址列表
     * @param string $controller 控制器
     * @param string $descr 描述
     * @param string $url 路由地址
     * @method GET
     * @url /admin/sys/route
     * @return Response
     */
    public function list(): Response
    {
        try {
            $params = $this->getAllRequest();
            if(!empty($params['descr'])){
                $params['descr'] = ['like',$params['descr']];
            }
            if(!empty($params['url'])){
                $params['url'] = ['like',$params['url']];
            }
            $data = $this->service->paginateArray($params);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 路由地址详情
     * @method GET
     * @url /admin/sys/route/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $routeObj = $this->service->get($id);
            if(empty($routeObj)){
                throw new VerifyException('执行失败');
            }
            $data = $routeObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
