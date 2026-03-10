-- 为微信用户表添加位置信息字段
ALTER TABLE `fa_wechat_users`
ADD COLUMN `latitude` decimal(10,7) DEFAULT NULL COMMENT '纬度' AFTER `user_id`,
ADD COLUMN `longitude` decimal(10,7) DEFAULT NULL COMMENT '经度' AFTER `latitude`,
ADD COLUMN `address` varchar(500) DEFAULT NULL COMMENT '详细地址' AFTER `longitude`,
ADD COLUMN `province` varchar(50) DEFAULT NULL COMMENT '省份' AFTER `address`,
ADD COLUMN `city` varchar(50) DEFAULT NULL COMMENT '城市' AFTER `province`,
ADD COLUMN `district` varchar(50) DEFAULT NULL COMMENT '区县' AFTER `city`;

-- 添加位置索引以提高查询性能
ALTER TABLE `fa_wechat_users`
ADD INDEX `idx_location` (`latitude`, `longitude`),
ADD INDEX `idx_city` (`city`, `district`);
