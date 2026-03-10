-- 为 fa_tutor_orders 表安全添加缺失的字段
-- 执行前请先备份数据库
-- 如果字段已存在会报错，可以忽略继续执行后面的语句

-- 2. 添加学生性别字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `student_gender` VARCHAR(10) DEFAULT NULL COMMENT '学生性别' AFTER `grade`;

-- 3. 添加科目字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `subject` VARCHAR(50) DEFAULT NULL COMMENT '辅导科目' AFTER `student_gender`;

-- 4. 添加学生情况描述字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `student_info` TEXT DEFAULT NULL COMMENT '学生情况描述' AFTER `subject`;

-- 5. 添加上课频率字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `frequency` VARCHAR(30) DEFAULT NULL COMMENT '上课频率（如：一周2次）' AFTER `student_info`;

-- 6. 添加每次时长字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `duration` VARCHAR(30) DEFAULT NULL COMMENT '每次时长（如：2小时/次）' AFTER `frequency`;

-- 7. 添加预算最小值字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `budget_min` INT DEFAULT 0 COMMENT '预算最小值（元/小时）' AFTER `salary`;

-- 8. 添加预算最大值字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `budget_max` INT DEFAULT 0 COMMENT '预算最大值（元/小时）' AFTER `budget_min`;

-- 9. 添加教师类型字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `teacher_type` VARCHAR(20) DEFAULT NULL COMMENT '教师类型（大学生/专职老师/不限）' AFTER `budget_max`;

-- 10. 添加教师性别要求字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `teacher_gender` VARCHAR(20) DEFAULT NULL COMMENT '教师性别要求（男老师/女老师/男女不限）' AFTER `teacher_type`;

-- 11. 添加教师要求字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `teacher_requirement` VARCHAR(100) DEFAULT NULL COMMENT '教师要求' AFTER `teacher_gender`;

-- 12. 添加授课方式字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `teaching_method` VARCHAR(20) DEFAULT NULL COMMENT '授课方式（线上授课/上门授课）' AFTER `teacher_requirement`;

-- 13. 添加详细地址字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `address` VARCHAR(255) DEFAULT NULL COMMENT '详细地址' AFTER `teaching_method`;

-- 14. 添加省份ID字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `province_id` INT DEFAULT 0 COMMENT '省份ID' AFTER `address`;

-- 15. 添加位置坐标字段（用于微信位置选择）
ALTER TABLE `fa_tutor_orders` ADD COLUMN `latitude` DECIMAL(10, 6) DEFAULT NULL COMMENT '纬度' AFTER `province_id`;

ALTER TABLE `fa_tutor_orders` ADD COLUMN `longitude` DECIMAL(10, 6) DEFAULT NULL COMMENT '经度' AFTER `latitude`;

ALTER TABLE `fa_tutor_orders` ADD COLUMN `location_name` VARCHAR(100) DEFAULT NULL COMMENT '位置名称' AFTER `longitude`;

-- 16. 添加联系人姓名字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `parent_name` VARCHAR(50) DEFAULT NULL COMMENT '联系人姓名' AFTER `location_name`;

-- 17. 添加联系人电话字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `parent_contact` VARCHAR(20) DEFAULT NULL COMMENT '联系人电话' AFTER `parent_name`;

-- 18. 添加备注字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `remark` TEXT DEFAULT NULL COMMENT '备注' AFTER `parent_contact`;

-- 19. 添加订单状态字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `status` VARCHAR(20) DEFAULT 'pending' COMMENT '订单状态（pending/processing/completed/cancelled）' AFTER `remark`;

-- 20. 添加预约渠道字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `booking_channel` VARCHAR(20) DEFAULT NULL COMMENT '预约渠道（H5/小程序）' AFTER `status`;

-- 21. 添加用户ID字段
ALTER TABLE `fa_tutor_orders` ADD COLUMN `user_id` INT DEFAULT 0 COMMENT '小程序用户ID' AFTER `booking_channel`;

-- 查看表结构确认
SHOW COLUMNS FROM `fa_tutor_orders`;
