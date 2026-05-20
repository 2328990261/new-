-- 企业管理模块数据库表

-- 人员表
CREATE TABLE IF NOT EXISTS `fa_personnel` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` varchar(20) DEFAULT '' COMMENT '手机号',
  `id_card` varchar(18) DEFAULT '' COMMENT '身份证号',
  `employment_status` enum('在职','离职') NOT NULL DEFAULT '在职' COMMENT '在职状态',
  `employment_type` enum('全职','兼职') NOT NULL DEFAULT '全职' COMMENT '雇佣类型',
  `entry_date` date DEFAULT NULL COMMENT '入职日期',
  `departure_date` date DEFAULT NULL COMMENT '离职日期',
  `department` varchar(50) DEFAULT '' COMMENT '部门',
  `position` varchar(50) DEFAULT '' COMMENT '职位',
  `remark` text COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_employment_status` (`employment_status`),
  KEY `idx_employment_type` (`employment_type`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人员信息表';

-- 薪酬表
CREATE TABLE IF NOT EXISTS `fa_salary` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `personnel_id` int(11) unsigned NOT NULL COMMENT '人员ID',
  `month` varchar(7) NOT NULL COMMENT '所属月份(YYYY-MM)',
  `base_salary` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '基本工资',
  `bonus` decimal(10,2) DEFAULT '0.00' COMMENT '奖金',
  `deduction` decimal(10,2) DEFAULT '0.00' COMMENT '扣款',
  `total_salary` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实发工资',
  `payment_status` enum('未发放','已发放') NOT NULL DEFAULT '未发放' COMMENT '发放状态',
  `payment_date` date DEFAULT NULL COMMENT '发放日期',
  `remark` text COMMENT '备注',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_personnel_id` (`personnel_id`),
  KEY `idx_month` (`month`),
  KEY `idx_payment_status` (`payment_status`),
  CONSTRAINT `fk_salary_personnel` FOREIGN KEY (`personnel_id`) REFERENCES `fa_personnel` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='薪酬记录表';
