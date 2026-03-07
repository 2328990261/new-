-- 线索表性能优化索引
-- 执行前请备份数据库

-- 1. 添加复合索引优化列表查询
ALTER TABLE `leads` ADD INDEX `idx_assigned_status` (`assigned_admin_id`, `status`);
ALTER TABLE `leads` ADD INDEX `idx_status_create` (`status`, `create_time`);
ALTER TABLE `leads` ADD INDEX `idx_city_status` (`city_id`, `status`);
ALTER TABLE `leads` ADD INDEX `idx_channel_status` (`channel`, `status`);
ALTER TABLE `leads` ADD INDEX `idx_create_time` (`create_time`);

-- 2. 跟进记录表索引优化
ALTER TABLE `lead_follow_logs` ADD INDEX `idx_lead_id` (`lead_id`);
ALTER TABLE `lead_follow_logs` ADD INDEX `idx_lead_create` (`lead_id`, `create_time`);

-- 3. 检查现有索引（执行前先查看）
-- SHOW INDEX FROM leads;
-- SHOW INDEX FROM lead_follow_logs;
