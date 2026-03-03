-- 为教师表添加籍贯、教龄、出生年份、所在区域字段
-- 执行时间：2026-02-09
-- 注意：fa_teachers表中已经有self_intro字段，不需要重复添加

ALTER TABLE `fa_teachers` 
ADD COLUMN `hometown` VARCHAR(100) NULL COMMENT '籍贯' AFTER `email`,
ADD COLUMN `teaching_years` INT(11) NULL DEFAULT 0 COMMENT '教龄（年）' AFTER `hometown`,
ADD COLUMN `birth_year` INT(11) NULL COMMENT '出生年份' AFTER `teaching_years`,
ADD COLUMN `location_province` VARCHAR(50) NULL COMMENT '所在省份' AFTER `birth_year`,
ADD COLUMN `location_city` VARCHAR(50) NULL COMMENT '所在城市' AFTER `location_province`,
ADD COLUMN `location_district` VARCHAR(50) NULL COMMENT '所在区县' AFTER `location_city`,
ADD COLUMN `location_address` VARCHAR(200) NULL COMMENT '详细地址' AFTER `location_district`;

-- 添加索引以提高查询性能
ALTER TABLE `fa_teachers` 
ADD INDEX `idx_hometown` (`hometown`),
ADD INDEX `idx_location_city` (`location_city`),
ADD INDEX `idx_teaching_years` (`teaching_years`),
ADD INDEX `idx_birth_year` (`birth_year`);
