-- 添加教师微信和登录相关字段
-- 包括：微信昵称、openid、最新登录时间

USE myjiajiao;

-- 添加微信昵称字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'wechat_nickname';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `wechat_nickname` varchar(100) DEFAULT NULL COMMENT ''微信昵称'' AFTER `wechat_id`',
  'SELECT ''wechat_nickname already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加openid字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'openid';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `openid` varchar(100) DEFAULT NULL COMMENT ''微信OpenID'' AFTER `wechat_nickname`',
  'SELECT ''openid already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加最新登录时间字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'last_login_time';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `last_login_time` datetime DEFAULT NULL COMMENT ''最新登录时间'' AFTER `is_top`',
  'SELECT ''last_login_time already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加索引
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND INDEX_NAME = 'idx_openid';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD INDEX `idx_openid` (`openid`)',
  'SELECT ''idx_openid already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND INDEX_NAME = 'idx_last_login_time';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD INDEX `idx_last_login_time` (`last_login_time`)',
  'SELECT ''idx_last_login_time already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 验证字段
SELECT 
    COLUMN_NAME as '字段名',
    COLUMN_TYPE as '类型',
    IS_NULLABLE as '可空',
    COLUMN_DEFAULT as '默认值',
    COLUMN_COMMENT as '注释'
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME IN ('wechat_nickname', 'openid', 'last_login_time')
ORDER BY ORDINAL_POSITION;

-- 完成提示
SELECT '✓ 教师微信和登录字段添加完成！' as message;
SELECT '新增字段：' as info;
SELECT '  - wechat_nickname (微信昵称)' as field;
SELECT '  - openid (微信OpenID)' as field;
SELECT '  - last_login_time (最新登录时间)' as field;
