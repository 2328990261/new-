-- 企业微信：客户联系回调 + 加好友欢迎语（需在管理端配置后，到企业微信后台填写回调 URL）
-- 执行前请确认表名为 fa_wecom_config（与 database.php 前缀一致）

ALTER TABLE `fa_wecom_config`
  ADD COLUMN `callback_token` varchar(64) NOT NULL DEFAULT '' COMMENT '客户联系回调Token' AFTER `join_way_mark_source`,
  ADD COLUMN `callback_encoding_aes_key` varchar(64) NOT NULL DEFAULT '' COMMENT '客户联系回调EncodingAESKey' AFTER `callback_token`,
  ADD COLUMN `welcome_after_contact_text` varchar(800) NOT NULL DEFAULT '' COMMENT '加好友后欢迎语文案（与链接卡片二选一可并存）' AFTER `callback_encoding_aes_key`,
  ADD COLUMN `welcome_link_title` varchar(128) NOT NULL DEFAULT '加入同城家教群' COMMENT '欢迎语链接卡片标题' AFTER `welcome_after_contact_text`,
  ADD COLUMN `welcome_public_base_url` varchar(512) NOT NULL DEFAULT '' COMMENT '落地页/回调拼接用公网根地址，如 https://api.example.com' AFTER `welcome_link_title`;
