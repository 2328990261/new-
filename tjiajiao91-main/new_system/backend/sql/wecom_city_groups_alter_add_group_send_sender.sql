-- 企业微信同城家教群：为每个城市群单独配置“客户群群发 sender”
-- 说明：创建企业群发(chat_type=group)时，sender 必须是这些客户群的可群发成员（通常用群主最稳）
-- 表前缀：fa_

ALTER TABLE `fa_wecom_city_groups`
  ADD COLUMN `group_send_sender_userid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '客户群群发发送者成员userid（优先于全局contact_way_userid）' AFTER `chat_id_list`;

