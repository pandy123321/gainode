<?php

namespace app\api\controller;

use library\service\auth\MemberAuth;
use library\service\member\UserService;
use library\validator\LoginValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 登录与注册管理
 */
class LoginController extends Api
{

    public function __construct()
    {
        $this->validation = new LoginValidation();
        parent::__construct();
    }

    /**
     * 登录
     * @method POST
     * @url /api/login
     * @return Response
     */
    public function login(): Response
    {
        try{
            $data = $this->getPost(['account','password','vcode']);
            $authService = new MemberAuth($this->request);
            $result = $authService->login($data);
            return $this->json($result,'登录成功');
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 注册
     * @method POST
     * @url /api/register
     * @return Response
     */
    public function register(): Response
    {
        try{
            $data = $this->getPost(['account','vcode','password','invite_code','account_type','nickname']);
            $authService = new MemberAuth($this->request);
            $result = $authService->register($data);
            return $this->json([
                'user_id'=>$result->id,
                'account'=>$result->account,
            ],'注册成功');
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 验证码登录
     * @method POST
     * @url /api/codeLogin
     * @return Response
     */
    public function codeLogin(): Response
    {
        try{
            $account = $this->getPost('account');
            $vcode = $this->getPost('vcode');
            $type = $this->getPost('type','email');
            $invite_code = $this->getPost('invite_code');
            $source = $this->getPost('source','login');
            $authService = new MemberAuth();
            $result = $authService->codeLogin($account,$vcode,$type,$invite_code,$source);
            return $this->json($result);
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 退出登录
     * @method POST
     * @url /api/logout
     * @return Response
     */
    public function logout(): Response
    {
        try{
            $this->request->getTokenUser();
            $authService = new MemberAuth();
            $token = $this->request->getToken('Token');
            $authService->logout($token);
            return $this->json();
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 忘记密码
     * @method POST
     * @url /api/forget
     * @return Response
     */
    public function forget(): Response
    {
        try{
            $data = $this->getPost(['account','password','vcode']);
            $memberService = new UserService();
            $userObj = $memberService->getUserByAccount($data['account']);
            if(empty($userObj)){
                throw new VerifyException('账号不存在');
            }
            if(!verifyCodeMsg($data['account'],$data['vcode'],'email')){
                throw new VerifyException('验证码错误');
            }
            $memberService->modifyPassword($userObj,$data['password']);
            return $this->json();
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 发送验证码
     * @method POST
     * @url /api/sendSmsCode
     * @return Response
     */
    public function sendSmsCode(): Response
    {
        try{
            $type = $this->getPost('type','email');
            $account = $this->getPost('account');
            $source = $this->getPost('source','login');
            $vcode = $this->getPost('vcode');
            if(!empty($vcode) && $vcode!=session('captcha')){
                throw new VerifyException('验证码错误');
            }
            if($source=='login'){
                $adminService = new UserService();
                $res = $adminService->getUserByAccount($account);
                if(empty($res)){
                    throw new VerifyException('账号不存在');
                }
            }
            elseif($source=='register'){
                $adminService = new UserService();
                $res = $adminService->getUserByAccount($account);
                if(!empty($res)){
                    throw new VerifyException('账号已存在');
                }
            }
            elseif($source=='forget'){
                $adminService = new UserService();
                $res = $adminService->getUserByAccount($account);
                if(empty($res)){
                    throw new VerifyException('账号不存在');
                }
            }
            elseif($source=='code'){

            }
            $code = sendCodeMsg($account,$type,$source);
            return $this->json(['code'=>$code],'success');
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
