<?php

namespace support\utils;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use support\extend\Log;
use support\extend\Redis;
use support\exception\VerifyException;

/**
 * 数据抓取类
 * @author Kevin
 */
class JwtToken {

    /**
     * 获取jwt token
     * @param array $payload jwt载荷
     * @payload iss (Issuer)：签发者
     * @payload sub (Subject)：主题
     * @payload aud (Audience)：接收者
     * @payload exp (Expiration time)：过期时间
     * @payload nbf (Not Before)：生效时间
     * @payload iat (Issued At)：签发时间
     * @payload jti (JWT ID)：编号
     * @payload int $expire 过期时间
     * @return bool|string
     */
    public static function getToken(array $payload=[],int $expire=0)
    {
        try{
            if($expire==0){
                $expire = config("app.jwt_expire");
            }
            $time = time();
            $snowflakeID = Random::getSnowflakeID();
            $token = sha1($snowflakeID);
            $iss = config("app.jwt_iss");
            $arr = [
                'iss'=>$iss,                   //该JWT的签发者
                'iat'=>$time,                  //签发时间
                'exp'=>$time+$expire,          //过期时间
//                'nbf'=>$time,                  //该时间之前不接收处理该Token
//                'sub'=>'login',                //主题
                'aud'=>'',                     //受众
                'jti'=>$token                  //用户唯一标识Token
            ];
            $payload = array_merge($arr,$payload);
            $key = config("app.jwt_key");
            $jwt = JWT::encode($payload, $key, 'HS256');
            Redis::hSet('jwtToken',$token,$jwt);
//            $cache_key = "jwtToken:".$token;
//            Redis::setEx($cache_key,$expire,$jwt);
            return $token;
        }
        catch (\Exception $e){
            Log::error($e->getMessage(),["type"=>"jwt_get_token"]);
            return false;
        }
    }

    /**
     * 获取Token数据
     * @param strint $token
     * @return object|string
     */
    public static function getJwtToken(string $token)
    {
//        $cache_key = "jwtToken:".$token;
//        $jwt = Redis::get($cache_key);
        return Redis::hGet('jwtToken',$token);
    }

    /**
     * @param $token
     * @return object|string
     */
    public static function getTokenJwtData($token)
    {
        $jwt = self::getJwtToken($token);
        if (!empty($jwt)) {
            return self::verifyToken($jwt);
        }
        return null;
    }

    /**
     * 验证jwt是否有效,默认验证exp,nbf,iat时间
     * @param string $jwt 需要验证的jwt
     * @return object|string
     */
    public static function verifyToken(string $jwt)
    {
        try{
            $key = config("app.jwt_key");
            return JWT::decode($jwt, new Key($key, 'HS256'));
        }
        catch (\Exception $e){
            Log::error($e->getMessage(),["type"=>"jwt_verify_token"]);
            throw $e;
        }
    }

    /**
     * 刷新Token
     * @param strint $token
     * @param array $payload 数据
     * @param int $expire 过期时间
     */
    public static function refreshToken(string $token,array $payload=[],int $expire=0)
    {
        try{
            $key = config("app.jwt_key");
            if($expire==0){
                $expire = config("app.jwt_expire");
            }
            $time = time();
            $arr = [
                'iss'=>'jwt_imbox',            //该JWT的签发者
                'iat'=>$time,                  //签发时间
                'exp'=>$time+$expire,          //过期时间
                'nbf'=>$time,                  //该时间之前不接收处理该Token
                'sub'=>'',                     //面向的用户
                'jti'=>$token                  //用户唯一标识Token
            ];
            $payload = array_merge($arr,$payload);
            $jwt = JWT::encode($payload, $key, 'HS256');
            Redis::hSet('jwtToken',$token,$jwt);
//            $cache_key = "jwtToken:".$token;
//            Redis::setEx($cache_key,$expire,$jwt);
            return $token;
        }
        catch (\Exception $e){
            Log::error($e->getMessage(),["type"=>"jwt_refresh_token"]);
            throw $e;
        }
    }

    /**
     * 删除Token
     * @param string $token
     */
    public static function deleteToken(string $token)
    {
//        $cache_key = "jwtToken:".$token;
//        return Redis::del($cache_key);
        return Redis::hDel('jwtToken',$token);
    }

    /**
     * 清除已经过期的token
     * @return void
     */
    public static function clearExpiredToken()
    {
        $rows = Redis::hGetAll('jwtToken');
        foreach($rows as $k=>$v){
            try{
                self::verifyToken($v);
            }
            catch (\Exception $e){
                if($e->getMessage()=='Expired token'){
                    self::deleteToken($k);
                }
            }
        }
    }
}
