<?php
// 使用mysqli测试数据库连接
$host = '127.0.0.1';
$port = 3306;
$database = 'tjiajiao91';
$username = 'tjiajiao91';
$password = 'Jiajiao@2024';

echo "使用mysqli测试数据库连接...\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $database\n";
echo "Username: $username\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

// 尝试连接
$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_error) {
    echo "✗ 连接失败: " . $mysqli->connect_error . "\n";
    
    // 尝试不指定数据库
    echo "\n尝试连接MySQL服务器（不指定数据库）...\n";
    $mysqli2 = new mysqli($host, $username, $password, '', $port);
    
    if ($mysqli2->connect_error) {
        echo "✗ MySQL服务器连接失败: " . $mysqli2->connect_error . "\n";
    } else {
        echo "✓ MySQL服务器连接成功!\n";
        
        // 检查数据库是否存在
        $result = $mysqli2->query("SHOW DATABASES LIKE '$database'");
        if ($result && $result->num_rows > 0) {
            echo "✓ 数据库 '$database' 存在\n";
        } else {
            echo "✗ 数据库 '$database' 不存在\n";
            echo "可用的数据库:\n";
            $result = $mysqli2->query("SHOW DATABASES");
            while ($row = $result->fetch_array()) {
                echo "  - " . $row[0] . "\n";
            }
        }
        $mysqli2->close();
    }
    exit(1);
}

echo "✓ 数据库连接成功!\n\n";

// 测试查询
$result = $mysqli->query("SELECT COUNT(*) as count FROM fa_tutor_orders WHERE status = 1");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ 查询成功! 找到 {$row['count']} 条家教信息\n";
} else {
    echo "✗ 查询失败: " . $mysqli->error . "\n";
    
    // 显示所有表
    echo "\n数据库中的表:\n";
    $result = $mysqli->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "\n";
    }
}

$mysqli->close();
?>