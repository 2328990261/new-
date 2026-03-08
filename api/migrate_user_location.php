<?php
/**
 * 数据库迁移脚本 - 添加用户位置字段
 * 访问此文件执行迁移：http://your-domain/api/migrate_user_location.php
 */

// 数据库配置
$host = 'localhost';
$dbname = 'myjiajiao';
$username = 'jE2se7DGe5HfE6zL';
$password = 'myjiajiao';
$table_prefix = 'fa_';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>数据库迁移 - 添加用户位置字段</h2>";
echo "<hr>";

try {
    // 连接数据库
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    
    echo "<p>✓ 数据库连接成功</p>";
    
    // 检查字段是否已存在
    $checkSql = "SHOW COLUMNS FROM {$table_prefix}wechat_users LIKE 'latitude'";
    $result = $pdo->query($checkSql);
    
    if ($result->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠ 字段已存在，无需重复迁移</p>";
        exit;
    }
    
    echo "<p>开始执行迁移...</p>";
    
    // 添加位置字段
    $sql1 = "ALTER TABLE `{$table_prefix}wechat_users`
             ADD COLUMN `latitude` decimal(10,7) DEFAULT NULL COMMENT '纬度' AFTER `user_id`,
             ADD COLUMN `longitude` decimal(10,7) DEFAULT NULL COMMENT '经度' AFTER `latitude`,
             ADD COLUMN `address` varchar(500) DEFAULT NULL COMMENT '详细地址' AFTER `longitude`,
             ADD COLUMN `province` varchar(50) DEFAULT NULL COMMENT '省份' AFTER `address`,
             ADD COLUMN `city` varchar(50) DEFAULT NULL COMMENT '城市' AFTER `province`,
             ADD COLUMN `district` varchar(50) DEFAULT NULL COMMENT '区县' AFTER `city`";
    
    $pdo->exec($sql1);
    echo "<p>✓ 位置字段添加成功</p>";
    
    // 添加索引
    $sql2 = "ALTER TABLE `{$table_prefix}wechat_users`
             ADD INDEX `idx_location` (`latitude`, `longitude`),
             ADD INDEX `idx_city` (`city`, `district`)";
    
    $pdo->exec($sql2);
    echo "<p>✓ 位置索引添加成功</p>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ 迁移完成！</h3>";
    echo "<p>已成功添加以下字段：</p>";
    echo "<ul>";
    echo "<li>latitude - 纬度</li>";
    echo "<li>longitude - 经度</li>";
    echo "<li>address - 详细地址</li>";
    echo "<li>province - 省份</li>";
    echo "<li>city - 城市</li>";
    echo "<li>district - 区县</li>";
    echo "</ul>";
    echo "<p>已添加索引：</p>";
    echo "<ul>";
    echo "<li>idx_location - 经纬度索引</li>";
    echo "<li>idx_city - 城市区县索引</li>";
    echo "</ul>";
    
    // 显示表结构
    echo "<hr>";
    echo "<h3>当前表结构：</h3>";
    $columns = $pdo->query("SHOW COLUMNS FROM {$table_prefix}wechat_users")->fetchAll();
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>字段名</th><th>类型</th><th>允许NULL</th><th>默认值</th><th>备注</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ 迁移失败：" . $e->getMessage() . "</p>";
    echo "<p>错误详情：</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
