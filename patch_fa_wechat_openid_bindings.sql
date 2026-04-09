-- 公众号/小程序 openid 绑定表
CREATE TABLE IF NOT EXISTS `fa_wechat_openid_bindings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `mini_openid` varchar(100) DEFAULT NULL COMMENT '小程序openid',
  `mp_openid` varchar(100) DEFAULT NULL COMMENT '公众号openid',
  `unionid` varchar(100) DEFAULT NULL COMMENT '微信unionid',
  `scene_key` varchar(128) DEFAULT NULL COMMENT '二维码场景值，如 bind_xxx',
  `is_subscribed` tinyint(1) NOT NULL DEFAULT '1' COMMENT '公众号关注状态：1已关注 0已取关',
  `subscribe_time` int(11) DEFAULT NULL COMMENT '最近关注时间戳',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mini_openid` (`mini_openid`),
  UNIQUE KEY `uk_mp_openid` (`mp_openid`),
  KEY `idx_unionid` (`unionid`),
  KEY `idx_scene_key` (`scene_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公众号与小程序openid绑定表';

