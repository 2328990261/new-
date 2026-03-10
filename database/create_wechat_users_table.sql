-- 创建小程序用户表
CREATE TABLE IF NOT EXISTS `fa_wechat_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `openid` varchar(100) NOT NULL COMMENT '用户OpenID',
  `unionid` varchar(100) DEFAULT NULL COMMENT '用户UnionID',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `nickname` varchar(100) DEFAULT NULL COMMENT '昵称',
  `headimgurl` varchar(500) DEFAULT NULL COMMENT '头像URL',
  `subscribe` tinyint(1) DEFAULT 0 COMMENT '是否关注公众号',
  `subscribe_time` datetime DEFAULT NULL COMMENT '关注时间',
  `user_type` varchar(20) DEFAULT NULL COMMENT '用户类型',
  `user_id` int(11) DEFAULT NULL COMMENT '关联用户ID',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信小程序用户表';
