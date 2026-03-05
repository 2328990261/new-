<?php
// 显示fa_teachers表的字段结构

$host = '127.0.0.1';
$dbname = 'myjiajiao';
$username = 'myjiajiao';
$password = 'jE2se7DGe5HfE6zL';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== fa_teachers 表字段结构 ===\n\n";
    
    $stmt = $pdo->query("DESCRIBE fa_teachers");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($fields as $field) {
        echo sprintf("%-30s %-20s %-10s %-10s\n", 
            $field['Field'], 
            $field['Type'], 
            $field['Null'], 
            $field['Key']
        );
    }
    
    echo "\n=== 查询一条示例数据 ===\n\n";
    $stmt = $pdo->query("SELECT * FROM fa_teachers LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        foreach ($sample as $key => $value) {
            echo sprintf("%-30s : %s\n", $key, $value);
        }
    }
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
