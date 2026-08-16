<?php

namespace support\upload;

use support\utils\Files;
use support\extend\Log;

/**
 * 文件上传类
 * 例:
  $upload = new \App\Upload\Upload([
  'rootPath'=>'/home/images',
  'filePath'=>'/shop',
  'isRandName'=>false,
  'cutArray'=>[1=>'500X0',2=>'200X0']
  ]);
  $a = $upload->uploadFile('pic');
  $url = 'http://hiphotos.baidu.com/lvpics/pic/item/21a4462309f79052efaab6cd0ef3d7ca7acbd588.jpg';
  $b = $upload->uploadCurlFile($url);
  print_r($upload->getCutFileNames());
 */
class Upload {

    private $uploadPath = '';   //域名访问地址
    private $uploadFileUrl = '';    //上传后的完整文件地址
    private $rootPath = '';   //设置文件根目录
    private $filePath;   //设置上传文件的路径
    private $cutArray = [];    //需要裁切的数据 [1 =>'240X0']
    private $cutFileNameArray = [];    //裁切后的文件名称
    private $allowType = ['jpg', 'jpeg', 'gif', 'bmp', 'png']; //默认的文件的类型
    private $maxSize = 51200000;   //默认文件的大小
    private $imgQuality = 100;     //生成图片的质量
    private $isRandName = true;  //是否使用随机文件名
    private $curlFileUrl;  //远程的文件路径
    private $newFileName;  //新文件的名称
    private $originName;   //源文件名称
    private $tmpFileName;  //临时文件名
    private $fileType;    //文件类型
    private $fileSize;   //文件的大小
    private $errorNum;  //错误号
    private $fileHash;  //文件md5
    private $image_width = 0;
    private $image_height = 0;
    private $engine = 'local';


    private $errorMsg = ""; //用来提示错误报告
    private $Orientation = '';

    /**
     * 上传类构造函数
     * @param $options {rootPath,filePath,cutArray,allowType,maxSize,imgQuality,isRandName}
     */

    public function __construct($options = []) {
        foreach ($options as $key => $val) {
            //获取当前类的所有的属性
            if (!in_array($key, get_class_vars(get_class($this)))) {
                continue;
            } else {
                $this->setOption($key, $val);
            }
        }
    }

    /**
     * 定义不同的错误级别
     * @return type
     */
    public function getError() {
        $str = "上传文件{$this->originName}时出错:";
        switch ($this->errorNum) {
            case 5:
                $str.="文件不存在";
                break;
            case 4:
                $str.="文件没有被上传";
                break;
            case 3:
                $str.="文件只有部分上传";
                break;
            case 2:
                $str.="上传文件超过了HTML表单中规定的MAX_FILE_SIZE选项的值";
                break;
            case 1:
                $str.="上传文件吵过了php.ini中upload_max_filesize选项的值";
                break;
            case -1:
                $str.="未允许的类型";
                break;
            case -2:
                $str.="上传文件过大，不能超过{$this->maxSize}个字节";
                break;
            case -3:
                $str.="上传失败";
                break;
            case -4:
                $str.="建立存放上传目录失败，请重新指定上传目录";
                break;
            case -5:
                $str.="必须指定上传文件的路径";
                break;
            case -6:
                $str.="有不存在的属性";
            case -7:
                $str.="上传驱动不存在";
            default:
                $str.="未知的错误";
        }
        return $str;
    }

    /**
     * 为成员属性赋值的函数
     * @param string $key
     * @param string $val
     */
    private function setOption(string $key,$val) {
        try {
            $this->$key = $val;
        } catch (\Exception $e) {
            $this->setOption('errorNum', -6);
        }
    }

