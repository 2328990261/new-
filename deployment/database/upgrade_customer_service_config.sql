-- ========================================
-- 添加客服配置功能
-- ========================================
-- 创建时间: 2025-01-07
-- 说明: 为 notification_config 表添加客服微信号字段
-- ========================================

-- 检查 notification_config 表是否存在，不存在则创建
CREATE TABLE IF NOT EXISTS `notification_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `smtp_host` varchar(100) DEFAULT NULL COMMENT 'SMTP服务器地址',
  `smtp_port` int(11) DEFAULT 465 COMMENT 'SMTP端口',
  `smtp_username` varchar(100) DEFAULT NULL COMMENT 'SMTP用户名',
  `smtp_password` varchar(255) DEFAULT NULL COMMENT 'SMTP密码',
  `smtp_secure` tinyint(1) DEFAULT 1 COMMENT '是否SSL',
  `from_email` varchar(100) DEFAULT NULL COMMENT '发件人邮箱',
  `from_name` varchar(100) DEFAULT '家教信息平台' COMMENT '发件人名称',
  `customer_service_wechat` varchar(100) DEFAULT NULL COMMENT '客服微信号',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知配置表';

-- 添加客服微信号字段（如果不存在）
ALTER TABLE `notification_config` 
ADD COLUMN IF NOT EXISTS `customer_service_wechat` varchar(100) DEFAULT NULL COMMENT '客服微信号' AFTER `from_name`;

-- 插入默认配置（如果不存在）
INSERT INTO `notification_config` (`id`, `from_name`) 
VALUES (1, '家教信息平台') 
ON DUPLICATE KEY UPDATE id=id;

-- 完成
SELECT '客服配置表升级完成！' AS message;

