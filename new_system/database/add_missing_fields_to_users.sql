-- 根据 DESC users 的结果，只添加缺失的字段
-- 如果 nickname 字段已存在，会报错 #1060，这是正常的，跳过即可

-- 添加 avatar 字段（如果不存在）
ALTER TABLE `users` 
ADD COLUMN `avatar` VARCHAR(500) DEFAULT NULL COMMENT '头像URL' 
AFTER `phone`;

-- 添加 platform 字段（如果不存在）
ALTER TABLE `users` 
ADD COLUMN `platform` VARCHAR(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端' 
AFTER `avatar`;

-- 更新现有数据的 platform 字段
UPDATE `users` 
SET `platform` = 'miniprogram' 
WHERE `platform` IS NULL OR `platform` = '';