    /**
     * 检查上传文件的路径
     * @return boolean
     */
    private function checkFilePath() {
        //如果文件路径为空
        $filePath = $this->getFilePath(true);
        if (empty($filePath)) {
            $this->setOption('errorNum', -5);
            return false;
        }
        //判断路径是否存在并且是否可写
        if (!file_exists($filePath) || !is_writable($filePath)) {
            //@是错误抑制符  @ 是忽略错误提示,使其错
            //误消息不会显示在程序里
            if (!@mkdir($filePath, 0755, true)) {
                $this->setOption('errorNum', -4);
                return false;
            }
        }
        return true;
    }

    /**
     * 检查文件大小的函数
     * @return boolean
     */
    private function checkFileSize() {
        if ($this->fileSize > $this->maxSize) {
            $this->setOption("errorNum", -2);
            return false;
        } else {
            return true;
        }
    }

    /**
     * 设置和$_FILES有关的内容
     * @param string $name
     * @param string $tmp_name
     * @param int $size
     * @param $error
     * @return boolean
     */
    private function setFiles($name = "", $tmp_name = "", $size = 0, $error = 0) {
        $this->setOption("errorNum", $error);
        if ($this->errorNum) {
            return false;
        }
        $arr = explode(".", $name);
        $this->setOption("fileType", strtolower($arr[count($arr) - 1]));
        $this->setOption("originName", $name);
        $this->setOption("tmpFileName", $tmp_name);
        $this->setOption("fileSize", $size);
        return true;
    }

    /**
     * 设置CURL获取的图片信息
     * @param string $url
     */
    private function setCurlFile($url) {
        $url = trim($url);
        $this->setOption("curlFileUrl", $url);
        preg_match('/.*[\/\.=]([^\.\/=]*)\.(jpg|jpeg|gif|bmp|png)/is', $url, $match);
        if(count($match)==3){
            $this->setOption("originName", $match[1].'.'.$match[2]);
            $this->setOption("tmpFileName",$url);
            $this->setOption("fileType", strtolower($match[2]));
        }
        else{
            $upFileExt = substr($url, strrpos($url, '.') + 1);
            $this->setOption("fileType", strtolower($upFileExt));
            $arr = explode('/', $url);
            $this->setOption("tmpFileName",$url);
            $this->setOption("originName", @end($arr));
        }
        $this->setOption("fileSize",strlen(file_get_contents($url)));
        return true;
    }

    /**
     * 设置base64的图片信息
     * @param string $url
     */
    private function setBase64File($content){
        if(preg_match('/^(data:\s*image\/(\w+);base64,(.*))/', $content, $result)){
            $fileType = strtolower($result[2]);
            $this->setOption("fileType", $fileType);
            $fileName = date('YmdHis').'.'.$fileType;
            $new_file = runtime_path('tmp').'/'.$fileName;
            $fileContent = base64_decode($result[3]);
            Files::writeFile($new_file,$fileContent);
            $this->setOption("tmpFileName",$new_file);
            $this->setOption("originName", $fileName);
            $this->setOption("fileSize", strlen($fileContent));
            return true;
        }
        else{
            $this->setOption('errorNum', -3);
            return false;
        }
    }

    /**
     * 检查上传文件的类型
     * @return boolean
     */
    private function checkFileType() {
        if ($this->allowType=='*' || in_array(strtolower($this->fileType), $this->allowType)) {
            return true;
        } else {
            $this->setOption("errorNum", -1);
            return false;
        }
    }

    /**
     * 获取文件大小
     * @return int
     */
    public function getFileSize() {
        return $this->fileSize ? $this->fileSize : 0;
    }

    /**
     * 获取图片宽度
     * @return int
     */
    public function getImageWidth(){
        return $this->image_width;
    }

    /**
     * 获取图片高度
     * @return int
     */
    public function getImageHeight(){
        return $this->image_height;
    }

    /**
     * 上传文件失败时，显示错误信息的函数s
     * @return type
     */
    public function getErrorMsg() {
        return $this->errorMsg;
    }

    /**
     * 随机获取文件名称
     * @return string
     */
    private function proRandName() {
        $filename = date("YmdHis") . rand(1000, 9999);
        return $filename . "." . $this->fileType;
    }

