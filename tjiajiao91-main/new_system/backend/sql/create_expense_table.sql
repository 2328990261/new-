-- 删除旧表（如果存在）
DROP TABLE IF EXISTS `fa_salary`;

-- 创建费用支出管理表
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
