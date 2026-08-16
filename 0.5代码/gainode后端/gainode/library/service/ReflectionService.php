<?php


namespace library\service;
use support\extend\Cache;
use support\extend\Redis;
use support\Db;

use support\utils\DocParser;
use support\utils\Files;

/**
 * 反射类逻辑层
 * @author Kevin
 */
class ReflectionService
{

    /**
     * 检测方法是否在数据库存在
     * @param string $url
     * @return boolean
     */
    public function checkUrlIsExists($url) {
        $result = Db::table('sys_route')->where('route_key',route_key($url))->first();
        return empty($result) ? false : true;
    }

    /**
     * 获取所有的路由列表
     */
    public function getRouteList($app=null,$clearCache=true)
    {
        $cache_key = "route_list";
        if(!empty($app)){
            $cache_key.='_'.$app;
        }
        $data = Cache::get($cache_key);
        if(empty($data) || $clearCache){
            $result = Db::table('sys_route')->where('module',$app)->get()->toArray();
            $rows = [];
            foreach($result as $v){
                $v = (array)$v;
                $key = $v['module'].'/'.$v['controller'].'/'.$v['action'];
                $rows[$key] = $v;
            }
            $data = [];
            foreach($rows as $v){
                $route_url = $v['url'];
                $middleware = [];
                if(!empty($v['middleware'])){
                    $middleware = json_decode($v['middleware'],true);
                    foreach($middleware as $k=>$name){
                        $middleware[$k] = sprintf("app\%s\middleware\%s",$v['module'],$name);
                    }
                }
                $methods = explode('|',$v['method']);
                $data[] = [
                    'methods'=>$methods,
                    'route_url'=>$route_url,
                    'path'=>$v['path'],
                    'action'=>$v['action'],
                    'middleware'=>$middleware
                ];
            }
            Cache::set($cache_key,$data,3600);
        }
        return $data;
    }

    /**
     * 获取具体的路由地址
     */
    public function getRouteUrl(string $url,string $method='GET'){
        $cache_key = "app_route";
        $route_key = route_key($url);
        $route_url = Redis::hGet($cache_key,$route_key);
        if(empty($route_url)){
            $route_url = Db::table('sys_route')->where('route_key',$route_key)->value('url');
            if(empty($route_url)){
                $route_url = $url;
            }
            Redis::hSet($cache_key,$route_key,$route_url);
        }
        return $route_url;
    }

    /**
     * 初始化权限列表
     * @param string $app 模块
     * @param false $isFilterDb 是否过滤数据中已经存在
     * @return array
     */
    public function initAppRouteMethod(string $app,$isFilterDb=false){
        try{
            $data = [];
            $controllers = $this->getAppControllerList($app);
            foreach($controllers as $v){
                $list = $this->getControllerReflectionList($v['class'],'not_authorized');
                if(!empty($list)){
                    $actions = Db::table('sys_route')->where('module',$app)->where('controller',$v['name'])->pluck('action')->toArray();
                    foreach($list as $c){
                        $url = str_replace('Controller','',$v['url']).'/'.$c['method']->name;
                        $route_key = route_key($url);
                        if(isset($c['doc']['url']) && !empty(isset($c['doc']['url']))){
                            $url = $c['doc']['url'];
                        }
                        if($isFilterDb){
                            if(in_array($c['method']->name,$actions)){
                                continue;
                            }
                        };
                        $data[] = [
                            'key'=>$route_key,
                            'module'=>$app,
                            'controller'=>$v['name'],
                            'action'=>$c['method']->name,
                            'url'=>$url,
                            'path'=>$v['class'],
                            'method'=>(isset($c['doc']['method'])?$c['doc']['method']:null),
                            'descr'=>(isset($c['doc']['description'])?$c['doc']['description']:(isset($c['doc']['long_description'])?$c['doc']['long_description']:null)),
                            'middleware'=>'["AuthMiddleware"]',
                            'verify'=>(in_array($app,['backend','admin'])?2:0),
                            'created_time'=>getCurrentDate('unix'),
                            'updated_time'=>getCurrentDate('unix'),
                        ];
                    }
                }
            }
            if(!empty($data)){
                Db::table('sys_route')->insert($data);
            }
            return count($data);
        }
        catch (\Exception $e){
            throw $e;
        }
    }



