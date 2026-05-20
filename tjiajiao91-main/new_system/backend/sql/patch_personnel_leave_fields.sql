-- 为 fa_personnel 表添加离职类型、离职原因、转正备注字段
-- 执行时间：2026-05-08

ALTER TABLE `fa_personnel`
  ADD COLUMN `leave_type`   VARCHAR(50)   NULL DEFAULT NULL COMMENT '离职类型（主动离职/被动离职/合同到期/其他）' AFTER `leave_date`,
  ADD COLUMN `leave_reason` VARCHAR(500)  NULL DEFAULT NULL COMMENT '离职原因' AFTER `leave_type`,
  ADD COLUMN `leave_remark` VARCHAR(500)  NULL DEFAULT NULL COMMENT '离职备注' AFTER `leave_reason`,
  ADD COLUMN `regularize_remark` VARCHAR(500) NULL DEFAULT NULL COMMENT '转正备注' AFTER `regularize_date`;
