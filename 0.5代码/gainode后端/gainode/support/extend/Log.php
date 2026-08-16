<?php

namespace support\extend;

use Monolog\Logger;

/**
 * Class Redis
 * @package support
 *
 * @method static void log($level, $message, array $context = [])
 * @method static void debug($message, array $context = [])
 * @method static void info($message, array $context = [])
 * @method static void notice($message, array $context = [])
 * @method static void warning($message, array $context = [])
 * @method static void error($message, array $context = [])
 * @method static void critical($message, array $context = [])
 * @method static void alert($message, array $context = [])
 * @method static void emergency($message, array $context = [])
 */
class Log
{

    /**
     * @var array
     */
    protected static $_instance = [];

    protected static $client = [];

    private $channel = 'default';

    /**
     * @param string $name
     * @return Logger
     */
    public static function channel($name = 'default')
    {
        if (!static::$_instance) {
            $configs = config('log', []);
            foreach ($configs as $channel => $config) {
                $logger = static::$_instance[$channel] = new Logger($channel);
                foreach ($config['handlers'] as $handler_config) {
                    $handler = new $handler_config['class'](... \array_values($handler_config['constructor']));
                    if (isset($handler_config['formatter'])) {
                        $formatter = new $handler_config['formatter']['class'](... \array_values($handler_config['formatter']['constructor']));
                        $handler->setFormatter($formatter);
                    }
                    $logger->pushHandler($handler);
                }
            }
        }
        if(!isset(self::$client[$name])){
            self::$client[$name] = new Log();
            self::$client[$name]->channel = $name;
        }
        return self::$client[$name];
    }

    /**
     * @return Logger
     */
    protected function getLogClient()
    {
        if (!isset(static::$_instance[$this->channel])) {
            $configs = config('log', []);
            foreach ($configs as $channel => $config) {
                $logger = static::$_instance[$channel] = new Logger($channel);
                foreach ($config['handlers'] as $handler_config) {
                    $handler = new $handler_config['class'](... \array_values($handler_config['constructor']));
                    if (isset($handler_config['formatter'])) {
                        $formatter = new $handler_config['formatter']['class'](... \array_values($handler_config['formatter']['constructor']));
                        $handler->setFormatter($formatter);
                    }
                    $logger->pushHandler($handler);
                }
            }
        }
        return static::$_instance[$this->channel];
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $request = \request();
        if(!empty($request) && isset($request->trace_id)){
            if(isset($arguments[1]) && is_array($arguments[1])){
                $arguments[1]['trace_id'] = $request->trace_id;
            }
            else{
                $arguments[1] = ['trace_id'=>$request->trace_id];
            }
        }
        $this->getLogClient()->{$name}(... $arguments);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::channel('default')->{$name}(... $arguments);
    }
}
