<?php
// 测试环境变量加载
namespace think;

require __DIR__ . '/../vendor/autoload.php';

echo "Testing environment variables...\n\n";

// 测试 env() 函数
echo "DB_HOST: " . \think\facade\Env::get('DB_HOST', 'NOT SET') . "\n";
echo "DB_PORT: " . \think\facade\Env::get('DB_PORT', 'NOT SET') . "\n";
echo "DB_DATABASE: " . \think\facade\Env::get('DB_DATABASE', 'NOT SET') . "\n";
echo "DB_USERNAME: " . \think\facade\Env::get('DB_USERNAME', 'NOT SET') . "\n";
echo "DB_PASSWORD: " . \think\facade\Env::get('DB_PASSWORD', 'NOT SET') . "\n";
echo "APP_DEBUG: " . \think\facade\Env::get('APP_DEBUG', 'NOT SET') . "\n";

echo "\n.env file exists: " . (file_exists(__DIR__ . '/../.env') ? 'YES' : 'NO') . "\n";
echo ".env file path: " . realpath(__DIR__ . '/../.env') . "\n";
