<?php
// 检查管理员状态字段
$host = '127.0.0.1';
$port = 3309;
$database = 'myjiajiao';
$username = 'jiajiao';
$password = '123456';

echo "检查管理员状态字段...\n\n";

try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        die("连接失败: " . $mysqli->connect_error . "\n");
    }
    
    echo "✓ 数据库连接成功!\n\n";
    
    // 查询管理员表结构
    echo "管理员表结构:\n";
    $result = $mysqli->query("DESCRIBE fa_admin");
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'status') {
            echo "  status字段类型: {$row['Type']}, 默认值: {$row['Default']}, 允许NULL: {$row['Null']}\n";
        }
    }
    
    echo "\n管理员状态数据:\n";
    $result = $mysqli->query("SELECT id, username, nickname, status FROM fa_admin LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $statusType = gettype($row['status']);
        $statusValue = var_export($row['status'], true);
        echo "  ID: {$row['id']}, 用户名: {$row['username']}, 状态: {$statusValue} (类型: {$statusType})\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "✗ 错误: " . $e->getMessage() . "\n";
}
?>