-- 薪酬管理新增字段：公积金（单位/个人）、社保（单位/个人）、归属月份
-- 执行前请备份数据库
-- 适用表：fa_personnel_salary

ALTER TABLE `fa_personnel_salary`
  ADD COLUMN `salary_month`              VARCHAR(7)     NULL DEFAULT NULL COMMENT '归属月份，格式 YYYY-MM' AFTER `other_allowance`,
  ADD COLUMN `provident_fund_company`    DECIMAL(10,2)  NOT NULL DEFAULT 0.00 COMMENT '公积金（单位）' AFTER `salary_month`,
  ADD COLUMN `provident_fund_personal`   DECIMAL(10,2)  NOT NULL DEFAULT 0.00 COMMENT '公积金（个人）' AFTER `provident_fund_company`,
  ADD COLUMN `social_insurance_company`  DECIMAL(10,2)  NOT NULL DEFAULT 0.00 COMMENT '社保（单位）' AFTER `provident_fund_personal`,
  ADD COLUMN `social_insurance_personal` DECIMAL(10,2)  NOT NULL DEFAULT 0.00 COMMENT '社保（个人）' AFTER `social_insurance_company`;

-- 用已有的 effective_date 回填 salary_month（兼容历史数据）
UPDATE `fa_personnel_salary`
SET `salary_month` = DATE_FORMAT(`effective_date`, '%Y-%m')
WHERE `salary_month` IS NULL AND `effective_date` IS NOT NULL;
