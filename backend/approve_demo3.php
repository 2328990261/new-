<?php
// 批准 demo3 教师
$host = '127.0.0.1';
$port = 3306;
$database = 'myjiajiao';
$username = 'myjiajiao';
$password = 'jE2se7DGe5HfE6zL';
$prefix = 'fa_';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "数据库连接成功！\n\n";
    
    $tableName = $prefix . 'teachers';
    
    // 更新 demo3 的审核状态
    $sql = "UPDATE {$tableName} SET review_status = 'approved' WHERE name = 'demo3'";
    $pdo->exec($sql);
    
    echo "✓ demo3 已批准通过！\n\n";
    
    // 查看更新后的数据
    $stmt = $pdo->query("SELECT id, name, gender, phone, review_status, status FROM {$tableName} WHERE name = 'demo3'");
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($teacher) {
        echo "更新后的数据：\n";
        echo "ID: {$teacher['id']}\n";
        echo "姓名: {$teacher['name']}\n";
        echo "性别: {$teacher['gender']}\n";
        echo "手机: {$teacher['phone']}\n";
        echo "审核状态: {$teacher['review_status']}\n";
        echo "状态: {$teacher['status']}\n";
    }
    
    echo "\n现在 demo3 应该可以在教师库中显示了！\n";
    
} catch (PDOException $e) {
    die("数据库错误: " . $e->getMessage() . "\n");
}
