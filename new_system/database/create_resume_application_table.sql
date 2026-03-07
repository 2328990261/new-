-- 创建投递管理表
CREATE TABLE IF NOT EXISTS `fa_resume_application` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '投递记录ID',
  `teacher_id` int(11) NOT NULL COMMENT '教师ID',
  `tutor_id` int(11) NOT NULL COMMENT '家教订单ID',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '状态：pending-待审核，approved-已通过，rejected-已拒绝',
  `apply_time` datetime NOT NULL COMMENT '投递时间',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `admin_remark` text COMMENT '管理员备注',
  PRIMARY KEY (`id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_tutor_id` (`tutor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_apply_time` (`apply_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='简历投递记录表';
