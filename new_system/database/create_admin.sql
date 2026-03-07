-- 创建管理员账号
-- 如果表不存在，先创建表
CREATE TABLE IF NOT EXISTS `fa_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 删除已存在的管理员（如果有）
DELETE FROM `fa_admin` WHERE `username` = 'admin';

-- 插入管理员账号
-- 用户名：admin
-- 密码：admin123
INSERT INTO `fa_admin` (`username`, `password`, `nickname`, `created_at`, `updated_at`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', NOW(), NOW());

-- 显示结果
SELECT id, username, nickname, created_at FROM `fa_admin`;






