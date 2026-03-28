<?php
// 检查环境变量加载
require __DIR__ . '/../vendor/autoload.php';

// 初始化ThinkPHP应用
$app = new \think\App();
$app->initialize();

echo "=== 环境变量检查 ===\n\n";

echo "从 .env 文件读取的配置:\n";
echo "DB_HOST: " . env('DB_HOST', 'NOT SET') . "\n";
echo "DB_PORT: " . env('DB_PORT', 'NOT SET') . "\n";
echo "DB_DATABASE: " . env('DB_DATABASE', 'NOT SET') . "\n";
echo "DB_USERNAME: " . env('DB_USERNAME', 'NOT SET') . "\n";
echo "DB_PASSWORD: " . env('DB_PASSWORD', 'NOT SET') . "\n";
echo "DB_USER: " . env('DB_USER', 'NOT SET') . "\n";
echo "DB_NAME: " . env('DB_NAME', 'NOT SET') . "\n\n";

echo "=== 支付宝手机号解密相关 ===\n";
$alipayAesKey = env('ALIPAY_PHONE_AES_KEY', '');
echo "ALIPAY_PHONE_AES_KEY: " . ($alipayAesKey ? ('SET(len=' . strlen((string)$alipayAesKey) . ')') : 'NOT SET') . "\n";

echo "=== 数据库配置文件实际使用的值 ===\n\n";

$config = config('database');
$dbConfig = $config['connections']['mysql'];

echo "hostname: " . $dbConfig['hostname'] . "\n";
echo "hostport: " . $dbConfig['hostport'] . "\n";
echo "database: " . $dbConfig['database'] . "\n";
echo "username: " . $dbConfig['username'] . "\n";
echo "password: " . str_repeat('*', strlen($dbConfig['password'])) . " (长度: " . strlen($dbConfig['password']) . ")\n";
echo "prefix: " . $dbConfig['prefix'] . "\n";
echo "break_reconnect: " . ($dbConfig['break_reconnect'] ? 'true' : 'false') . "\n";

echo "\n=== 测试数据库连接 ===\n\n";

try {
    $db = \think\facade\Db::connect();
    echo "✓ 数据库连接成功!\n";
    
    // 测试查询
    $tables = \think\facade\Db::query("SHOW TABLES");
    echo "✓ 数据库中有 " . count($tables) . " 个表\n";
    
} catch (\Exception $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
}
?>