-- ============================================
-- 教师投递管理表
-- 用于记录教师对家教订单的投递申请
-- ============================================

CREATE TABLE IF NOT EXISTS `fa_teacher_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '投递ID',
  `teacher_id` int(11) NOT NULL COMMENT '教师ID，关联fa_teachers表',
  `tutor_order_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders表',
  `status` varchar(20) DEFAULT 'pending' COMMENT '投递状态：pending-待处理, accepted-已接受, rejected-已拒绝, cancelled-已取消',
  `application_note` text COMMENT '投递备注/自我推荐',
  `admin_note` text COMMENT '管理员备注',
  `reviewed_by` int(11) DEFAULT NULL COMMENT '审核人ID，关联fa_admin表',
  `reviewed_at` datetime DEFAULT NULL COMMENT '审核时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '投递时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_tutor_order_id` (`tutor_order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师投递管理表';

-- 添加外键约束（可选，根据实际需求决定是否启用）
-- ALTER TABLE `fa_teacher_applications` 
--   ADD CONSTRAINT `fk_teacher_applications_teacher` 
--   FOREIGN KEY (`teacher_id`) REFERENCES `fa_teachers` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_teacher_applications_tutor_order` 
--   FOREIGN KEY (`tutor_order_id`) REFERENCES `fa_tutor_orders` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_teacher_applications_admin` 
--   FOREIGN KEY (`reviewed_by`) REFERENCES `fa_admin` (`id`) ON DELETE SET NULL;

-- 创建唯一索引，防止同一教师重复投递同一订单
CREATE UNIQUE INDEX `idx_unique_application` ON `fa_teacher_applications` (`teacher_id`, `tutor_order_id`);
