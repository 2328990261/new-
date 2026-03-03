-- ============================================
-- 创建投递管理表（线上部署版本）
-- 表名：fa_resume_application
-- 用于记录教师对家教订单的投递申请
-- ============================================

-- 检查表是否存在，如果存在则删除（谨慎使用）
-- DROP TABLE IF EXISTS `fa_resume_application`;

-- 创建投递管理表
CREATE TABLE IF NOT EXISTS `fa_resume_application` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '投递记录ID',
  `teacher_id` int(11) NOT NULL COMMENT '教师ID，关联fa_teachers表',
  `tutor_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders_new表',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '状态：pending-待审核，approved-已通过，rejected-已拒绝',
  `apply_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '投递时间',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `admin_remark` text COMMENT '管理员备注',
  `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID，关联fa_admin表',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_tutor_id` (`tutor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_apply_time` (`apply_time`),
  KEY `idx_reviewer_id` (`reviewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='简历投递记录表';

-- 创建唯一索引，防止同一教师重复投递同一订单
CREATE UNIQUE INDEX `idx_unique_application` ON `fa_resume_application` (`teacher_id`, `tutor_id`);

-- 添加外键约束（可选，根据实际需求决定是否启用）
-- ALTER TABLE `fa_resume_application` 
--   ADD CONSTRAINT `fk_resume_application_teacher` 
--   FOREIGN KEY (`teacher_id`) REFERENCES `fa_teachers` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_resume_application_tutor` 
--   FOREIGN KEY (`tutor_id`) REFERENCES `fa_tutor_orders_new` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_resume_application_reviewer` 
--   FOREIGN KEY (`reviewer_id`) REFERENCES `fa_admin` (`id`) ON DELETE SET NULL;

-- 插入测试数据（可选）
-- INSERT INTO `fa_resume_application` (`teacher_id`, `tutor_id`, `status`, `apply_time`, `admin_remark`) 
-- VALUES (1, 1, 'pending', NOW(), '测试投递记录');

SELECT '✓ 投递管理表 fa_resume_application 创建成功！' AS message;
