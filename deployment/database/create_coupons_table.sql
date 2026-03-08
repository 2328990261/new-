-- ============================================
-- 教师推荐奖励系统 - 抵扣券表
-- ============================================
-- 功能说明：
-- 1. 存储推荐奖励抵扣券信息
-- 2. 记录抵扣券的发放、核销、过期状态
-- 3. 关联抵扣券与用户和推荐关系
-- 4. 支持管理员人工核销流程
-- 5. 跟踪抵扣券的完整生命周期
-- ============================================

-- 创建抵扣券表
CREATE TABLE IF NOT EXISTS `fa_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '抵扣券ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `referral_id` int(11) NOT NULL COMMENT '关联的推荐关系ID',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '抵扣券金额（元）',
  `status` varchar(20) NOT NULL DEFAULT 'issued' COMMENT '状态：issued-已发放，verified-已核销，expired-已过期',
  `issued_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发放时间',
  `verified_at` timestamp NULL DEFAULT NULL COMMENT '核销时间',
  `verified_by` int(11) DEFAULT NULL COMMENT '核销管理员ID',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`) COMMENT '用户ID索引',
  KEY `idx_referral_id` (`referral_id`) COMMENT '推荐关系ID索引',
  KEY `idx_status` (`status`) COMMENT '状态索引',
  KEY `idx_expires_at` (`expires_at`) COMMENT '过期时间索引',
  KEY `idx_verified_by` (`verified_by`) COMMENT '核销管理员索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='抵扣券表';
