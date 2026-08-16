<?php

namespace support\extend;

use Casbin\Enforcer as BaseEnforcer;
use Casbin\Model\Model;
use InvalidArgumentException;
use support\adapter\CasbinDatabaseAdapter;

/**
 * Class Casbin
 * @package support
 */
class Casbin
{
    /**
     * @var null
     */
    protected static $_instance = null;

    /**
     * @param string $type {rbac,restful}
     * @return \Casbin\Enforcer|null
     * @throws \Casbin\Exceptions\CasbinException
     */
    public static function instance($type = 'restful')
    {
        if (empty(self::$_instance)) {
            $config = config('casbin', []);
            if (!isset($config[$type])) {
                throw new \RuntimeException("Casbin {$type} config not found.");
            }
            // 加载casbin model 配置
            $model = new Model();
            $configType = $config[$type]['model']['config_type'];
            if ('file' == $configType) {
                $model->loadModel($config[$type]['model']['config_file_path']);
            } elseif ('text' == $configType) {
                $model->loadModelFromText($config[$type]['model']['config_text']);
            }

            // 实例化casbin adapter 适配器
            if (empty($config[$type]['adapter']) && empty($config[$type]['adapter']['type']) && empty($config[$type]['adapter']['class']) && !class_exists($config[$type]['adapter']['class'])) {
                throw new InvalidArgumentException("Enforcer adapter is not defined.");
            }

            switch ($config[$type]['adapter']['type']) {
                case 'model':
                    $ruleModel = new $config[$type]['adapter']['class']();
                    $adapter = new CasbinDatabaseAdapter($ruleModel);
                    break;
                case 'adapter':
                    // 使用自定义适配器
                    $adapter = new $config[$type]['adapter']['class']();
                    break;
                default:
                    throw new InvalidArgumentException("Only model and adapter are supported.");
                    break;
            }
            self::$_instance = new BaseEnforcer($model, $adapter, false);
        }
        return self::$_instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Casbin\Exceptions\CasbinException
     */
    public static function __callStatic($name, $arguments)
    {
        return static::instance('restful')->{$name}(... $arguments);
    }
}

