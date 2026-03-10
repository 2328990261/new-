-- ============================================
-- 教师推荐奖励系统 - 排行榜统计表
-- ============================================
-- 功能说明：
-- 1. 存储用户推荐数量的聚合统计数据
-- 2. 用于快速查询排行榜信息
-- 3. 实时更新用户的推荐成功数量
-- 4. 支持按推荐数量排序查询
-- ============================================

-- 创建排行榜统计表
CREATE TABLE IF NOT EXISTS `fa_referral_rankings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '排行榜记录ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `referral_count` int(11) NOT NULL DEFAULT 0 COMMENT '成功推荐数量',
  `last_updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`) COMMENT '用户ID唯一索引',
  KEY `idx_referral_count` (`referral_count`) COMMENT '推荐数量索引（用于排行榜排序）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='推荐排行榜统计表';