    /**
     * 获取文件后缀名
     * @return string
     */
    public function getFileType() {
        return $this->fileType;
    }

    /**
     * 获取裁切后的文件名称
     * @return string
     */
    public function getCutFileNames() {
        return $this->cutFileNameArray;
    }

    /**
     * 获取裁切后的文件地址
     * @return string
     */
    public function getCutFileUrls() {
        if($this->engine=='local'){
            $cutFiles = $this->getCutFileNames();
            if(!empty($cutFiles)){
                foreach($cutFiles as &$v){
                    $v = $this->uploadPath . $this->getFilePath() . $v;
                }
            }
            return $cutFiles;
        }
        elseif($this->engine=='s3'){
            $cutFiles = $this->cutArray;
            if(!empty($cutFiles)){
                foreach($cutFiles as &$v){
                    $v = $this->uploadFileUrl;
                }
            }
            return $cutFiles;
        }
        elseif($this->engine=='oss'){
            $cutFiles = $this->cutArray;
            if(!empty($cutFiles)){
                foreach($cutFiles as &$v){
                    $arr = explode('x',$v);
                    $v = $this->uploadFileUrl . '?x-oss-process=image/resize,h_'.$arr[0].',w_'.$arr[1].''.$v;
                }
            }
            return $cutFiles;
        }
    }

    /**
     * 用于获取上传文件后文件的名称
     * @return string
     */
    public function getNewFileName() {
        return $this->newFileName;
    }

    /**
     * 设置上传后的文件名称
     * @param string $newFileName
     */
    public function setNewFileName($newFileName = null) {
        if (empty($newFileName)) {
            if ($this->isRandName) {
                $newFileName = $this->proRandName();
            }
            else {
                $newFileName = $this->originName;
            }
        }
        $arr = explode('.', $newFileName);
        if(in_array(strtolower($arr[1]),['jpg','png','jpeg','gif','bmp'])){
            $info = [];
            if (!empty($this->curlFileUrl)) {
                $info = getimagesize($this->curlFileUrl);
            }
            elseif(!empty($this->tmpFileName)) {
                $info = getimagesize($this->tmpFileName);
            }
            if(empty($info)){
                $this->setOption("errorNum", -3);
                return false;
            }
            $this->image_width = $info[0];
            $this->image_height = $info[1];
            $this->fileHash = md5_file($this->tmpFileName);
        }
        $this->setOption('newFileName', $newFileName);
        return true;
    }

    /**
     * 获取原图片名称
     * @return string
     */
    public function getOriginName(){
        return $this->originName;
    }

    /**
     * 获取文件路径
     * @param string $isFullPath
     * @return string
     */
    public function getFilePath($isFullPath = false) {
        if ($isFullPath !== false && !empty($this->rootPath)) {
            $filePath = rtrim($this->rootPath, '/');
            $filePath .= rtrim($this->filePath, '/') . "/";
        } else {
            $filePath = rtrim($this->filePath, '/') . "/";
        }
        return $filePath;
    }

    /**
     * 获取文件的hash
     * @return string
     */
    public function getFileHash(){
        return $this->fileHash;
    }

    /**
     * 获取上传的图片地址
     * @return string
     */
    public function getUploadFileUrl(){
        if($this->engine=='local'){
            return $this->uploadPath . $this->getFilePath() . $this->getNewFileName();
        }
        else{
            return $this->uploadFileUrl;
        }
    }

