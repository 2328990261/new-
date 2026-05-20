-- 企业微信同城家教群：增加群机器人 webhook（用于自动推送家教单到对应城市客户群）
-- 表前缀：fa_

ALTER TABLE `fa_wecom_city_groups`
  ADD COLUMN `robot_webhook` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '企微群机器人 Webhook（客户群推送）' AFTER `status`,
  ADD COLUMN `robot_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用机器人推送 0=关闭 1=开启' AFTER `robot_webhook`;

