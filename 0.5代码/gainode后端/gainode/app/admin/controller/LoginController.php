<?php

namespace app\admin\controller;

use library\service\auth\AdminAuth;
use library\service\sys\AdminService;
use library\validator\LoginValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 登录与注册管理
 */
class LoginController extends Admin
{

    public function __construct()
    {
        $this->validation = new LoginValidation();
        parent::__construct();
    }

    /**
     * 输出验证码图像
     * @method GET
     * @url /admin/login/captcha
     */
    public function captcha()
    {
        try{
            $captcha = new \support\utils\Captcha(106,37);
            $imageContent = $captcha->getImageContent();
            $this->request->session()->set('captcha', strtolower($captcha->getCheckCode()));
            $base64ImageContent = base64_encode($imageContent);
            return $this->json('data:image/jpeg;base64,' .$base64ImageContent);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 扫码登录
     * @method GET
     * @url /admin/login/qrcode
     */
    public function qrcode(){
        try{
            $data = url('/admin/login/captcha',['s'=>time()]);
//            $QRCode = new QRCode();
//            $img_content = $QRCode->render($data);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 登录
     * @method POST
     * @url /admin/login
     * @return Response
     */
    public function login(): Response
    {
        try{
            $data = $this->getPost(['account','password','vcode']);
            $authService = new AdminAuth($this->request);
            $result = $authService->login($data);
            return $this->json($result,'登录成功');
        }
        catch (\Exception $e){
            $captcha = url('/admin/login/captcha');
            return $this->failJson(['captcha'=>$captcha],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 验证码登录
     * @method POST
     * @url /admin/codeLogin
     * @return Response
     */
    public function codeLogin(): Response
    {
        try{
            $account = $this->getPost('account');
            $vcode = $this->getPost('vcode');
            $type = $this->getPost('type','email');
            $authService = new AdminAuth();
            $result = $authService->codeLogin($account,$vcode,$type);
            return $this->json($result);
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 退出登录
     * @method POST
     * @url /admin/logout
     * @return Response
     */
    public function logout(): Response
    {
        try{
            $this->request->getTokenUser();
            $authService = new AdminAuth();
            $token = $this->request->getToken('Token');
            $authService->logout($token);
            return $this->json([]);
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 发送验证码
     * @method POST
     * @url /admin/sendSmsCode
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
                $adminService = new AdminService();
                $res = $adminService->getUserByAccount($account);
                if(empty($res)){
                    throw new VerifyException('账号不存在');
                }
            }
            $code = sendCodeMsg($account,$type,$source);
            return $this->json(['code'=>$code],'success');
        }
        catch (\Exception $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