    /**
     * 拷贝本地文件
     * @param string $waterImage 水印图片地址
     * @return boolean
     */
    private function copyFile($waterImage = null) {
        if (!$this->errorNum) {
            $filePath = $this->getFilePath(true);
            $filePath.=$this->getNewFileName();
            if($this->engine=='local'){
                //将文件拷贝到指定的路径当中
                if (Files::moveFile($this->tmpFileName,$filePath)) {
                    if($this->Orientation=='6'){        //需要顺时针（向左）90度旋转
                        $this->imageRotate($filePath, 270);
                    }
                    elseif($this->Orientation=='8'){        //需要逆时针（向右）90度旋转
                        $this->imageRotate($filePath, 90);
                    }
                    elseif($this->Orientation=='3'){        //需要180度旋转
                        $this->imageRotate($filePath, 180);
                    }
                    if (!empty($waterImage)) {        //添加水印
                        $this->imageWaterMark($filePath, 9, $waterImage);
                    }
                    return true;
                } else {
                    $this->setOption('errorNum', -3);
                    return false;
                }
            }
            elseif($this->engine=='s3'){
                $this->uploadFileUrl = engine\S3::getInstance()->putObject($this->tmpFileName,$this->getFilePath().$this->getNewFileName());
                if(!empty($this->uploadFileUrl)){
                    return true;
                }
                else {
                    $this->setOption('errorNum', -3);
                    return false;
                }
            }
            elseif($this->engine=='oss'){
                $this->uploadFileUrl = engine\OSS::getInstance()->putObject($this->tmpFileName,$this->getFilePath().$this->getNewFileName());
                if(!empty($this->uploadFileUrl)){
                    return true;
                }
                else {
                    $this->setOption('errorNum', -3);
                    return false;
                }
            }
            else{
                $this->setOption('errorNum', -7);
                return false;
            }
        }
    }

    /**
     * 拷贝远程文件
     * @param string $waterImage 水印图片地址
     * @return boolean
     */
    private function copyCurlFile($waterImage = null) {
        if (!$this->errorNum) {
            $filePath = $this->getFilePath(true);
            $filePath.=$this->getNewFileName();
            if($this->engine=='local'){
                ob_start();
                readfile($this->curlFileUrl);
                $fileContent = ob_get_contents();
                ob_clean();
                $fp = fopen($filePath, 'w');
                fwrite($fp, $fileContent);
                fclose($fp);
                if (file_exists($filePath)) {
                    if (!empty($waterImage)) {        //添加水印
                        $this->imageWaterMark($filePath, 9, $waterImage);
                    }
                    return true;
                }
                else {
                    $this->setOption('errorNum', -3);
                    return false;
                }
            }
            elseif($this->engine=='s3'){
                $this->uploadFileUrl = engine\S3::getInstance()->putObject($this->curlFileUrl,$this->getFilePath().$this->getNewFileName());
                if(!empty($this->uploadFileUrl)){
                    return true;
                }
                else {
                    $this->setOption('errorNum', -3);
                    return false;
                }
            }
            elseif($this->engine=='oss'){
                $this->uploadFileUrl = engine\OSS::getInstance()->putObject($this->curlFileUrl,$this->getFilePath().$this->getNewFileName());
                if(!empty($this->uploadFileUrl)){
                    return true;
                }
                else {
                    $this->setOption('errorNum', -3);
                    return false;
                }
            }
            else{
                $this->setOption('errorNum', -7);
                return false;
            }
        }
    }

    /**
     * 移动文件
     * @param string $pic
     * @param string $waterImage
     */
    public function moveFile($pic_url,$filename=null,$waterImage = null){
        $return = false;
        if (file_exists($pic_url)) {
            $name = $filename; //post提交的名称
            $tmp_name = $pic_url;  //上传时的临时文件名
            $size = filesize($pic_url);
            $error = 0;
            //检查文件的路径是否存在错误
            if (!$this->checkFilePath()) {
                $this->errorMsg = $this->getError();
                $return = false;
            }
            elseif ($this->setFiles($name, $tmp_name, $size, $error)) {
                if ($this->checkFileSize() && $this->checkFileType()) {
                    $this->setNewFileName($filename);
                    $filePath = $this->getFilePath(true);
                    $filePath.=$this->getNewFileName();
                    if (Files::copyFile($this->tmpFileName, $filePath)) {
                        $return = $this->thumbImg();    //裁切图片
                    } else {
                        $return = false;
                    }
                } else {
                    $return = false;
                }
            } else {
                $return = false;
            }
            if (!$return) {
                $this->errorMsg = $this->getError();
            }
        }
        return $return;
    }

