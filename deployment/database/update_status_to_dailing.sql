-- 将所有"待跟进"状态更新为"待联系"
-- 注意：此脚本已废弃，请使用 alter_status_enum.sql
-- 原因：status字段是ENUM类型，需要先修改字段定义才能使用新值

-- 请执行以下脚本：
-- source new_system/database/alter_status_enum.sql

SELECT '请使用 alter_status_enum.sql 脚本来修改status字段类型' as message;
