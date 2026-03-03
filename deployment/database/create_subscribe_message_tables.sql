-- 小程序订阅消息日志表
CREATE TABLE IF NOT EXISTS `fa_subscribe_message_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '用户OpenID',
  `template_id` varchar(100) NOT NULL COMMENT '模板ID',
  `template_name` varchar(100) DEFAULT NULL COMMENT '模板名称',
  `page` varchar(255) DEFAULT NULL COMMENT '跳转页面',
  `data` text COMMENT '发送数据(JSON)',
  `status` tinyint(1) DEFAULT '0' COMMENT '发送状态：0=失败，1=成功',
  `error_msg` varchar(500) DEFAULT NULL COMMENT '错误信息',
  `send_time` datetime DEFAULT NULL COMMENT '发送时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_send_time` (`send_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小程序订阅消息日志表';

-- 用户订阅记录表
CREATE TABLE IF NOT EXISTS `fa_user_subscribe` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `openid` varchar(100) NOT NULL COMMENT '用户OpenID',
  `template_id` varchar(100) NOT NULL COMMENT '模板ID',
  `subscribe_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '订阅时间',
  `is_used` tinyint(1) DEFAULT '0' COMMENT '是否已使用：0=未使用，1=已使用',
  `used_time` datetime DEFAULT NULL COMMENT '使用时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_is_used` (`is_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户订阅记录表';
