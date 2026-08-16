<?php

use Workerman\RedisQueue\Client as RedisClient;

return [
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path('logs/app.log'),
                    30, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [ null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'api' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path('logs/api.log'),
                    30, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [ null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'library' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path('logs/library.log'),
                    30, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [ null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'task' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path('logs/task.log'),
                    30, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [ null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'crontab' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path('logs/crontab.log'),
                    30, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [ null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'queue' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path('logs/queue.log'),
                    30, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [ null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'change_logs' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path('logs/change_logs.log'),
                    15, //$maxFiles
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [ null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
];
