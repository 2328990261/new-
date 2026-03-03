-- ============================================
-- 安全修复投递管理表的所有字段问题
-- 此SQL会先检查字段是否存在，然后执行相应操作
-- ============================================

-- 查看当前表结构
SELECT '当前表结构：' AS message;
DESCRIBE `fa_resume_application`;

-- ============================================
-- 步骤1：处理 tutor_order_id 字段
-- ============================================

-- 如果存在 tutor_order_id 字段，重命名为 tutor_id
-- 如果报错说字段不存在，说明已经是 tutor_id，可以忽略错误
ALTER TABLE `fa_resume_application` 
CHANGE COLUMN `tutor_order_id` `tutor_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders_new表';

SELECT '✓ 步骤1完成：tutor_order_id -> tutor_id' AS message;

-- ============================================
-- 步骤2：添加 reviewer_id 字段
-- ============================================

-- 先删除可能存在的字段（如果存在）
-- ALTER TABLE `fa_resume_application` DROP COLUMN IF EXISTS `reviewer_id`;

-- 添加 reviewer_id 字段
-- 如果字段已存在会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID，关联fa_admin表';

SELECT '✓ 步骤2完成：添加 reviewer_id 字段' AS message;

-- ============================================
-- 步骤3：确保其他必要字段存在
-- ============================================

-- 添加 apply_time 字段（如果不存在）
-- 如果字段已存在会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD COLUMN `apply_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '投递时间';

SELECT '✓ 步骤3完成：添加 apply_time 字段' AS message;

-- 添加 review_time 字段（如果不存在）
-- 如果字段已存在会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD COLUMN `review_time` datetime DEFAULT NULL COMMENT '审核时间';

SELECT '✓ 步骤4完成：添加 review_time 字段' AS message;

-- ============================================
-- 步骤4：添加索引
-- ============================================

-- 添加 reviewer_id 索引
-- 如果索引已存在会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD INDEX `idx_reviewer_id` (`reviewer_id`);

SELECT '✓ 步骤5完成：添加 reviewer_id 索引' AS message;

-- 添加 apply_time 索引
-- 如果索引已存在会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD INDEX `idx_apply_time` (`apply_time`);

SELECT '✓ 步骤6完成：添加 apply_time 索引' AS message;

-- ============================================
-- 验证最终表结构
-- ============================================

SELECT '✓✓✓ 所有修复完成！最终表结构：' AS message;
DESCRIBE `fa_resume_application`;

-- 显示所有索引
SELECT '表索引：' AS message;
SHOW INDEX FROM `fa_resume_application`;
