<?php

namespace support\extend;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Expression;
use support\exception\VerifyException;
use support\exception\RunException;

/**
 * Model
 * Strings methods
 * @method Expression raw($value)
 * @method Model find($id, $columns = ['*'])
 * @method Collection findMany($ids, $columns = ['*'])
 * @method Model findOrNew($id, $columns = ['*'])
 * @method Model firstOrCreate(array $attributes = [], array $values = [])
 * @method Model firstWhere($column, $operator = null, $value = null, $boolean = 'and')
 * @method Model first($columns = ['*'])
 *
 */
class Dao
{
    /**
     * 指示模型主键是否递增
     * @var bool
     */
    public $model;

    /**
     * 获取搜索类型
     */
    public function getFieldCondition($num=null){
        $data = [
            'eq'=>'等于',
            'neq'=>'不等于',
            'gt'=>'大于',
            'gte'=>'大于等于',
            'lt'=>'小于',
            'lte'=>'小于等于',
            'between'=>'介于',
            'not_between'=>'不介于',
            'like'=>'包含',
            'not_like'=>'不包含',
            'like_right'=>'结尾是',
            'like_left'=>'开头是',
            'in'=>'在里面',
            'not_in'=>'不在里面',
            'empty'=>'为空',
            'not_empty'=>'不为空',
            'null'=>'等于NULL',
            'not_null'=>'不等于NULL',
        ];
        if(!empty($num)){
            return isset($data[$num])?$data[$num]:null;
        }
        return $data;
    }

    /**
     * @param $id
     * @param bool $force
     * @return array|Model
     */
    public function create($data){
        $model = $this->getNewModel();
        $model->setAttributes($data);
        $res = $model->save();
        return $res?$model:[];
    }

    /**
     * 添加或修改
     * @param array $params
     * @param array $data
     * @return Model
     */
    public function updateOrCreate(array $params,array $data){
        try{
            $model = $this->fetch($params);
            if(!empty($model)){
                return $model->update($data);
            }
            else{
                return $this->create($data);
            }
        }
        catch (\Exception $e){
            Log::channel('library')->error('Dao updateOrCreate',['msg'=>$e->getMessage()]);
            return false;
        }
    }

    /**
     * @param $id
     * @param array $data
     * @return Builder|Model|null
     */
    public function update($id,array $data){
        $model = $this->getNewModel()->findOrFail($id);
        if(!empty($model)){
            return $model->update($data);
        }
        return false;
    }

    /**
     * @param array $data
     * @param array $consult
     * @return int
     */
    public function insert(array $data,array $consult=[],$isThrowException=true){
        try{
            $model = $this->getNewModel();
            if(!empty($model::CREATED_AT)){
                $consult[$model::CREATED_AT]= getCurrentDate('unix');
            }
            if(!empty($model::UPDATED_AT)){
                $consult[$model::UPDATED_AT]= getCurrentDate('unix');
            }
            array_walk($data,function(&$item) use ($consult) {
                $item=array_merge($item, $consult);
            });
            return $model->insert($data);
        }
        catch (\Throwable $e){
            if($isThrowException){
                throw $e;
            }
            else{
                Log::channel('library')->error('Dao Insert',['msg'=>$e->getMessage()]);
                return false;
            }
        }
    }

    /**
     * @param $id
     * @param bool $force
     * @return bool
     * @throws VerifyException
     */
    public function delete($id,bool $force=false){
        $modelObj = $this->get($id);
        if($force){
            return $modelObj->delete();
        }
        else{
            if(!empty($modelObj->delete_field)){
                $update = [$modelObj->delete_field=>$modelObj->delete_value];
                if(!empty($modelObj::DELETED_AT)){
                    $update[$modelObj::DELETED_AT] = getCurrentDate('unix');
                }
                $res = $modelObj->update($update);
                return $res?true:false;
            }
            else{
                return $modelObj->delete();
            }
        }
    }

    /**
     * 共享锁
     * @param array $params
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    public function sharedLock($params=[]){
        return $this->selector($params)->sharedLock();
    }

    /**
     * 排他锁
     * @param array $params
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    public function lockForUpdate($params=[]){
        return $this->selector($params)->lockForUpdate();
    }

    /**
     * 根据条件删除
     * @param array $params 查询参数
     * @param integer $force 是否物理删除
     * @return mixed|int
     */
    public function deleteAll(array $params,bool $force=false){
        $selector = $this->selector($params);
        if($force){
            return $selector->delete();
        }
        else{
            $delete_field = $this->getNewModel()->delete_field;
            $delete_value = $this->getNewModel()->delete_value;
            if(!empty($delete_field)){
                return $selector->update([$delete_field=>$delete_value]);
            }
            return false;
        }
    }

