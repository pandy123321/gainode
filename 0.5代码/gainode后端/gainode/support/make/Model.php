<?php

namespace support\make;

use support\exception\RunException;
use support\extend\Db;
use support\utils\Files;

/**
 * 创建Modle类
 * @author Kevin
 */
class Model implements MakeInterface
{
    /**
     * 获取数据连接对象
     * @var Db
     */
    public $db;

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
        $this->path = library_path("model");
    }

    /**
     * 获取表的类名
     * @param string $name 数据库表
     */
    public function getFileClass(string $name)
    {
        $data = explode('_',$name);
        $module = array_shift($data);
        $path = 'library\model\\'.$module.'\\';
        foreach($data as $m){
            $path.=ucfirst($m);
        }
        $path.='Model';
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
        $path.='Model.php';
        return $path;
    }

    /**
     * 获取过滤后的所有表
     * @return array
     */
    public function getList(): array {
        $tableLists = $this->db->getTableList();
        foreach($tableLists as $k=>$v){
            $filepath = $this->getFilePath($v);
            if(file_exists($filepath)){
                unset($tableLists[$k]);
            }
        }
        return $tableLists;
    }

    /**
     * 创建model文件
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
            $this->content = file_get_contents(resource_path("template/make/TemplateModel.php"));
        }
        $data = explode('_',$name);
        $module = array_shift($data);
        $model = '';
        foreach($data as $m){
            $model.=ucfirst($m);
        }
        $pk='';
        $updated_at='';
        $tableColumns = $this->db->getTableColumns($name,false);
        $property='/**'.PHP_EOL;
        $fillable='['.PHP_EOL;
        $fields ='['.PHP_EOL;
        foreach($tableColumns as $v){
            if($v['is_pri']){
                $pk=$v['field'];
            }
            if($v['field']=='updated_time'){
                $updated_at='updated_time';
            }
            if(empty($v['is_null']) && empty($v['default']) && !in_array($v['field'],['created_time','updated_time'])){
                $fillable.="\t\t".'"'.$v['field'].'",'.PHP_EOL;
            }
            $fields.="\t\t".'"'.$v['field'].'",'.PHP_EOL;
            $property.=' * @property '.(strpos($v['type'],'int')!==false?'integer':'string').' $'.$v['field'].' '.$v['comment'].PHP_EOL;
        }
        $property.=' */';
        $fillable.="    ]";
        $fields.="    ]";

        $content= str_replace(
                ['/**property*/','module','Template','{table}','{adapter}','{pk}','{updated_at}','["fillable"]','["fields"]'],
                [$property,$module,$model,$name,$this->db->adapter,$pk,$updated_at,$fillable,$fields],
                $this->content
        );
        return str_replace(["const UPDATED_AT = 'updated_time';","const UPDATED_AT = '';"],['',"const UPDATED_AT = null;"], $content);
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
