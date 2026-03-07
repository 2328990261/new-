-- ============================================
-- 重命名投递管理表
-- 将 fa_teacher_applications 重命名为 fa_resume_application
-- ============================================

-- 方案一：直接重命名表（推荐）
-- 如果您已经创建了 fa_teacher_applications 表，执行此语句
RENAME TABLE `fa_teacher_applications` TO `fa_resume_application`;

-- 方案二：如果重命名失败，可以先删除旧表，再创建新表
-- 注意：这会丢失数据，请谨慎使用！
-- DROP TABLE IF EXISTS `fa_teacher_applications`;
-- DROP TABLE IF EXISTS `fa_resume_application`;

-- 然后创建正确的表
-- CREATE TABLE IF NOT EXISTS `fa_resume_application` (
--   `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '投递记录ID',
--   `teacher_id` int(11) NOT NULL COMMENT '教师ID，关联fa_teachers表',
--   `tutor_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders_new表',
--   `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '状态：pending-待审核，approved-已通过，rejected-已拒绝',
--   `apply_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '投递时间',
--   `review_time` datetime DEFAULT NULL COMMENT '审核时间',
--   `admin_remark` text COMMENT '管理员备注',
--   `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID，关联fa_admin表',
--   `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
--   `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
--   PRIMARY KEY (`id`),
--   KEY `idx_teacher_id` (`teacher_id`),
--   KEY `idx_tutor_id` (`tutor_id`),
--   KEY `idx_status` (`status`),
--   KEY `idx_apply_time` (`apply_time`),
--   KEY `idx_reviewer_id` (`reviewer_id`),
--   UNIQUE KEY `idx_unique_application` (`teacher_id`, `tutor_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='简历投递记录表';

-- 验证表是否重命名成功
SELECT '✓ 表重命名成功！' AS message;
SHOW TABLES LIKE 'fa_resume_application';
