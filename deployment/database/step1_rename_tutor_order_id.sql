-- ============================================
-- 步骤1：重命名 tutor_order_id 为 tutor_id
-- ============================================

ALTER TABLE `fa_resume_application` 
CHANGE COLUMN `tutor_order_id` `tutor_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders_new表';

SELECT '✓ 字段重命名成功：tutor_order_id -> tutor_id' AS message;
SHOW COLUMNS FROM `fa_resume_application` LIKE 'tutor_id';
