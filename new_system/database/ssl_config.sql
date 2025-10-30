-- SSL证书配置表
CREATE TABLE IF NOT EXISTS `fa_ssl_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `domain` varchar(255) NOT NULL COMMENT '域名',
  `contact_email` varchar(255) DEFAULT NULL COMMENT '联系邮箱',
  `provider` varchar(50) DEFAULT 'letsencrypt' COMMENT '证书提供商：letsencrypt, aliyun, tencent',
  `auto_renew` tinyint(1) DEFAULT 1 COMMENT '是否自动续约：0-否，1-是',
  `is_enabled` tinyint(1) DEFAULT 1 COMMENT '是否启用：0-否，1-是',
  `status` varchar(20) DEFAULT 'pending' COMMENT '状态：pending-待申请，active-有效，expired-过期，failed-失败',
  `cert_issue_time` datetime DEFAULT NULL COMMENT '证书签发时间',
  `cert_expire_time` datetime DEFAULT NULL COMMENT '证书过期时间',
  `last_apply_time` datetime DEFAULT NULL COMMENT '最后申请时间',
  `last_renew_time` datetime DEFAULT NULL COMMENT '最后续约时间',
  `last_check_time` datetime DEFAULT NULL COMMENT '最后检查时间',
  `error_message` text DEFAULT NULL COMMENT '错误信息',
  `config_data` text DEFAULT NULL COMMENT '配置数据（JSON格式）',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain` (`domain`),
  KEY `idx_status` (`status`),
  KEY `idx_auto_renew` (`auto_renew`),
  KEY `idx_expire_time` (`cert_expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSL证书配置表';

-- 插入默认配置示例（使用Let's Encrypt免费证书）
INSERT IGNORE INTO `fa_ssl_config` (
  `domain`, 
  `contact_email`, 
  `provider`, 
  `auto_renew`, 
  `is_enabled`, 
  `status`, 
  `create_time`, 
  `update_time`
) VALUES 
('www.yourdomain.com', 'admin@yourdomain.com', 'letsencrypt', 1, 1, 'pending', NOW(), NOW()),
('admin.yourdomain.com', 'admin@yourdomain.com', 'letsencrypt', 1, 1, 'pending', NOW(), NOW()),
('api.yourdomain.com', 'admin@yourdomain.com', 'letsencrypt', 1, 1, 'pending', NOW(), NOW());

-- 配置说明：
-- 1. 默认使用Let's Encrypt免费证书
-- 2. 自动续约功能已开启
-- 3. 请将yourdomain.com替换为您的实际域名
-- 4. 请将admin@yourdomain.com替换为您的实际邮箱

-- SSL证书续约日志表
CREATE TABLE IF NOT EXISTS `fa_ssl_renew_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `ssl_config_id` int(11) NOT NULL COMMENT 'SSL配置ID',
  `domain` varchar(255) NOT NULL COMMENT '域名',
  `action` varchar(20) NOT NULL COMMENT '操作类型：apply-申请，renew-续约，check-检查',
  `status` varchar(20) NOT NULL COMMENT '操作状态：success-成功，failed-失败',
  `message` text DEFAULT NULL COMMENT '操作结果信息',
  `error_message` text DEFAULT NULL COMMENT '错误信息',
  `cert_expire_time` datetime DEFAULT NULL COMMENT '证书过期时间',
  `execute_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '执行时间',
  PRIMARY KEY (`id`),
  KEY `idx_ssl_config_id` (`ssl_config_id`),
  KEY `idx_domain` (`domain`),
  KEY `idx_action` (`action`),
  KEY `idx_status` (`status`),
  KEY `idx_execute_time` (`execute_time`),
  CONSTRAINT `fk_ssl_renew_log_config` FOREIGN KEY (`ssl_config_id`) REFERENCES `fa_ssl_config` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSL证书续约日志表';
