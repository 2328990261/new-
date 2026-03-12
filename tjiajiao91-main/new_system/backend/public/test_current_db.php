<?php
// 测试当前配置的数据库连接
echo "测试数据库连接...\n\n";

// 测试配置
$configs = [
    [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'myjiajiao',
        'username' => 'jiajiao',
        'password' => '123456',
        'name' => '端口3306'
    ],
    [
        'host' => '127.0.0.1',
        'port' => 3309,
        'database' => 'myjiajiao',
        'username' => 'jiajiao',
        'password' => '123456',
        'name' => '端口3309'
    ]
];

foreach ($configs as $config) {
    echo "尝试连接: {$config['name']}\n";
    echo "Host: {$config['host']}:{$config['port']}\n";
    echo "Database: {$config['database']}\n";
    echo "Username: {$config['username']}\n\n";
    
    try {
        $mysqli = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port']
        );
        
        if ($mysqli->connect_error) {
            echo "✗ 连接失败: " . $mysqli->connect_error . "\n\n";
            continue;
        }
        
        echo "✓ 连接成功!\n";
        
        // 测试查询
        $result = $mysqli->query("SELECT COUNT(*) as count FROM fa_tutor_orders WHERE status = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✓ 查询成功! 找到 {$row['count']} 条家教信息\n";
        } else {
            echo "✗ 查询失败: " . $mysqli->error . "\n";
        }
        
        $mysqli->close();
        echo "\n=== 使用这个配置 ===\n\n";
        break;
        
    } catch (Exception $e) {
        echo "✗ 异常: " . $e->getMessage() . "\n\n";
    }
}
?>