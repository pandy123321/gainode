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

use support\Container;
use support\Response;

if (!function_exists('library_path')) {
    /**
     * @return string
     */
    function library_path(string $name = null)
    {
        $path = BASE_PATH . DIRECTORY_SEPARATOR . 'library';
        if (!empty($name)) {
            $path .= DIRECTORY_SEPARATOR . $name;
        }
        return $path;
    }
}

if (!function_exists('public_url')) {
    /**
     * @return string
     */
    function public_url(string $name = null)
    {
        $path = env('APP_DOMAIN');
        if (!empty($name)) {
            $path .= DIRECTORY_SEPARATOR . $name;
        }
        return $path;
    }
}

if (!function_exists('static_url')) {
    /**
     * @return string
     */
    function static_url(string $name = null)
    {
        $path = public_url("static");
        if (!empty($name)) {
            $path .= $name;
        }
        return $path;
    }
}

if (!function_exists('upload_path')) {
    /**
     * @return string
     */
    function upload_path(string $name = null)
    {
        $path = public_path("uploads");
        if (!empty($name)) {
            $path .= $name;
        }
        return $path;
    }
}

if (!function_exists('upload_url')) {
    /**
     * @return string
     */
    function upload_url(string $url = null, $size = null)
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        $path = public_url("uploads");
        if (!empty($url)) {
            $path .= $url;
        } elseif (!is_null($url)) {
            $path = static_url('/common/images/nopic.png');
        }
        return $path;
    }
}

if (!function_exists('file_hash_url')) {
    /**
     * @param $file_hash
     * @param $size
     * @return string
     */
    function file_hash_url($file_hash, $size = null)
    {
        if (empty($file_hash)) {
            return static_url('/common/images/nopic.png');
        }
        $uploadService = Container::get(\library\service\sys\UploadFilesService::class);
        return $uploadService->getResourceUrl($file_hash, $size);
    }
}

if (!function_exists('resource_path')) {
    /**
     * @return string
     */
    function resource_path(string $name = null)
    {
        $path = BASE_PATH . DIRECTORY_SEPARATOR . 'resource';
        if (!empty($name)) {
            $path .= DIRECTORY_SEPARATOR . $name;
        }
        return $path;
    }
}


if (!function_exists('failJson')) {
    /**
     * Fail Json response
     * @param $code
     * @param $data
     * @param int $options
     * @return Response
     */
    function failJson($code, $data, int $options = JSON_UNESCAPED_UNICODE): Response
    {
        return new Response($code, ['Content-Type' => 'application/json'], json_encode($data, $options));
    }
}

if (!function_exists('jsonp')) {
    /**
     * Jsonp response
     * @param $data
     * @param string $callbackName
     * @return Response
     */
    function jsonp($data, string $callbackName = 'callback'): Response
    {
        if (!is_scalar($data) && null !== $data) {
            $data = json_encode($data);
        }
        $html = '<script type="text/javascript">try{'."$callbackName($data)".'}catch(e){console.log(e)}</script>';
        return new Response(200, [], $html);
    }
}

if (!function_exists('getIpData')) {
    function getIpData(string $ip)
    {
        $ip2region = new \Ip2Region();
        $result = $ip2region->binarySearch($ip);
        if (!empty($result['region'])) {
            $result['region'] = str_replace(['0|', '|0'], ['', ''], $result['region']);
        }
        return $result;
    }
}



if (!function_exists('getBase62String')) {
    function getBase62String($link_type = 'default')
    {
        $data = [
            'default' => '3rO5Hhlgq4f7KXMJD9YsRp1yoS6nVuBPcwQt2zvLIiUCFANkmWx0abTeG8ZdjE',
            'main' => 'Idrfo0XeAmci58aRlWZpzvnNVwkYGj9CTQLh1F4xES7tBHUgqJPDOb26syK3uM',
        ];
        return isset($data[$link_type]) ? $data[$link_type] : $data['default'];
    }
}

if (!function_exists('base10To62')) {
    function base10To62($num, $domain = 'default')
    {
        $str_shuffle_62 = getBase62String($domain);
        $res = '';
        while ($num > 0) {
            $res = substr($str_shuffle_62, $num % 62, 1) . $res;
            $num = floor($num / 62);
        }
        return $res;
    }
}