    /**
     * 获取指定模块下面所有授权的方法
     * @param string $app 模块
     * @param string $filter 过滤类型 {not_authorized,only_authorized}
     */
    public function getAppMenuMethodList(string $app,$filter=null){
        try{
            $data = [];
            $controllers = $this->getAppControllerList($app);
            foreach($controllers as $v){
                $list = $this->getControllerReflectionList($v['class'],$filter);
                if(!empty($list)){
                    foreach($list as $c){
                        $data[] = [
                            'controller'=>$v['class'],
                            'action'=>$c['method']->name,
                            'method'=>(isset($c['doc']['method'])?$c['doc']['method']:null),
                            'middleware'=>(isset($c['doc']['middleware'])?$c['doc']['middleware']:null),
                            'url'=>(isset($c['doc']['url'])?$c['doc']['url']:$v['url'].'/'.$c['method']->name),
                            'descr'=>(isset($c['doc']['description'])?$c['doc']['description']:(isset($c['doc']['long_description'])?$c['doc']['long_description']:null)),
                        ];
                    }
                }
            }
            return $data;
        }
        catch (\Exception $e){
            return [];
        }
    }



    /**
     * 获取模块下面的所有控制器列表
     * @param string $app
     */
    public function getAppControllerList(string $app){
        try{
            $data = [];
            $klass_path = sprintf('app\%s\controller',$app);
            $path = app_path($app.'/controller');
            $files = Files::getPathFiles($path);
            if(is_array($files)){
                foreach($files as $f1){
                    if(strpos($f1,'.php')!==false){
                        $name = str_replace('.php','',$f1);
                        $klass = $klass_path.'\\'.$name;
                        $name = lcfirst($name);
                        $data[] = [
                            'name'=>$name,
                            'url'=>'/'.$app.'/'.$name,
                            'class'=>$klass
                        ];
                    }
                    else{
                        $subFiles = Files::getPathFiles($path.'/'.$f1);
                        if(!empty($subFiles)){
                            foreach($subFiles as $f2){
                                $name = str_replace('.php','',$f2);
                                $klass = $klass_path.'\\'.$f1.'\\'.$name;
                                $name = lcfirst($name);
                                $data[] = [
                                    'name'=>$name,
                                    'url'=>'/'.$app.'/'.$name,
                                    'class'=>$klass
                                ];
                            }
                        }
                    }
                }
            }
            return $data;
        }
        catch (\Exception $e){
            return [];
        }
    }

    /**
     * 获取控制器下面的所有方法
     */
    public function getControllerMethodList(string $klass,$filter=null){
        $methods = $this->getControllerReflectionList($klass,$filter);
        $data = [];
        if(!empty($methods)){
            foreach($methods as $k=>$method){
                $data[] = $method['method']->name;
            }
        }
        return $data;
    }

