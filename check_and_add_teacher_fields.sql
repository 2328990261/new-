-- 检查并添加教师表缺失的字段
-- 执行时间：2026-03-09

-- 检查并添加 hometown 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'hometown';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `hometown` VARCHAR(100) NULL COMMENT ''籍贯'' AFTER `email`',
    'SELECT ''hometown already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 teaching_years 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'teaching_years';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `teaching_years` INT(11) NULL DEFAULT 0 COMMENT ''教龄（年）'' AFTER `hometown`',
    'SELECT ''teaching_years already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 birth_year 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'birth_year';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `birth_year` INT(11) NULL COMMENT ''出生年份'' AFTER `teaching_years`',
    'SELECT ''birth_year already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 location_province 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'location_province';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `location_province` VARCHAR(50) NULL COMMENT ''所在省份'' AFTER `birth_year`',
    'SELECT ''location_province already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 location_city 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'location_city';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `location_city` VARCHAR(50) NULL COMMENT ''所在城市'' AFTER `location_province`',
    'SELECT ''location_city already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 location_district 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'location_district';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `location_district` VARCHAR(50) NULL COMMENT ''所在区县'' AFTER `location_city`',
    'SELECT ''location_district already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 location_address 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'location_address';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `location_address` VARCHAR(200) NULL COMMENT ''详细地址'' AFTER `location_district`',
    'SELECT ''location_address already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 location_longitude 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'location_longitude';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `location_longitude` DECIMAL(10,7) NULL COMMENT ''经度'' AFTER `location_address`',
    'SELECT ''location_longitude already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 location_latitude 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'fa_teachers' 
AND COLUMN_NAME = 'location_latitude';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fa_teachers` ADD COLUMN `location_latitude` DECIMAL(10,7) NULL COMMENT ''纬度'' AFTER `location_longitude`',
    'SELECT ''location_latitude already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 显示最终的表结构
DESCRIBE fa_teachers;
