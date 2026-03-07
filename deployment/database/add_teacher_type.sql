-- 添加老师类型字段到家教订单表
-- teacher_type: student(大学生), professional(专职老师)

ALTER TABLE `fa_tutor_orders_new` 
ADD COLUMN `teacher_type` VARCHAR(20) DEFAULT 'student' COMMENT '老师类型：student-大学生，professional-专职老师' AFTER `salary`;

-- 为已有数据设置默认值
UPDATE `fa_tutor_orders_new` SET `teacher_type` = 'student' WHERE `teacher_type` IS NULL;

-- 添加索引以提高查询性能
ALTER TABLE `fa_tutor_orders_new` ADD INDEX `idx_teacher_type` (`teacher_type`);

