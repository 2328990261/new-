-- ========================================
-- 升级预约系统 - 增加城市区域选择功能
-- ========================================
-- 创建时间: 2025-01-07
-- 说明: 为 parent_bookings 表添加 city_id 和 district_id 字段
--       将授课地址分为城市选择、区域选择和详细地址
-- ========================================

-- 检查表是否存在,如果不存在则创建
CREATE TABLE IF NOT EXISTS `parent_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `grade` varchar(50) NOT NULL COMMENT '学员年级',
  `subject` varchar(100) NOT NULL COMMENT '辅导科目',
  `student_info` text NOT NULL COMMENT '学生情况描述',
  `frequency` varchar(200) NOT NULL COMMENT '辅导次数和频率',
  `teacher_requirement` text NOT NULL COMMENT '对老师的要求',
  `city_id` int(11) DEFAULT NULL COMMENT '授课城市ID',
  `district_id` int(11) DEFAULT NULL COMMENT '授课区域ID',
  `address` varchar(500) NOT NULL COMMENT '详细地址（小区名、门牌号等）',
  `parent_name` varchar(50) NOT NULL COMMENT '家长称呼',
  `parent_contact` varchar(100) NOT NULL COMMENT '联系方式（手机/微信）',
  `remark` text COMMENT '备注信息',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0-待处理，1-已处理，2-已关闭',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_city` (`city_id`),
  KEY `idx_district` (`district_id`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='家长预约需求表';

-- 如果表已存在但缺少字段,则添加字段
ALTER TABLE `parent_bookings` 
ADD COLUMN IF NOT EXISTS `city_id` int(11) DEFAULT NULL COMMENT '授课城市ID' AFTER `teacher_requirement`;

ALTER TABLE `parent_bookings` 
ADD COLUMN IF NOT EXISTS `district_id` int(11) DEFAULT NULL COMMENT '授课区域ID' AFTER `city_id`;

-- 添加索引（如果不存在）
ALTER TABLE `parent_bookings` 
ADD INDEX IF NOT EXISTS `idx_city` (`city_id`);

ALTER TABLE `parent_bookings` 
ADD INDEX IF NOT EXISTS `idx_district` (`district_id`);

-- 添加外键约束（可选，如果需要严格的数据完整性）
-- ALTER TABLE `parent_bookings` 
-- ADD CONSTRAINT `fk_booking_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ALTER TABLE `parent_bookings` 
-- ADD CONSTRAINT `fk_booking_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 完成
SELECT '预约系统升级完成！已添加城市和区域字段。' AS message;

