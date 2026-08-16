<?php

namespace support\extend;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use \Illuminate\Validation\Factory;
use Illuminate\Filesystem\Filesystem;
use library\service\sys\LangKeyService;
use support\exception\AppException;
use support\Request;

/**
 * 验证基类
 */
class Validator
{
    private $message = 'ok';
    protected $rules = [];
    protected $attributes = [];
    protected $scenes = [];

    public $lang = 'zh_CN';

    /*
     * 创建验证实例
     * @return \Factory
     */
    private function getValidationInstance() {
        $test_translation_path = resource_path("translations");
        $translation_file_loader = new FileLoader(new Filesystem, $test_translation_path);
        $translator = new Translator($translation_file_loader, $this->lang);
        return new Factory($translator);
    }

    /**
     * 设置语言包
     * @param string $lang
     */
    public function setLanguage(string $lang) {
        $langArr = config('translation.fallback_locale');
        if(!in_array($lang,$langArr)){
            $lang = config('translation.locale');
        }
        $this->lang = $lang;
    }

    /**
     * 提示语言
     * @param $msg
     */
    protected function trans(string $msg,array $parameters = []){
        $langKeyService = new LangKeyService();
        $langKeyService->saveTranslateValue($msg,0,'validator',get_class($this));
        return trans($msg);
    }

    /**
     * 设置验证规则
     * @param array $rules
     */
    protected function setRules(array $rules) {
        $this->rules = $rules;
    }

    /**
     * 获取验证规则
     * @return array
     */
    protected function getRules() {
        return $this->rules;
    }

    /**
     * 设置属性
     * @param array $attributes
     */
    protected function setAttributes(array $attributes) {
        $this->attributes = $attributes;
    }

    /**
     * 获取属性
     * @return array
     */
    protected function getAttributes() {
        return $this->attributes;
    }

    /**
     * @param array $data   验证数据
     * @return bool
     */
    protected function checkValidate(array $data = []) {
        $validator = $this->getValidateRules();
        $validator = $validator->make($data, $this->getRules(), [], $this->getAttributes());
        if ($validator->fails()) {
            $this->message = $validator->messages();
            return false;
        }
        return true;
    }

    /**
     * 获取返回的Json数据
     */
    public function getValidateJson(){

    }

    /**
     * 获取的验证器
     * @return Factory
     */
    private function getValidateRules(){
        $validator = $this->getValidationInstance();
        $validator->extend('mobile', function ($attribute, $value, $parameters, $validator) {
            return validateMobile($value);
        }, ':attribute 格式不正确');
        $validator->extend('email', function ($attribute, $value, $parameters, $validator) {
            return validateEmail($value);
        }, ':attribute 格式不正确');
        return $validator;
    }

    /**
     * 获取错误消息
     * @return string
     */
    public function getMessage() {
        if(is_string($this->message)){
            return $this->message;
        }
        return $this->message->getMessages();
    }

    /**
     * 获取翻译的属性值
     * @param $attr_key
     * @return string
     */
    public function getAttributeValue($attr_key)
    {
        return $this->trans($this->attributes[$attr_key]);
    }

    /**
     * 验证post请求数据
     * @param string $method 方法名
     * @param array $data 请求的数据
     */
    public function verifyPostData(Request $request,array $fields){
        $post = [];
        $rules = [];
        $attributes= [];
        foreach($fields as $key=>$v){
            if(is_string($key)){
                $rules[$key] = $this->rules[$key];
                $attributes[$key] = $this->getAttributeValue($key);
                $post[$key] =  $request->post($key,$v);;
            }
            else{
                $rules[$v] = $this->rules[$v];
                $attributes[$v] = $this->getAttributeValue($v);
                $value = $request->post($v);
                $post[$v] = $value;
            }
        }
        $this->setRules($rules);
        $this->setAttributes($attributes);
        $res = $this->checkValidate($post);
        if(!$res){
            $message = $this->getMessage();
            if(is_string($message)){
                throw new AppException($message);
            }
            else{
                foreach($message as $k=>$v){
                    throw new AppException($v[0]);
                }
            }
        }
        return $post;
    }

    /**
     * 验证方法的参数数据
     * @param string $method 方法名
     * @param array $data 请求的数据
     */
    public function verifyRequestData(string $name,array $data){
        if(method_exists($this, $name)){
            return call_user_func([$this,$name],$data);
        }
        elseif(isset($this->scenes[$name])){
            if (empty($data)) {
                $this->message = 'data is empty';
                return false;
            }
            return $this->scenes($name)->checkValidate($data);
        }
        return true;
    }

    /**
     * 场景校验方法
     * @param $name
     */
    public function scenes($name){
        if(!empty($this->scenes[$name])){
            $rules = [];
            $attributes= [];
            foreach ($this->scenes[$name] as $key){
                if(isset($this->rules[$key])){
                    $rules[$key] = $this->rules[$key];
                    $attributes[$key] = $this->getAttributeValue($key);
                }
            }
            $this->setRules($rules);
            $this->setAttributes($attributes);
        }
        return $this;
    }

    /**
     * 验证公用方法的参数数据
     * @param array $data 请求的数据
     */
    public function verifyHeaderData(array $data,$type=null){
        if($type=='api'){
            $rules = [
                'Sign' => 'required|string',
                'Timestamp' => 'required|int',
                'Language' => 'required|string',
                'Version' => 'required|string',
            ];
            $attrs = [
                'Sign'=>trans('Validation_Sign'),
                'Timestamp'=>trans('Validation_Timestamp'),
                'Language'=>trans('Validation_Lang'),
                'Version'=>trans('Validation_Version'),
            ];
            $this->setRules($rules);
            $this->setAttributes($attrs);
            return $this->checkValidate($data);
        }
        elseif($type=='backend'){
            $rules = [
                'Sign' => 'required|string',
                'Timestamp' => 'required|int',
                'Language' => 'required|string',
                'Version' => 'required|string',
                'TraceId' => 'required|string',
            ];
            $attrs = [
                'Sign'=>trans('Validation_Sign'),
                'Timestamp'=>trans('Validation_Timestamp'),
                'Language'=>trans('Validation_Lang'),
                'Version'=>trans('Validation_Version'),
                'TraceId'=>trans('Validation_TraceId'),
            ];
            $this->setRules($rules);
            $this->setAttributes($attrs);
            return $this->checkValidate($data);
        }
        elseif($type=='common'){
            $rules = [
                'Sign' => 'required|string',
                'Timestamp' => 'required|int',
                'Language' => 'required|string',
                'Version' => 'required|string',
                'TraceId' => 'required|string',
            ];
            $attrs = [
                'Sign'=>trans('Validation_Sign'),
                'Timestamp'=>trans('Validation_Timestamp'),
                'Language'=>trans('Validation_Lang'),
                'Version'=>trans('Validation_Version'),
                'TraceId'=>trans('Validation_TraceId'),
            ];
            $this->setRules($rules);
            $this->setAttributes($attrs);
            return $this->checkValidate($data);
        }
        return true;
    }
}
