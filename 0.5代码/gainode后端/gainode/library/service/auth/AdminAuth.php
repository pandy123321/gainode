<?php

namespace library\service\auth;

use library\dict\ErrorDict;
use library\model\sys\AdminAuthModel;
use library\model\sys\AdminModel;
use library\service\sys\AdminAuthService;
use library\service\sys\AdminLogsService;
use library\service\sys\AdminService;
use support\exception\VerifyException;
use support\exception\AuthorizeException;
use support\extend\Cache;
use support\utils\Http;
use support\utils\JwtToken;
use Webman\Event\Event;

/**
 * 后台认证逻辑层
 * @author Kevin
 */
class AdminAuth extends AuthAbstract
{
    public $guard = 'admin';

    /**
     * @var AdminAuthModel
     */
    private $userAuthObj;

    /**
     * @param $data {account,password,vcode}
     * @return AdminModel
     * @throws VerifyException
     */
    protected function verifyLogin(array $data=[]){
        if(empty($data['vcode']) || strtolower($data['vcode'])!=session('captcha')){
            throw new VerifyException("输入的验证码不正确",ErrorDict::LoginVerifyError);
        }
        $this->request->session()->delete('captcha');
        $lock_key = md5($this->guard.'_login_lock_'.$data['account']);
        $is_lock = Cache::get($lock_key);
        if(!empty($is_lock)){
            throw new VerifyException('账号或密码错误次数太多，清稍后尝试',ErrorDict::LoginVerifyError);
        }
        $userService = new AdminService();
        $userObj = $userService->getUserByAccount($data['account']);
        if (!empty($userObj)){
            if ($userObj->password!=hashPassword($data['password'],$userObj->encrypt,true)) {
                $this->createLoginLogs($data['account'],'login','密码输入错误');
                $this->loginFailure($userObj->account);
            }
            elseif ($userObj->account!='administrator') {
                if($userObj->status == -1){
                    throw new VerifyException('帐号已删除');
                }
                elseif($userObj->status == 0){
                    throw new VerifyException('帐号已锁定');
                }
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
            $this->userAuthObj = $this->setUserAuth($userObj);
            if(empty($this->userAuthObj)){
                throw new VerifyException('授权失败',ErrorDict::SQLExecutionFailed);
            }
            $logsObj = $this->createLoginLogs($userObj->account,'login','登陆授权成功');
            if(empty($logsObj)){
                throw new VerifyException('创建登录日志失败',ErrorDict::SQLExecutionFailed);
            }
            $userObj->saveData([
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
     * 登录
     * @param $data {account,password,vcode}
     * @throws VerifyException
     * @throws VerifyException
     */
    public function codeLogin($account,$vcode,$type='mobile') {
        try{
            $adminService = new AdminService();
            $userObj = $adminService->getUserByAccount($account);
            if(empty($userObj)){
                throw new VerifyException('账号不存在');
            }
            if(!verifyCodeMsg($account,$vcode,$type)){
                throw new VerifyException('验证码错误');
            }
            return $this->createLoginAuth($userObj);
        }
        catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 创建登录授权
     * @param AdminModel $userObj
     * @return array
     * @throws VerifyException
     */
    public function createLoginAuth(AdminModel $userObj){
        try{
            $this->userAuthObj = $this->setUserAuth($userObj);
            if(empty($this->userAuthObj)){
                throw new VerifyException('授权失败',ErrorDict::SQLExecutionFailed);
            }
            $logsObj = $this->createLoginLogs($userObj->account,'login','登陆授权成功');
            if(empty($logsObj)){
                throw new VerifyException('创建登录日志失败',ErrorDict::SQLExecutionFailed);
            }
            $userObj->saveData([
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
    public function setUserAuth(AdminModel $userObj){
        $http = Http::getInstance($this->request);
        $expire_time = config('app.jwt_expire');
        $refresh_exp = config('app.jwt_refresh_expire');
        $access_token = $this->createUserToken($userObj->id,$userObj->account,$expire_time);
        $data = [
            'eid'=>getEid(),
            'admin_id'=>$userObj['id'],
            'terminal'=>$this->client_type,
            'token_type'=>'Bearer',
            'access_token'=>$access_token,
            'refresh_token'=>$this->createToken(['id'=>$userObj->id,'account'=>$userObj->account]),
            'client_ip'=>$http->getClientIP(),
            'expires_in'=>$refresh_exp,
            'expired_time'=>(time()+$expire_time),
            'status'=>1
        ];
        $authService = new AdminAuthService();
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
        $admin_id = 0;
        if(!empty($this->userAuthObj)){
            $eid = $this->userAuthObj->eid;
            $token = $this->userAuthObj->access_token;
            $admin_id = $this->userAuthObj->admin_id;
        }
        $data = [
            'eid'=>$eid,
            'admin_id'=>$admin_id,
            'account'=>$account,
            'token'=>$token,
            "action"=>$action,
            'os'=>$http->getClientOS('os'),
            'browser'=>$http->getBrowser('browser'),
            'client_ip'=>$http->getClientIP(),
            'descr'=>$descr
        ];
        $logsService = new AdminLogsService();
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
            $userAuthService = new AdminAuthService();
            $this->userAuthObj = $userAuthService->fetch(['admin_id'=>$userid,'refresh_token'=>$refresh_token]);
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
     * @param $data
     */
    public function register($data){
        $data['client_ip'] = Http::getInstance()->getClientIP();
        $adminService = new AdminService();
        return $adminService->createUser($data);
    }

    /**
     * 根据token获取用户ID
     * @return AdminModel
     */
    public function getUserByToken($token){
        $userService = new AdminService();
        $cache = JwtToken::getTokenJwtData($token);
        if(!empty($cache)){
            return $userService->getUserById($cache->aud);
        }
        else{
            $authService = new AdminAuthService();
            $this->userAuthObj = $authService->getUserLoginAuth($token);
            if(empty($this->userAuthObj)){
                throw new AuthorizeException('Token已过期');
            }
            return $userService->getUserById($this->userAuthObj->admin_id);
        }
        return null;
    }

    /**
     * 删除用户token
     * @param int $userid 用户ID
     */
    public function deleteUserToken(int $userid){
        try{
            $authService = new AdminAuthService();
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
                 $authService = new AdminAuthService();
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
             return true;
         }
         catch (\Exception $e) {
             throw $e;
         }
    }
}
