-- 教师授课信息表
CREATE TABLE IF NOT EXISTS `teacher_teaching_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `teacher_id` int(11) NOT NULL COMMENT '教师ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信openid',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `city_id` int(11) DEFAULT NULL COMMENT '授课城市ID',
  `city_name` varchar(50) DEFAULT NULL COMMENT '授课城市名称',
  `districts` text COMMENT '授课区域，JSON格式存储 [{"id":1,"name":"朝阳区"},...]',
  `grades` text COMMENT '授课年级，JSON格式存储 ["小学一年级","小学二年级",...]',
  `subjects` text COMMENT '授课科目，JSON格式存储 ["语文","数学",...]',
  `subscribe_push` tinyint(1) DEFAULT '0' COMMENT '是否订阅家教推送 0-否 1-是',
  `wechat_notify` tinyint(1) DEFAULT '0' COMMENT '是否开启服务号通知 0-否 1-是',
  `email_notify` tinyint(1) DEFAULT '0' COMMENT '是否开启邮箱通知 0-否 1-是',
  `email` varchar(100) DEFAULT NULL COMMENT '接收通知的邮箱',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_teacher_id` (`teacher_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师授课信息表';

-- 教师收藏家教表
CREATE TABLE IF NOT EXISTS `teacher_favorite_tutor` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `teacher_id` int(11) DEFAULT NULL COMMENT '教师ID',
  `openid` varchar(100) NOT NULL COMMENT '微信openid',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `tutor_order_id` int(11) NOT NULL COMMENT '家教订单ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid_tutor` (`openid`, `tutor_order_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_tutor_order_id` (`tutor_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师收藏家教表';
