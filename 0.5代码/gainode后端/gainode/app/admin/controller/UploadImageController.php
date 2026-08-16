<?php

namespace app\admin\controller;

use library\service\sys\UploadFilesService;
use library\validator\UploadImageValidation;
use support\exception\VerifyException;
use support\extend\Controller;
use support\controller\Admin;
use support\Request;
use support\Response;

/**
 * 上传管理
 */
class UploadImageController extends Admin
{

    public function __construct()
    {
        $this->service = new UploadFilesService();
        $this->validation = new UploadImageValidation();
        parent::__construct();
    }

    /**
     * 本地图片上传
     * @method POST
     * @url /admin/uploadImage/file
     * @return Response
     */
    public function file(Request $request)
    {
        $callback = $this->getPost('callback');
        try {
            $type = $this->getPost('type', 'items');
            $num = $this->getPost('num',0);
            $file = $request->file('file');
            if ($file && $file->isValid()){
                $result = $this->service->uploadFile($file, $type,$request->getUserID());
                if(empty($result)){
                    throw new VerifyException('上传数据失败');
                }
            }
            else{
                throw new VerifyException('上传文件不存在');
            }
            $result['num'] = $num;
            $result['type'] = $type;
            if(empty($callback)){
                return $this->json($result,'上传成功');
            }
            $json = json_encode(['success'=>true,'code'=>0,'data'=>$result,'msg'=>'success']);
        }
        catch (\Exception $e) {
            if(empty($callback)){
                return $this->failJson([],$e->getMessage(),$e->getCode(),400);
            }
            $json = json_encode(['success'=>false,'code'=>-1,'data'=>[],'msg'=>$e->getMessage()]);
        }
        if(!empty($callback)){
            return "<script type=\"text/javascript\">try{parent.".$callback."(" . $json . ");}catch(e){console.log(e)}</script>";
        }
        return $json;
    }

    /**
     * 根据地址上传图片
     * @method POST
     * @url /admin/uploadImage/file
     * @return Response
     */
    public function curl(Request $request)
    {
        $callback = $this->getPost('callback');
        try {
            $type = $this->getPost('type', 'items');
            $num = $this->getPost('num',0);
            $url = $this->getPost('url');
            if(empty($url)){
                throw new VerifyException('图片地址不能为空');
            }
            $result = $this->service->uploadCurlFile($url, $type,$request->getUserID());
            if(empty($result)){
                throw new VerifyException('上传数据失败');
            }
            $result['num'] = $num;
            $result['type'] = $type;
            if(empty($callback)){
                return $this->json($result,'上传成功');
            }
            $json = json_encode(['success'=>true,'code'=>0,'data'=>$result,'msg'=>'success']);
        }
        catch (\Exception $e) {
            if(empty($callback)){
                return $this->json([],$e->getMessage());
            }
            $json = json_encode(['success'=>false,'code'=>-2,'data'=>[],'msg'=>$e->getTraceAsString()]);
        }
        if(!empty($callback)){
            return "<script type=\"text/javascript\">try{parent.".$callback."(" . $json . ");}catch(e){console.log(e)}</script>";
        }
        return $json;
    }
}
