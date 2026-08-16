<?php

return [
    // 默认数据库
    'default' => 'mysql',
    // 各种数据库配置
    'connections' => [
        'mysql' => [
            'driver'      => 'mysql',
            'host'        => env('MYSQL_HOST'),
            'port'        => env('MYSQL_PORT'),
            'database'    => env('MYSQL_DB'),
            'username'    => env('MYSQL_USER'),
            'password'    => env('MYSQL_PASS'),
            'unix_socket' => '',
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => '',
            'strict'      => true,
            'engine'      => 'InnoDB',
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false, // 当使用swoole或swow作为驱动时是必须的
            ],
            'pool' => [ // 连接池配置，仅支持swoole/swow驱动
                'max_connections' => 5, // 最大连接数
                'min_connections' => 1, // 最小连接数
                'wait_timeout' => 3,    // 从连接池获取连接等待的最大时间，超时后会抛出异常
                'idle_timeout' => 60,   // 连接池中连接最大空闲时间，超时后会关闭回收，直到连接数为min_connections
                'heartbeat_interval' => 50, // 连接池心跳检测时间，单位秒，建议小于60秒
            ],
        ],
        'gainode' => [
            'driver'      => 'mysql',
            'host'        => env('MYSQL_HOST'),
            'port'        => env('MYSQL_PORT'),
            'database'    => 'gainode',
            'username'    => env('MYSQL_USER'),
            'password'    => env('MYSQL_PASS'),
            'unix_socket' => '',
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => '',
            'strict'      => true,
            'engine'      => 'InnoDB',
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false, // 当使用swoole或swow作为驱动时是必须的
            ],
            'pool' => [ // 连接池配置，仅支持swoole/swow驱动
                'max_connections' => 5, // 最大连接数
                'min_connections' => 1, // 最小连接数
                'wait_timeout' => 3,    // 从连接池获取连接等待的最大时间，超时后会抛出异常
                'idle_timeout' => 60,   // 连接池中连接最大空闲时间，超时后会关闭回收，直到连接数为min_connections
                'heartbeat_interval' => 50, // 连接池心跳检测时间，单位秒，建议小于60秒
            ],
        ],
//        'mongodb' => [
//            'driver'   => 'mongodb',
//            'host'     => '127.0.0.1',
//            'port'     =>  27017,
//            'database' => 'test',
//            'username' => null,
//            'password' => null,
//            'options' => [
//                // here you can pass more settings to the Mongo Driver Manager
//                // see https://www.php.net/manual/en/mongodb-driver-manager.construct.php under "Uri Options" for a list of complete parameters that you can use
//
//                'appname' => 'homestead'
//            ],
//        ],
//        'sqlite' => [
//            'driver'   => 'sqlite',
//            'database' => '',
//            'prefix'   => '',
//            'pool' => [ // 连接池配置，仅支持swoole/swow驱动
//                'max_connections' => 5, // 最大连接数
//                'min_connections' => 1, // 最小连接数
//                'wait_timeout' => 3,    // 从连接池获取连接等待的最大时间，超时后会抛出异常
//                'idle_timeout' => 60,   // 连接池中连接最大空闲时间，超时后会关闭回收，直到连接数为min_connections
//                'heartbeat_interval' => 50, // 连接池心跳检测时间，单位秒，建议小于60秒
//            ],
//        ],
//        'pgsql' => [
//            'driver'   => 'pgsql',
//            'host'     => '127.0.0.1',
//            'port'     => 5432,
//            'database' => 'webman',
//            'username' => 'webman',
//            'password' => '',
//            'charset'  => 'utf8',
//            'prefix'   => '',
//            'schema'   => 'public',
//            'sslmode'  => 'prefer',
//            'pool' => [ // 连接池配置，仅支持swoole/swow驱动
//                'max_connections' => 5, // 最大连接数
//                'min_connections' => 1, // 最小连接数
//                'wait_timeout' => 3,    // 从连接池获取连接等待的最大时间，超时后会抛出异常
//                'idle_timeout' => 60,   // 连接池中连接最大空闲时间，超时后会关闭回收，直到连接数为min_connections
//                'heartbeat_interval' => 50, // 连接池心跳检测时间，单位秒，建议小于60秒
//            ],
//        ],
    ],
];
