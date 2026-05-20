-- 教师验证码表（表前缀 fa_，与 ThinkPHP 配置保持一致）
-- 可被 deploy_teacher_verification_codes.bat / .sh 引用

CREATE TABLE IF NOT EXISTS `fa_teacher_verification_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `teacher_id` int(11) DEFAULT NULL COMMENT '教师ID（登录时记录）',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱地址',
  `code` varchar(20) NOT NULL COMMENT '验证码',
  `type` varchar(20) DEFAULT 'email' COMMENT '验证码类型：email-邮箱验证，login-登录记录，reset-密码重置',
  `is_used` tinyint(1) DEFAULT 0 COMMENT '是否已使用：0-未使用，1-已使用',
  `expire_time` int(11) DEFAULT NULL COMMENT '过期时间（Unix时间戳）',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间（Unix时间戳）',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间（Unix时间戳）',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_code` (`code`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师验证码表';
