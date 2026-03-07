-- ============================================
-- 一次性修复投递管理表的所有字段问题
-- 执行前请先查看当前表结构：DESCRIBE fa_resume_application;
-- ============================================

-- 1. 重命名字段：tutor_order_id -> tutor_id
-- 如果字段名已经是 tutor_id，这条会报错，可以忽略
ALTER TABLE `fa_resume_application` 
CHANGE COLUMN `tutor_order_id` `tutor_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders_new表';

-- 2. 添加 reviewer_id 字段
-- 如果字段已存在，这条会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID，关联fa_admin表' AFTER `admin_remark`;

-- 3. 添加 apply_time 字段
-- 如果字段已存在，这条会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD COLUMN `apply_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '投递时间' AFTER `status`;

-- 4. 添加 review_time 字段
-- 如果字段已存在，这条会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD COLUMN `review_time` datetime DEFAULT NULL COMMENT '审核时间' AFTER `apply_time`;

-- 5. 添加索引
-- 如果索引已存在，这条会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD INDEX `idx_reviewer_id` (`reviewer_id`);

-- 如果索引已存在，这条会报错，可以忽略
ALTER TABLE `fa_resume_application` 
ADD INDEX `idx_apply_time` (`apply_time`);

-- 验证表结构
SELECT '✓ 所有字段修复完成！' AS message;
DESCRIBE `fa_resume_application`;
