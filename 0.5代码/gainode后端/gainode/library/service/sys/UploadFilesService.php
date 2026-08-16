<?php

namespace library\service\sys;

use library\model\sys\UploadFilesModel;
use library\dao\sys\UploadFilesDao;
use support\exception\VerifyException;
use support\extend\Redis;
use support\extend\Service;
use support\upload\Upload as UploadFile;

/**
 * Service
 * @method UploadFilesModel create($data)
 * @method UploadFilesModel updateOrCreate(array $params,array $data)
 * @method UploadFilesModel update($id,array $data){
 * @method UploadFilesModel get($id,string $field = null)
 * @method UploadFilesModel find($id)
 * @method UploadFilesModel findOrFail($id)
 * @method UploadFilesModel firstOrCreate(array $params,array $data)
 * @method UploadFilesModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class UploadFilesService extends Service
{

    private $config;

    public function __construct($options=[])
    {
        $this->dao = UploadFilesDao::class;
        $config = config('app.upload');
        if(!empty($options)){
            $config = array_merge($config,$options);
        }
        $this->config = $config;
        parent::__construct();
    }

    /**
     * 获取文件地址
     * @param string $file_hash JSON数据
     * @param string $size 尺寸
     */
    public function getResourceUrl($file_hash,$size=null){
        $cache_key = 'upload_file';
        $file_url = Redis::hGet($cache_key,$file_hash);
        if(empty($file_url)){
            $uploadObj = $this->fetch(['file_hash'=>$file_hash]);
            if(!empty($uploadObj['file_url'])){
                $file_url = upload_url($uploadObj['file_url'],$size);
                Redis::hSet($cache_key,$file_hash,$file_url);
            }
        }
        return $file_url;
    }

    /**
     * 查看文件的hash
     * @param $file
     */
    private function checkFileExists($file,$user_id=0){
        if(is_string($file)){
            $fileHash = md5($file);
        }
        else{
            $fileHash = md5_file($file->getPathname());
        }
        $row = $this->fetch(['file_hash'=>$fileHash,'user_id'=>$user_id]);
        if(!empty($row)){
            return [
                "file_id"=>$row['file_id'],
                "file_url"=>$row->getFileUrl(),
                'file_hash'=>$row["file_hash"],
                'type'=>$row['from_type'],
                'cut'=>[]
            ];
        }
        return null;
    }

    /**
     * 获取裁切的图片尺寸
     * @param string $type 类型
     * @return array|string[]
     */
    protected function getImgSize($type=null) {
        switch($type) {
//            case 'backend_upload_article':
//                return array(1 => '640x200');
        }
        return [];
    }

    public function uploadBase64Content($content,$type='item',$user_id=0,$is_admin=0){
        if (!empty($content)){
            $res = $this->checkFileExists($content,$user_id);
            if(empty($res)){
                //{max_size,engine,ext_allow,upload_dir,upload_path,img_quality,water_image,file_path}
                $file_path = $this->config['file_path'] . $type;
                $uploadObj = new UploadFile([
                    'engine'=>$this->config['engine'],
                    'uploadPath'=>$this->config['upload_path'],
                    'rootPath'=>upload_path($this->config['upload_dir']),
                    'filePath'=>$file_path,
                    'allowType'=>$this->config['ext_allow'],
                    'maxSize'=>$this->config['max_size'],
                    'isRandName'=>true,
                    'imgQuality'=>$this->config['img_quality'],
                    'cutArray'=>$this->getImgSize($type),
                ]);
                $res = $uploadObj->uploadBase64Content($content,null,$this->config['water_image']);
                if($res){
                    return $this->saveUploadFile($uploadObj,$type,$user_id,$is_admin);
                }else{
                    throw new VerifyException($uploadObj->getErrorMsg());
                }
            }
            return $res;
        }
        else{
            throw new VerifyException('上传内容不存在');
        }
    }

    public function uploadFile(\Webman\Http\UploadFile $file,$type='item',$user_id=0,$is_admin=0)
    {
        if ($file && $file->isValid()){
            $res = $this->checkFileExists($file,$user_id);
            if(empty($res)){
                //{max_size,engine,ext_allow,upload_dir,upload_path,img_quality,water_image,file_path}
                $file_path = $this->config['file_path'] . $type;
                $uploadObj = new UploadFile([
                    'engine'=>$this->config['engine'],
                    'uploadPath'=>$this->config['upload_path'],
                    'rootPath'=>upload_path($this->config['upload_dir']),
                    'filePath'=>$file_path,
                    'allowType'=>$this->config['ext_allow'],
                    'maxSize'=>$this->config['max_size'],
                    'isRandName'=>true,
                    'imgQuality'=>$this->config['img_quality'],
                    'cutArray'=>$this->getImgSize($type),
                ]);
                $res = $uploadObj->uploadFile($file,null,$this->config['water_image']);
                if($res){
                    return $this->saveUploadFile($uploadObj,$type,$user_id,$is_admin);
                }else{
                    throw new VerifyException($uploadObj->getErrorMsg());
                }
            }
            return $res;
        }
        else{
            throw new VerifyException('上传文件不存在');
        }
    }

    /**
     * CURL上传需要缩略的图片
     * @param string $url 文件地址
     * @param string $type 文件类型
     */
    public function uploadCurlFile(string $url,$type='item',$user_id=0,$is_admin=0) {
        if (!empty($type) && !empty($url)) {
            $res = $this->checkFileExists($url,$user_id);
            if(empty($res)){
                $file_path = $this->config['file_path'] . $type;
                $uploadObj = new UploadFile([
                    'engine'=>$this->config['engine'],
                    'uploadPath'=>$this->config['upload_path'],
                    'rootPath'=>upload_path($this->config['upload_dir']),
                    'filePath'=>$file_path,
                    'allowType'=>$this->config['ext_allow'],
                    'maxSize'=>$this->config['max_size'],
                    'isRandName'=>true,
                    'imgQuality'=>$this->config['img_quality'],
                    'cutArray'=>$this->getImgSize($type),
                ]);
                $res = $uploadObj->uploadCurlFile($url,null,$this->config['water_image']);
                if($res){
                    return $this->saveUploadFile($uploadObj,$type,$user_id,$is_admin);
                }
                else{
                    throw new VerifyException($uploadObj->getErrorMsg());
                }
            }
            return $res;
        }
        else{
            throw new VerifyException('上传文件不存在');
        }
    }

    /**
     * 上传需要缩略的图片
     * @param UploadFile $uploadObj 上传对象
     * @param string $type 文件类型
     * @return {file_id,file_url,cut_name,cut_url}
     */
    private function saveUploadFile(UploadFile $uploadObj,string $type,int $user_id,$source='admin'){
        $data = array(
            'user_id'=>$user_id,
            'source'=>$source,
            'from_type'=>$type,
            'engine'=>$this->config['engine'],
            'file_name'=>$uploadObj->getNewFileName(),
            'file_url'=>$uploadObj->getUploadFileUrl(),
            "file_path"=>$uploadObj->getFilePath(),
            'file_ext'=>$uploadObj->getFileType(),
            'file_size'=>$uploadObj->getFileSize(),
            'origin_name'=>$uploadObj->getOriginName(),
            'width'=>$uploadObj->getImageWidth(),
            'height'=>$uploadObj->getImageHeight(),
            'file_hash'=>$uploadObj->getFileHash()
        );
        $res = $this->create($data);
        if(!empty($res)){
            if($res['file_name']!=$data['file_name']){
                unlink(public_path("uploads/".$data["file_path"]));
            }
            $cutAry = $uploadObj->getCutFileUrls();
            foreach($cutAry as $k=>$url){
                $cutAry[$k] = upload_url($url);
            }
            $result = [
                "file_id"=>$res['file_id'],
                "file_url"=>upload_url($res['file_url']),
                'file_hash'=>$res["file_hash"],
                'type'=>$type,
                'cut'=>$cutAry
            ];
            return $result;
        }
        else{
            return [];
        }
    }
}
