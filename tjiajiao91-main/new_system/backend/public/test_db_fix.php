<?php
// 测试数据库连接 - 修复版
require __DIR__ . '/../vendor/autoload.php';

// 加载环境变量
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        putenv("$key=$value");
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'myjiajiao';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';

echo "尝试连接数据库...\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $database\n";
echo "Username: $username\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

try {
    // 使用兼容的PDO选项
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "✓ 数据库连接成功!\n\n";
    
    // 测试查询
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fa_tutor_orders WHERE status = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✓ 查询成功! 找到 {$result['count']} 条家教信息\n";
    
} catch (PDOException $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
    
    // 尝试不指定数据库连接
    try {
        echo "\n尝试连接到MySQL服务器（不指定数据库）...\n";
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, $options);
        echo "✓ MySQL服务器连接成功!\n";
        
        // 检查数据库是否存在
        $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
        $dbExists = $stmt->fetch();
        
        if ($dbExists) {
            echo "✓ 数据库 '$database' 存在\n";
        } else {
            echo "✗ 数据库 '$database' 不存在\n";
        }
        
    } catch (PDOException $e2) {
        echo "✗ MySQL服务器连接也失败: " . $e2->getMessage() . "\n";
    }
    
    exit(1);
}