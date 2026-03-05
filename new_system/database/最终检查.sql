-- 最终检查 SQL

-- 1. 检查 fa_users 表是否存在
SHOW TABLES LIKE 'fa_users';

-- 2. 检查 users 表是否还存在（应该不存在了）
SHOW TABLES LIKE 'users';

-- 3. 查看 fa_users 表结构
DESC `fa_users`;

-- 4. 统计数据条数
SELECT COUNT(*) as total FROM `fa_users`;

-- 5. 查看所有数据
SELECT id, openid, phone, nickname, avatar, platform, create_time, update_time 
FROM `fa_users` 
ORDER BY create_time DESC;

-- 6. 如果没有数据，插入一条测试数据
-- INSERT INTO `fa_users` (openid, phone, nickname, avatar, platform, create_time, update_time) 
-- VALUES ('test_openid_123', '13800138000', '测试用户', 'https://test.com/avatar.jpg', 'miniprogram', NOW(), NOW());
