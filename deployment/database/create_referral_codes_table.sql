-- ============================================
-- 教师推荐奖励系统 - 推荐码表
-- ============================================
-- 功能说明：
-- 1. 存储用户生成的唯一推荐码
-- 2. 关联推荐码与用户ID
-- 3. 记录推荐码的创建和更新时间
-- 4. 支持推荐码状态管理（活跃/禁用）
-- ============================================

-- 创建推荐码表
CREATE TABLE IF NOT EXISTS `fa_referral_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '推荐码ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `code` varchar(32) NOT NULL COMMENT '推荐码（唯一）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1-活跃，0-禁用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`) COMMENT '推荐码唯一索引',
  KEY `idx_user_id` (`user_id`) COMMENT '用户ID索引',
  KEY `idx_status` (`status`) COMMENT '状态索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推荐码表';
