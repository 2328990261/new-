-- 安全地给 users 表添加字段
-- 先查看表结构，确认哪些字段已存在

-- 查看当前表结构
DESC `users`;

-- 如果上面显示 nickname 不存在，执行下面这条
-- ALTER TABLE `users` ADD COLUMN `nickname` VARCHAR(100) DEFAULT NULL COMMENT '昵称' AFTER `phone`;

-- 如果上面显示 avatar 不存在，执行下面这条
-- ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(500) DEFAULT NULL COMMENT '头像URL' AFTER `nickname`;

-- 如果上面显示 platform 不存在，执行下面这条
-- ALTER TABLE `users` ADD COLUMN `platform` VARCHAR(20) DEFAULT 'miniprogram' COMMENT '用户端口' AFTER `avatar`;

-- 更新现有数据（这条可以安全执行）
UPDATE `users` SET `platform` = 'miniprogram' WHERE `platform` IS NULL OR `platform` = '';

-- 再次查看表结构确认
DESC `users`;

-- 查看现有数据
SELECT id, openid, phone, nickname, avatar, platform, create_time FROM `users` LIMIT 5;
