-- ============================================
-- 通知系统数据表创建脚本
-- 包含：邮件通知、微信服务号通知、订阅管理、发送日志
-- ============================================

-- 1. 通知配置表（邮件+微信服务号）
CREATE TABLE IF NOT EXISTS `fa_notification_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  
  -- 邮件配置
  `email_enabled` tinyint(1) DEFAULT 0 COMMENT '是否启用邮件通知',
  `smtp_host` varchar(100) DEFAULT NULL COMMENT 'SMTP服务器地址',
  `smtp_port` int(11) DEFAULT 465 COMMENT 'SMTP端口',
  `smtp_username` varchar(100) DEFAULT NULL COMMENT 'SMTP用户名',
  `smtp_password` varchar(255) DEFAULT NULL COMMENT 'SMTP密码',
  `smtp_secure` tinyint(1) DEFAULT 1 COMMENT '是否启用SSL',
  `from_email` varchar(100) DEFAULT NULL COMMENT '发件人邮箱',
  `from_name` varchar(100) DEFAULT '家教信息平台' COMMENT '发件人名称',
  `email_template` text COMMENT '邮件模板',
  
  -- 微信服务号配置
  `wechat_enabled` tinyint(1) DEFAULT 0 COMMENT '是否启用微信通知',
  `wechat_app_id` varchar(100) DEFAULT NULL COMMENT '微信AppID',
  `wechat_app_secret` varchar(255) DEFAULT NULL COMMENT '微信AppSecret',
  `wechat_access_token` varchar(512) DEFAULT NULL COMMENT '微信AccessToken',
  `wechat_token_expire_time` int(11) DEFAULT 0 COMMENT 'AccessToken过期时间',
  
  -- 微信分享配置
  `wechat_share_enabled` tinyint(1) DEFAULT 1 COMMENT '是否启用微信分享',
  `wechat_share_title` varchar(200) DEFAULT '优质家教信息平台' COMMENT '微信分享标题',
  `wechat_share_desc` varchar(500) DEFAULT '专业的家教信息平台，为您提供优质的家教服务' COMMENT '微信分享描述',
  `wechat_share_image` varchar(500) DEFAULT '' COMMENT '微信分享图片URL',
  
  -- 客服配置
  `customer_service_wechat` varchar(100) DEFAULT NULL COMMENT '客服微信号',
  
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知配置表（邮件+微信）';

-- 插入默认配置
INSERT INTO `fa_notification_config` (`id`, `email_enabled`, `wechat_enabled`, `from_name`) 
VALUES (1, 0, 0, '家教信息平台') 
ON DUPLICATE KEY UPDATE id=id;

-- 2. 微信模板消息配置表
CREATE TABLE IF NOT EXISTS `fa_wechat_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `template_code` varchar(50) NOT NULL COMMENT '模板代码（如：order_notify）',
  `template_name` varchar(100) NOT NULL COMMENT '模板名称',
  `template_id` varchar(100) NOT NULL COMMENT '微信模板ID',
  `title` varchar(100) DEFAULT NULL COMMENT '模板标题',
  `content` text COMMENT '模板内容',
  `primary_industry` varchar(50) DEFAULT NULL COMMENT '主行业',
  `deputy_industry` varchar(50) DEFAULT NULL COMMENT '副行业',
  `is_enabled` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `field_mapping` text COMMENT '字段映射（JSON格式）',
  `url` varchar(500) DEFAULT NULL COMMENT '跳转链接',
  `miniprogram_appid` varchar(100) DEFAULT NULL COMMENT '小程序AppID',
  `miniprogram_pagepath` varchar(500) DEFAULT NULL COMMENT '小程序页面路径',
  `color` varchar(20) DEFAULT '#173177' COMMENT '字体颜色',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_code` (`template_code`),
  KEY `idx_template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信模板消息配置表';

-- 3. 通知订阅表（邮件+微信）
CREATE TABLE IF NOT EXISTS `fa_notification_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订阅ID',
  `channel` varchar(20) NOT NULL DEFAULT 'email' COMMENT '订阅渠道：email-邮件, wechat-微信',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱地址（邮件渠道）',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信OpenID（微信渠道）',
  `city_id` int(11) DEFAULT NULL COMMENT '订阅城市ID',
  `district_ids` varchar(500) DEFAULT NULL COMMENT '订阅区域ID列表（逗号分隔）',
  `subject_ids` varchar(500) DEFAULT NULL COMMENT '订阅科目ID列表（逗号分隔）',
  `grade` varchar(50) DEFAULT NULL COMMENT '订阅年级',
  `status` tinyint(1) DEFAULT 1 COMMENT '订阅状态：1-已订阅，0-已取消',
  `is_verified` tinyint(1) DEFAULT 0 COMMENT '是否已验证',
  `verify_code` varchar(10) DEFAULT NULL COMMENT '验证码',
  `verify_time` datetime DEFAULT NULL COMMENT '验证时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_openid` (`openid`),
  KEY `idx_city` (`city_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知订阅表';

-- 4. 通知发送日志表
CREATE TABLE IF NOT EXISTS `fa_notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `channel` varchar(20) NOT NULL DEFAULT 'email' COMMENT '发送渠道：email-邮件, wechat-微信',
  `receiver` varchar(100) DEFAULT NULL COMMENT '接收者（邮箱或OpenID）',
  `template_code` varchar(50) DEFAULT NULL COMMENT '模板代码',
  `subject` varchar(200) DEFAULT NULL COMMENT '邮件主题',
  `content` text COMMENT '发送内容',
  `status` tinyint(1) DEFAULT 0 COMMENT '发送状态：1-成功，0-失败',
  `error_msg` varchar(500) DEFAULT NULL COMMENT '错误信息',
  `send_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
  PRIMARY KEY (`id`),
  KEY `idx_receiver` (`receiver`),
  KEY `idx_channel` (`channel`),
  KEY `idx_status` (`status`),
  KEY `idx_send_time` (`send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知发送日志表';

-- 完成
SELECT '通知系统数据表创建完成' as message;
