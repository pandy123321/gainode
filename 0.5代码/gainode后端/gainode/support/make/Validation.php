<?php

namespace support\make;

use support\Container;
use support\exception\RunException;
use support\extend\Db;
use support\utils\Data;
use support\utils\Files;

/**
 * 创建Validation类
 * @author Kevin
 */
class Validation implements MakeInterface
{
    /**
     * 获取数据连接对象
     * @var Db
     */
    private $db;

    /**
     * model目录
     * @var string
     */
    private $path;

    /**
     * 模版数据内容
     * @var string
     */
    private $content;

    public function __construct($adapter) {
        $this->db = Db::getInstance($adapter);
        $this->path = library_path("validator");
    }

    /**
     * 获取表的类名
     * @param string $name 数据库表
     */
    public function getFileClass(string $name)
    {
        $data = explode('_',$name);
        $module = array_shift($data);
        $path = 'library\validator\\'.$module.'\\';
        foreach($data as $m){
            $path.=ucfirst($m);
        }
        $path.='Validation';
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
            Files::createDir($this->path,$module);
        }
        $path = $this->path.'/'.$module.'/';
        foreach($data as $m){
            $path.=ucfirst($m);
        }
        $path.='Validation.php';
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
                if($this->checkTableHasValidator($v)){
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
    public function checkTableHasValidator(string $name){
        return false;
        $data = explode('_',$name);
        $module = array_shift($data);
        $path = 'library\service\\'.$module.'\\';
        foreach($data as $m){
            $path.=ucfirst($m);
        }
        $path.='Service';
        $serviceObj = Container::get($path);
        return !empty($serviceObj)?true:false;
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
            $this->content = file_get_contents(resource_path("template/make/TemplateValidation.php"));
        }
        $data = explode('_',$name);
        $module = array_shift($data);
        $model = '';
        foreach($data as $m){
            $model.=ucfirst($m);
        }

        $pk='';
        $update_time=null;
        $tableColumns = $this->db->getTableColumns($name,false);
        $rules  ='['.PHP_EOL;
        $attributes='['.PHP_EOL;
        foreach($tableColumns as $v){
            if($v['is_pri']){
                $pk=$v['field'];
            }
            if(!in_array($v['field'],[$pk,'created_time','updated_time'])){
                $rules.="\t\t".'"'.$v['field'].'"=>"required|'.($this->getFieldValidatorType($v['type'])).'",'.PHP_EOL;
                $attributes.="\t\t".'"'.$v['field'].'"=>"'.($v['comment']).'",'.PHP_EOL;
            }
        }
        $rules.="    ]";
        $attributes.="    ]";
        $content= str_replace(
            ['module','Template',"['rules']","['attributes']"],
            [$module,$model,$rules,$attributes],
            $this->content
        );
        return $content;
    }

    private function getFieldValidatorType($type){
        if(strpos($type,'int')!==false){
            return 'integer';
        }
        elseif(strpos($type,'decimal')!==false){
            return 'numeric';
        }
        return 'string';
    }

    /**
     * 获取今天创建的文件列表
     * @param int $days 最近修改时间天数
     * @return array
     */
    public function getTodayList(int $days=1,$rtype='mtime'): array {
        $data = [];
        $fileLists = Files::getPathAllFiles($this->path);
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
