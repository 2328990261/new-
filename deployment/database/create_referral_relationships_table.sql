-- ============================================
-- 教师推荐奖励系统 - 推荐关系表
-- ============================================
-- 功能说明：
-- 1. 存储推荐人与被推荐人之间的关系
-- 2. 记录推荐关系建立时使用的推荐码
-- 3. 跟踪推荐关系的状态（活跃/已作废）
-- 4. 支持管理员手动作废可疑推荐关系
-- 5. 防止重复推荐（同一用户只能被推荐一次）
-- ============================================

-- 创建推荐关系表
CREATE TABLE IF NOT EXISTS `fa_referral_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '推荐关系ID',
  `referrer_id` int(11) NOT NULL COMMENT '推荐人用户ID',
  `referred_user_id` int(11) NOT NULL COMMENT '被推荐人用户ID',
  `referral_code` varchar(32) NOT NULL COMMENT '使用的推荐码',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1-活跃，0-已作废',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '推荐关系建立时间',
  `invalidated_at` timestamp NULL DEFAULT NULL COMMENT '作废时间',
  `invalidate_reason` varchar(255) DEFAULT NULL COMMENT '作废原因',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_referred_user` (`referred_user_id`) COMMENT '被推荐人唯一索引（防止重复推荐）',
  KEY `idx_referrer_id` (`referrer_id`) COMMENT '推荐人ID索引',
  KEY `idx_referral_code` (`referral_code`) COMMENT '推荐码索引',
  KEY `idx_status` (`status`) COMMENT '状态索引',
  KEY `idx_created_at` (`created_at`) COMMENT '创建时间索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推荐关系表';

