-- 双端收藏功能补丁
-- 1. 规范教师收藏家教表字段，和家长收藏教师表保持一致的设计风格
-- 2. 新增家长收藏教师表

ALTER TABLE `teacher_favorite_tutor`
  MODIFY COLUMN `teacher_id` int(11) DEFAULT NULL COMMENT '教师ID',
  MODIFY COLUMN `openid` varchar(100) NOT NULL COMMENT '教师微信openid',
  MODIFY COLUMN `phone` varchar(20) DEFAULT NULL COMMENT '教师手机号',
  MODIFY COLUMN `tutor_order_id` varchar(20) NOT NULL COMMENT '家教订单ID',
  MODIFY COLUMN `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间';

DROP TABLE IF EXISTS `parent_favorite_teacher`;
CREATE TABLE `parent_favorite_teacher` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '家长用户ID',
  `openid` varchar(100) NOT NULL COMMENT '家长微信openid',
  `phone` varchar(20) DEFAULT NULL COMMENT '家长手机号',
  `teacher_id` int(11) NOT NULL COMMENT '教师ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid_teacher` (`openid`, `teacher_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='家长收藏教师表';
