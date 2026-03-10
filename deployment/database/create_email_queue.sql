-- 创建邮件队列表
CREATE TABLE IF NOT EXISTS `fa_email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '队列ID',
  `email_type` varchar(50) NOT NULL COMMENT '邮件类型：lead_assign=线索指派',
  `recipient_email` varchar(255) NOT NULL COMMENT '收件人邮箱',
  `recipient_name` varchar(100) DEFAULT NULL COMMENT '收件人姓名',
  `subject` varchar(255) NOT NULL COMMENT '邮件主题',
  `body` text NOT NULL COMMENT '邮件内容(HTML)',
  `related_id` int(11) DEFAULT NULL COMMENT '关联ID（如线索ID）',
  `status` enum('pending','sending','sent','failed') DEFAULT 'pending' COMMENT '状态',
  `retry_count` int(11) DEFAULT 0 COMMENT '重试次数',
  `error_message` text COMMENT '错误信息',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `sent_at` datetime DEFAULT NULL COMMENT '发送时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_email_type` (`email_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件发送队列';
