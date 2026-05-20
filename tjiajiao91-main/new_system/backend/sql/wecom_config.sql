-- 企业微信配置（id=1 单行配置）

-- 注意：本项目数据库表前缀为 fa_（从报错 fa_wecom_config 可见），因此表名使用 fa_wecom_config

CREATE TABLE IF NOT EXISTS `fa_wecom_config` (
  `id` INT UNSIGNED NOT NULL,
  `corp_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '企业ID',
  `agent_id` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '应用AgentId',
  `secret` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '应用Secret',
  `contact_secret` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '客户联系Secret（客户群入群二维码需要）',
  `owner_userids` TEXT NULL COMMENT '用于拉取客户群列表的成员userid列表JSON（一次配置，全城复用）',
  `join_way_scene` TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '入群方式场景scene(1小程序插件/2二维码插件)',
  `join_way_auto_create_room` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'auto_create_room(0否/1是)',
  `join_way_room_base_name` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'room_base_name(自动建群前缀)',
  `join_way_room_base_id` INT NOT NULL DEFAULT 0 COMMENT 'room_base_id(自动建群起始序号)',
  `join_way_mark_source` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'mark_source(仅营销获客应用生效；若应用非获客助手建议设为0)',
  `create_time` DATETIME NULL DEFAULT NULL,
  `update_time` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信配置';

