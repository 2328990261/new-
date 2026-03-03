-- ============================================
-- 直接添加缺失的字段
-- 如果字段已存在会报错，可以忽略
-- ============================================

-- 先查看当前表结构
SELECT '当前表结构：' AS message;
DESCRIBE `fa_resume_application`;

-- ============================================
-- 添加 reviewer_id 字段
-- ============================================
ALTER TABLE `fa_resume_application` 
ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID，关联fa_admin表';

SELECT '✓ reviewer_id 字段添加成功' AS message;

-- ============================================
-- 验证
-- ============================================
SELECT '修复后的表结构：' AS message;
DESCRIBE `fa_resume_application`;
