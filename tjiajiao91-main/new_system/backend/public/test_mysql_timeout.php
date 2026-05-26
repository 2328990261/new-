<?php
// 测试MySQL超时设置
$host = '127.0.0.1';
$port = 3309;
$database = 'myjiajiao';
$username = 'jiajiao';
$password = '123456';

echo "测试MySQL连接和超时设置...\n\n";

try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        die("连接失败: " . $mysqli->connect_error . "\n");
    }
    
    echo "✓ 连接成功!\n\n";
    
    // 检查MySQL超时设置
    echo "MySQL超时设置:\n";
    $result = $mysqli->query("SHOW VARIABLES LIKE '%timeout%'");
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Variable_name']}: {$row['Value']}\n";
    }
    
    echo "\n检查MySQL版本:\n";
    $result = $mysqli->query("SELECT VERSION() as version");
    $row = $result->fetch_assoc();
    echo "  MySQL版本: {$row['version']}\n";
    
    echo "\n测试简单查询:\n";
    $result = $mysqli->query("SELECT 1 as test");
    if ($result) {
        echo "  ✓ 查询成功\n";
    }
    
    echo "\n测试表查询:\n";
    $result = $mysqli->query("SHOW TABLES");
    if ($result) {
        echo "  ✓ 表查询成功，共 " . $result->num_rows . " 个表\n";
    }
    
    echo "\n测试数据查询:\n";
    $result = $mysqli->query("SELECT * FROM fa_admin LIMIT 1");
    if ($result) {
        echo "  ✓ 数据查询成功\n";
    } else {
        echo "  ✗ 数据查询失败: " . $mysqli->error . "\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "✗ 错误: " . $e->getMessage() . "\n";
}
?>