-- 企业配置表（用于企业管理模块的企业微信配置）
CREATE TABLE IF NOT EXISTS `fa_enterprise_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `corp_id` varchar(100) NOT NULL DEFAULT '' COMMENT '企业ID',
  `agent_id` varchar(50) NOT NULL DEFAULT '' COMMENT '应用AgentId',
  `agent_secret` varchar(255) NOT NULL DEFAULT '' COMMENT '应用Secret',
  `contacts_secret` varchar(255) NOT NULL DEFAULT '' COMMENT '通讯录Secret',
  `visible_users` text COMMENT '可见成员列表（JSON格式）',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否启用二维码回显',
  `userid_mapping` varchar(50) DEFAULT 'userid' COMMENT 'userid映射字段',
  `remark` text COMMENT '备注',
  `create_time` int(11) unsigned DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) unsigned DEFAULT 0 COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_corp_id` (`corp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业配置表（企业管理模块）';
