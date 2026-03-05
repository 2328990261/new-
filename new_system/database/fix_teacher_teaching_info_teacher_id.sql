-- 修复 teacher_teaching_info 表的 teacher_id 字段
-- 问题：teacher_id 字段被定义为 NOT NULL 但没有默认值，导致插入失败
-- 解决方案：将 teacher_id 字段修改为允许 NULL

-- 修改 teacher_id 字段允许为 NULL
ALTER TABLE `teacher_teaching_info` 
MODIFY COLUMN `teacher_id` INT(11) NULL COMMENT '教师ID';

-- 验证修改
SHOW COLUMNS FROM `teacher_teaching_info` LIKE 'teacher_id';
