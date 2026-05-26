-- 创建小程序问题反馈表
-- 如果表已存在，请先删除：DROP TABLE IF EXISTS `fa_mini_feedbacks`;

CREATE TABLE IF NOT EXISTS `fa_mini_feedbacks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `platform` VARCHAR(20) NOT NULL DEFAULT 'wechat' COMMENT '平台：wechat=微信小程序, alipay=支付宝小程序',
  `user_role` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '用户角色：teacher=老师, parent=家长',
  `openid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '用户openid',
  `phone` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '用户手机号',
  `content` TEXT NOT NULL COMMENT '反馈内容',
  `images` TEXT NULL COMMENT '图片URL列表（JSON数组）',
  `reply_content` TEXT NULL COMMENT '回复内容',
  `reply_time` DATETIME NULL COMMENT '回复时间',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态：pending=待处理, replied=已回复',
  `create_time` DATETIME NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_role` (`user_role`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序问题反馈表';

-- 如果表已存在，添加新字段
ALTER TABLE `fa_mini_feedbacks` ADD COLUMN `reply_content` TEXT NULL COMMENT '回复内容' AFTER `images`;
ALTER TABLE `fa_mini_feedbacks` ADD COLUMN `reply_time` DATETIME NULL COMMENT '回复时间' AFTER `reply_content`;
ALTER TABLE `fa_mini_feedbacks` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态：pending=待处理, replied=已回复' AFTER `reply_time`;
ALTER TABLE `fa_mini_feedbacks` ADD KEY `idx_status` (`status`);
