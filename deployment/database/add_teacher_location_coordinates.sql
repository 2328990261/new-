-- 为教师表添加经纬度字段
-- 执行时间：2026-02-20
-- 用途：保存教师地址的经纬度信息，方便计算家长与教师之间的距离

ALTER TABLE `fa_teachers` 
ADD COLUMN `location_longitude` DECIMAL(10, 7) NULL COMMENT '所在地经度' AFTER `location_address`,
ADD COLUMN `location_latitude` DECIMAL(10, 7) NULL COMMENT '所在地纬度' AFTER `location_longitude`;

-- 添加索引以提高基于位置的查询性能
ALTER TABLE `fa_teachers` 
ADD INDEX `idx_location_coordinates` (`location_longitude`, `location_latitude`);
