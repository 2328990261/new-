-- status字段已存在，只需要修复用户角色
-- 修复现有用户的空角色问题，将空的user_type设置为parent（家长）
UPDATE fa_wechat_users SET user_type = 'parent' WHERE user_type IS NULL OR user_type = '';