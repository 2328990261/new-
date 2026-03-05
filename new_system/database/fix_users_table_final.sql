-- 修复用户表的最终方案
-- 请按顺序执行以下SQL

-- 步骤1：查看 users 表的结构
DESC `users`;

-- 步骤2：给 users 表添加缺失的字段

-- 添加 nickname 字段（如果不存在）
ALTER TABLE `users` ADD COLUMN `nickname` VARCHAR(100) DEFAULT NULL COMMENT '昵称' AFTER `phone`;

-- 添加 avatar 字段（如果不存在）
ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(500) DEFAULT NULL COMMENT '头像URL' AFTER `nickname`;

-- 添加 platform 字段（如果不存在）
ALTER TABLE `users` ADD COLUMN `platform` VARCHAR(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端' AFTER `avatar`;

-- 步骤3：更新现有数据
UPDATE `users` SET `platform` = 'miniprogram' WHERE `platform` IS NULL OR `platform` = '';

-- 步骤4：查看修改后的表结构
DESC `users`;

-- 步骤5：查看现有数据
SELECT * FROM `users` LIMIT 5;
