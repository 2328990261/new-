-- 若你已建过 wecom_city_groups 表，请执行本 ALTER 补字段

-- 注意：本项目数据库表前缀为 fa_，请对 fa_wecom_city_groups 执行

ALTER TABLE `fa_wecom_city_groups`
  ADD COLUMN `chat_id_list` TEXT NULL COMMENT '客户群ID列表JSON（add_join_way必填）' AFTER `group_name`;