    /**
     * 表单上传文件
     * @param $fileField 文件名称
     * @param $newFileName 新文件名称
     * @param $waterImage 水印图片地址
     * @return boolean
     */
    public function uploadFile(\Webman\Http\UploadFile $file,$newFileName=null,$waterImage = null) {
        $return = false;
        $name = $file->getUploadName(); //post提交的名称
        $tmp_name = $file->getPathname();  //上传时的临时文件名
        $size = $file->getSize();
        $error = $file->getUploadErrorCode();
        //检查文件的路径是否存在错误
        if (!$this->checkFilePath()) {
            $this->errorMsg = $this->getError();
        }
        elseif ($this->setFiles($name, $tmp_name, $size, $error)) {
            if ($this->checkFileSize() && $this->checkFileType()) {
                if($this->setNewFileName($newFileName)){
                    if ($this->copyFile($waterImage)) {
                        $return = $this->thumbImage();
                    }
                }
            }
        }
        if (!$return) {
            $this->errorMsg = $this->getError();
        }
        return $return;
    }

    /**
     * 通过curl上传
     * @param string $url 远程图片地址
     * @param string $newFileName 新文件名称
     * @param string $waterImage 水印图片地址
     */
    public function uploadCurlFile($url,$newFileName=null,$waterImage = null) {
        $return = false;
        if (!empty($url)) {
            //检查文件的路径是否存在错误
            if (!$this->checkFilePath()) {
                $this->errorMsg = $this->getError();
            }
            elseif ($this->setCurlFile($url) && $this->checkFileType()) {
                if($this->setNewFileName($newFileName)){
                    if ($this->copyCurlFile($waterImage)) {
                        $return = $this->thumbImage();
                    }
                }
            }
            if (!$return) {
                $this->errorMsg = $this->getError();
            }
        } else {
            $this->setOption('errorNum', 5);
        }
        return $return;
    }

    /**
     * 通过curl上传
     * @param string $url 远程图片地址
     * @param string $newFileName 新文件名称
     * @param string $waterImage 水印图片地址
     */
    public function uploadBase64Content($content,$newFileName=null,$waterImage = null) {
        $return = false;
        if (!empty($content)) {
            if ($this->setBase64File($content) && $this->checkFileType()) {
                if($this->setNewFileName($newFileName)){
                    if ($this->copyFile($waterImage)) {
                        $return = $this->thumbImage();
                    }
                }
            }
            if (!$return) {
                $this->errorMsg = $this->getError();
            }
        }
        else {
            $this->setOption('errorNum', 5);
        }
        return $return;
    }

    /**
     * 生成缩略图片
     * @return string|boolean
     */
    public function thumbImage() {
        $imgSizes = $this->cutArray;
        if (!empty($imgSizes) && is_array($imgSizes) && $this->engine=='local') {
            $uploadpath = $this->getFilePath(true);
            $savefile = $uploadpath . $this->getNewFileName();
            foreach ($imgSizes as $key => $v) {
                $new_filename = $key . '-' . $this->getNewFileName();
                if (!file_exists($savefile)) {
                    return false;
                }
                $new_savefile = $uploadpath . $new_filename;
                $si = explode('x', $v);
                if (count($si) == 1) {
                    $this->thumbImageCropper($savefile, $v, $v, $new_savefile);
                } else {
                    if ($si[0] > $si[1]) {
                        if($si[1]==0){
                            $this->thumbImageResize($savefile,$si[0],$si[0],'width',$new_savefile);
                        }
                        else{
                            $this->thumbImageCropper($savefile, $si[0],$si[1], $new_savefile);
                        }
                    }
                    elseif ($si[0] < $si[1]) {
                        if($si[0]==0){
                            $this->thumbImageResize($savefile,$si[1],$si[1],'width',$new_savefile);
                        }
                        else{
                            $this->thumbImageCropper($savefile,$si[0],$si[1],$new_savefile);
                        }
                    }
                    else {
                        $this->thumbImageCropper($savefile, $si[0], $si[1], $new_savefile);
                    }
                }
                if (!file_exists($new_savefile)) {
                    return false;
                }
                $this->cutFileNameArray[$key] = $new_filename;
            }
        }
        return true;
    }