    /**
     * 修改多个对象数据
     * @param array $params 查询参数
     * @param array $data  修改的数据
     * @return mixed|int
     */
    public function updateAll(array $params,array $data){
        return $this->selector($params)->update($data);
    }

    /**
     * 获取一个对象
     * @param $pk
     */
    public function get($id,$columns = ['*']){
        return $this->find($id,$columns);
    }

    /**
     * 获取某一个字段
     * @param $id
     * @param $field
     * @param null $default
     * @return mixed|null
     */
    public function getValue($id,$field,$default=null){
        $obj = $this->get($id);
        return isset($obj[$field])?$obj[$field]:$default;
    }

    /**
     * 返回查询的字段值
     * @param string $column 制定查询字段
     * @param array $params 查询参数
     * @param array $orderBy  根据字段排序
     * @return mixed
     */
    public function value(string $column,array $params=[],array $orderBy=[]){
        return $this->selector($params, $orderBy)->value($column);
    }

    /**
     * 返回查询的字段数据
     * @param string $column 制定查询字段
     * @param array $params 查询参数
     * @param array $orderBy  根据字段排序
     * @return array
     */
    public function pluck(string $column,array $params=[],array $orderBy=[]){
        $size = 0;
        if(!empty($params['size']) && is_numeric($params['size'])){
            $size = (int)$params['size'];
            unset($params['size']);
        }
        $selector = $this->selector($params, $orderBy);
        if($size>0){
            $selector->take($size);
        }
        return $selector->pluck($column)->toArray();
    }

    /**
     * 统计数量
     * @param array $params 查询参数
     * @return int
     */
    public function count(array $params=[]){
        return $this->selector($params)->count();
    }

    /**
     * 数量求和
     * @param array $params 查询参数
     * @return int
     */
    public function sum($column,array $params=[]){
        return $this->selector($params)->sum($column);
    }

    /**
     * 查找一个对象
     * @param array $params 查询参数
     * @param array $orderBy  根据字段排序
     * @param array $fields  制定查询字段
     * @return Model
     */
    public function fetch(array $params=[],array $orderBy=[],array $fields=[]){
        return $this->selector($params, $orderBy, $fields)->first();
    }

    /**
     * 查找列表
     * @param array $params 查询参数
     * @param array $orderBy  根据字段排序
     * @param array $fields  制定查询字段
     * @return Collection
     */
    public function fetchAll(array $params=[],array $orderBy=[],array $fields=[]){
        $size = 0;
        if(!empty($params['size']) && is_numeric($params['size']) && $params['size']>0){
            $size = (int)$params['size'];
            unset($params['size']);
        }
        $selector = $this->selector($params, $orderBy, $fields);
        if($size>0){
            $selector->take($size);
        }
        return $selector->get();
    }

    /**
     * 查找对象列表构造器
     * @param array $params 查询参数
     * @param array $orderBy  根据字段排序
     * @param array $fields  制定查询字段
     * @return Builder
     */
    public function selector(array $params=[],array $orderBy=[],array $fields=[]){
        $model = $this->getNewModel();
        $selector = $model->newQuery();
        foreach($params as $key=>$v){
            $new_selector = $model->setSearchAttrName($selector,$key,$v);
            if(!empty($new_selector)){
                $selector = $new_selector;
            }
            elseif(!is_null($v) && $model->isFields($key)){
                $selector = $this->createCondition($selector,$key,$v);
            }
        }
        if(!empty($model->delete_field) && !isset($params[$model->delete_field])){
            $selector = $this->createCondition($selector,$model->delete_field,['gt',-1]);
        }
        if(!empty($orderBy)){
            foreach($orderBy as $key=>$val){
                $selector->orderBy($key,$val);
            }
        }
        if(!empty($fields)){
            $selector->select($fields);
        }
        return $selector;
    }

