-- 为 payments 表添加 tutor_order_id 索引
-- 如果索引已存在则跳过

-- 检查并添加索引
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() 
               AND table_name = 'fa_payments' 
               AND index_name = 'tutor_order_id');

SET @sqlstmt := IF(@exist > 0, 
    'SELECT "索引 tutor_order_id 已存在，跳过创建" AS message',
    'ALTER TABLE `fa_payments` ADD INDEX `tutor_order_id` (`tutor_order_id`)');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
