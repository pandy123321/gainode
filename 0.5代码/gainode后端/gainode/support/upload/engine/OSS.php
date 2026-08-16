<?php

namespace support\upload\engine;
use library\logic\DictLogic;
use library\service\sys\DictListService;
use OSS\Core\OssException;
use OSS\OssClient;
use support\Container;
use support\exception\VerifyException;

/**
 * 阿里云存储文件操作类
 * @cut URL+?imageMogr2/gravity/NorthWest/crop/600x600
 */
class OSS {

    private static $instance = null;

    private static $config = [
        'key' => '',
        'secret' => '',
        'endpoint_in' => 'https://oss-cn-hangzhou-internal.aliyuncs.com',
        'endpoint_out' => 'https://oss-cn-hangzhou.aliyuncs.com',
        'bucket' => ''
    ];

    private $uploadClient = null;

    public $bucket = null;

    private function __construct(){
        $dictLogic = Container::get(DictLogic::class);
        $configs = $dictLogic->getDictConfigs('oss');
        if(!empty($configs)){
            self::$config['key'] = $configs['key'];
            self::$config['secret'] = $configs['secret'];
            self::$config['endpoint_in'] = $configs['endpoint_in'];
            self::$config['endpoint_out'] = $configs['endpoint_out'];
            self::$config['bucket'] = $configs['bucket'];
        }
    }
    private function __clone(){}

    /**
     * 获取对象实例
     * @return $this
     */
    public static function getInstance(array $configs=[]){
        if(empty(self::$instance)){
            self::$config = array_merge(self::$config, $configs);
            self::$instance = new OSS();
            self::$instance->uploadClient = new OssClient(
                self::$config['key'],
                self::$config['secret'],
                self::$config['endpoint_out'],
            );
            self::$instance->bucket = self::$config['bucket'];
        }
        return self::$instance;
    }

    /**
     * 获取云存储的对象
     * @return OssClient
     */
    public function getUploadClient(){
        return $this->uploadClient;
    }

    /**
     * 保存文件名增加其他信息
     * @param string $fileName 文件名
     * @param string  $extra 额外信息
     * @return string
     */
    public function setFileExtName($fileName,$extra='') {
        if ($extra) {
            $ext = $this->getFileExt($fileName);
            $fileName = str_replace($ext, '_' . $extra . $ext, $fileName);
        }
        return $fileName;
    }

    /**
     * 获取文件扩展名
     * @param string $fileName
     * @return string $ext
     */
    public function getFileExt($fileName) {
        return substr($fileName, strrpos($fileName, '.'));
    }

    /**
     * 获取文件列表
     * @param $PrefixFileName
     * @param $limit
     * @return \OSS\Model\ObjectInfo
     */
    public function listObjects($prefix,$limit=10){
        $options = array(
            'delimiter' => '/',
            'prefix' => $prefix,
            'max-keys' => $limit,
            'marker' => '',
        );
        $result = $this->getUploadClient()->listObjects($this->bucket, $options);
        return $result->getObjectList();
    }

    /**
     * 删除文件
     * @param filename
     * @return bool
     */
    public function deleteObject($filename){
        try {
            $result = $this->getUploadClient()->deleteObject($this->bucket, $filename);
            return !empty($result['oss-request-url'])?true:false;
        }
        catch (OssException $e) {
            throw new VerifyException($e->getMessage());
        }

    }

    /**
     * 上传文件
     * @param string $localFilePath 本地文件
     * @param string $filename oss键
     * @return boolean
     */
    public function putObject($localFilePath,$filename,$options=null){
        try {
            if(!empty($filename)){
                $filename = ltrim($filename,'/');
            }
            $result = $this->getUploadClient()->uploadFile($this->bucket,$filename, $localFilePath,$options);
            return str_replace('http://','https://',$result['oss-request-url']);
        }
        catch (OssException $e) {
            throw new VerifyException($e->getMessage());
        }
    }

    /**
     * 字符串上传
     * @param string $contents 内容
     * @param string $filename oss键
     * @return string
     */
    public function putObjectContent($contents,$filename,$options=null){
        try {
            // 上传时可以设置相关的headers，例如设置访问权限为private、自定义元信息等。
//            $options = [
//                OssClient::OSS_HEADERS => [
//                    'x-oss-object-acl' => 'private',
//                    'x-oss-meta-info' => 'your info'
//                ],
//            ];
            if(!empty($filename)){
                $filename = ltrim($filename,'/');
            }
            $result =  $this->getUploadClient()->putObject($this->bucket,$filename, $contents,$options);
            return str_replace('http://','https://',$result['oss-request-url']);
        }
        catch (OssException $e) {
            throw new VerifyException($e->getMessage());
        }
    }

    /**
     * @param string $contents 内容
     * @param string $filename oss键
     * @param int $position 位置
     * @param null $options
     * @return int
     * @throws \Exception
     */
    public function appendObject($localFilePath,$filename,$position=0,$options=null){
        try{
            if(!empty($filename)){
                $filename = ltrim($filename,'/');
            }
            return $this->getUploadClient()->appendFile($this->bucket,$filename, $localFilePath,$position,$options);
        }
        catch (OssException $e) {
            throw new VerifyException($e->getMessage());
        }
    }

    /**
     * @param string $contents 内容
     * @param string $filename oss键
     * @param int $position 位置
     * @param null $options
     * @return int
     * @throws \Exception
     */
    public function appendObjectContent($contents,$filename,$position=0,$options=null){
        try{
            if(!empty($filename)){
                $filename = ltrim($filename,'/');
            }
//            $options = [
//                'headers' => [
            // 指定该Object被下载时的网页缓存行为。
            // 'Cache-Control' => 'no-cache',
            // 指定该Object被下载时的名称。
            // 'Content-Disposition' => 'attachment;filename=oss_download.jpg',
            // 指定该Object的内容编码格式。
            // 'Content-Encoding' => 'utf-8',
            // 指定过期时间。
            // 'Expires' => 'Fri, 31 Dec 2021 16:57:01 GMT',
            // 指定追加上传时是否覆盖同名Object。此处设置为true，表示禁止覆盖同名Object。
            // 'x-oss-forbid-overwrite' => 'true',
            // 指定上传该Object的每个part时使用的服务器端加密方式。
            // 'x-oss-server-side-encryption'=> 'AES256',
            // 指定Object的加密算法。
            // 'x-oss-server-side-data-encryption'=>'SM4',
            // 指定Object的存储类型。
            // 'x-oss-storage-class' => 'Standard',
            // 指定Object的访问权限。
            // 'x-oss-object-acl' => 'private',
//                ],
//            ];
            return $this->getUploadClient()->appendObject($this->bucket,$filename, $contents,$position,$options);
        }
        catch (OssException $e) {
            throw new VerifyException($e->getMessage());
        }
    }
}