    /**
     * 分组查询
     * @param array $groupBy
     * @param array $params
     * @param array $orderBy
     * @return Builder|mixed
     */
    public function groupBySelector($groupBy=[],array $params=[],$orderBy=[]){
        $model = $this->getNewModel();
        $selector = $model->newQuery()->groupBy($groupBy);
        foreach($params as $key=>$v){
            $new_selector = $model->setSearchAttrName($selector,$key,$v);
            if(!empty($new_selector)){
                $selector = $new_selector;
            }
            elseif(!is_null($v) && $model->isFields($key)){
                $selector = $this->createCondition($selector,$key,$v);
            }
        }
        if(!empty($model->delete_field) && !isset($params[$model->delete_field])){
            $selector = $this->createCondition($selector,$model->delete_field,['gt',-1]);
        }
        if(!empty($orderBy)){
            foreach($orderBy as $key=>$val){
                $selector->orderBy($key,$val);
            }
        }
        if(!empty($fields)){
            $selector->select($fields);
        }
        return $selector;
    }

    /**
     * 时间查询作用域
     * @param Model $query
     * @param string $value
     * @param string $type unix,datetime
     * @return mixed
     */
    public function getSearchTimeAttr($value,$type='datetime')
    {
        $data = [];
        switch ($value) {
            case 'today'://今天
                if($type=='unix'){
                    $data = [Carbon::today()->startOfDay()->unix(), Carbon::today()->endOfDay()->unix()];
                }
                else{
                    $data = [Carbon::today()->startOfDay()->toDateTimeString(), Carbon::today()->endOfDay()->toDateTimeString()];
                }
                break;
            case 'week'://本周
                if($type=='unix') {
                    $data = [Carbon::today()->startOfWeek()->unix(), Carbon::today()->endOfWeek()->unix()];
                }
                else{
                    $data = [Carbon::today()->startOfWeek()->toDateTimeString(), Carbon::today()->endOfWeek()->toDateTimeString()];
                }
                break;
            case 'month'://本月
                if($type=='unix') {
                    $data = [Carbon::today()->startOfMonth()->unix(), Carbon::today()->endOfMonth()->unix()];
                }
                else{
                    $data = [Carbon::today()->startOfMonth()->toDateTimeString(), Carbon::today()->endOfMonth()->toDateTimeString()];
                }
                break;
            case 'year'://今年
                if($type=='unix') {
                    $data = [Carbon::today()->startOfYear()->unix(), Carbon::today()->endOfYear()->unix()];
                }
                else{
                    $data = [Carbon::today()->startOfYear()->toDateTimeString(), Carbon::today()->endOfYear()->toDateTimeString()];
                }
                break;
            case 'yesterday'://昨天
                if($type=='unix') {
                    $data = [strtotime('yesterday'), strtotime('today -1second')];
                }
                else{
                    $data = [date('Y-m-d H:i:s', strtotime('yesterday')), date('Y-m-d H:i:s', strtotime('today -1second'))];
                }
                break;
            case 'last year'://去年
                if($type=='unix') {
                    $data = [Carbon::today()->subYear()->unix(), Carbon::today()->subYear()->endOfYear()->unix()];
                }
                else{
                    $data = [Carbon::today()->subYear()->year, Carbon::today()->subYear()->endOfYear()->toDateTimeString()];
                }
                break;
            case 'last week'://上周
                if($type=='unix') {
                    $data = [Carbon::today()->subWeek()->startOfWeek()->unix(), Carbon::today()->subWeek()->endOfWeek()->unix()];
                }
                else{
                    $data = [Carbon::today()->subWeek()->startOfWeek()->toDateTimeString(), Carbon::today()->subWeek()->endOfWeek()->toDateTimeString()];
                }
                break;
            case 'last month'://上个月
                if($type=='unix') {
                    $data = [Carbon::today()->subMonth()->startOfMonth()->unix(), Carbon::today()->subMonth()->endOfMonth()->unix()];
                }
                else{
                    $data = [Carbon::today()->subMonth()->startOfMonth()->toDateTimeString(), Carbon::today()->subMonth()->endOfMonth()->toDateTimeString()];
                }
                break;
            case 'quarter'://本季度
                if($type=='unix') {
                    $data = [Carbon::today()->startOfQuarter()->unix(), Carbon::today()->endOfQuarter()->unix()];
                }
                else{
                    $data = [Carbon::today()->startOfQuarter()->toDateTimeString(), Carbon::today()->endOfQuarter()->toDateTimeString()];
                }
                break;
            case 'lately7'://近7天
                if($type=='unix') {
                    $data = [Carbon::today()->subDays(7)->unix(), Carbon::today()->unix()];
                }
                else{
                    $data = [Carbon::today()->subDays(7)->toDateTimeString(), Carbon::today()->toDateTimeString()];
                }
                break;
            case 'lately30':
                if($type=='unix') {
                    $data = [Carbon::today()->subDays(30)->unix(), Carbon::today()->unix()];
                }
                else{
                    $data = [Carbon::today()->subDays(30)->toDateTimeString(), Carbon::today()->toDateTimeString()];
                }
                break;
            default:
                $time = strtotime($value);
                if($time!==false){
                    $begin = date('Y-m-d',strtotime($value));
                    $end = date('Y-m-d',strtotime($value.' +1 days'));
                    if($type=='unix'){
                        $data  = [strtotime($begin),strtotime($end)];
                    }
                    else{
                        $data  = [$begin,$end];
                    }
                }
                elseif (false !== strstr($value, '-')) {
                    [$startTime, $endTime] = explode('-', $value);
                    $startTime = str_replace('/', '-', trim($startTime));
                    $endTime   = str_replace('/', '-', trim($endTime));
                    if ($startTime && $endTime && $startTime != $endTime) {
                        if($type=='unix'){
                            $data = [strtotime($startTime), strtotime($endTime)];
                        }
                        else{
                            $data = [$startTime, $endTime];
                        }
                    }
                    else if ($startTime && $endTime && $startTime == $endTime) {
                        if($type=='unix') {
                            $data = [strtotime($startTime), (strtotime($endTime) + 86400)];
                        }
                        else{
                            $data = [$startTime, date('Y-m-d H:i:s', strtotime($endTime) + 86400)];
                        }
                    }
                }
                elseif (preg_match('/^lately+[1-9]{1,3}/', $value)) {
                    //最近天数 lately[1-9] 任意天数
                    $day = (int)str_replace('lately', '', $value);
                    if($type=='unix') {
                        $data = [Carbon::today()->subDays($day)->unix(), Carbon::today()->unix()];
                    }
                    else{
                        $data = [Carbon::today()->subDays($day)->toDateTimeString(), Carbon::today()->toDateTimeString()];
                    }
                }
        }
        return $data;
    }

