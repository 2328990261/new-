-- 检查并修复用户表
-- 请在MySQL中执行此SQL

-- 第一步：查看所有表名，找到用户相关的表
SHOW TABLES LIKE '%user%';

-- 如果上面显示的是 `users` 而不是 `fa_users`，说明表名没有前缀
-- 那么需要重命名表或者创建新表

-- 方案1：如果表名是 `users`，重命名为 `fa_users`
-- RENAME TABLE `users` TO `fa_users`;

-- 方案2：如果表不存在，创建新表
CREATE TABLE IF NOT EXISTS `fa_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `openid` varchar(100) NOT NULL COMMENT '微信OpenID',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `nickname` varchar(100) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(500) DEFAULT NULL COMMENT '头像URL',
  `platform` varchar(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_platform` (`platform`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
