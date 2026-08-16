<?php

namespace support\extend;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;

/**
 * Class Db
 * @package support
 * @method static array select(string $query, $bindings = [], $useReadPdo = true)
 * @method static int insert(string $query, $bindings = [])
 * @method static int update(string $query, $bindings = [])
 * @method static int delete(string $query, $bindings = [])
 * @method static bool statement(string $query, $bindings = [])
 * @method static mixed transaction(\Closure $callback, $attempts = 1)
 * @method static void beginTransaction()
 * @method static void rollBack($toLevel = null)
 * @method static void commit()
 */
class Db extends Manager
{
    /**
     * 获取数据连接对象
     * @var Connection
     */
    public $db = null;
    public $adapter;
    private static $client = [];

    /**
     * 获取实例对象
     * @return Db
     */
    public static function getInstance($adapter){
        if(empty(self::$client[$adapter])){
            self::$client[$adapter] = new Db();
            self::$client[$adapter]->db = self::connection($adapter);
            self::$client[$adapter]->adapter = $adapter;
        }
        return self::$client[$adapter];
    }

    /**
     * 获取数据库配置
     */
    public function getConfig(){
        return $this->db->getConfig();
    }

    /**
     * 获取所有的的表名
     * @return array
     */
    public function getTableList($only_table=true){
        $data = [];
        $tables = $this->db->select('show table status');
        foreach($tables as $v){
            $arr = explode('_',$v->Name);
            if(!in_array($arr[0],['ai','wa'])){
                if($only_table){
                    $data[]=$v->Name;
                }
                else{
                    $data[]=[
                        'name'=>$v->Name,
                        'comment'=>$v->Comment,
                        'engine'=>$v->Engine,
                        'rows'=>$v->Rows,
                        'charset'=>$v->Collation,
                        'created_time'=>$v->Create_time,
                        'updated_time'=>$v->Update_time,
                        'data_length'=>$v->Data_length
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * 获取表的所有字段
     * @param string $table 表名
     * @param bool $only_field 是否只获取字段名
     * @return array
     */
    public function getTableColumns(string $table,$only_field=true){
        $data = [];
        $columns = $this->db->select('show full columns from '.$table);
        foreach($columns as $v){
            if($only_field){
                $data[]=$v->Field;
            }
            else{
                $data[]=[
                    'field'=>$v->Field,
                    'comment'=>$v->Comment,
                    'type'=>$v->Type,
                    'default'=>$v->Default,
                    'is_null'=>($v->Null=='YES'),
                    'is_pri'=>($v->Key=='PRI')
                ];
            }
        }
        return $data;
    }

    /**
     * 获取创建的表
     * @param string $type
     * @return array
     */
    public function getMakeLogsTable($type='model'){
        $data = [];
        $columns = $this->db->table('sys_make_logs')->where('type',$type)->get(['table']);
        foreach($columns as $v){
            $data[] = $v->table;
        }
        return $data;
    }
}
