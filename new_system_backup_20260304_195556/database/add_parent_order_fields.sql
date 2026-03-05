-- 为 parent_orders 表添加学生昵称和预约教师ID字段
-- 执行时间：2026-02-21

-- 添加学生昵称字段
ALTER TABLE `parent_orders` 
ADD COLUMN `student_name` VARCHAR(50) NULL COMMENT '学生昵称' AFTER `student_gender`;

-- 添加预约教师ID字段（如果不存在）
ALTER TABLE `parent_orders` 
ADD COLUMN `teacher_id` INT(11) NULL COMMENT '预约的教师ID' AFTER `teacher_gender`;

-- 添加索引以提高查询性能
ALTER TABLE `parent_orders` 
ADD INDEX `idx_teacher_id` (`teacher_id`);

-- 查看表结构确认
SHOW COLUMNS FROM `parent_orders`;
