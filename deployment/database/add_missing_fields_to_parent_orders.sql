-- 为 fa_parent_orders 表安全添加缺失的字段
-- 执行前请先备份数据库
-- 如果字段已存在会报错，可以忽略继续执行后面的语句

-- 1. 添加学生性别字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `student_gender` VARCHAR(10) DEFAULT NULL COMMENT '学生性别' AFTER `grade`;

-- 2. 添加学生情况描述字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `student_info` TEXT DEFAULT NULL COMMENT '学生情况描述' AFTER `subject`;

-- 3. 添加上课频率字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `frequency` VARCHAR(30) DEFAULT NULL COMMENT '上课频率（如：一周2次）' AFTER `student_info`;

-- 4. 添加每次时长字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `duration` VARCHAR(30) DEFAULT NULL COMMENT '每次时长（如：2小时/次）' AFTER `frequency`;

-- 5. 添加预算最小值字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `budget_min` INT DEFAULT 0 COMMENT '预算最小值（元/小时）' AFTER `salary`;

-- 6. 添加预算最大值字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `budget_max` INT DEFAULT 0 COMMENT '预算最大值（元/小时）' AFTER `budget_min`;

-- 7. 添加教师类型字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `teacher_type` VARCHAR(20) DEFAULT NULL COMMENT '教师类型（大学生/专职老师/不限）' AFTER `teacher_requirement`;

-- 8. 添加教师性别要求字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `teacher_gender` VARCHAR(20) DEFAULT NULL COMMENT '教师性别要求（男老师/女老师/男女不限）' AFTER `teacher_type`;

-- 9. 添加授课方式字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `teaching_method` VARCHAR(20) DEFAULT NULL COMMENT '授课方式（线上授课/上门授课）' AFTER `teacher_gender`;

-- 10. 添加详细地址字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `address` VARCHAR(255) DEFAULT NULL COMMENT '详细地址' AFTER `teaching_method`;

-- 11. 添加省份ID字段
ALTER TABLE `fa_parent_orders` ADD COLUMN `province_id` INT DEFAULT 0 COMMENT '省份ID' AFTER `address`;

-- 12. 添加位置坐标字段（用于微信位置选择）
ALTER TABLE `fa_parent_orders` ADD COLUMN `latitude` DECIMAL(10, 6) DEFAULT NULL COMMENT '纬度' AFTER `district_id`;

ALTER TABLE `fa_parent_orders` ADD COLUMN `longitude` DECIMAL(10, 6) DEFAULT NULL COMMENT '经度' AFTER `latitude`;

ALTER TABLE `fa_parent_orders` ADD COLUMN `location_name` VARCHAR(100) DEFAULT NULL COMMENT '位置名称' AFTER `longitude`;

-- 查看表结构确认
SHOW COLUMNS FROM `fa_parent_orders`;
