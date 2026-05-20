-- 为“联系我”方案补充字段（按城市生成/复用联系我二维码）
-- 表前缀为 fa_

ALTER TABLE `fa_wecom_city_groups`
  ADD COLUMN `contact_way_config_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '企业微信联系我config_id' AFTER `qr_code`,
  ADD COLUMN `contact_way_qr_code` VARCHAR(512) NOT NULL DEFAULT '' COMMENT '联系我二维码链接/URL' AFTER `contact_way_config_id`,
  ADD COLUMN `missing_group` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '缺群待处理：1=缺外部客户群，需要客服创建并回填chat_id_list' AFTER `contact_way_qr_code`,
  ADD COLUMN `request_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '缺群请求次数（contact_way 分支累计）' AFTER `missing_group`,
  ADD COLUMN `last_request_time` DATETIME NULL DEFAULT NULL COMMENT '最近一次缺群请求时间' AFTER `request_count`;

-- 为 fa_wecom_city_groups 增加“联系我”二维码存储字段
ALTER TABLE `fa_wecom_city_groups`
  ADD COLUMN `contact_way_config_id` VARCHAR(64) NULL DEFAULT NULL COMMENT '联系我配置ID（add_contact_way）' AFTER `join_way_config_id`,
  ADD COLUMN `contact_way_qr_code` TEXT NULL DEFAULT NULL COMMENT '联系我二维码（qr_code）' AFTER `contact_way_config_id`;

