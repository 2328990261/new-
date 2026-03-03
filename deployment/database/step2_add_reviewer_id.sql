-- ============================================
-- 步骤2：添加 reviewer_id 字段
-- ============================================

ALTER TABLE `fa_resume_application` 
ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID，关联fa_admin表';

-- 添加索引
ALTER TABLE `fa_resume_application` 
ADD INDEX `idx_reviewer_id` (`reviewer_id`);

SELECT '✓ reviewer_id 字段和索引添加成功' AS message;
SHOW COLUMNS FROM `fa_resume_application` LIKE 'reviewer_id';
