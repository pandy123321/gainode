<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace support;

use library\model\member\UserModel;
use library\model\sys\AdminModel;
use library\service\auth\AdminAuth;
use library\service\auth\MemberAuth;
use support\exception\AuthorizeException;
use support\utils\JwtToken;
use Workerman\Coroutine\Context;

/**
 * Class Request
 * @package support
 */
class Request extends \Webman\Http\Request
{
    public $runtime=0;
    public $trace_id=null;

    public function getLoginUrl()
    {
        return 'https://www.baidu.com/';
    }

    public function verifyIpBlacklist()
    {

    }

    /**
     * @param $type {Authorization|Token}
     * @return array|string|null
     */
    public function getToken($type='Authorization')
    {
        return $this->header($type);
    }

    /**
     * @return string
     */
    public function getPlatformType(){
        return $this->app;
    }

    public function getLanguage(){
        $lang = $this->header('Language');
        if(empty($lang)){
            $lang = $this->header('Accept-Language', 'zh-CN');
        }
        return $lang;
    }

    /**
     * 获取授权用户
     * @return AdminModel|UserModel
     * @throws AuthorizeException
     */
    public function getAuthorizationUser()
    {
        $authorization = $this->getToken('Authorization');
        $token = str_replace('Bearer ','',$authorization);
        if(empty($token)){
            throw new AuthorizeException('Token暂未找到');
        }
        $jwtData = JwtToken::verifyToken($token);
        if(empty($jwtData)){
            throw new AuthorizeException('Token已过期');
        }
        $userObj = null;
        if($jwtData->guard=='admin'){
            $authService = new AdminAuth($this);
            $userObj = $authService->getUserByToken($jwtData->jti);
        }
        elseif($jwtData->guard=='api'){
            $authService = new MemberAuth($this);
            $userObj = $authService->getUserByToken($jwtData->jti);
        }
        if(empty($userObj)){
            throw new AuthorizeException('Token已过期');
        }
        return $userObj;
    }

    /**
     * 获取登录用户
     * @param $guard {user|admin}
     * @return AdminModel|UserModel|NULL|bool
     * @throws AuthorizeException
     */
    public function getTokenUser($is_throw=true)
    {
        $token = $this->getToken('Token');
        if(empty($token)){
            if($is_throw){
                throw new AuthorizeException('Token暂未找到');
            }
            return false;
        }
        $jwtData = JwtToken::getTokenJwtData($token);
        if(empty($jwtData)){
            if($is_throw){
                throw new AuthorizeException('Token已过期');
            }
            return false;
        }
        $userObj = null;
        if($this->app=='admin' && $jwtData->guard=='admin'){
            $authService = new AdminAuth($this);
            $userObj = $authService->getUserByToken($token);
        }
        elseif($this->app=='api' && ($jwtData->guard=='member' || $jwtData->guard=='api')){
            $authService = new MemberAuth($this);
            $userObj = $authService->getUserByToken($token);
        }
        if(empty($userObj)){
            if($is_throw){
                throw new AuthorizeException('Token已过期');
            }
            return false;
        }
        return $userObj;
    }

    /**
     * @return int
     * @throws AuthorizeException
     */
    public function getUserID()
    {
        try{
            $token = $this->getToken('Token');
            if(empty($token)){
                throw new AuthorizeException('未获取到Token信息');
            }
            $jwtData = JwtToken::getTokenJwtData($token);
            if(empty($jwtData)){
                throw new AuthorizeException('Token已过期');
            }
            return $jwtData->aud??0;
        }
        catch (\Exception $e){
            return 0;
        }
    }

    /**
     * 获取用户访问端
     *
     * @return array|string|null
     */
    public function getTerminalType(): array|string|null
    {
        return $this->header('TerminalType', 'pc');
    }

    /**
     * 当前访问端
     *
     * @param string $terminal
     *
     * @return bool
     */
    public function isTerminal(string $terminal): bool
    {
        return strtolower($this->getTerminalType()) === $terminal;
    }

    /**
     * 是否是H5端
     *
     * @return bool
     */
    public function isH5(): bool
    {
        return $this->isTerminal('h5');
    }

    /**
     * 是否是微信端
     *
     * @return bool
     */
    public function isWechat(): bool
    {
        return $this->isTerminal('wechat');
    }

    /**
     * 是否是小程序端
     *
     * @return bool
     */
    public function isRoutine(): bool
    {
        return $this->isTerminal('program');
    }

    /**
     * 是否是app端
     *
     * @return bool
     */
    public function isApp(): bool
    {
        return $this->isTerminal('app');
    }

    /**
     * 是否是app端
     *
     * @return bool
     */
    public function isPc(): bool
    {
        return $this->isTerminal('pc');
    }
}
