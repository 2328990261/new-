-- 给支出管理表添加单价金额字段
-- 执行时间：2026-04-28

ALTER TABLE `fa_salary` 
ADD COLUMN `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价金额' AFTER `quantity`;
