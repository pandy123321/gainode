<?php

namespace support\utils;

use support\exception\VerifyException;

/**
 * 文件操作类
 */
class Files {

    private $resource = null; //文件资源句柄

    /**
     * 构造函数，打开资源流，并独占锁定
     * @param String $fileName 文件路径名
     * @param String $mode     操作方式，默认为读操作，可供选择的项为：r,r+,w+,w+,a,a+
     * 'r'  只读方式打开，将文件指针指向文件头
     * 'r+' 读写方式打开，将文件指针指向文件头
     * 'w'  写入方式打开，将文件指针指向文件头并将文件大小截为零。如果文件不存在则尝试创建之。
     * 'w+' 读写方式打开，将文件指针指向文件头并将文件大小截为零。如果文件不存在则尝试创建之。
     * 'a'  写入方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建之。
     * 'a+' 读写方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建之。
     */
    public function __construct($fileName,$mode='r')
    {
        $dirName  = dirname($fileName);
        //检查并创建文件夹
        self::mkdir($dirName);
        $this->resource = fopen($fileName,$mode.'b');
        if($this->resource)
        {
            flock($this->resource,LOCK_EX);
        }
    }

    /**
     * 获取文件内容
     * @return String 文件内容
     */
    public function read()
    {
        $content = null;
        while(!feof($this->resource))
        {
            $content.= fread($this->resource,1024);
        }
        return $content;
    }

    /**
     * 文件写入操作
     * @param  String $content 要写入的文件内容
     * @return Int or false    写入的字符数; false:写入失败;
     */
    public function write($content)
    {
        $worldsnum = fwrite($this->resource,$content);
        $this->save();
        return is_bool($worldsnum) ? false : $worldsnum;
    }

    /**
     * 释放文件锁定
     */
    public function save()
    {
        flock($this->resource,LOCK_UN);
    }

