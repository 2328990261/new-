-- 给 fa_users 表添加 platform 字段，用于区分用户来源端口
ALTER TABLE `fa_users` 
ADD COLUMN `platform` VARCHAR(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端' AFTER `avatar`;

-- 更新现有数据，默认设置为小程序
UPDATE `fa_users` SET `platform` = 'miniprogram' WHERE `platform` IS NULL OR `platform` = '';
