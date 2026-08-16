<?php

namespace library\service\auth;

use library\dict\ErrorDict;
use library\model\member\UserAuthModel;
use library\model\member\UserModel;
use library\service\member\UserAuthService;
use library\service\member\UserLogsService;
use library\service\member\UserService;
use support\exception\VerifyException;
use support\exception\AuthorizeException;
use support\utils\Http;
use support\utils\JwtToken;
use Webman\Event\Event;

/**
 * 用户认证逻辑层
 * @author Kevin
 */
class MemberAuth extends AuthAbstract
{
    public $guard = 'member';

    /**
     * @var UserAuthModel
     */
    private $userAuthObj;

    /**
     * @param $data {account,password,vcode}
     * @return UserModel
     * @throws VerifyException
     */
    protected function verifyLogin(array $data=[]){
        $userService = new UserService();
        $userObj = $userService->getUserByAccount($data['account']);
        if (!empty($userObj)){
            if ($userObj->password!=hashPassword($data['password'],$userObj->encrypt,$data['vcode']==0?false:true)) {
                $this->createLoginLogs($data['account'],'login','密码输入错误');
                $this->loginFailure($userObj->account);
            }
            if($userObj->status == -1){
                throw new VerifyException('帐号已删除');
            }
            elseif($userObj->status == 0){
                throw new VerifyException('帐号已锁定');
            }
        }
        else {
            $this->createLoginLogs($data['account'],'login','账号不存在');
            throw new VerifyException('账户名或密码错误',ErrorDict::LoginVerifyError);
        }
        return $userObj;
    }

