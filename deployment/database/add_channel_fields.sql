-- ============================================
-- 为家教订单表添加渠道相关字段
-- 创建时间: 2025-10-28
-- ============================================

-- 1. 添加是否是渠道单字段
ALTER TABLE `fa_tutor_orders_new` 
ADD COLUMN `is_channel` tinyint(1) DEFAULT 0 COMMENT '是否是渠道单：1是 0否' AFTER `status`;

-- 2. 添加渠道代号字段
ALTER TABLE `fa_tutor_orders_new` 
ADD COLUMN `channel_code` varchar(50) DEFAULT NULL COMMENT '渠道代号' AFTER `is_channel`;

-- 3. 为渠道字段添加索引（优化查询性能）
ALTER TABLE `fa_tutor_orders_new` 
ADD INDEX `idx_is_channel` (`is_channel`),
ADD INDEX `idx_channel_code` (`channel_code`);

-- 查看表结构确认
SHOW COLUMNS FROM `fa_tutor_orders_new`;

SELECT '✅ 渠道字段添加完成！' AS status;