    /**
     * 旋转图片
     * @param string $filename  图片地址
     * @param string $degrees 旋转角度
     */
    public function imageRotate($filename,$degrees){
        $source_info = getimagesize($filename);
        $source_mime = $source_info['mime'];
        switch ($source_mime) {
            case 'image/gif':
                $source_image = imagecreatefromgif($filename);
                $rotate = imagerotate($source_image, $degrees, 0);
                imagegif($rotate,$filename);
                break;
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($filename);
                $rotate = imagerotate($source_image, $degrees, 0);
                imagejpeg($rotate,$filename);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($filename);
                $rotate = imagerotate($source_image, $degrees, 0);
                imagepng($rotate,$filename);
                break;
            default:
                break;
        }
    }
    /**
     * 把图片剪切为固定大小
     * @param string $source_path 源图片
     * @param int $target_width 目标宽度
     * @param int $target_height 目标高度
     * @param string $new_savefile  指定路径
     * @return boolean
     */
    public function thumbImageCropper($source_path, $target_width, $target_height, $new_savefile = null) {
        $source_info = getimagesize($source_path);
        $source_width = $source_info[0];
        $source_height = $source_info[1];
        $source_mime = $source_info['mime'];
        $source_ratio = $source_height / $source_width;
        $target_ratio = $target_height / $target_width;
        // 源图过高
        if ($source_ratio > $target_ratio) {
            $cropped_width = $source_width;
            $cropped_height = $source_width * $target_ratio;
            $source_x = 0;
            $source_y = ($source_height - $cropped_height) / 2;
        } elseif ($source_ratio < $target_ratio) { // 源图过宽
            $cropped_width = $source_height / $target_ratio;
            $cropped_height = $source_height;
            $source_x = ($source_width - $cropped_width) / 2;
            $source_y = 0;
        } else { // 源图适中
            $cropped_width = $source_width;
            $cropped_height = $source_height;
            $source_x = 0;
            $source_y = 0;
        }
        switch ($source_mime) {
            case 'image/gif':
                $source_image = imagecreatefromgif($source_path);
                break;
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($source_path);
                break;
            default:
                return false;
                break;
        }
        $target_image = imagecreatetruecolor($target_width, $target_height);
        $cropped_image = imagecreatetruecolor($cropped_width, $cropped_height);
        // 裁剪
        imagecopy($cropped_image, $source_image, 0, 0, $source_x, $source_y, $cropped_width, $cropped_height);
        // 缩放
        imagecopyresampled($target_image, $cropped_image, 0, 0, 0, 0, $target_width, $target_height, $cropped_width, $cropped_height);
        if (empty($new_savefile)) {
            $dotpos = strrpos($source_path, '.');
            $imgName = substr($source_path, 0, $dotpos);
            $suffix = substr($source_path, $dotpos);
            $new_savefile = $imgName . '_small' . $suffix;
        }
        imagejpeg($target_image, $new_savefile, $this->imgQuality);
        imagedestroy($source_image);
        imagedestroy($target_image);
        imagedestroy($cropped_image);
    }

