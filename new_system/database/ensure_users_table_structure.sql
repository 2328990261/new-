-- 确保 fa_users 表结构正确
-- 请在MySQL中执行此SQL

-- 如果表不存在，创建表
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

-- 如果表已存在，添加缺失的字段（如果字段不存在）
-- 注意：如果字段已存在会报错，可以忽略

-- 添加 nickname 字段
ALTER TABLE `fa_users` ADD COLUMN `nickname` VARCHAR(100) DEFAULT NULL COMMENT '昵称' AFTER `phone`;

-- 添加 avatar 字段  
ALTER TABLE `fa_users` ADD COLUMN `avatar` VARCHAR(500) DEFAULT NULL COMMENT '头像URL' AFTER `nickname`;

-- 添加 platform 字段
ALTER TABLE `fa_users` ADD COLUMN `platform` VARCHAR(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端' AFTER `avatar`;

-- 更新现有数据
UPDATE `fa_users` SET `platform` = 'miniprogram' WHERE `platform` IS NULL OR `platform` = '';
