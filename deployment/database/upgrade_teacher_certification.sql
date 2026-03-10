-- 教师认证系统升级脚本
-- 实现三种认证：实名认证、学历认证、教师认证

USE myjiajiao;

-- 第一步：添加认证相关字段
-- 添加实名认证字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'real_name_verified';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `real_name_verified` tinyint(1) DEFAULT 0 COMMENT ''实名认证：0-未认证，1-已认证'' AFTER `status`',
  'SELECT ''real_name_verified already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加学历认证字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'education_verified';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `education_verified` tinyint(1) DEFAULT 0 COMMENT ''学历认证：0-未认证，1-已认证'' AFTER `real_name_verified`',
  'SELECT ''education_verified already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加教师认证字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'teacher_verified';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `teacher_verified` tinyint(1) DEFAULT 0 COMMENT ''教师认证：0-未认证，1-已认证'' AFTER `education_verified`',
  'SELECT ''teacher_verified already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加认证材料字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'id_card_front';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `id_card_front` varchar(255) DEFAULT NULL COMMENT ''身份证正面照'' AFTER `teacher_verified`',
  'SELECT ''id_card_front already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'id_card_back';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `id_card_back` varchar(255) DEFAULT NULL COMMENT ''身份证反面照'' AFTER `id_card_front`',
  'SELECT ''id_card_back already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'education_certificate';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `education_certificate` varchar(255) DEFAULT NULL COMMENT ''学历证书照片'' AFTER `id_card_back`',
  'SELECT ''education_certificate already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'teacher_certificate';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `teacher_certificate` varchar(255) DEFAULT NULL COMMENT ''教师资格证照片'' AFTER `education_certificate`',
  'SELECT ''teacher_certificate already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加审核相关字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'review_status';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `review_status` varchar(20) DEFAULT ''pending'' COMMENT ''审核状态：pending-待审核，approved-已审核，rejected-已拒绝'' AFTER `teacher_certificate`',
  'SELECT ''review_status already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'review_time';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `review_time` datetime DEFAULT NULL COMMENT ''审核时间'' AFTER `review_status`',
  'SELECT ''review_time already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'reviewer_id';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT ''审核人ID'' AFTER `review_time`',
  'SELECT ''reviewer_id already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'review_note';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `review_note` text COMMENT ''审核备注'' AFTER `reviewer_id`',
  'SELECT ''review_note already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 第二步：修改status字段注释
ALTER TABLE `fa_teachers` 
  MODIFY COLUMN `status` varchar(20) DEFAULT 'active' COMMENT '状态：active-激活，inactive-未激活，disabled-禁用';

-- 第三步：添加索引
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND INDEX_NAME = 'idx_review_status';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD INDEX `idx_review_status` (`review_status`)',
  'SELECT ''idx_review_status already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND INDEX_NAME = 'idx_verified';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD INDEX `idx_verified` (`real_name_verified`, `education_verified`, `teacher_verified`)',
  'SELECT ''idx_verified already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 第四步：更新现有数据
-- 将现有active状态的教师设置为已审核
UPDATE `fa_teachers` 
SET `review_status` = 'approved', 
    `review_time` = NOW()
WHERE `status` = 'active' AND `review_status` = 'pending';

-- 完成提示
SELECT '✓ 教师认证系统升级完成！' as message;
SELECT '新增字段：' as info;
SELECT '  - real_name_verified (实名认证)' as field;
SELECT '  - education_verified (学历认证)' as field;
SELECT '  - teacher_verified (教师认证)' as field;
SELECT '  - id_card_front/back (身份证照片)' as field;
SELECT '  - education_certificate (学历证书)' as field;
SELECT '  - teacher_certificate (教师资格证)' as field;
SELECT '  - review_status (审核状态)' as field;
SELECT '  - review_time (审核时间)' as field;
SELECT '  - reviewer_id (审核人)' as field;
SELECT '  - review_note (审核备注)' as field;
