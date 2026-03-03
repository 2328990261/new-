-- 为家教订单表添加预约渠道字段
-- 用于区分订单来源：H5网页 或 小程序

-- 添加预约渠道字段
ALTER TABLE `tutor_orders_new` 
ADD COLUMN `booking_channel` VARCHAR(20) DEFAULT 'H5' COMMENT '预约渠道：H5/小程序' AFTER `channel_code`;

-- 添加用户ID字段（关联小程序用户）
ALTER TABLE `tutor_orders_new` 
ADD COLUMN `user_id` INT(11) DEFAULT NULL COMMENT '小程序用户ID' AFTER `booking_channel`;

-- 添加索引
ALTER TABLE `tutor_orders_new` 
ADD INDEX `idx_booking_channel` (`booking_channel`),
ADD INDEX `idx_user_id` (`user_id`);

-- 更新现有数据，默认为H5渠道
UPDATE `tutor_orders_new` SET `booking_channel` = 'H5' WHERE `booking_channel` IS NULL;
