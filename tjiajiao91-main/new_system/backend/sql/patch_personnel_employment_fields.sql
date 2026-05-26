-- 人员管理新增字段：入职日期、在职状态、离职日期、转正日期
-- 执行前请备份数据库
-- 适用表：fa_personnel

ALTER TABLE `fa_personnel`
  ADD COLUMN `entry_date`         DATE         NULL DEFAULT NULL COMMENT '入职日期' AFTER `position_type`,
  ADD COLUMN `employment_status`  VARCHAR(10)  NOT NULL DEFAULT '在职' COMMENT '在职状态：在职/离职' AFTER `entry_date`,
  ADD COLUMN `leave_date`         DATE         NULL DEFAULT NULL COMMENT '离职日期' AFTER `employment_status`,
  ADD COLUMN `regularize_date`    DATE         NULL DEFAULT NULL COMMENT '转正日期（实习/兼职转全职时填写）' AFTER `leave_date`;

-- 为在职状态加索引，方便按状态筛选
ALTER TABLE `fa_personnel`
  ADD INDEX `idx_employment_status` (`employment_status`),
  ADD INDEX `idx_position_type` (`position_type`);
