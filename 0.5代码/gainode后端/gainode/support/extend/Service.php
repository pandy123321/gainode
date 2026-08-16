<?php

namespace support\extend;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use support\exception\RunException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service
 * Strings methods
 * @method Model create($data)
 * @method Model updateOrCreate(array $params,array $data)
 * @method Model update($id,array $data){
 * @method int insert(array $data,array $consult=[],$isThrowException=false)
 * @method boolean delete($id,bool $force=false)
 * @method Builder sharedLock($params=[])
 * @method Builder lockForUpdate($params=[])
 * @method int updateAll(array $params,array $data)
 * @method int deleteAll(array $params,bool $force=false)
 * @method Expression raw($str)
 * @method Model get($id,string $field = null)
 * @method Model|Builder find($id)
 * @method mixed getValue($id,$field,$default=null)
 * @method Model|Builder findOrFail($id)
 * @method Model firstOrCreate(array $params,array $data)
 * @method mixed value(string $column,array $params=[],array $orderBy=[])
 * @method array pluck(string $column,array $params=[],array $orderBy=[])
 * @method int count(array $params=[])
 * @method float sum($column,array $params=[])
 * @method int max($column,array $params=[])
 * @method Model fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method Collection fetchAll(array $params=[],array $orderBy=[],array $fields=[])
 * @method Builder groupBySelector($groupBy=[],array $params=[],$orderBy=[])
 * @method Builder selector(array $params=[],array $orderBy=[],array $fields=[])
 * @method LengthAwarePaginator paginate(array $params=[],array $orderBy=[],array $fields = ['*'])
 * @method array paginateArray(array $params=[],array $orderBy=[],array $fields = ['*'])
 * @method string getTable()
 */
class Service
{
    /**
     * dao类名
     * @var string
     */
    protected $dao;

    protected $eid;

    public function __construct()
    {
        $this->eid = 0;
    }

    /**
     * 获取一个数据库连接
     * @param string $adapter
     * @return \Illuminate\Database\Connection
     */
    public function connection($adapter="mysql"){
        return Db::connection($adapter);
    }

    /**
     * @return Dao
     */
    public function getNewDao(){
        return new $this->dao();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        try {
            if(empty($this->dao)){
                throw new RunException("暂未找到Model类");
            }
            return $this->getNewDao()->{$name}(... $arguments);
        }
        catch (\Throwable $e) {
            Log::channel('library')->error('Service Call',['func'=>$name,'msg'=>$e->getMessage()]);
            throw $e;
        }
    }
}