    /**
     * 获取控制器下面的所有方法
     */
    public function getControllerReflectionList(string $klass,$filter=null){
        $reflection = new \ReflectionClass($klass);
        $methods = $reflection->getMethods(true);
        $data = [];
        foreach($methods as $k=>$method){
            if(!in_array($method->name,['__construct','beforeAction','afterAction'])){
                if(empty($filter)){
                    $data[] = ['method'=>$method,'doc'=>[]];
                }
                else{
                    $doc = $this->getMethodDoc($method);
                    if($filter=='only_authorized'){
                        if(!empty($doc['authorized'])){
                            $data[] = ['method'=>$method,'doc'=>$doc];
                        }
                    }
                    elseif($filter=='not_authorized'){
                        if(!isset($doc['authorized'])){
                            $data[] = ['method'=>$method,'doc'=>$doc];
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 验证URL是否已授权
     * @param $url
     * @param string|null $method
     * @return bool
     */
    public function checkUrlIsAuthorized(string $url,string $method=null){
        try{
            $url = trim($url,'/');
            list($m,$c,$a) = explode('/',$url);
            $klass = sprintf('app\%s\controller\%s',$m,ucfirst($c));
            $reflection = new \ReflectionClass($klass);
            $methodObj = $reflection->getMethod($a);
            $doc = $methodObj->getDocComment();
            $docParse = new DocParser();
            $res = $docParse->parse($doc);
            if (isset($res['authorized']) && !empty($res['authorized'])) {
                if($res['authorized']=='YES'){
                    if(!empty($method) && !empty($res['method'])){
                        if(strtolower($method)==strtolower($res['method'])){
                            return true;
                        }
                    }
                    else{
                        return true;
                    }
                }
                return false;
            }
            return null;
        }
        catch (\Exception $e){
            return null;
        }
    }

    /**
     * 获取反射方法获取的注释文档
     * @param \ReflectionMethod $method
     * @return array
     */
    private function getMethodDoc(\ReflectionMethod $method){
        $doc = $method->getDocComment();
        $docParse = new DocParser();
        return $docParse->parse($doc);
    }

    /**
     * 获取类下面的所有方法列表
     * @param $klass
     */
    private function getClassMethodList($klass){
        $reflection = new \ReflectionClass($klass);
        $methods = $reflection->getMethods(true);
        $data = [];
        foreach($methods as $v){
            $doc = $this->getMethodDoc($v);
            $data[] = [
                'name'=>$v->name,
                'doc'=>(isset($doc['description'])?$doc['description']:(isset($doc['long_description'])?$doc['long_description']:null))
            ];
        }
        return $data;
    }

    public function getTaskFileList(){
        try{
            $data = [];
            $klass_path = sprintf('library\\task');
            $path = library_path('task');
            $files = Files::getPathFiles($path);
            if(is_array($files)){
                foreach($files as $f1){
                    if(strpos($f1,'.php')!==false){
                        $name = str_replace('.php','',$f1);
                        $klass = $klass_path.'\\'.$name;
                        $methods = $this->getClassMethodList($klass);
                        $name = lcfirst($name);
                        $data[] = [
                            'name'=>$name,
                            'class'=>$klass,
                            'method'=>$methods
                        ];
                    }
                    else{
                        $subFiles = Files::getPathFiles($path.'/'.$f1);
                        if(!empty($subFiles)){
                            foreach($subFiles as $f2){
                                $name = str_replace('.php','',$f2);
                                $klass = $klass_path.'\\'.$f1.'\\'.$name;
                                $methods = $this->getClassMethodList($klass);
                                $name = lcfirst($name);
                                $data[] = [
                                    'name'=>$name,
                                    'class'=>$klass,
                                    'method'=>$methods
                                ];
                            }
                        }
                    }
                }
            }
            return $data;
        }
        catch (\Exception $e){
            return [];
        }
    }

    /**
     * 获取任务下面的方法
     * @param string $klass
     */
    public function getTaskList(array $filterJobCmd=[]){
        $fileTaskList = $this->getTaskFileList();
        $data = [];
        foreach($fileTaskList as $v){
            if(!empty($v['method'])){
                foreach($v['method'] as $c){
                    $cmd = $v['class'].':'.$c['name'];
                    if(!empty($filterJobCmd) && in_array($cmd,$filterJobCmd)){
                        continue;
                    }
                    $data[] = [
                        'name'=>$v['name'],
                        'class'=>$v['class'],
                        'action'=>$c['name'],
                        'doc'=>$c['doc'],
                        'cmd'=>$cmd
                    ];
                }
            }
        }
        return $data;
    }
}
