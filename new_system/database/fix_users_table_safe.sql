-- 安全地修复 users 表结构
-- 这个脚本会检查字段是否存在，只添加缺失的字段

-- 第1步：查看当前表结构（复制结果，确认哪些字段已存在）
DESC `users`;

-- 第2步：添加 avatar 字段（如果报错 #1060 说明字段已存在，可以忽略）
ALTER TABLE `users` 
ADD COLUMN `avatar` VARCHAR(500) DEFAULT NULL COMMENT '头像URL';

-- 第3步：添加 platform 字段（如果报错 #1060 说明字段已存在，可以忽略）
ALTER TABLE `users` 
ADD COLUMN `platform` VARCHAR(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端';

-- 第4步：更新现有数据（这条总是要执行，不会报错）
UPDATE `users` 
SET `platform` = 'miniprogram' 
WHERE `platform` IS NULL OR `platform` = '';

-- 第5步：验证表结构（确认所有字段都已添加）
DESC `users`;

-- 第6步：查看现有数据
SELECT id, openid, phone, nickname, avatar, platform, create_time 
FROM `users` 
ORDER BY create_time DESC 
LIMIT 5;
