<?php
/**
 * 修复 payment_config 表，添加 key_path 字段
 * 在浏览器中访问: http://localhost:8080/fix_db.php
 */

// 数据库配置
$host = '127.0.0.1';
$dbname = 'myjiajiao';
$username = 'jE2se7DGe5HfE6zL';
$password = 'myjiajiao';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>数据库修复工具</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #5B8FF9; padding-bottom: 10px; }
        .success { color: #52c41a; padding: 10px; background: #f6ffed; border: 1px solid #b7eb8f; border-radius: 4px; margin: 10px 0; }
        .info { color: #1890ff; padding: 10px; background: #e6f7ff; border: 1px solid #91d5ff; border-radius: 4px; margin: 10px 0; }
        .error { color: #ff4d4f; padding: 10px; background: #fff1f0; border: 1px solid #ffa39e; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #5B8FF9; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .btn:hover { background: #4080e8; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h2>数据库修复工具</h2>
        
<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>1. 检查 payment_config 表结构</h3>";
    
    // 显示当前表结构
    $stmt = $pdo->query("SHOW COLUMNS FROM payment_config");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>当前字段列表:</p><pre>";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    echo "</pre>";
    
    // 检查 key_path 字段
    $hasKeyPath = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'key_path') {
            $hasKeyPath = true;
            break;
        }
    }
    
    echo "<h3>2. 修复 key_path 字段</h3>";
    
    if (!$hasKeyPath) {
        echo "<p>正在添加 key_path 字段...</p>";
        
        // 添加字段
        $pdo->exec("ALTER TABLE payment_config ADD COLUMN key_path varchar(255) DEFAULT NULL COMMENT '密钥路径' AFTER cert_path");
        
        echo "<div class='success'>✓ 成功添加 key_path 字段到 payment_config 表</div>";
    } else {
        echo "<div class='info'>✓ key_path 字段已存在</div>";
    }
    
    // 清除 ThinkPHP 缓存
    echo "<h3>3. 清除缓存</h3>";
    $cacheDir = __DIR__ . '/../runtime/cache/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*');
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }
        echo "<div class='success'>✓ 已清除 {$count} 个缓存文件</div>";
    } else {
        echo "<div class='info'>✓ 缓存目录不存在或已清空</div>";
    }
    
    echo "<div class='success'><strong>修复完成！</strong></div>";
    echo "<p>现在可以关闭此页面，<strong>刷新管理后台</strong>了。</p>";
    echo "<a href='/admin' class='btn'>返回管理后台</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>✗ 数据库错误: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>请检查数据库配置是否正确。</p>";
}
?>
    </div>
</body>
</html>