    /**
     * 登录
     * @param $data {account,password,vcode}
     * @throws VerifyException
     * @throws VerifyException
     */
    public function login(array $data) {
        try{
            $userObj = $this->verifyLogin($data);
            return $this->createLoginAuth($userObj);
        }
        catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 登录
     * @param $data {account,password,vcode}
     * @throws VerifyException
     * @throws VerifyException
     */
    public function codeLogin($account,$vcode,$type='email',$invite_code=null,$source='login') {
        try{
            $adminService = new UserService();
            $userObj = $adminService->getUserByAccount($account);
            if(!verifyCodeMsg($account,$vcode,$type)){
                throw new VerifyException('验证码错误');
            }
            if(empty($userObj)){
                if($source=='login'){
                    throw new \Exception("请先去注册");
                }
                elseif(empty($invite_code)){
                    throw new VerifyException('请填写邀请码');
                }
                $userObj = $this->register([
                    'account'=>$account,
                    'password'=>null,
                    'account_type'=>$type,
                    'invite_code'=>$invite_code
                ]);
            }
            return $this->createLoginAuth($userObj);
        }
        catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 创建登录授权
     * @param UserModel $userObj
     * @return array
     * @throws VerifyException
     */
    public function createLoginAuth(UserModel $userObj){
        try{
            $this->userAuthObj = $this->setUserAuth($userObj);
            if(empty($this->userAuthObj)){
                throw new VerifyException('授权失败',ErrorDict::SQLExecutionFailed);
            }
            $logsObj = $this->createLoginLogs($userObj->account,'login','登陆授权成功');
            if(empty($logsObj)){
                throw new VerifyException('创建登录日志失败',ErrorDict::SQLExecutionFailed);
            }
            $userObj->update([
                'login_cnt'=>($userObj->login_cnt+1),
                'login_time'=>getCurrentDate('unix'),
                'login_ip'=>$this->userAuthObj->client_ip,
            ]);
            Event::emit('user.login',$userObj);
            return $this->userAuthObj->toM();
        }
        catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 添加授权记录
     * @param array $data 授权的数据
     */
    public function setUserAuth(UserModel $userObj){
        $http = Http::getInstance($this->request);
        $expire_time = config('app.jwt_expire');
        $refresh_exp = config('app.jwt_refresh_expire');
        $access_token = $this->createUserToken($userObj->id,$userObj->account,$expire_time);
        $data = [
            'eid'=>getEid(),
            'user_id'=>$userObj['id'],
            'terminal'=>$this->client_type,
            'token_type'=>'Bearer',
            'access_token'=>$access_token,
            'refresh_token'=>$this->createToken(['id'=>$userObj->id,'account'=>$userObj->account]),
            'client_ip'=>$http->getClientIP(),
            'expires_in'=>$refresh_exp,
            'expired_time'=>(time()+$expire_time),
            'status'=>1
        ];
        $authService = new UserAuthService();
        if($userObj->is_multiple_login){
            $this->userAuthObj = $authService->getUserTerminalAuth($userObj['id'],$this->client_type);
        }
        else{
            $this->userAuthObj = $authService->getUserTerminalAuth($userObj['id']);
        }
        if(!empty($this->userAuthObj)){
            JwtToken::deleteToken($this->userAuthObj->access_token);   //挤出登录状态
            $this->userAuthObj->update($data);
        }
        else{
            $this->userAuthObj = $authService->create($data);
        }
        return $this->userAuthObj;
    }



    /**
     * 添加登陆日志
     * @param string $userAuthObj 授权对象
     * @param string $action 行为
     */
    private function createLoginLogs($account,$action,$descr=null){
        $http = Http::getInstance($this->request);
        $eid = 0;
        $token = null;
        $user_id = 0;
        if(!empty($this->userAuthObj)){
            $eid = $this->userAuthObj->eid;
            $token = $this->userAuthObj->access_token;
            $user_id = $this->userAuthObj->user_id;
        }
        $data = [
            'eid'=>$eid,
            'user_id'=>$user_id,
            'account'=>$account,
            'token'=>$token,
            "action"=>$action,
            'os'=>$http->getClientOS('os'),
            'browser'=>$http->getBrowser('browser'),
            'client_ip'=>$http->getClientIP(),
            'descr'=>$descr
        ];
        $logsService = new UserLogsService();
        return $logsService->create($data);
    }

    /**
     * 更新用户token
     * @param int $userid 用户ID
     * @param string $refresh_token 刷新 token
     */
    public function refreshUserToken(int $userid,string $refresh_token,int $expire_time=0){
        try{
            if(empty($expire_time)){
                $expire_time = config('app.jwt_expire');
            }
            $userAuthService = new UserAuthService();
            $this->userAuthObj = $userAuthService->fetch(['user_id'=>$userid,'refresh_token'=>$refresh_token]);
            if(!empty($this->userAuthObj) && ($this->userAuthObj['expires_in']+$this->userAuthObj['expired_time'])>time()){
                return $this->userAuthObj->update(['expired_time'=>(time()+$expire_time)]);
            }
            return false;
        }
        catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 账号注册
     * @param $data {account,password,vcode,invite_code,account_type}
     * @return UserModel
     */
    public function register($data){
        if(isset($data['vcode']) && !verifyCodeMsg($data['account'],$data['vcode'],$data['account_type'])){
            throw new VerifyException('验证码错误');
        }
        $data['client_ip'] = Http::getInstance()->getClientIP();
        $userService = new UserService();
        return $userService->createUser($data);
    }

    /**
     * 根据token获取用户ID
     * @return UserModel
     */
    public function getUserByToken($token){
        $userService = new UserService();
        $cache = JwtToken::getTokenJwtData($token);
        if(!empty($cache)){
            return $userService->getUserById($cache->aud);
        }
        else{
            $authService = new UserAuthService();
            $this->userAuthObj = $authService->getUserLoginAuth($token);
            if(empty($this->userAuthObj)){
                throw new AuthorizeException('Token已过期3');
            }
            return $userService->getUserById($this->userAuthObj->user_id);
        }
        return null;
    }

    /**
     * 删除用户token
     * @param int $userid 用户ID
     */
    public function deleteUserToken(int $userid){
        try{
            $authService = new UserAuthService();
            $authUserList = $authService->getUserAuthList($userid);
            foreach($authUserList as $v){
                JwtToken::deleteToken($v['access_token']);
                $v->update(['status'=>-1]);
            }
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 退出登录
     */
    public function logout($token) {
         try{
             $jwtData = JwtToken::getTokenJwtData($token);
             if(!empty($jwtData)){
                 $authService = new UserAuthService();
                 $this->userAuthObj = $authService->getUserLoginAuth($token);
                 if(empty($this->userAuthObj)){
                     throw new VerifyException('Token已过期');
                 }
                 $this->userAuthObj->update(['status'=>0]);
                 $this->createLoginLogs($jwtData->account,'logout','退出登陆成功');
                 JwtToken::deleteToken($token);
             }
             else{
                 throw new VerifyException('已经退出登录');
             }
             Event::emit('user.logout',$token);
             return true;
         }
         catch (\Exception $e) {
             throw $e;
         }
    }
}
