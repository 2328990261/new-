-- 清理企业配置表中的错误时间戳数据
-- 删除现有的错误数据，让用户重新保存

TRUNCATE TABLE `fa_enterprise_config`;

-- 或者如果想保留数据，只修复时间戳：
-- UPDATE `fa_enterprise_config` 
-- SET 
--   `create_time` = UNIX_TIMESTAMP(),
--   `update_time` = UNIX_TIMESTAMP()
-- WHERE `id` = 1;
