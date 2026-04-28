-- ========================================
-- 支出管理完整建表SQL
-- 包含：费用支出表、费用类型表、收款单位配置表、支付方式配置表
-- ========================================

-- 1. 创建费用支出管理表 (fa_salary)
DROP TABLE IF EXISTS `fa_salary`;
CREATE TABLE `fa_salary` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `expense_date` date NOT NULL COMMENT '支出日期',
  `expense_type` varchar(50) NOT NULL DEFAULT '' COMMENT '费用类型',
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '数量',
  `project_name` varchar(200) NOT NULL DEFAULT '' COMMENT '项目名称',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `invoice_attachment` varchar(500) DEFAULT '' COMMENT '发票附件',
  `payment_attachment` varchar(500) DEFAULT '' COMMENT '付款附件',
  `payment_status` varchar(20) NOT NULL DEFAULT '未付款' COMMENT '付款状态：未付款、已付款、部分付款',
  `invoice_status` varchar(20) NOT NULL DEFAULT '未开票' COMMENT '发票状态：未开票、已开票、已收票',
  `receipt_method` varchar(50) DEFAULT '' COMMENT '收款方式',
  `payment_method` varchar(50) DEFAULT '' COMMENT '支付方式',
  `period` varchar(20) DEFAULT '' COMMENT '所属周期',
  `remark` text COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT '0' COMMENT '删除时间（软删除）',
  PRIMARY KEY (`id`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_expense_type` (`expense_type`),
  KEY `idx_project_name` (`project_name`),
  KEY `idx_period` (`period`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_invoice_status` (`invoice_status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='费用支出管理表';


-- 2. 创建费用类型表 (fa_expense_types)
DROP TABLE IF EXISTS `fa_expense_types`;
CREATE TABLE `fa_expense_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) NOT NULL COMMENT '费用类型名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=启用，0=禁用',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统内置：1=是，0=否',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='费用类型表';

-- 插入默认费用类型
INSERT INTO `fa_expense_types` (`name`, `sort`, `status`, `is_system`, `createtime`, `updatetime`) VALUES
('工资', 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('租金', 2, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('水电费', 3, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('办公用品', 4, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('差旅费', 5, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('招待费', 6, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('其他', 99, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 3. 创建收款单位配置表 (fa_receipt_method_config)
DROP TABLE IF EXISTS `fa_receipt_method_config`;
CREATE TABLE `fa_receipt_method_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(200) NOT NULL COMMENT '收款单位名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序（数字越小越靠前）',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间（软删除）',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收款单位配置表';

-- 插入默认收款单位
INSERT INTO `fa_receipt_method_config` (`name`, `sort`, `create_time`, `update_time`) VALUES
('公司对公账户', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('个人账户', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('第三方平台', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- 4. 创建支付方式配置表 (fa_payment_method_config)
DROP TABLE IF EXISTS `fa_payment_method_config`;
CREATE TABLE `fa_payment_method_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(200) NOT NULL COMMENT '支付方式名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序（数字越小越靠前）',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间（软删除）',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付方式配置表';

-- 插入默认支付方式
INSERT INTO `fa_payment_method_config` (`name`, `sort`, `create_time`, `update_time`) VALUES
('对公转账', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('支付宝', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('微信支付', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('现金', 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('银行卡', 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- ========================================
-- 建表完成！
-- 
-- 创建的表：
-- 1. fa_salary - 费用支出管理表（主表）
-- 2. fa_expense_types - 费用类型表（配置表）
-- 3. fa_receipt_method_config - 收款单位配置表（配置表）
-- 4. fa_payment_method_config - 支付方式配置表（配置表）
-- ========================================
