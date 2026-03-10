-- 优化教师注册表结构
-- 移除审核相关字段，添加新的注册字段

USE myjiajiao;

-- 第一步：添加新字段（如果不存在）
-- 检查并添加微信号字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'wechat_id';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `wechat_id` varchar(100) DEFAULT NULL COMMENT ''微信号'' AFTER `phone`',
  'SELECT ''wechat_id already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加教师类型字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'teacher_type';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `teacher_type` varchar(50) DEFAULT NULL COMMENT ''教师类型：undergraduate-在读本科，graduate_student-在读研究生，doctoral_student-在读博士，graduated-毕业生，professional-专职老师'' AFTER `major`',
  'SELECT ''teacher_type already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加年级层次字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'grade_level';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `grade_level` varchar(50) DEFAULT NULL COMMENT ''年级层次：如准大一到大五、研一到研三等'' AFTER `teacher_type`',
  'SELECT ''grade_level already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加学历层次字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'education_level';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `education_level` varchar(50) DEFAULT NULL COMMENT ''学历层次：associate-大专，bachelor-本科，master-研究生，doctorate-博士'' AFTER `grade_level`',
  'SELECT ''education_level already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加个人优势字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'personal_advantage';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `personal_advantage` text COMMENT ''个人优势描述'' AFTER `self_intro`',
  'SELECT ''personal_advantage already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加优势标签字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'advantage_tags';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD COLUMN `advantage_tags` varchar(500) DEFAULT NULL COMMENT ''个人优势标签（JSON数组）'' AFTER `personal_advantage`',
  'SELECT ''advantage_tags already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 第二步：修改状态字段
ALTER TABLE `fa_teachers` 
  MODIFY COLUMN `status` varchar(20) DEFAULT 'active' COMMENT '状态：active-激活，inactive-未激活，disabled-禁用';

-- 第三步：删除审核相关字段（如果存在）
-- 检查并删除reject_reason字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'reject_reason';

SET @sql = IF(@col_exists > 0, 
  'ALTER TABLE `fa_teachers` DROP COLUMN `reject_reason`',
  'SELECT ''reject_reason does not exist'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并删除review_time字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'review_time';

SET @sql = IF(@col_exists > 0, 
  'ALTER TABLE `fa_teachers` DROP COLUMN `review_time`',
  'SELECT ''review_time does not exist'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并删除reviewer_id字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'reviewer_id';

SET @sql = IF(@col_exists > 0, 
  'ALTER TABLE `fa_teachers` DROP COLUMN `reviewer_id`',
  'SELECT ''reviewer_id does not exist'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 第四步：添加索引（如果不存在）
-- 检查并添加teacher_type索引
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND INDEX_NAME = 'idx_teacher_type';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD INDEX `idx_teacher_type` (`teacher_type`)',
  'SELECT ''idx_teacher_type already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加wechat_id索引
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND INDEX_NAME = 'idx_wechat_id';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD INDEX `idx_wechat_id` (`wechat_id`)',
  'SELECT ''idx_wechat_id already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加phone索引
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND INDEX_NAME = 'idx_phone';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_teachers` ADD INDEX `idx_phone` (`phone`)',
  'SELECT ''idx_phone already exists'' as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 第五步：创建教师注册进度表（如果不存在）
CREATE TABLE IF NOT EXISTS `fa_teacher_registration_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '进度ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信小程序openid',
  `union_id` varchar(100) DEFAULT NULL COMMENT '微信unionid',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `current_step` int(11) DEFAULT 1 COMMENT '当前步骤',
  `form_data` text COMMENT '表单数据（JSON格式）',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间（7天后）',
  PRIMARY KEY (`id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师注册进度表';

-- 第六步：更新现有数据的状态
UPDATE `fa_teachers` SET `status` = 'active' WHERE `status` = 'approved';
UPDATE `fa_teachers` SET `status` = 'disabled' WHERE `status` = 'rejected';
UPDATE `fa_teachers` SET `status` = 'active' WHERE `status` = 'pending';

-- 完成提示
SELECT '✓ 数据库升级完成！' as message;