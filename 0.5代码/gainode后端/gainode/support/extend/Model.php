<?php

namespace support\extend;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use library\dict\QueueDict;
use support\exception\VerifyException;
use support\Model as BaseModel;

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
 * @method Model create(array $attributes = [])
 * @method Model updateOrCreate(array $attributes, array $values = [])
 */
class Model extends BaseModel
{
    public $timestamps = true;
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    const DELETED_AT = 'deleted_time';
    protected $ListenChangeField = [];
    protected $keyType = 'int';
    protected $dateFormat = 'U';
    public $delete_field = 'status';
    public $delete_value = '-1';
    public $incrementing = true;
    protected $fillable = [];
    protected $attributes = [];
    protected $appends = [];
    public $fields = [];

    /**
     * 自动将 $fields 中的字段添加到 $fillable 白名单，防止 Mass Assignment
     */
    public function __construct(array $attributes = [])
    {
        if (!empty($this->fields) && empty($this->fillable)) {
            $this->fillable = $this->fields;
        }
        parent::__construct($attributes);
    }

    public function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s'); // 返回格式化的日期时间字符串
    }

    /**
     * 获取时间
     * @return String
     */
    public function getDateTime(string $field,$format='Y-m-d H:i:s'){
        if(is_numeric($field)){
            return date($format, $this->$field);
        }
        elseif(is_object($this->$field)){
            return $this->asDateTime($this->$field)->format($format);
        }
        return $this->$field;
    }

    /**
     * 判断是否属性
     * @param $key
     * @return bool
     */
    public function isFields($key)
    {
        return in_array($key,$this->fields);
    }

    /**
     * 设置特殊的查询字段
     * @param Builder $selector
     * @param $key
     * @param $value
     * @return void
     */
    public function setSearchAttrName(Builder $selector,$key,$value){
        if(is_null($value)){
            return null;
        }
        $arr = explode('_',$key);
        $name = 'search';
        foreach($arr as $str){
            $name.=ucfirst($str);
        }
        $name.='Attr';
        if(method_exists($this,$name)){
            return call_user_func([$this, $name], $selector,$value);
        }
        return null;
    }

    /**
     * 设置model的对应参数数据
     * @param array $rows
     * @return $this
     */
    public function setAttributes(array $rows) {
        if(!empty($rows)){
            foreach($rows as $key=>$v){
                if($this->isFields($key)){
                    if(is_numeric($v) || is_string($v) || !empty($v)){
                        if (is_array($v)) {
                            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                        } elseif (is_string($v)) {
                            $v = $this->sanitizeXss($v);
                        }
                        $this->setAttribute($key, $v);
                    }
                }
            }
        }
    }

    /**
     * XSS 过滤：移除危险标签和事件属性
     */
    private function sanitizeXss(string $value): string
    {
        $value = preg_replace(
            '/<\s*(script|iframe|object|embed|form|link|style|applet|meta|frame|bgsound|title|base)[^>]*>.*?<\s*\/\s*\1\s*>/is',
            '', $value
        );
        $value = preg_replace(
            '/<\s*(script|iframe|object|embed|link|meta)[^>]*\/?\s*>/is',
            '', $value
        );
        $value = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/is', '', $value);
        $value = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/is', '', $value);
        $value = preg_replace('/javascript\s*:/is', '', $value);
        $value = preg_replace('/vbscript\s*:/is', '', $value);
        return trim($value);
    }

    /**
     * 获取修改的数据
     */
    public function getChangeData(){
        $change = $this->getDirty();
        $change = array_filter($change);
        if(!empty($change)){
            foreach($change as $key=>$v){
                if(!in_array($key,$this->ListenChangeField)){
                    unset($change[$key]);
                }
            }
            return $change;
        }
        return false;
    }

    /**
     * 写修改日志
     */
    private function writeChangeLogs($options){
        $change = $this->getChangeData();
        $primary_id = $this[$this->primaryKey];
        if(!empty($change) && !empty($primary_id)){
            $this->setAttributes($options);
            $original = [];
            foreach($change as $k=>$v){
                $original[$k] = $this->getOriginal($k);
            }
            $logsData = [
                'change_table'=>$this->table,
                'primary_id'=>$primary_id,
                'original'=>$original,
                'change'=>$change
            ];
            Log::channel('change_logs')->info('model变动日志',$logsData);
//            pushQueue(QueueDict::QUEUE_MODEL_CHANGE,$logsData);
        }
    }

    /**
     * 对象直接修改
     * @param array $attributes
     * @param array $options
     * @return Model
     */
    public function update(array $attributes = [], array $options = []){
        $this->setAttributes($attributes);
        if(!empty($this->ListenChangeField)){
            $this->writeChangeLogs($options);
        }
        $this->save();
        return $this;
    }

    public function saveData(array $attributes = [], array $options = []){
        $this->setAttributes($attributes);
        if(!empty($this->ListenChangeField)){
            $this->writeChangeLogs($options);
        }
        return $this->save();
    }

    /**
     * model某字段自增
     * @param string $field 字段
     * @param int $num  值
     * @return $this
     */
    public function increase(string $field,float $num=1){
        if($this->isFields($field)){
            $this->$field+=$num;
        }
        return $this;
    }

    /**
     * model某字段自减
     * @param string $field 字段
     * @param int $num  值
     */
    public function decrease(string $field,float $num=1){
        if($this->isFields($field)) {
            if ($this->$field < $num) {
                throw new VerifyException($this->table . ' ' . $field . '(' . $this->$field . ') less than ' . $num);
            }
            $this->$field -= $num;
        }
        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->newQuery()->{$name}(... $arguments);
    }
}