    /**
     * 复制文件
     * @param string $file_from 原始文件
     * @param string $file_to 移动的文件
     * @return boolean
     */
    public static function copyFile($file_from, $file_to) {
        if (file_exists($file_from)) {
            copy($file_from, $file_to);
            if (file_exists($file_to)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 移动文件
     * @param string $file_from 原始文件
     * @param string $file_to 移动的文件
     * @return boolean
     */
    public static function moveFile($file_from, $file_to) {
        if (file_exists($file_from)) {
            $result = rename($file_from, $file_to);
            if ($result != false && file_exists($file_to)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 本方法把$path路劲下面的ename文件重命名为newname文件
     * @param string $path 文件路劲
     * @param string $name 原文件名称
     * @param string $newname 新名称
     * @return true|false
     */
    public static function renameFile($path, $name, $newname) {   //文件或目录更名
        $filename = $path . '/' . $name;
        if (file_exists($filename)) {   //判断文件是否存在
            $newfilename = $path . '/' . $newname;
            $result = rename($filename, $newfilename);
            if ($result != false && file_exists($newfilename)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 本方法用来写文件，向path路径下name文件写入content内容，bool为写入选项，值为1时
     * 接着文件原内容下继续写入，值为2时写入后的文件只有本次content内容
     * @param string $file_path  文件路径
     * @param string $content  内容
     * @param string $type
     * @return boolean true|false
     */
    public static function writeFile($file_path, $content, $type = 'w') { //写文件
        try {
            if (!file_exists($file_path)) {
                fopen($file_path, "w+");
            }
            //首先要确定文件存在并且可写
            if (is_writable($file_path)) {
                if (!$handle = fopen($file_path, $type)) {    //使用添加模式打开$filename，文件指针将会在文件的开头
                    throw new VerifyException('cannot open file ' . $file_path);
                }
                if (!fwrite($handle, $content)) {
                    throw new VerifyException('cannot write to file ' . $file_path);
                }
                fclose($handle);    //关闭文件
                return true;
            } else {
                throw new VerifyException($file_path . " is not to be written");
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 创建文件夹
     * @param String $path  路径
     * @param int    $chmod 文件夹权限
     * @note  $chmod 参数不能是字符串(加引号)，否则linux会出现权限问题
     */
    public static function mkdir($path,$chmod=0777)
    {
        return is_dir($path) or (self::mkdir(dirname($path),$chmod) and mkdir($path,$chmod));
    }

    /**
     * 本方法在path目录下创建名为dirname的目录
     * @param string $path 路径
     * @param string $dirname 目录名
     * @return boolean true|false
     */
    public static function createDir($path, $dirname) { //创建目录
        if (is_dir($path)) {
            $file_dir = $path . '/' . $dirname;
            if (file_exists($file_dir)) {
                return true;
            }
            $result = @mkdir($file_dir, 0775, true);
            if ($result) {
                return true;
            }
        }
        return false;
    }

    /**
     * 本方法把name文件从path路径删除
     * @param string $filename 文件路径
     * @return boolean true|false
     */
    public static function delPathFile($filename) {     //删除文件
        if (!file_exists($filename)) {
            echo "file is not exists";
            return false;
        } elseif (unlink($filename)) {
            return true;
        }
        return false;
    }

    /**
     * 本方法删除pathname目录，包括该目录下所有的文件及子目录
     * @param string $path 文件路径
     * @return boolean true|false
     */
    public static function delPathDir($path) {      //删除目录及目录里所有的文件夹和文件
        if (is_dir($path)) {
            $handle = opendir($path); //打开目录                        //
            //列出目录中的所有文件并去掉 . 和 ..
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $filename = $path . '/' . $file;
                    self::delPathFile($filename);
                }
            }
            closedir($handle);
            return rmdir($path);
        }
        return false;
    }

    /**
     * 本方法删pathname目录下所有的文件
     * @param string $path 文件路径
     * @return boolean true|false
     */
    public static function delPathFiles($path) {      //删除目录及目录里所有的文件夹和文件
        if (is_dir($path)) {
            $handle = opendir($path); //打开目录                        //
            //列出目录中的所有文件并去掉 . 和 ..
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $filename = $path . '/' . $file;
                    self::delPathFile($filename);
                }
            }
            closedir($handle);
            return true;
        }
        return false;
    }

    /**
     * 查找该文件夹下面所有文件
     * @param string $path 文件夹路径
     * @param string $suffix 文件后缀
     * @return array|string
     */
    public static function getPathFiles(string $path, $suffix = null) {
        if (is_dir($path)) {
            $data = [];
            $handle = opendir($path);
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $data[] = $file;
                }
            }
            closedir($handle);
            if (!empty($data) && $suffix != null) {
                foreach ($data as $key => $r) {
                    if (!preg_match("/[\w]+\.$suffix$/i", $r)) {
                        unset($data[$key]);
                    }
                }
            }
            return $data;
        }
        else {
            return $path;
        }
    }

    /**
     * 获取路径下面所有的文件
     * @param string $path 地址
     * @param null $suffix 指定后缀
     * @return array
     */
    public static function getPathAllFiles(string $path, $suffix = null) {
        static $findFiles=[];
        $data = self::getPathFiles($path,$suffix);
        if(is_array($data)){
            foreach($data as $dir){
                self::getPathAllFiles($path.'/'.$dir,$suffix);
            }
        }else{
            $findFiles[] = $data;
        }
        return $findFiles;
    }

    /**
     * 获取该目录下的子文件夹
     * @param string $path 文件夹路径
     * @return array|bool
     */
    public static function getPathDirs($path) {
        if (is_dir($path)) {
            $handle = opendir($path); //这里输入其它路径
            $fileName = [];
            while ($file = readdir($handle)) {
                if (strlen($file) > 3)
                    $fileName[] = $file; //输出文件名
            }
            closedir($handle);
            return $fileName;
        }
        else {
            return false;
        }
    }

    /**
     * 删除文件
     * @param String $fileName 文件路径
     * @return bool  操作结果 false:删除失败;
     */
    public static function unlink($fileName)
    {
        if(is_file($fileName) && is_writable($fileName))
        {
            return unlink($fileName);
        }
        else
            return false;
    }

    /**
     * @param String $path 路径地址
     * @return bool true:$dir为空目录; false:$dir为非空目录;
     */
    public static function isEmptyDir($path)
    {
        if(is_dir($path))
        {
            $isEmpty = true;
            $dirRes  = opendir($path);
            while(false !== ($fileName = readdir($dirRes)))
            {
                if($fileName!='.' && $fileName!='..')
                {
                    $isEmpty = false;
                    break;
                }
            }
            closedir($dirRes);
            return $isEmpty;
        }
    }

    /**
     * @param  String $fileName  文件名
     * @return String 文件后缀名
     */
    public static function getFileSuffix($fileName)
    {
        $fileInfoArray = pathinfo($fileName);
        return $fileInfoArray['extension'];
    }

    /**
     * 获取文件大小
     * @param  String $fileName 文件名
     * @return Int    文件大小的积分数，如果文件无效则返回 NULL
     */
    public static function getFileSize($fileName)
    {
        return is_file($fileName) ? filesize($fileName):null;
    }
}
