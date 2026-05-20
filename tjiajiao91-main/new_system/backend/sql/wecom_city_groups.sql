-- 企业微信同城家教群（按城市生成/复用群二维码）
-- 注意：本项目未发现统一迁移体系，此文件用于手工建表/对照。

-- 注意：本项目数据库表前缀为 fa_，因此表名使用 fa_wecom_city_groups

CREATE TABLE IF NOT EXISTS `fa_wecom_city_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `city_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '城市ID',
  `city_name` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '城市名',
  `group_name` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '群名：【91家教】{城市}家教群',
  `chat_id_list` TEXT NULL COMMENT '客户群ID列表JSON（add_join_way必填）',
  `join_way_config_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '企业微信入群方式config_id',
  `qr_code` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '二维码链接/URL',
  `contact_way_config_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '企业微信联系我config_id',
  `contact_way_qr_code` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '联系我二维码链接/URL',
  `missing_group` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '缺群待处理：1=缺外部客户群，需要客服创建并回填chat_id_list',
  `request_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '缺群请求次数（contact_way 分支累计）',
  `last_request_time` DATETIME NULL DEFAULT NULL COMMENT '最近一次缺群请求时间',
  `member_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '群人数（可手动维护）',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
  `create_time` DATETIME NULL DEFAULT NULL,
  `update_time` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_city` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信同城家教群';

