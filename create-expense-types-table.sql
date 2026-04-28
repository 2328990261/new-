-- 创建费用类型表
CREATE TABLE IF NOT EXISTS `fa_expense_types` (
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
