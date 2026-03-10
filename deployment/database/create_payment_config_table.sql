-- 创建支付配置表
CREATE TABLE IF NOT EXISTS `payment_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `payment_method` varchar(20) NOT NULL COMMENT '支付方式：wechat-微信支付, alipay-支付宝',
  `app_id` varchar(50) DEFAULT NULL COMMENT '应用ID',
  `mch_id` varchar(50) DEFAULT NULL COMMENT '商户号',
  `api_key` varchar(100) DEFAULT NULL COMMENT 'API密钥',
  `app_secret` varchar(100) DEFAULT NULL COMMENT '应用密钥',
  `cert_path` varchar(255) DEFAULT NULL COMMENT '证书路径',
  `key_path` varchar(255) DEFAULT NULL COMMENT '密钥路径',
  `notify_url` varchar(255) DEFAULT NULL COMMENT '支付回调地址',
  `is_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用：0-禁用，1-启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_method` (`payment_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付配置表';

-- 插入默认配置
INSERT INTO `payment_config` (`payment_method`, `is_enabled`) VALUES 
('wechat', 0),
('alipay', 0)
ON DUPLICATE KEY UPDATE `payment_method` = VALUES(`payment_method`);
