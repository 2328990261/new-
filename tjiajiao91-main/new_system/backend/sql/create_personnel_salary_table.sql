-- 创建人员薪酬表
CREATE TABLE IF NOT EXISTS `fa_personnel_salary` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `personnel_id` int(11) NOT NULL COMMENT '人员ID，关联fa_personnel表',
  `base_salary` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '基本工资',
  `performance_salary` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '绩效工资',
  `post_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '岗位津贴',
  `housing_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '住房补贴',
  `meal_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '餐补',
  `transport_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '交通补贴',
  `other_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '其他补贴',
  `total_salary` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总薪酬（自动计算）',
  `effective_date` date NOT NULL COMMENT '生效日期',
  `end_date` date DEFAULT NULL COMMENT '结束日期（NULL表示当前有效）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=有效，0=失效',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_personnel_id` (`personnel_id`),
  KEY `idx_effective_date` (`effective_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人员薪酬表';
