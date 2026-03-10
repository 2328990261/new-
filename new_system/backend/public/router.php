<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

// 获取请求URI
$request_uri = $_SERVER["REQUEST_URI"];

// 处理phpMyAdmin请求 - 直接返回false让PHP内置服务器处理
if (strpos($request_uri, '/phpMyAdmin4.8.5') === 0) {
    return false;
}

// 处理静态文件（uploads、static等）
$file_path = __DIR__ . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if (is_file($file_path)) {
    return false;
}

// 其他请求交给ThinkPHP处理
$_SERVER["SCRIPT_FILENAME"] = __DIR__ . '/index.php';
require __DIR__ . "/index.php";
