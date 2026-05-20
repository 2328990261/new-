-- 为 fa_wecom_config 增加入群活码默认参数（99546）
ALTER TABLE `fa_wecom_config`
  ADD COLUMN `join_way_scene` TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '入群方式场景scene(1小程序插件/2二维码插件)' AFTER `owner_userids`,
  ADD COLUMN `join_way_auto_create_room` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'auto_create_room(0否/1是)' AFTER `join_way_scene`,
  ADD COLUMN `join_way_room_base_name` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'room_base_name(自动建群前缀)' AFTER `join_way_auto_create_room`,
  ADD COLUMN `join_way_room_base_id` INT NOT NULL DEFAULT 0 COMMENT 'room_base_id(自动建群起始序号)' AFTER `join_way_room_base_name`,
  ADD COLUMN `join_way_mark_source` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'mark_source(仅营销获客应用生效)' AFTER `join_way_room_base_id`;

