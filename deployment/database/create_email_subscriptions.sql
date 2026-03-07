-- 创建邮件订阅表
-- 数据库: myjiajiao
-- 创建时间: 2025-10-07

USE myjiajiao;

-- 创建邮件订阅表
CREATE TABLE IF NOT EXISTS `fa_email_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订阅ID',
  `email` varchar(100) NOT NULL COMMENT '邮箱地址',
  `city_ids` varchar(255) DEFAULT NULL COMMENT '订阅城市ID（逗号分隔，NULL表示全部）',
  `district_ids` varchar(255) DEFAULT NULL COMMENT '订阅区域ID（逗号分隔，NULL表示全部）',
  `subject_ids` varchar(255) DEFAULT NULL COMMENT '订阅科目ID（逗号分隔，NULL表示全部）',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `verify_token` varchar(64) DEFAULT NULL COMMENT '邮箱验证令牌',
  `is_verified` tinyint(1) DEFAULT 0 COMMENT '是否已验证：1是 0否',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_is_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件订阅表';

-- 完成
SELECT '邮件订阅表创建完成！' AS message;

