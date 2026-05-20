-- 企业微信配置表
CREATE TABLE IF NOT EXISTS `fa_wecom_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `corp_id` varchar(100) NOT NULL DEFAULT '' COMMENT '企业ID',
  `contact_secret` varchar(200) DEFAULT '' COMMENT '通讯录同步Secret',
  `agent_id` varchar(50) DEFAULT '' COMMENT '应用AgentId',
  `agent_secret` varchar(200) DEFAULT '' COMMENT '应用Secret',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信配置表';

-- 插入默认配置（请替换为实际的企业微信配置）
INSERT INTO `fa_wecom_config` (`id`, `corp_id`, `contact_secret`, `agent_id`, `agent_secret`, `create_time`, `update_time`) 
VALUES (1, '', '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `update_time` = UNIX_TIMESTAMP();
