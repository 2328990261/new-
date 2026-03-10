<?php
/**
 * 数据库配置示例文件
 * 复制此文件为 database.php 使用
 * 
 * 重要提示：
 * 1. 数据库表前缀必须设置为 'fa_'
 * 2. 数据库名称为 'myjiajiao'
 * 3. 确保数据库已导入完整的SQL文件
 */

return [
    // 默认使用的数据库连接配置
    'default'         => env('database.driver', 'mysql'),

    // 数据库连接配置信息
    'connections'     => [
        'mysql' => [
            // 数据库类型
            'type'            => env('database.type', 'mysql'),
            // 服务器地址
            'hostname'        => env('DB_HOST', '127.0.0.1'),
            // 数据库名
            'database'        => env('DB_DATABASE', 'myjiajiao'),
            // 用户名
            'username'        => env('DB_USERNAME', 'jE2se7DGe5HfE6zL'),
            // 密码
            'password'        => env('DB_PASSWORD', 'myjiajiao'),
            // 端口
            'hostport'        => env('DB_PORT', '3306'),
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8
            'charset'         => env('database.charset', 'utf8mb4'),
            // 数据库表前缀（重要：必须设置为 fa_）
            'prefix'          => env('database.prefix', 'fa_'),
            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => false,
            // 监听SQL
            'trigger_sql'     => env('app_debug', true),
            // 开启字段缓存
            'fields_cache'    => false,
            // 字段缓存路径
            'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
        ],
    ],
];