    /**
     * 图片缩放函数（可设置高度固定，宽度固定或者最大宽高，支持gif/jpg/png三种类型）
     * @param string $source_path 源图片
     * @param int $target_width 目标宽度
     * @param int $target_height 目标高度
     * @param string $fixed_orig 锁定宽高（可选参数 width、height或者空值）
     * @param string $new_savefile  指定路径
     * @return string
     */
    public function thumbImageResize($source_path, $target_width = 200, $target_height = 200, $fixed_orig = '', $new_savefile = null) {
        $source_info = getimagesize($source_path);
        $source_width = $source_info[0];
        $source_height = $source_info[1];
        $source_mime = $source_info['mime'];
        $ratio_orig = $source_width / $source_height;
        if ($fixed_orig == 'width') {
            //宽度固定
            $target_height = $target_width / $ratio_orig;
        } elseif ($fixed_orig == 'height') {
            //高度固定
            $target_width = $target_height * $ratio_orig;
        } else {
            //最大宽或最大高
            if ($target_width / $target_height > $ratio_orig) {
                $target_width = $target_height * $ratio_orig;
            } else {
                $target_height = $target_width / $ratio_orig;
            }
        }
        switch ($source_mime) {
            case 'image/gif':
                $source_image = imagecreatefromgif($source_path);
                break;
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($source_path);
                break;
            default:
                return false;
                break;
        }
        $target_image = imagecreatetruecolor($target_width, $target_height);
        imagecopyresampled($target_image, $source_image, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height);
        if (empty($new_savefile)) {
            $imgArr = explode('.', $source_path);
            $new_savefile = $imgArr[0] . '_new.' . $imgArr[1];
        }
        imagejpeg($target_image, $new_savefile, $this->imgQuality);
        imagedestroy($source_image);
        imagedestroy($target_image);
    }

