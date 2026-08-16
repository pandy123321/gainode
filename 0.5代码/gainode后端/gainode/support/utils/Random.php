<?php

namespace support\utils;

use Ramsey\Uuid\Uuid;
use support\Request;

/**
 * 随机数处理函数集合
 * @author Kevin
 */
class Random {

    /**
     * 在指定数据范围内实现随机字符组合
     * @param int $length
     * @param int $type
     * @return string
     */
    public static function getRandStr($length = 0, $type = 0) {
        $range = array(
            0 => '0123456789',
            1 => 'abcdefghijklmnopqrstuvwxyz',
            2 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            3 => '0123456789abcdefghijklmnopqrstuvwxyz',
            4 => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            5 => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            6 => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            7 => '!@#$%^&*-+?;0123456789abcdefghijklmnopqrstuvwxyz',
            8 => '!@#$%^&*-+?;0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            9 => '!@#$%^&*-+?;0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        );
        if (false === array_key_exists($type, $range)) {
            $type = 6;
        }
        $character = '';
        $maxLength = strlen($range[$type]) - 1;
        for ($i = 0; $i < $length; ++$i) {
            $character .= $range[$type][mt_rand(0, $maxLength)];
        }
        return $character;
    }

    /**
     * 获取全球唯一ID
     * @return string
     */
    public static function guid(Request $request,$u = '') {
        $tmp = rand(1000, 9999) . rand(1000, 9999) . rand(1000, 9999) . rand(100, 999) . rand(100, 999);
        $time = str_split(microtime(true) * 10000);
        $str = '';
        for ($i = 0; $i < 13; ++$i) {
            $str .= $time[$i] . self::getRandStr(1, 6);
        }
        $guid = sha1($u . '-' . $tmp . ':' . $str);
        $request->session()->set('guid',$guid);
        return $guid;
    }

    /**
     * 获取UUID
     * @return string
     */
    public static function getUuidStr(){
         return Uuid::uuid4()->toString();
    }

    /**
     * 获取雪花算法对象
     * @param int|null $datacenter
     * @param int|null $workerid
     * @return \Godruoyi\Snowflake\Snowflake
     */
    private static function getSnowflakeObj(int $datacenter = null, int $workerid = null){
        static $instance = null;
        if(empty($instance)){
            $instance = new \Godruoyi\Snowflake\Snowflake($datacenter,$workerid);
        }
        return $instance;
    }

    /**
     * 获取雪花算法ID
     * @param int|null $datacenter
     * @param int|null $workerid
     * @return string
     */
    public static function getSnowflakeID(int $datacenter = null, int $workerid = null){
        return self::getSnowflakeObj($datacenter,$workerid)->id();
    }

    /**
     * 获取用户token
     * @param $userid 用户ID
     * @param $secret 密钥
     * @return string
     */
    public static function createDomainToken($userid,$secret='') {
        $useridEncrypt = sha1($userid);
        return hash('sha256', $useridEncrypt .$secret);
    }


    /**
     * 检测跨域票券 - 真实性
     * @param $userid 用户ID
     * @param $secret 密码
     * @param $token token
     * @return boolean
     */
    public static function checkDomainToken($userid,$secret,$token) {
        if (self::createDomainToken($userid,$secret) === $token) {
            return true;
        }
        return false;
    }

    /**
     * 获取邮件激活码
     * @param string $userid
     * @return string
     */
    public static function getEmailActivationCode(Request $request,$userid) {
        return self::guid($request,$userid);
    }

    /**
     * 获取密码 - 找回密码
     * @return string
     */
    public static function getPwdRandom($type = 6) {
        return self::getRandStr(6, $type);
    }
}
