<?php
// 快速修复数据库字段
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
    
    // 检查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
    if ($stmt->rowCount() === 0) {
        die("错误：表 {$tableName} 不存在！\n");
    }
    
    echo "开始检查和添加字段...\n\n";
    
    // 要添加的字段
    $fields = [
        'hometown' => "VARCHAR(100) NULL COMMENT '籍贯' AFTER wechat_id",
        'teaching_years' => "INT(11) NULL DEFAULT 0 COMMENT '教龄（年）' AFTER hometown",
        'birth_year' => "INT(11) NULL COMMENT '出生年份' AFTER teaching_years",
        'location_province' => "VARCHAR(50) NULL COMMENT '所在省份' AFTER birth_year",
        'location_city' => "VARCHAR(50) NULL COMMENT '所在城市' AFTER location_province",
        'location_district' => "VARCHAR(50) NULL COMMENT '所在区县' AFTER location_city",
        'location_address' => "VARCHAR(200) NULL COMMENT '详细地址' AFTER location_district",
        'self_intro' => "TEXT NULL COMMENT '自我介绍' AFTER experience"
    ];
    
    foreach ($fields as $fieldName => $fieldDef) {
        // 检查字段是否存在
        $stmt = $pdo->query("SHOW COLUMNS FROM {$tableName} LIKE '{$fieldName}'");
        
        if ($stmt->rowCount() > 0) {
            echo "✓ 字段 {$fieldName} 已存在\n";
        } else {
            // 添加字段
            $sql = "ALTER TABLE {$tableName} ADD COLUMN {$fieldName} {$fieldDef}";
            $pdo->exec($sql);
            echo "✓ 字段 {$fieldName} 添加成功\n";
        }
    }
    
    echo "\n所有字段检查完成！\n";
    
} catch (PDOException $e) {
    die("数据库错误: " . $e->getMessage() . "\n");
}
