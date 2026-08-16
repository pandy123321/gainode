<?php

namespace app\command\make;

use library\service\sys\RouteService;
use library\service\sys\TableFieldService;
use library\service\sys\TableListService;
use support\extend\Db;
use support\make\Model;
use support\utils\Random;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class MakeTableField extends Command
{

    protected static $defaultName = 'make:tableField';
    protected static $defaultDescription = '根据控制器方法创建路由数据';

    protected function configure()
    {
        $this->addArgument('type', InputArgument::OPTIONAL, '类型');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument("type");
        if($type=='table'){
            $this->initTable($output);
        }
        elseif($type=='field'){
            $this->initTableField($output);
        }
        else{
            $output->writeln('暂无该类型:'.$type);
        }
        return self::SUCCESS;
    }

    /**
     * 添加数据到数据表
     * @param OutputInterface $output
     */
    private function initTable(OutputInterface $output){
        $conn = Db::getInstance('mysql');
        $tableList = $conn->getTableList(false);
        $tableAry = $conn->db->table('sys_table_list')->pluck('tb_name')->toArray();
        $datalist = [];
        $make = new Model('mysql');
        foreach($tableList as $v){
            if(!in_array($v['name'],$tableAry)){
                $type = explode('_',$v['name'])[0];
                if($type!='common'){
                    $datalist[] = [
                        'tb_name'=>$v['name'],
                        'tb_code'=>Random::getRandStr(10,6),
                        'tb_desc'=>$v['comment'],
                        'tb_type'=>$type,
                        'entity_name'=>$make->getFileClass($v['name']),
                        'created_time'=>getCurrentDate('unix'),
                        'updated_time'=>getCurrentDate('unix')
                    ];
                }
            }
        }
        $output->writeln('插入数据表：'.count($datalist));
        $num = $conn->db->table('sys_table_list')->insert($datalist);
        $output->writeln('插入数据：'.$num?'成功':'失败');
    }

    /**
     * 添加数据到字段表
     * @param OutputInterface $output
     */
    private function initTableField(OutputInterface $output){
        $conn = Db::getInstance('mysql');
        $fieldAry = $conn->db->table('sys_table_field')->select(['tb_name','fd_name'])->get()->toArray();
        foreach($fieldAry as $k=>$v){
            $fieldAry[$k] = $v->tb_name.':'.$v->fd_name;
        }
        $tableAry = $conn->db->table('sys_table_list')->pluck('tb_name')->toArray();
        $datalist=[];
        foreach($tableAry as $name){
            $fields = $conn->getTableColumns($name,false);
            foreach($fields as $k=>$v){
                $key = $name.':'.$v['field'];
                if(!in_array($key,$fieldAry)){
                    if($pos=strpos($v['type'],')')){
                        $type = substr($v['type'],0,$pos+1);
                    }
                    else{
                        $type = $v['type'];
                    }
                    $datalist[] = [
                        'tb_name'=>$name,
                        'fd_name'=>$v['field'],
                        'fd_type'=>$type,
                        'fd_desc'=>$v['comment'],
                        'fd_sort'=>$k,
                        'is_null'=>$v['is_null']?'Y':'N',
                        'is_primary'=>$v['is_pri']?'Y':'N',
                        'default_value'=>$v['default'],
                        'created_time'=>getCurrentDate('unix'),
                        'updated_time'=>getCurrentDate('unix')
                    ];
                }
            }
        }
        $output->writeln('插入数据字段：'.count($datalist));
        if(count($datalist)>0){
            $num = $conn->db->table('sys_table_field')->insert($datalist);
            $output->writeln('插入数据：'.$num?'成功':'失败');
        }
    }
}
