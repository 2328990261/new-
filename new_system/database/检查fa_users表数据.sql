-- 检查 fa_users 表是否存在
SHOW TABLES LIKE 'fa_users';

-- 查看表结构
DESC `fa_users`;

-- 查看所有数据
SELECT * FROM `fa_users`;

-- 统计数据条数
SELECT COUNT(*) as total FROM `fa_users`;

-- 查看最近的数据
SELECT id, openid, phone, nickname, avatar, platform, create_time 
FROM `fa_users` 
ORDER BY create_time DESC 
LIMIT 10;
