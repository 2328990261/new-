<?php
// 使用ThinkPHP框架测试数据库连接
namespace think;

require __DIR__ . '/../vendor/autoload.php';

// 初始化应用
$app = new App();
$app->initialize();

echo "使用ThinkPHP测试数据库连接...\n\n";

try {
    // 获取数据库配置
    $config = config('database');
    $dbConfig = $config['connections']['mysql'];
    
    echo "数据库配置:\n";
    echo "Host: " . $dbConfig['hostname'] . "\n";
    echo "Port: " . $dbConfig['hostport'] . "\n";
    echo "Database: " . $dbConfig['database'] . "\n";
    echo "Username: " . $dbConfig['username'] . "\n";
    echo "Password: " . str_repeat('*', strlen($dbConfig['password'])) . "\n";
    echo "Prefix: " . $dbConfig['prefix'] . "\n\n";
    
    // 测试数据库连接
    $db = \think\facade\Db::connect();
    
    echo "✓ ThinkPHP数据库连接成功!\n\n";
    
    // 测试查询 - 检查家教订单表
    $count = \think\facade\Db::table('tutor_orders')->where('status', 1)->count();
    echo "✓ 查询成功! 找到 {$count} 条活跃家教信息\n\n";
    
    // 显示所有表
    echo "数据库中的表（前10个）:\n";
    $tables = \think\facade\Db::query("SHOW TABLES");
    $i = 0;
    foreach ($tables as $table) {
        if ($i >= 10) break;
        $tableName = array_values($table)[0];
        echo "  - " . $tableName . "\n";
        $i++;
    }
    
    if (count($tables) > 10) {
        echo "  ... 还有 " . (count($tables) - 10) . " 个表\n";
    }
    
} catch (\Exception $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
    
    // 显示环境变量
    echo "\n环境变量:\n";
    echo "DB_HOST: " . env('DB_HOST', 'NOT SET') . "\n";
    echo "DB_PORT: " . env('DB_PORT', 'NOT SET') . "\n";
    echo "DB_DATABASE: " . env('DB_DATABASE', 'NOT SET') . "\n";
    echo "DB_USERNAME: " . env('DB_USERNAME', 'NOT SET') . "\n";
    echo "DB_PASSWORD: " . (env('DB_PASSWORD') ? str_repeat('*', strlen(env('DB_PASSWORD'))) : 'NOT SET') . "\n";
    
    exit(1);
}
?>