    /*
     * 功能：PHP图片水印 (水印支持图片或文字)
     * 参数：
     * $groundImage 背景图片，即需要加水印的图片，暂只支持GIF,JPG,PNG格式；
     * $waterPos水印位置，有10种状态，0为随机位置；
     * 1为顶端居左，2为顶端居中，3为顶端居右；
     * 4为中部居左，5为中部居中，6为中部居右；
     * 7为底端居左，8为底端居中，9为底端居右；
     * $waterImage图片水印，即作为水印的图片，暂只支持GIF,JPG,PNG格式；
     * $waterText文字水印，即把文字作为为水印，支持ASCII码，不支持中文；
     * $textFont文字大小，值为1、2、3、4或5，默认为5；
     * $textColor文字颜色，值为十六进制颜色值，默认为#FF0000(红色)；
     *
     * 注意：Support GD 2.0，Support FreeType、GIF Read、GIF Create、JPG 、PNG
     * $waterImage 和 $waterText 最好不要同时使用，选其中之一即可，优先使用 $waterImage。
     * 当$waterImage有效时，参数$waterString、$stringFont、$stringColor均不生效。
     * 加水印后的图片的文件名和 $groundImage 一样。
     */
    public function imageWaterMark($groundImage, $waterPos = 0, $waterImage = "", $waterText = "", $textFont = 5, $textColor = "#FF0000") {
        $isWaterImage = FALSE;
        $formatMsg = "暂不支持该文件格式，请用图片处理软件将图片转换为GIF、JPG、PNG格式。";
        //读取水印文件
        if (!empty($waterImage) && file_exists($waterImage)) {
            $isWaterImage = TRUE;
            $water_info = getimagesize($waterImage);
            $water_w = $water_info[0]; //取得水印图片的宽
            $water_h = $water_info[1]; //取得水印图片的高
            switch ($water_info[2]) {//取得水印图片的格式
                case 1:$water_im = imagecreatefromgif($waterImage);
                    break;
                case 2:$water_im = imagecreatefromjpeg($waterImage);
                    break;
                case 3:$water_im = imagecreatefrompng($waterImage);
                    break;
                default:
                    \support\extend\Log::error('水印图片格式不支持', ['image' => $waterImage]);
                    return false;
            }
        }
        //读取背景图片
        if (!empty($groundImage) && file_exists($groundImage)) {
            $ground_info = getimagesize($groundImage);
            $ground_w = $ground_info[0]; //取得背景图片的宽
            $ground_h = $ground_info[1]; //取得背景图片的高
            switch ($ground_info[2]) {//取得背景图片的格式
                case 1:$ground_im = imagecreatefromgif($groundImage);
                    break;
                case 2:$ground_im = imagecreatefromjpeg($groundImage);
                    break;

                case 3:$ground_im = imagecreatefrompng($groundImage);
                    break;
                default:
                    \support\extend\Log::error('水印背景图片格式不支持', ['image' => $groundImage]);
                    return false;
            }
        } else {
            \support\extend\Log::error('需要加水印的图片不存在', ['image' => $groundImage]);
            return false;
        }
        //水印位置
        if ($isWaterImage) {//图片水印
            $w = $water_w;
            $h = $water_h;
            $label = "图片的";
        } else {//文字水印
            $temp = imagettfbbox(ceil($textFont * 5), 0, "./cour.ttf", $waterText); //取得使用 TrueType 字体的文本的范围
            $w = $temp[2] - $temp[6];
            $h = $temp[3] - $temp[7];
            unset($temp);
            $label = "文字区域";
        }
        if (($ground_w < $w) || ($ground_h < $h)) {
            echo "需要加水印的图片的长度或宽度比水印" . $label . "还小，无法生成水印！";
            return;
        }
        switch ($waterPos) {
            case 0://随机
                $posX = rand(0, ($ground_w - $w));
                $posY = rand(0, ($ground_h - $h));
                break;
            case 1://1为顶端居左
                $posX = 0;
                $posY = 0;
                break;
            case 2://2为顶端居中
                $posX = ($ground_w - $w) / 2;
                $posY = 0;
                break;
            case 3://3为顶端居右
                $posX = $ground_w - $w;
                $posY = 0;
                break;
            case 4://4为中部居左
                $posX = 0;
                $posY = ($ground_h - $h) / 2;
                break;
            case 5://5为中部居中
                $posX = ($ground_w - $w) / 2;
                $posY = ($ground_h - $h) / 2;
                break;
            case 6://6为中部居右
                $posX = $ground_w - $w;
                $posY = ($ground_h - $h) / 2;
                break;
            case 7://7为底端居左
                $posX = 0;
                $posY = $ground_h - $h;
                break;
            case 8://8为底端居中
                $posX = ($ground_w - $w) / 2;
                $posY = $ground_h - $h;
                break;
            case 9://9为底端居右
                $posX = $ground_w - $w - 15;   // -10 是距离右侧10px 可以自己调节
                $posY = $ground_h - $h - 10;   // -10 是距离底部10px 可以自己调节
                break;
            default://随机
                $posX = rand(0, ($ground_w - $w));
                $posY = rand(0, ($ground_h - $h));
                break;
        }

        //设定图像的混色模式

        imagealphablending($ground_im, true);
        if ($isWaterImage) {//图片水印
            imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $water_w, $water_h); //拷贝水印到目标文件
        } else {//文字水印
            if (!empty($textColor) && (strlen($textColor) == 7)) {
                $R = hexdec(substr($textColor, 1, 2));
                $G = hexdec(substr($textColor, 3, 2));
                $B = hexdec(substr($textColor, 5));
            } else {
                \support\extend\Log::error('水印文字颜色格式不正确', ['textColor' => $textColor]);
                return false;
            }
            imagestring($ground_im, $textFont, $posX, $posY, $waterText, imagecolorallocate($ground_im, $R, $G, $B));
        }
        //生成水印后的图片
        @unlink($groundImage);
        switch ($ground_info[2]) {//取得背景图片的格式
            case 1:imagegif($ground_im, $groundImage);
                break;
            case 2:imagejpeg($ground_im, $groundImage);
                break;
            case 3:imagepng($ground_im, $groundImage);
                break;
        }
        //释放内存
        if (isset($water_info))
            unset($water_info);
        if (isset($water_im))
            imagedestroy($water_im);
        unset($ground_info);
        imagedestroy($ground_im);
    }

    /**
     * 获取图片地址
     * @param string $info json数据 {id,url}
     */
    public static function getFileUrl($info){
        if(empty($info)){
            return $info;
        }
        $data = json_decode($info,true);
        if(!empty($data)){
            return $data['file_url'];
        }
        return $info;
    }
}
