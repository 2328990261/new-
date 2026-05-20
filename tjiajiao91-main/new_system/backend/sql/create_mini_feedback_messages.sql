-- 小程序反馈对话消息表
CREATE TABLE IF NOT EXISTS `fa_mini_feedback_messages` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `feedback_id` BIGINT UNSIGNED NOT NULL COMMENT '关联 fa_mini_feedbacks.id',
  `sender`      VARCHAR(20)     NOT NULL DEFAULT 'user'  COMMENT '发送方：user=用户, admin=管理员',
  `content`     TEXT            NOT NULL COMMENT '消息内容',
  `images`      TEXT            NULL     COMMENT '图片URL列表（JSON数组，用户消息可带图）',
  `create_time` DATETIME        NOT NULL COMMENT '发送时间',
  PRIMARY KEY (`id`),
  KEY `idx_feedback_id` (`feedback_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反馈对话消息表';
