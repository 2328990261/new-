-- 若你已建过 fa_wecom_config 表，请执行本 ALTER 补字段

ALTER TABLE `fa_wecom_config`
  ADD COLUMN `owner_userids` TEXT NULL COMMENT '用于拉取客户群列表的成员userid列表JSON（一次配置，全城复用）' AFTER `contact_secret`;

