<?php

namespace support\utils;

use Carbon\Carbon;
use support\exception\VerifyException;

/**
 * 数据处理
 * @author Kevin
 */
final class Data {

    /**
     * 获取数字编码
     * @param string $domain 主域名
     * @return string
     */
    private static function getBase62String($domain='default')
    {
        $data = [
            'default'=> '3rO5Hhlgq4f7KXMJD9YsRp1yoS6nVuBPcwQt2zvLIiUCFANkmWx0abTeG8ZdjE',
            'main'=> 'Idrfo0XeAmci58aRlWZpzvnNVwkYGj9CTQLh1F4xES7tBHUgqJPDOb26syK3uM',
        ];
        return isset($data[$domain])?$data[$domain]:$data['default'];
    }

    /**
     * @param integer $num 数字
     * @param string $domain 主域名
     * @return string
     */
    public static function base10To62($num,$domain = 'default')
    {
        $str_shuffle_62 = self::getBase62String($domain);
        $res = '';
        while ($num > 0) {
            $res = substr($str_shuffle_62, $num % 62, 1) . $res;
            $num = floor($num / 62);
        }
        return $res;
    }

    public static function verifyPassword($password,$pass_hash){
        return password_verify($password,$pass_hash);
    }

    /**
     * 密码加密
     * @param $password
     * @return string|null
     * @throws VerifyException
     */
    public static function hashPassword($password): ?string
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);
        if ($hash === false) {
            throw new \Exception('不支持Bcrypt哈希');
        }
        return $hash;
    }

    /**
     * 根据经验获取用户等级
     * @param float $exp 经验值
     * @return int
     */

    public static function getLevelByExp($exp) {
        $num = ($exp/100)+0.25;
        $num = floor(sqrt($num)-0.5);
        $num += 1;
        return $num;
    }

    /**
     * 根据等级获取经验值
     * @param int $level 等级
     * @return int
     */
    public static function getExpByLevel($level) {
        $level--;
        return ($level * $level + $level) *100;
    }

    /**
     * 对数据进行签名
     * @param array $params
     */
    public static function sign(array $params){
        $params = array_filter($params);
        if(isset($params["Sign"]) && !empty($params["Sign"])){
            unset($params["Sign"]);
        }
        //加密私钥
        $private_key = config('app.sign_private_key');
        ksort($params);
        $str='';
        foreach($params as $k=>$v){
            $str.=$k.'='.$v.'&';
        }
        $str.='Key='.$private_key;
        return strtoupper(md5($str));
//        $json = json_encode($params);
//        return hash_hmac('sha256',$json,$private_key, false);
    }

    /**
     * 验证是否指定字符开头
     * @param string|array $needles
     */
    public static function startsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ('' !== $needle && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * 字符串转换成数组
     * @param $string
     * @param $delimiter
     * @return array
     */
    public static function strToArray($string, $delimiter = PHP_EOL) {

        $items = explode($delimiter, $string);
        foreach ($items as $key => &$item) {
            // 查找 # 的位置
            $pos = strpos($item, '#');
            if ($pos === 0) {
                // # 开头, 整行注释
                unset($items[$key]);
                // 直接到下一个
                continue;
            }
            if ($pos > 0) {
                // # 在中间, 后面一段注释
                $item = substr($item, 0, $pos);
            }
            $item = trim($item);
            if (empty($item)) {
                unset($items[$key]);
            }
        }
        return $items;
    }

    /**
     * 字符串截取
     * @param string $str 需要截取的字符串
     * @param int $len 截取长度
     * @return string
     */
    public static function cutStr($str, $len = 0, $append = true, $encode = 'utf8') {
        if (mb_strlen($str, $encode) < $len) {
            return $str;
        } else {
            return mb_substr($str, 0, $len, 'utf8') . (($append) ? '...' : '');
        }
    }

    /**
     * 对象集合转换成无键数组
     * @param array|object $iterator 对象集合
     * @param string $field 对象字段
     * @return array
     */
    public static function toFlatArray($iterator, $field = null) {
        $field = $field ? $field : 'id';
        $rs = [];
        foreach ($iterator as $t) {
            if(isset($t[$field]) || isset($t->$field)){
                $rs[] = is_array($t) ? $t[$field] : $t->$field;
            }
        }
        return empty($rs)?[]:array_unique($rs);
    }

    /**
     * 对象集合转换成字符串
     * @param array|object $iterator 对象集合
     * @param string $field 对象字段
     * @param string $connector 连接的符号
     * @return string
     */
    public static function toFlatString($iterator, $field = null, $connector = ',') {
        $data = self::toFlatArray($iterator, $field);
        return empty($data) ? '' : implode($connector, $data);
    }

    /**
     * 对象集合转换成有键数组
     * @param mixed $iterator 对象集合
     * @param string $fieldKey 对象字段 数组键
     * @param string $fieldKey 对象字段 数组值
     * @return array
     */
    public static function toKVArray($iterator, $fieldKey, $fieldVal=null) {
        $rs = [];
        foreach ($iterator as $t) {
            $k = is_array($t) ? $t[$fieldKey] : $t->$fieldKey;
            if(!empty($fieldVal)){
                $v = is_array($t) ? $t[$fieldVal] : $t->$fieldVal;
            }
            else{
                $v = $t;
            }
            $rs[$k] = $v;
        }
        return $rs;
    }

    /**
     * 用户自定义排序 - $array数组值是引用传递
     * @param array &$array 需要排序的数组
     * @param string $field 需要使用排序的key
     * @param string $order asc 升序 desc 降序
     */
    public static function usort(&$array, $field, $sort = 'asc') {
//        $sort_names = array_column($array,$field);
//        if($sort=='asc'){
//            array_multisort($sort_names,SORT_ASC,$array);
//        }
//        else{
//            array_multisort($sort_names,SORT_DESC,$array);
//        }
        if ($sort == 'asc') {
            usort($array, function($a, $b)use($field) {
                $al = $a[$field];
                $bl = $b[$field];
                if ($al == $bl) {
                    return 0;
                }
                return ($al > $bl) ? +1 : -1;
            });
        } else {
            usort($array, function($a, $b)use($field) {
                $al = $a[$field];
                $bl = $b[$field];
                if ($al == $bl) {
                    return 0;
                }
                return ($al > $bl) ? -1 : +1;
            });
        }
    }

    /**
     * 对一个数组中的某个字段求和
     * @param $data
     * @param $field
     */
    public static function getArraySum($array,$field){
        $num = 0;
        foreach($array as $v){
            if(isset($v[$field])){
                $num+=$v[$field];
            }
        }
        return $num;
    }

    /**
     * Two dimensional array sort
     * 二维数组排序
     * @param array $arrays 需要排序的数组
     * @param string $sort_key 按照key排序
     * @param int $sort_order   升序/降序
     * @param int $sort_type    排序的类型
     * @return array|bool
     */
    public static function sortTwoDimensionalArray($arrays, $sort_key, $sort_order = SORT_DESC, $sort_type = SORT_NUMERIC) {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        return $arrays;
    }

    /**
     * 把数组中某个value 作为key {当key有重复,后面覆盖前面}
     * @param mixed $array
     * @param string $key
     */
    public static function toKeyArray($array, $key) {
        $result = [];
        foreach ($array as $v) {
            $result[$v[$key]] = $v;
        }
        return $result;
    }

    /**
     * 获取数组中的级别
     * @param array $data 数据
     * @return int
     */
    public static function getArrayTreeList(array $list,$pk='id',$pid='pid',$child='children',$root=0) {
        // 创建Tree
        $tree = [];
        if (is_array($list)) {
            $refer = [];
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = & $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存bai在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] = & $list[$key];
                }
                else {
                    if (isset($refer[$parentId])) {
                        $parent = & $refer[$parentId];
                        $parent[$child][] = & $list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 获取数组中的级别
     * @return array $data 数据
     */
    public static $zoomAry=[];
    public static function getArrayZoomList(array $list,$name,$pk='id',$pid = 0, $level = 0){
        foreach ($list as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
            if ($value['pid'] == $pid){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['level'] = $level;
                //把数组放到list中
                $value[$name] = str_pad($value[$name], (strlen($value[$name]) + $level * 2), '--', STR_PAD_LEFT);
                self::$zoomAry[$value[$pk]] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($list[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                self::getArrayZoomList($list,$name,$pk, $value[$pk],$level+1);
            }
        }
        return self::$zoomAry;
    }

    public static function generateUniqueNumbers($count, $length)
    {
        $result = [];
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        while(count($result) < $count){
            $num = random_int($min, $max);
            $result[$num] = true;
        }
        return array_keys($result);
    }

    /**
     * 随机红包算法
     * 二倍均值算法
     */
    public static function randomPackets(float $amount,int $count ){
        $result=[];
        $remain=$amount;
        for($i=0;$i<$count-1;$i++){
            $min=0.01;
            $max= ($remain/$count)*2;
            if($max<$min){
                $max=$min;
            }
            $money= mt_rand($min*100,$max*100)/100;
            $money= min( $money,$remain-$min*($count-$i-1));
            $money= round($money,2);
            $result[]=$money;
            $remain=  round($remain-$money,2);
        }
        //最后一个补余额
        $result[]= round($remain,2);
        //打乱顺序
        shuffle($result);
        return $result;
    }

    /**
     * 固定红包
     */
    public static function fixedPackets(float $amount,int $count){
        $money= round($amount/$count,2);
        $result=[];
        for($i=0;$i<$count;$i++){
            $result[]=$money;
        }
        /**
         * 处理分钱误差
         */
        $diff= round($amount-array_sum($result),2);
        $result[0]+=$diff;
        return $result;
    }
}
