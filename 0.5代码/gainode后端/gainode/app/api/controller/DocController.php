<?php

namespace app\api\controller;

use library\service\sys\RouteService;
use support\controller\Api;
use support\Request;
use support\Response;
use support\utils\Reflection;
use support\utils\Data;
use ReflectionClass;
use Throwable;
use support\extend\Validator;

/**
 * 接口文档中心
 * @authorized NO
 *
 */
class DocController extends Api
{
    /**
     * 文档 HTML 页面
     * @authorized NO
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return $this->view('doc/index', [
            'module' => $request->get('module', ''),
            'title' => '接口文档中心',
            'url'=>url('')
        ]);
    }

    /**
     * 获取所有模块列表
     * @authorized NO
     * @return Response
     */
    public function modules(): Response
    {
        try {
            $service = new RouteService();
            $list = $service->pluck('module', ['status' => ['gt', -1]], ['module' => 'asc']);
            $modules = array_values(array_unique($list));
            return $this->json(['modules' => $modules]);
        }
        catch (Throwable $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 获取接口文档数据（按 module 筛选，按 controller 分组）
     * @authorized NO
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        try {
            $module = $request->get('module');
            $service = new RouteService();
            $params = ['status' => ['gt', -1]];
            if (!empty($module)) {
                $params['module'] = $module;
            }

            $routes = $service->fetchAll($params,['id'=>'asc'])->toArray();
            $data = $this->buildDocData($routes);

            return $this->json([
                'module' => $module,
                'modules' => array_values($data),
            ]);
        }
        catch (Throwable $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 根据模块生成签名（供文档测试使用）
     * @authorized NO
     * @param Request $request
     * @return Response
     */
    public function sign(Request $request): Response
    {
        try {
            $module = $request->post('module', 'api');
            $headers = [
                'Token' => $request->post('token', ''),
                'Timestamp' => $request->post('timestamp', ''),
                'Version' => $request->post('version', '1.0'),
                'Language' => $request->post('language', 'zh_CN'),
                'TraceId' => $request->post('trace_id', ''),
            ];
            if (!validation_sign($module)) {
                return $this->json(['sign' => '', 'sign_required' => false]);
            }
            $headers['Language'] = $headers['Language'] ?: 'zh_CN';
            $sign = Data::sign($headers);
            return $this->json(['sign' => $sign, 'sign_required' => true]);
        }
        catch (Throwable $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 获取单个接口的详细信息
     * @authorized NO
     * @param int $id 路由ID
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $service = new RouteService();
            $route = $service->get($id);
            if (empty($route)) {
                return $this->failJson([], '接口不存在', 404);
            }
            $info = $this->enrichRoute($route->toArray());
            return $this->json($info);
        }
        catch (Throwable $e) {
            return $this->failJson([], $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 单个接口详情 HTML 页面（供前端/AI 开发对接使用）
     * @authorized NO
     * @param string $key 路由标识
     * @return Response
     */
    public function detailPage(string $key): Response
    {
        try {
            $service = new RouteService();
            $route = $service->fetch(['key' => $key, 'status' => ['gt', -1]]);
            if (empty($route)) {
                return $this->view('doc/detail', [
                    'title' => '接口不存在',
                    'info' => null,
                ]);
            }
            $info = $this->enrichRoute($route->toArray());
            return $this->view('doc/detail', [
                'title' => $info['action_name'] . ' - 接口详情',
                'info' => $info,
            ]);
        }
        catch (Throwable $e) {
            return $this->view('doc/detail', [
                'title' => '加载失败',
                'info' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 构建文档数据结构：module -> controllers -> actions
     * @authorized NO
     */
    private function buildDocData(array $routes): array
    {
        $result = [];
        foreach ($routes as $route) {
            $route = is_array($route) ? $route : $route->toArray();
            $module = $route['module'];
            $controller = $route['controller'];
            $className = $route['path'];

            if (!isset($result[$module])) {
                $result[$module] = [
                    'module' => $module,
                    'controllers' => [],
                ];
            }

            if (!isset($result[$module]['controllers'][$controller])) {
                $result[$module]['controllers'][$controller] = [
                    'controller' => $controller,
                    'controller_name' => $this->reflectClassDoc($className) ?: $controller,
                    'class' => $className,
                    'actions' => [],
                ];
            }

            $result[$module]['controllers'][$controller]['actions'][] = $this->enrichRoute($route);
        }

        // controllers 转索引数组
        foreach ($result as $module => &$item) {
            $item['controllers'] = array_values($item['controllers']);
        }

        return $result;
    }

    /**
     * 为单个路由补充反射和验证器信息
     */
    private function enrichRoute(array $route): array
    {
        $className = $route['path'];
        $action = $route['action'];
        $docInfo = $this->reflectMethodDoc($className, $action);
        $validatorInfo = $this->getValidatorParams($className, $action);

        $module = $route['module'] ?? '';
        $responseFields = $this->getResponseParams($className, $action);
        if (empty($responseFields)) {
            $responseFields = $this->getDocResponseParams($className, $action);
        }

        return [
            'id' => $route['id'] ?? 0,
            'module' => $module,
            'key' => $route['key'],
            'sign_required' => validation_sign($module),
            'action' => $action,
            'action_name' => $docInfo['description'] ?? $action,
            'method' => $route['method'],
            'url' => '/v1'.$route['url'],
            'middleware' => $this->parseMiddleware($route['middleware'] ?? ''),
            'verify' => $route['verify'] ?? 0,
            'description' => $docInfo['long_description'] ?? '',
            'params' => $validatorInfo['params'] ?? $docInfo['params'] ?? [],
            'response_fields' => $responseFields,
            'tags' => $docInfo['tags'] ?? [],
            'badge' => $this->getRouteBadge($route),
        ];
    }

    /**
     * 判断接口是否为新接口/已变更
     * created_time 不超过3天 → 新接口（优先）
     * updated_time 不超过3天 → 已变更
     */
    private function getRouteBadge(array $route): ?string
    {
        $now = time();
        $threeDays = 3 * 86400;
        $createdTime = (int)(strtotime($route['created_time']) ?? 0);
        $updatedTime = (int)(strtotime($route['updated_time']) ?? 0);

        if ($createdTime > 0 && ($now - $createdTime) <= $threeDays) {
            return 'new';
        }
        if ($updatedTime > 0 && ($now - $updatedTime) <= $threeDays) {
            return 'changed';
        }
        return null;
    }

    /**
     * 从验证器获取参数信息
     * 优先级：验证方法 > 场景 > 无参数
     */
    private function getValidatorParams(string $controllerClass, string $action): array
    {
        try {
            $validator = $this->resolveValidator($controllerClass);
            if (empty($validator)) {
                return [];
            }

            $ref = new ReflectionClass($validator);

            // 1. 优先检查是否有对应的验证方法（如 list/add/update 方法）
            if ($ref->hasMethod($action)
                && $ref->getMethod($action)->getDeclaringClass()->getName() !== Validator::class
            ) {
                $method = $ref->getMethod($action);
                $method->setAccessible(true);
                try {
                    $method->invoke($validator, []);
                } catch (Throwable) {
                    // checkValidate 可能因空数据而失败，忽略异常
                }
            }
            // 2. 其次使用场景定义（直接读原始规则和场景，绕过 scenes() 的翻译调用）
            else {
                $scenesProp = $ref->getProperty('scenes');
                $scenesProp->setAccessible(true);
                $scenes = $scenesProp->getValue($validator);

                $sceneFields = $scenes[$action] ?? null;
                if (empty($sceneFields)) {
                    return [];
                }

                // 按场景字段手动过滤规则和属性，不触发 scenes() 中的翻译
                $rawRules = $ref->getProperty('rules');
                $rawRules->setAccessible(true);
                $allRules = $rawRules->getValue($validator);

                $rules = [];
                foreach ($sceneFields as $field) {
                    if (isset($allRules[$field])) {
                        $rules[$field] = $allRules[$field];
                    }
                }
                // 通过反射直接写属性，绕过 protected setRules 的访问限制
                $rawRules->setValue($validator, $rules);
            }

            // 读取方法或场景设置后的 rules
            $rulesProp = $ref->getProperty('rules');
            $rulesProp->setAccessible(true);
            $rules = $rulesProp->getValue($validator);

            if (empty($rules)) {
                return [];
            }

            $attrsProp = $ref->getProperty('attributes');
            $attrsProp->setAccessible(true);
            $attributes = $attrsProp->getValue($validator);

            $params = [];
            foreach ($rules as $field => $rule) {
                $ruleParts = explode('|', $rule);
                $params[] = [
                    'field' => $field,
                    'required' => in_array('required', $ruleParts),
                    'type' => $this->extractParamType($ruleParts),
                    'description' => $attributes[$field] ?? '',
                    'rule' => $rule,
                ];
            }

            return ['params' => $params];
        }
        catch (Throwable) {
            return [];
        }
    }

    /**
     * 根据控制器类名推断验证器类名
     */
    private function guessValidatorClass(string $controllerClass): ?string
    {
        // 例如：app\admin\controller\sys\AdminController -> library\validator\sys\AdminValidation
        $parts = explode('\\', $controllerClass);
        if (count($parts) < 4) {
            return null;
        }

        $controllerName = array_pop($parts);
        $controllerName = str_replace('Controller', '', $controllerName);
        if (empty($controllerName)) {
            return null;
        }
        $module = $parts[1] ?? '';
        $subModule = $parts[3] ?? '';
        if (!empty($subModule)) {
            return sprintf('library\\validator\\%s\\%sValidation', $subModule, $controllerName);
        }
        return sprintf('library\\validator\\%sValidation', $controllerName);
    }

    /**
     * 解析验证器实例
     * 优先按命名空间推断验证器类；推断失败时，退而从控制器实例读取实际注入的 $validation 属性
     * （例如 AccountController 在构造函数中注入的是 UserValidation，而非按命名规则推断的 AccountValidation）
     */
    private function resolveValidator(string $controllerClass)
    {
        $validatorClass = $this->guessValidatorClass($controllerClass);
        if (!empty($validatorClass) && class_exists($validatorClass)) {
            return new $validatorClass();
        }

        // 退而求其次：实例化控制器并读取其 $validation 属性（控制器实际使用的验证器）
        try {
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                $ref = new ReflectionClass($controller);
                if ($ref->hasProperty('validation')) {
                    $prop = $ref->getProperty('validation');
                    $prop->setAccessible(true);
                    $instance = $prop->getValue($controller);
                    if ($instance instanceof Validator) {
                        return $instance;
                    }
                }
            }
        } catch (Throwable) {
            // 控制器实例化失败时忽略，交由调用方返回空参数
        }
        return null;
    }

    /**
     * 从 Response 类获取响应字段树（支持递归嵌套）
     */
    private function getResponseParams(string $controllerClass, string $action): array
    {
        try {
            $responseClass = $this->guessResponseClass($controllerClass);
            if (empty($responseClass) || !class_exists($responseClass)) {
                return [];
            }
            $response = new $responseClass();
            return $response->buildTree($action);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * 从方法注释的 @responseField 标签获取响应字段
     * 格式: @responseField type name description
     * 示例: @responseField string account 用户账号
     */
    private function getDocResponseParams(string $controllerClass, string $methodName): array
    {
        try {
            if (!class_exists($controllerClass)) {
                return [];
            }
            $reflection = new Reflection($controllerClass);
            $method = $reflection->getMethodObject($methodName);
            $doc = $method->getDocComment();
            if (empty($doc)) {
                return [];
            }
            // 直接匹配原始注释中的 @responseField type name description
            if (preg_match_all('/@responseField\s+(\w+)\s+(\$?[\w_]+)(?:\s+(.+))?/u', $doc, $matches, PREG_SET_ORDER)) {
                $fields = [];
                foreach ($matches as $m) {
                    $fields[] = [
                        'field'       => ltrim($m[2], '$'),
                        'type'        => $m[1],
                        'description' => isset($m[3]) ? trim($m[3]) : '',
                    ];
                }
                return $fields;
            }
            return [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * 根据控制器类名推断 Response 类名
     */
    private function guessResponseClass(string $controllerClass): ?string
    {
        // 例如：app\admin\controller\sys\AdminController -> library\response\sys\AdminResponse
        $parts = explode('\\', $controllerClass);
        if (count($parts) < 4) {
            return null;
        }

        $controllerName = array_pop($parts);
        $controllerName = str_replace('Controller', '', $controllerName);
        if (empty($controllerName)) {
            return null;
        }
        $subModule = $parts[3] ?? '';
        if (!empty($subModule)) {
            return sprintf('library\\response\\%s\\%sResponse', $subModule, $controllerName);
        }
        return sprintf('library\\response\\api\\%sResponse', $controllerName);
    }

    /**
     * 提取参数类型
     */
    private function extractParamType(array $ruleParts): string
    {
        $typeMap = [
            'string' => 'string', 'integer' => 'int', 'int' => 'int',
            'numeric' => 'number', 'array' => 'array', 'boolean' => 'bool',
            'email' => 'email', 'mobile' => 'mobile', 'date' => 'date',
        ];
        foreach ($ruleParts as $part) {
            if (isset($typeMap[$part])) {
                return $typeMap[$part];
            }
        }
        return 'string';
    }

    /**
     * 解析方法注释
     */
    private function reflectMethodDoc(string $className, string $methodName): array
    {
        try {
            if (!class_exists($className)) {
                return [];
            }
            $reflection = new Reflection($className);
            $method = $reflection->getMethodObject($methodName);
            $doc = $method->getDocComment();
            if (empty($doc)) {
                return [];
            }
            $parsed = $reflection->getParseDoc($doc);

            return [
                'description' => $parsed['description'] ?? '',
                'long_description' => $parsed['long_description'] ?? '',
                'params' => $this->normalizeParams($parsed['param'] ?? []),
                'tags' => $parsed,
            ];
        }
        catch (Throwable) {
            return [];
        }
    }

    /**
     * 解析类注释
     */
    private function reflectClassDoc(string $className): string
    {
        try {
            if (!class_exists($className)) {
                return '';
            }
            $reflection = new Reflection($className);
            $doc = $reflection->getClassDocComment();
            return $doc['description'] ?? ($doc['long_description'] ?? '');
        }
        catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * 将 DocParser 的 param 输出转为文档结构化格式
     * DocParser 已将: 类型关键字 + 变量名 + 描述 → (类型)变量名 描述
     */
    private function normalizeParams($params): array
    {
        if (empty($params)) {
            return [];
        }
        if (is_string($params)) {
            $params = [$params];
        }
        $result = [];
        foreach ($params as $item) {
            if (is_string($item)) {
                $parsed = $this->parseParamDocItem($item);
                if ($parsed) {
                    $result[] = $parsed;
                }
            }
            elseif (is_array($item)) {
                // 已结构化的参数（来自 Validator）
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * 拆分 DocParser 格式的参数字符串
     * 输入: (int)$page 页码  →  输出: [field, type, required, description]
     */
    private function parseParamDocItem(string $doc): ?array
    {
        $doc = trim($doc);
        if ($doc === '') {
            return null;
        }
        // 匹配 (type)name description，自动去除变量名前的 $
        if (preg_match('/^\(([^)]+)\)\s*(\$?[\w_]+)(?:\s+(.*))?$/u', $doc, $m)) {
            return [
                'field'       => ltrim($m[2], '$'),
                'type'        => $m[1],
                'required'    => false,
                'description' => isset($m[3]) ? trim($m[3]) : '',
            ];
        }
        return [
            'field'       => $doc,
            'type'        => 'mixed',
            'required'    => false,
            'description' => '',
        ];
    }

    /**
     * 解析中间件 JSON
     */
    private function parseMiddleware(string $middleware): array
    {
        if (empty($middleware)) {
            return [];
        }
        $list = json_decode($middleware, true);
        return is_array($list) ? $list : [];
    }
}
