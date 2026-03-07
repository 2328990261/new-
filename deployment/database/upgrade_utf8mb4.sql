-- ====================================
-- 升级数据库字符集以支持 emoji（utf8mb4）
-- ====================================
-- 执行日期: 2025-01-24
-- 说明: 将 tutor_orders_new 表的 content 字段改为 utf8mb4，支持 emoji 表情存储

-- 1. 修改 content 字段为 utf8mb4
ALTER TABLE `tutor_orders_new` 
MODIFY COLUMN `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '家教信息内容';

-- 2. 将整个表转换为 utf8mb4（可选，但推荐）
ALTER TABLE `tutor_orders_new` 
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3. 修改数据库默认字符集为 utf8mb4（如果需要）
-- ALTER DATABASE `fa_91jiajiao` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 验证修改结果
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CHARACTER_SET_NAME,
    COLLATION_NAME,
    COLUMN_TYPE
FROM 
    INFORMATION_SCHEMA.COLUMNS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tutor_orders_new'
    AND COLUMN_NAME = 'content';

-- 预期结果：
-- CHARACTER_SET_NAME 应该是 utf8mb4
-- COLLATION_NAME 应该是 utf8mb4_unicode_ci


