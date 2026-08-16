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

return [
    // 全局中间件
//    '' => [
//        support\middleware\Cors::class,
//        support\middleware\ActionHook::class,
//    ],
    // admin应用中间件
    'admin' => [
        support\middleware\Cors::class,
        support\middleware\RequestContext::class,
//        support\middleware\VerifySign::class,
        support\middleware\ActionHook::class,
    ],
    // api应用中间件
    'api' => [
        support\middleware\Cors::class,
        support\middleware\RequestContext::class,
//        support\middleware\VerifySign::class,
        support\middleware\ActionHook::class,
    ],
    'common' => [
        support\middleware\Cors::class,
        support\middleware\RequestContext::class,
//        support\middleware\VerifySign::class,
        support\middleware\ActionHook::class,
    ],
];
