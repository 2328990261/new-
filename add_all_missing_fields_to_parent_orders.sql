-- 为 fa_parent_orders 表添加所有可能缺失的字段
-- 执行前请先备份数据库
-- 如果字段已存在会报错，可以忽略继续执行后面的语句

-- 1. 添加 user_id 字段（小程序用户ID）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `user_id` INT DEFAULT NULL COMMENT '小程序用户ID' AFTER `id`;

-- 2. 添加 teacher_id 字段（指定的教师ID）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `teacher_id` INT DEFAULT NULL COMMENT '指定的教师ID（从教师详情页预约时使用）' AFTER `user_id`;

-- 3. 添加 student_name 字段（学生姓名）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `student_name` VARCHAR(50) DEFAULT NULL COMMENT '学生姓名' AFTER `grade`;

-- 4. 添加 student_gender 字段（学生性别）- 如果还没有
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `student_gender` VARCHAR(10) DEFAULT NULL COMMENT '学生性别' AFTER `student_name`;

-- 5. 添加 child_description 字段（学生情况描述）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `child_description` TEXT DEFAULT NULL COMMENT '学生学习情况描述' AFTER `subject`;

-- 6. 添加 frequency 字段（辅导频率）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `frequency` VARCHAR(30) DEFAULT NULL COMMENT '辅导频率（如：每周2次）' AFTER `child_description`;

-- 7. 添加 duration 字段（每次时长）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `duration` VARCHAR(30) DEFAULT NULL COMMENT '每次时长（如：2小时）' AFTER `frequency`;

-- 8. 添加 budget 字段（预算文本）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `budget` VARCHAR(100) DEFAULT NULL COMMENT '预算文本（如：100-150元/小时）' AFTER `duration`;

-- 9. 添加 budget_min 字段（预算最小值）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `budget_min` INT DEFAULT 0 COMMENT '预算最小值（元/小时）' AFTER `budget`;

-- 10. 添加 budget_max 字段（预算最大值）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `budget_max` INT DEFAULT 0 COMMENT '预算最大值（元/小时）' AFTER `budget_min`;

-- 11. 添加 teacher_type 字段（教师类型）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `teacher_type` VARCHAR(50) DEFAULT NULL COMMENT '教师类型（在校大学生/毕业大学生/专职老师）' AFTER `budget_max`;

-- 12. 添加 teacher_gender 字段（教师性别要求）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `teacher_gender` VARCHAR(20) DEFAULT NULL COMMENT '教师性别要求（不限/男老师/女老师）' AFTER `teacher_type`;

-- 13. 添加 teaching_method 字段（授课方式）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `teaching_method` VARCHAR(20) DEFAULT NULL COMMENT '授课方式（上门辅导/在线辅导）' AFTER `teacher_gender`;

-- 14. 添加 contact 字段（联系人姓名）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `contact` VARCHAR(50) DEFAULT NULL COMMENT '联系人姓名' AFTER `teaching_method`;

-- 15. 添加 phone 字段（联系电话）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话' AFTER `contact`;

-- 16. 添加 wechat 字段（微信号）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `wechat` VARCHAR(50) DEFAULT NULL COMMENT '微信号' AFTER `phone`;

-- 17. 添加位置坐标字段
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `latitude` DECIMAL(10, 6) DEFAULT NULL COMMENT '纬度' AFTER `address`;

ALTER TABLE `fa_parent_orders` 
ADD COLUMN `longitude` DECIMAL(10, 6) DEFAULT NULL COMMENT '经度' AFTER `latitude`;

-- 18. 添加 city_id 和 district_id 字段（如果需要）
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `city_id` INT DEFAULT NULL COMMENT '城市ID' AFTER `longitude`;

ALTER TABLE `fa_parent_orders` 
ADD COLUMN `district_id` INT DEFAULT NULL COMMENT '区县ID' AFTER `city_id`;

-- 查看表结构确认
SHOW COLUMNS FROM `fa_parent_orders`;
