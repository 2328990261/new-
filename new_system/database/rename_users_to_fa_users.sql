-- 将 users 表改名为 fa_users，与其他表保持一致

-- 第1步：检查 fa_users 表是否已存在
SHOW TABLES LIKE 'fa_users';

-- 第2步：如果 fa_users 不存在，将 users 改名为 fa_users
RENAME TABLE `users` TO `fa_users`;

-- 第3步：验证改名成功
SHOW TABLES LIKE 'fa_users';

-- 第4步：查看表结构
DESC `fa_users`;

-- 第5步：查看数据
SELECT id, openid, phone, nickname, avatar, platform, create_time 
FROM `fa_users` 
ORDER BY create_time DESC 
LIMIT 5;
