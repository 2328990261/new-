-- 线索管理系统 - 数据库表创建脚本
-- 数据库: myjiajiao
-- 创建时间: 2025-10-19
-- 说明: 线索管理功能相关表

USE myjiajiao;

-- 禁用外键检查
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. 线索主表
-- ============================================
CREATE TABLE IF NOT EXISTS `fa_leads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '线索ID',
  `lead_no` VARCHAR(50) NOT NULL COMMENT '线索编号',
  `raw_content` TEXT NOT NULL COMMENT '原始录入文本',
  `city_id` INT(11) DEFAULT NULL COMMENT '所属城市ID',
  `district_id` INT(11) DEFAULT NULL COMMENT '所属区域ID',
  `grade` VARCHAR(50) DEFAULT NULL COMMENT '年级',
  `subject` VARCHAR(50) DEFAULT NULL COMMENT '科目',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
  `contact_name` VARCHAR(50) DEFAULT NULL COMMENT '联系人姓名',
  `assigned_admin_id` INT(11) DEFAULT NULL COMMENT '指派客服ID',
  `status` ENUM('待跟进', '跟进中', '已发单', '不需要', '无效') DEFAULT '待跟进' COMMENT '跟进状态',
  `tutor_content` TEXT DEFAULT NULL COMMENT '发单内容（状态为已发单时必填）',
  `invalid_image` VARCHAR(255) DEFAULT NULL COMMENT '无效图片路径（状态为无效时必填）',
  `create_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `creator_admin_id` INT(11) DEFAULT NULL COMMENT '创建人管理员ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lead_no` (`lead_no`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_admin` (`assigned_admin_id`),
  KEY `idx_city_id` (`city_id`),
  KEY `idx_create_time` (`create_time`),
  CONSTRAINT `fk_lead_city` FOREIGN KEY (`city_id`) REFERENCES `fa_cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lead_district` FOREIGN KEY (`district_id`) REFERENCES `fa_districts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lead_assigned_admin` FOREIGN KEY (`assigned_admin_id`) REFERENCES `fa_admin` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lead_creator_admin` FOREIGN KEY (`creator_admin_id`) REFERENCES `fa_admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='线索主表';

-- ============================================
-- 2. 跟进记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `fa_lead_follow_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `lead_id` INT(11) NOT NULL COMMENT '线索ID',
  `old_status` VARCHAR(20) DEFAULT NULL COMMENT '原状态',
  `new_status` VARCHAR(20) NOT NULL COMMENT '新状态',
  `remark` TEXT DEFAULT NULL COMMENT '备注',
  `operator_admin_id` INT(11) DEFAULT NULL COMMENT '操作人管理员ID',
  `create_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_create_time` (`create_time`),
  CONSTRAINT `fk_follow_log_lead` FOREIGN KEY (`lead_id`) REFERENCES `fa_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_follow_log_operator` FOREIGN KEY (`operator_admin_id`) REFERENCES `fa_admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='线索跟进记录表';

-- 启用外键检查
SET FOREIGN_KEY_CHECKS = 1;

-- 完成
SELECT '线索管理系统数据库表创建完成！' AS message;

