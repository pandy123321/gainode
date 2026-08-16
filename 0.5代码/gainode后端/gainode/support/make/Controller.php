<?php

namespace support\make;

use support\Container;
use support\exception\RunException;
use support\extend\Db;
use support\utils\Files;

/**
 * 创建Controller类
 * @author Kevin
 */
class Controller implements MakeInterface
{
    /**
     * 获取数据连接对象
     * @var Db
     */
    private $db;

    /**
     * controller的所属模块
     * @var string
     */
    private $app;

    /**
     * 模版数据内容
     * @var string
     */
    private $content;

    public function __construct($app,$adapter) {
        $this->db = Db::getInstance($adapter);
        $this->app = $app;
    }

    /**
     * 检测终端目录是否存在
     * @return bool
     */
    public function checkAppExists(){
        $path = app_path($this->app."/controller");
        return is_dir($path);
    }

    /**
     * 获取表的类名
     * @param string $name 数据库表
     */
    public function getFileClass(string $name)
    {
        $data = explode('_',$name);
        $module = array_shift($data);
        $path = 'app\\'.$this->app.'\\controller\\'.$module.'\\';
        foreach($data as $m){
            $path.=ucfirst($m);
        }
        $path.='Controller';
        return $path;
    }

    /**
     * 获取表的文件路径
     * @param string $name 数据库表
     */
    public function getFilePath(string $name,bool $isCreateDir=false)
    {
        $data = explode('_',$name);
        $module = array_shift($data);
        if($isCreateDir){
            Files::createDir(app_path($this->app."/controller"),$module);
        }
        $path = app_path($this->app."/controller/".$module).'/';
        foreach($data as $m){
            $path.=ucfirst($m);
        }
        $path.='Controller.php';
        return $path;
    }

    /**
     * 获取过滤后的所有表
     * @return array
     */
    public function getList(): array {
        $tableLists = $this->db->getTableList();
        foreach($tableLists as $k=>$v){
            if(strpos($v,'casbin_')!==false){
                unset($tableLists[$k]);
            }
            else {
                $filepath = $this->getFilePath($v);
                if (file_exists($filepath)) {
                    unset($tableLists[$k]);
                }
                if(!$this->checkTableHasService($v)){
                    unset($tableLists[$k]);
                }
            }
        }
        return $tableLists;
    }

    /**
     * 验证数据表是否可以创建验证器
     * @param string $name
     */
    public function checkTableHasService(string $name){
        $data = explode('_',$name);
        $module = array_shift($data);
        $path = '/library/service/'.$module.'/';
        foreach($data as $m){
            $path.=ucfirst($m);
        }
        $path.='Service.php';
        $path = base_path($path);
        return file_exists($path);
    }

    /**
     * 创建validation文件
     * @param string $name 表名
     * @return bool
     */
    public function createFile(string $name) {
        $filePath = $this->getFilePath($name,true);
        $content = $this->getTemplate($name);
        if(file_exists($filePath)){
            throw new RunException('文件已经存在:'.$filePath);
        }
        else{
            return Files::writeFile($filePath,$content);
        }
    }

    /**
     * 获取生成的模版数据
     * @param string $name 表名
     * @return string
     */
    public function getTemplate(string $name) {
        if(empty($this->content)){
            $this->content = file_get_contents(resource_path("template/make/TemplateController.php"));
        }
        $data = explode('_',$name);
        $module = array_shift($data);
        $model = '';
        foreach($data as $m){
            $model.=ucfirst($m);
        }
        $content= str_replace(
            ['from','module','template','urlPath','Template'],
            [$this->app,$module,lcfirst($model),($module.'/'.lcfirst($model)),$model],
            $this->content
        );
        return $content;
    }

    /**
     * 获取今天创建的文件列表
     * @param int $days 最近修改时间天数
     * @return array
     */
    public function getTodayList(int $days=1,$rtype='mtime'): array {
        $data = [];
        $path = app_path($this->app."/controller");
        $fileLists = Files::getPathAllFiles($path);
        $set_time = strtotime('-'.$days.' day');
        foreach($fileLists as $file){
            if($rtype=='mtime'){
                $ftime= filemtime($file);
            }
            else{
                $ftime = filectime($file);
            }
            if($ftime>$set_time){
                $data[] = [
                    'name'=>$file,
                    'time'=>date('Y-m-d H:i:s',$ftime)
                ];
            }
        }
        return $data;
    }

}
