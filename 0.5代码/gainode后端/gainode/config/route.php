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

use Webman\Route;

Route::disableDefaultRoute();

foreach (config('autoload.routes', []) as $file) {
    include_once $file;
}

//Route::get('/api/common/getLangList', [app\common\controller\LangController::class, 'list']);
//Route::get('/v1/s/{code}', [app\common\controller\ShortUrlController::class, 'link']);
