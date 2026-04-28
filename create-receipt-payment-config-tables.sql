-- 创建收款单位配置表
CREATE TABLE IF NOT EXISTS `fa_receipt_method_config` (
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

-- 创建支付方式配置表
CREATE TABLE IF NOT EXISTS `fa_payment_method_config` (
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

-- 插入一些默认数据
INSERT INTO `fa_receipt_method_config` (`name`, `sort`, `create_time`, `update_time`) VALUES
('公司对公账户', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('个人账户', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('第三方平台', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO `fa_payment_method_config` (`name`, `sort`, `create_time`, `update_time`) VALUES
('对公转账', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('支付宝', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('微信支付', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('现金', 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('银行卡', 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
