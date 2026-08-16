<?php

namespace library\service\auth;

use library\dict\ErrorDict;
use support\exception\VerifyException;
use support\extend\Cache;
use support\Request;
use support\utils\JwtToken;
use support\utils\Random;

/**
 * 用户认证逻辑层
 * @author Kevin
 */
abstract class AuthAbstract
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var string
     */
    protected $client_type='web';

    /**
     * @var string
     */
    protected $guard = 'admin';

    public function __construct(Request $request=null)
    {
        if(!empty($request)){
            $this->request = $request;
            $this->guard = $request->getPlatformType();
            $this->client_type = $request->getTerminalType();
        }
    }

    /**
     * 登录尝试次数限制
     * @param $account
     * @param int $number
     * @param int $n
     */
    public function loginFailure($account,$number = 5,$n = 3)
    {
        $fail_key = md5($this->guard.'_login_fail_'.$account);
        $numb = Cache::get($fail_key) ?? 0;
        $numb++;
        if($numb >= $number){
            $lock_key = md5($this->guard.'_login_lock_'.$account);
            Cache::set($lock_key,1,15*60);
            throw new VerifyException('账号或密码错误次数太多，清稍后尝试');
        }
        else{
            Cache::set($fail_key,$numb,5*60);
            $msg = '账号或密码错误！';
            $_n = $number - $numb;
            if($_n <= $n){
                $msg .= ',还可尝试'.$_n.'次';
            }
            throw new \Exception($msg,ErrorDict::LoginVerifyError);
        }
    }

    /**
     * 创建token
     * @return string
     */
    protected function createToken(array $data=[],int $expire=0){
        if(!empty($data)){
            return JwtToken::getToken($data,$expire);
        }
        else{
            $snowflakeID = Random::getSnowflakeID();
            return sha1($snowflakeID);
        }
    }

    /**
     * 创建用户的token
     * @param object $userObj 用户数据
     * @param int $expire_time 过期时间
     * @return object {token_type,expires_in,refresh_expires_in,access_token,refresh_token,token,client_type}
     */
    public function createUserToken($userid,$account,int $expire_time=0){
        return $this->createToken([
            'aud'=>$userid,
            'eid'=>getEid(),
            'account'=>$account,
            'guard'=>$this->guard
        ],$expire_time);
    }
    abstract public function login(array $data);
    abstract public function codeLogin($account,$vcode,$type='mobile');
    abstract public function refreshUserToken(int $userid,string $refresh_token,int $expire_time=0);

    abstract public function register($data);

    abstract public function getUserByToken($token);

    abstract public function deleteUserToken(int $userid);

}
