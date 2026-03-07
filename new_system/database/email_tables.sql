-- ========================================
-- 邮件系统相关表
-- 创建日期：2025-10-04
-- ========================================

USE myjiajiao;

-- 邮件配置表
CREATE TABLE IF NOT EXISTS `fa_email_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'ID',
  `smtp_host` varchar(255) DEFAULT NULL COMMENT 'SMTP服务器',
  `smtp_port` int(11) DEFAULT 465 COMMENT 'SMTP端口',
  `smtp_username` varchar(255) DEFAULT NULL COMMENT 'SMTP用户名',
  `smtp_password` varchar(255) DEFAULT NULL COMMENT 'SMTP密码',
  `smtp_secure` tinyint(1) DEFAULT 1 COMMENT '是否启用SSL：0-否，1-是',
  `from_email` varchar(255) DEFAULT NULL COMMENT '发件人邮箱',
  `from_name` varchar(100) DEFAULT '家教信息平台' COMMENT '发件人名称',
  `email_template` text COMMENT '邮件模板',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件配置表';

-- 插入默认配置
INSERT INTO `fa_email_config` (`id`, `smtp_host`, `smtp_port`, `from_name`, `email_template`) 
VALUES (1, 'smtp.qq.com', 465, '家教信息平台', 
'<div style="background: #f5f5f5; padding: 40px;">
  <div style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;">
    <h2 style="color: #409EFF; border-bottom: 2px solid #409EFF; padding-bottom: 10px;">{{title}}</h2>
    <div style="margin: 20px 0; line-height: 1.8;">
      {{content}}
    </div>
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px;">
      <p>此邮件由系统自动发送，请勿回复</p>
      <p>如有疑问，请联系我们</p>
    </div>
  </div>
</div>')
ON DUPLICATE KEY UPDATE `id` = 1;

-- 邮件订阅表
CREATE TABLE IF NOT EXISTS `fa_email_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'ID',
  `email` varchar(255) NOT NULL COMMENT '订阅邮箱',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0-已取消，1-已订阅',
  `is_verified` tinyint(1) DEFAULT 0 COMMENT '是否已验证：0-未验证，1-已验证',
  `verify_code` varchar(64) DEFAULT NULL COMMENT '验证码',
  `subscribe_ip` varchar(50) DEFAULT NULL COMMENT '订阅IP',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '订阅时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件订阅表';

-- 邮件发送日志表
CREATE TABLE IF NOT EXISTS `fa_email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'ID',
  `email` varchar(255) NOT NULL COMMENT '收件人邮箱',
  `subject` varchar(255) DEFAULT NULL COMMENT '邮件主题',
  `content` text COMMENT '邮件内容',
  `status` tinyint(1) DEFAULT 0 COMMENT '发送状态：0-失败，1-成功',
  `error_msg` text COMMENT '错误信息',
  `send_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
  KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `send_time` (`send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件发送日志表';

-- 显示表结构
SHOW TABLES LIKE 'fa_email%';

-- 完成提示
SELECT '========== 邮件表创建完成 ==========' AS '';
SELECT '已创建3个表：fa_email_config、fa_email_subscriptions、fa_email_logs' AS message;

