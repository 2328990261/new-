<?php
/**
 * 数据库配置 - 使用环境变量
 * 安全提示：敏感信息已移至 .env 文件
 */

// 从环境变量读取配置
$database = env('DB_NAME', env('DB_DATABASE', 'myjiajiao'));
$username = env('DB_USER', env('DB_USERNAME', 'jE2se7DGe5HfE6zL'));
$password = env('DB_PASSWORD', 'myjiajiao');
$hostname = env('DB_HOST', '127.0.0.1');
$hostport = env('DB_PORT', '3306');
$domain = env('APP_DOMAIN', 'http://localhost');

// 判断是否为本地环境（用于debug模式）
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($serverName, ['localhost', '127.0.0.1', '0.0.0.0']) 
    || strpos($httpHost, 'localhost') !== false 
    || strpos($httpHost, '127.0.0.1') !== false;

define('APP_DOMAIN', $domain);

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'type'            => 'mysql',
            'hostname'        => $hostname,
            'database'        => $database,
            'username'        => $username,
            'password'        => $password,
            'hostport'        => $hostport,
            'params'          => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'
            ],
            'charset'         => 'utf8mb4',
            'prefix'          => 'fa_',
            'debug'           => $isLocal,
            'deploy'          => 0,
            'rw_separate'     => false,
            'master_num'      => 1,
            'slave_no'        => '',
            'fields_strict'   => true,
            'break_reconnect' => false,
            'trigger_sql'     => true,
            'fields_cache'    => false,
        ],
    ],
];

