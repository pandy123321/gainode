<?php

namespace app\admin\controller\member;

use library\service\member\RedPacketItemService;
use library\service\member\RedPacketService;
use library\validator\member\RedPacketValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 红包管理
 */
class RedPacketController extends Api
{
    public function __construct()
    {
        $this->service = new RedPacketService();
        $this->validation = new RedPacketValidation();
        parent::__construct();
    }

    /**
     * 红包列表
     * @method GET
     * @url /admin/member/redPacket
     * @return Response
     */
    public function list(): Response
    {
        try {
            $params = $this->getAllRequest();
            $data = $this->service->paginateArray($params);
            $packetItemService = new RedPacketItemService();
            foreach($data['data'] as $k=>$v){
                $data['data'][$k]['items'] = $packetItemService->getPacketItems($v['id']);
            }
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 红包详情
     * @method GET
     * @url /admin/member/redPacket/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $redPacketObj = $this->service->get($id);
            if(empty($redPacketObj)){
                throw new VerifyException('执行失败');
            }
            $data = $redPacketObj->toArray();
            $packetItemService = new RedPacketItemService();
            $data['items'] = $packetItemService->getPacketItems($redPacketObj['id']);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加红包
     * @method POST
     * @url /admin/member/redPacket
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $post['admin_id'] = $this->request->getUserID();
            $redPacketObj = $this->service->createPacketData($post);
            if(empty($redPacketObj)){
                throw new VerifyException('执行失败');
            }
            $data = $redPacketObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改红包
     * @method PUT
     * @url /admin/member/redPacket/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $redPacketObj = $this->service->update($id,$post);
            if(empty($redPacketObj)){
                throw new VerifyException('执行失败');
            }
            $data = $redPacketObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 删除红包
     * @method DELETE
     * @url /admin/member/redPacket/{id}
     * @return Response
     */
    public function delete(int $id): Response
    {
        try {
            $res = $this->service->delete($id);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'删除成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
