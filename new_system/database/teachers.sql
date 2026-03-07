-- 教师表
-- 用于存储教师的详细信息

USE myjiajiao;

CREATE TABLE IF NOT EXISTS `fa_teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '教师ID',
  `account_id` int(11) DEFAULT NULL COMMENT '关联的教师账号ID',
  `name` varchar(50) NOT NULL COMMENT '教师姓名',
  `gender` varchar(10) DEFAULT NULL COMMENT '性别：男/女',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `education` varchar(50) DEFAULT NULL COMMENT '学历：本科/硕士/博士等',
  `school` varchar(100) DEFAULT NULL COMMENT '毕业院校',
  `major` varchar(100) DEFAULT NULL COMMENT '专业',
  `hourly_rate` decimal(10,2) DEFAULT NULL COMMENT '课时费（元/小时）',
  `subject_ids` varchar(255) DEFAULT NULL COMMENT '教授科目ID列表（逗号分隔）',
  `subject_names` varchar(255) DEFAULT NULL COMMENT '教授科目名称列表（逗号分隔）',
  `district_ids` varchar(255) DEFAULT NULL COMMENT '授课区域ID列表（逗号分隔）',
  `district_names` varchar(255) DEFAULT NULL COMMENT '授课区域名称列表（逗号分隔）',
  `experience` text COMMENT '教学经历',
  `self_intro` text COMMENT '自我介绍',
  `photos` text COMMENT '照片URL列表（JSON格式）',
  `status` varchar(20) DEFAULT 'pending' COMMENT '审核状态：pending-待审核，approved-已通过，rejected-已拒绝',
  `reject_reason` text COMMENT '拒绝原因',
  `is_top` tinyint(1) DEFAULT 0 COMMENT '是否置顶：0-否，1-是',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师表';

