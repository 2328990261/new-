<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

// 欢迎页面
Route::get('/', function () {
    return json([
        'code' => 200,
        'msg' => '欢迎使用家教信息管理系统 v2.0',
        'data' => [
            'version' => '2.0',
            'framework' => 'ThinkPHP 6.x',
            'time' => date('Y-m-d H:i:s')
        ]
    ]);
});

// 引入管理端路由
require_once __DIR__ . '/admin.php';

// 引入API路由
require_once __DIR__ . '/api.php';
