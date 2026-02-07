<?php
// 创建教师账号表
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
    
    $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}teacher_accounts` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '账号ID',
      `teacher_id` int(11) DEFAULT NULL COMMENT '关联的教师ID',
      `email` varchar(100) NOT NULL COMMENT '邮箱',
      `password` varchar(255) NOT NULL COMMENT '密码（加密）',
      `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0=未验证，1=已验证，2=已禁用',
      `last_login_time` int(11) DEFAULT NULL COMMENT '最后登录时间',
      `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
      `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`),
      KEY `teacher_id` (`teacher_id`),
      KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='教师账号表'";
    
    $pdo->exec($sql);
    
    echo "✓ 表 {$prefix}teacher_accounts 创建成功！\n";
    
} catch (PDOException $e) {
    die("数据库错误: " . $e->getMessage() . "\n");
}
