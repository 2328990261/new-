-- 添加 student_name 字段到家长预约订单表
-- 错误信息：Column not found: 1054 Unknown column 'student_name' in 'field list'
-- 
-- 说明：fa_parent_orders 表中有 student_gender 字段，但缺少 student_name 字段
-- 此脚本将添加 student_name 字段到 fa_parent_orders 表

-- 为 fa_parent_orders 表添加 student_name 字段（放在 student_gender 之后）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `student_name` VARCHAR(50) DEFAULT NULL COMMENT '学生姓名' AFTER `student_gender`;

-- 查看表结构确认
SHOW COLUMNS FROM `fa_parent_orders`;
