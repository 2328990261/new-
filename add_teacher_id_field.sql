-- 添加 teacher_id 字段到家长预约订单表
-- 错误信息：Column not found: 1054 Unknown column 'teacher_id' in 'field list'
-- 
-- 说明：fa_parent_orders 表中缺少 teacher_id 字段
-- 此字段用于记录用户选择的教师ID（如果是从教师详情页预约的话）

-- 为 fa_parent_orders 表添加 teacher_id 字段
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `teacher_id` INT DEFAULT NULL COMMENT '指定的教师ID（从教师详情页预约时使用）' AFTER `user_id`;

-- 查看表结构确认
SHOW COLUMNS FROM `fa_parent_orders`;