    /**
     * 构造条件
     * @param Builder $selector 构造器
     * @param string $key  字段
     * @param mixed $v  搜索数据
     * @return mixed
     * @throws VerifyException
     */
    public function createCondition(Builder $selector,$key,$v){
        if(!is_array($v)){
            $selector->where($key,$v);
        }
        else{
            $type = strtolower($v[0]);
            switch ($type) {
                case 'eq':      //等于（=）
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'=',$v[1],$boolean);
                    break;
                case 'neq':     //不等于（<>）
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'<>',$v[1],$boolean);
                    break;
                case 'gt':      //大于（>）
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'>',$v[1],$boolean);
                    break;
                case 'gte':     //大于等于（>=）
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'>=',$v[1],$boolean);
                    break;
                case 'lt':      //小于（<）
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'<',$v[1],$boolean);
                    break;
                case 'lte':     //小于等于（<=）
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'<=',$v[1],$boolean);
                    break;
                case "empty":
                    $boolean = empty($v[1])?'and':$v[1];
                    $selector->where($key,'=','',$boolean);
                    break;
                case "not_empty":
                    $boolean = empty($v[1])?'and':$v[1];
                    $selector->where($key,'!=','',$boolean);
                    break;
                case "null":
                    $boolean = empty($v[1])?'and':$v[1];
                    $selector->whereNull($key,$boolean);
                    break;
                case "not_null":
                    $boolean = empty($v[1])?'and':$v[1];
                    $selector->whereNotNull($key,$boolean);
                    break;
                case "like_right":
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'like','%'.$v[1],$boolean);
                    break;
                case "like_left":
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'like',$v[1].'%',$boolean);
                    break;
                case 'like':    //模糊查询
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'like','%'.$v[1].'%',$boolean);
                    break;
                case 'not_like':    //模糊查询
                    $boolean = empty($v[2])?'and':$v[2];
                    $selector->where($key,'not like','%'.$v[1].'%',$boolean);
                    break;
                case 'in':      //IN 查询
                    $boolean = empty($v[2])?'and':$v[2];
                    $values = (is_array($v[1])?$v[1]:explode(',', $v[1]));
                    $selector->whereIn($key,$values,$boolean);
                    break;
                case 'not_in':
                    $boolean = empty($v[2])?'and':$v[2];
                    $values = (is_array($v[1])?$v[1]:explode(',', $v[1]));
                    $selector->whereNotIn($key,$values,$boolean);
                    break;
                case 'between':
                    $boolean = empty($v[2])?'and':$v[2];
                    $values = (is_array($v[1])?$v[1]:explode(',', $v[1]));
                    $selector->whereBetween($key,$values,$boolean);
                    break;
                case 'not_between':
                    $boolean = empty($v[2])?'and':$v[2];
                    $values = (is_array($v[1])?$v[1]:explode(',', $v[1]));
                    $selector->whereNotBetween($key,$values,$boolean);
                    break;
                case 'date':
                    $boolean = empty($v[3])?'and':$v[3];
                    $type= empty($v[2])?'unix':$v[2];
                    $dateAry  = $this->getSearchTimeAttr($v[1],$type);
                    $selector->whereBetween($key,$dateAry,$boolean);
                    break;
                case 'has':
                    if(isset($v[3]) && $v[3]=='like'){
                        $selector->whereHas($v[1],function($query) use($key,$v){
                            $query->where($key,'like','%'.$v[2].'%');
                        });
                    }
                    else{
                        $selector->whereHas($v[1],function($query) use($key,$v){
                            $query->where($key,$v[2]);
                        });
                    }
                    break;
                case 'with':
                    if(isset($v[1]) && is_array($v[1])){
                        $selector->with($key,function($query) use($v){
                            return $query->select($v[1]);
                        });
                    }
                    else{
                        $selector->with($key);
                    }
                case 'when':
                    if(isset($v[1]) && is_array($v[1])) {
                        $selector->when(isset($v[1]) && is_array($v[1]), function ($query) use ($v) {
                            $query->where(...$v[1]);
                        });
                    }
                    break;
                case 'raw':
                    // 安全警告：raw查询仅在白名单字段中使用，避免SQL注入
                    if (is_string($v[1])) {
                        $selector->whereRaw($v[1]);
                    }
                    break;
                default:
                    if($key=='created_time'){
                        $selector->whereBetween($key,[strtotime($v[0]),strtotime($v[1])]);
                    }
                    else{
                        throw new VerifyException($key."传输格式不正确");
                    }
            }
        }
        return $selector;
    }

    /**
     * 分页搜索
     * @param array $params 查询参数
     * @param array $orderBy  根据字段排序
     * @param array $fields  制定查询字段
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $params=[],array $orderBy=[],array $fields = ['*']){
        $page = 1;
        if(!empty($params['page']) && is_numeric($params['page']) && $params['page']>0){
            $page = (int)$params['page'];
            unset($params['page']);
        }
        $size = 10;
        if(!empty($params['size']) && is_numeric($params['size']) && $params['size']>0){
            $size = (int)$params['size'];
            unset($params['size']);
            if($size>50){
                $size = 50;
            }
        }
        $selector = $this->selector($params,$orderBy,$fields);
        $paginate = $selector->paginate($size,$fields,'page',$page);
//        $paginate->render(\support\Response::init());
        return $paginate;
    }

    /**
     * 获取分页的数据
     * @param array $params 查询参数
     * @param array $orderBy  根据字段排序
     * @param array $fields  制定查询字段
     * @return array
     */
    public function paginateArray(array $params=[],array $orderBy=[],array $fields = ['*'])
    {
        $page = 1;
        if (!empty($params['page']) && is_numeric($params['page']) && $params['page'] > 1) {
            $page = (int)$params['page'];
            unset($params['page']);
        }
        $size = 10;
        if (!empty($params['size']) && is_numeric($params['size']) && $params['size'] > 0) {
            $size = (int)$params['size'];
            unset($params['size']);
            if ($size > 50) {
                $size = 50;
            }
        }
        $selector = $this->selector($params, $orderBy, $fields);
        $count = $selector->count();
        $offset = $page-1;
        if($offset>0){
            $offset = $offset * $size;
        }
        $data = $selector->offset($offset)->limit($size)->get()->toArray();
        $sum_page = ceil($count/$size);
        return ['page'=>$page,'size'=>$size,'count'=>$count,'total_page'=>$sum_page,'data'=>$data];
    }

    public function getTable()
    {
        return $this->getNewModel()->getTable();
    }

    /**
     * @return Model
     */
    public function getNewModel(){
        return new $this->model();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        try {
            if(empty($this->model)){
                throw new RunException("暂未找到Model类");
            }
            return $this->getNewModel()->{$name}(... $arguments);
        }
        catch (\Throwable $e) {
            Log::channel('library')->error('Dao Call',['func'=>$name,'msg'=>$e->getMessage()]);
            throw $e;
        }
    }
}
