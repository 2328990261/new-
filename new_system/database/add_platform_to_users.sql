-- 给 fa_users 表添加 platform 字段和其他必要字段
-- 请在MySQL中执行此SQL

-- 添加 platform 字段
ALTER TABLE `fa_users` 
ADD COLUMN `platform` VARCHAR(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端' AFTER `openid`;

-- 确保 avatar 和 nickname 字段存在
ALTER TABLE `fa_users` 
ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(500) DEFAULT NULL COMMENT '用户头像' AFTER `phone`;

ALTER TABLE `fa_users` 
ADD COLUMN IF NOT EXISTS `nickname` VARCHAR(100) DEFAULT NULL COMMENT '用户昵称' AFTER `phone`;

-- 更新现有数据
UPDATE `fa_users` SET `platform` = 'miniprogram' WHERE `platform` IS NULL OR `platform` = '';
