-- 只添加 platform 字段（如果报错 #1060 说明字段已存在，可以忽略）
ALTER TABLE `users` 
ADD COLUMN `platform` VARCHAR(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端';

-- 更新现有数据
UPDATE `users` 
SET `platform` = 'miniprogram' 
WHERE `platform` IS NULL OR `platform` = '';

-- 验证表结构
DESC `users`;

-- 查看数据
SELECT id, openid, phone, nickname, avatar, platform, create_time 
FROM `users` 
ORDER BY create_time DESC 
LIMIT 5;
