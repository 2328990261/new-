-- 将薪酬管理表改造为费用支出管理表
-- 备份原表
CREATE TABLE IF NOT EXISTS `fa_salary_backup` LIKE `fa_salary`;
INSERT INTO `fa_salary_backup` SELECT * FROM `fa_salary`;

-- 修改表结构
ALTER TABLE `fa_salary` 
  -- 删除旧字段
  DROP COLUMN `personnel_id`,
  DROP COLUMN `base_salary`,
  DROP COLUMN `bonus`,
  DROP COLUMN `deduction`,
  DROP COLUMN `total_salary`,
  DROP COLUMN `payment_date`,
  
  -- 添加新字段
  ADD COLUMN `expense_date` DATE NOT NULL COMMENT '支出日期' AFTER `id`,
  ADD COLUMN `expense_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '费用类型' AFTER `expense_date`,
  ADD COLUMN `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '数量' AFTER `expense_type`,
  ADD COLUMN `project_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '项目名称' AFTER `quantity`,
  ADD COLUMN `amount` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '金额' AFTER `project_name`,
  ADD COLUMN `invoice_attachment` VARCHAR(500) DEFAULT '' COMMENT '发票附件' AFTER `amount`,
  ADD COLUMN `payment_attachment` VARCHAR(500) DEFAULT '' COMMENT '付款附件' AFTER `invoice_attachment`,
  ADD COLUMN `invoice_status` VARCHAR(20) NOT NULL DEFAULT '未开票' COMMENT '发票状态：未开票、已开票、已收票' AFTER `payment_status`,
  ADD COLUMN `receipt_method` VARCHAR(50) DEFAULT '' COMMENT '收款方式' AFTER `invoice_status`,
  ADD COLUMN `payment_method` VARCHAR(50) DEFAULT '' COMMENT '支付方式' AFTER `receipt_method`,
  ADD COLUMN `period` VARCHAR(20) DEFAULT '' COMMENT '所属周期' AFTER `payment_method`,
  
  -- 修改现有字段
  MODIFY COLUMN `month` VARCHAR(20) DEFAULT '' COMMENT '所属月份（保留用于兼容）',
  MODIFY COLUMN `payment_status` VARCHAR(20) NOT NULL DEFAULT '未付款' COMMENT '付款状态：未付款、已付款、部分付款';

-- 添加索引
ALTER TABLE `fa_salary` 
  ADD INDEX `idx_expense_date` (`expense_date`),
  ADD INDEX `idx_expense_type` (`expense_type`),
  ADD INDEX `idx_project_name` (`project_name`),
  ADD INDEX `idx_period` (`period`);

-- 更新表注释
ALTER TABLE `fa_salary` COMMENT='费用支出管理表';
