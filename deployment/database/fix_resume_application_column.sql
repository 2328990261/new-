-- ============================================
-- 修复投递管理表字段名
-- 将 tutor_order_id 重命名为 tutor_id
-- ============================================

-- 重命名字段
ALTER TABLE `fa_resume_application` 
CHANGE COLUMN `tutor_order_id` `tutor_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders_new表';

-- 验证字段是否修改成功
SELECT '✓ 字段重命名成功！' AS message;
SHOW COLUMNS FROM `fa_resume_application` LIKE 'tutor_id';
