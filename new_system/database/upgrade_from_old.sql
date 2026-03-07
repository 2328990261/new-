-- ============================================
-- 家教信息管理系统 - 数据库升级迁移脚本
-- ============================================
-- 适用场景：从旧版数据库升级到新版系统
-- 功能：保留现有数据，创建新表结构，迁移旧数据
-- 创建时间：2025-10-03
-- ============================================

USE myjiajiao;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 第一步：创建新的基础数据表
-- ============================================

-- 1. 城市表
CREATE TABLE IF NOT EXISTS `fa_cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '城市ID',
  `name` varchar(50) NOT NULL COMMENT '城市名称',
  `level` varchar(20) DEFAULT NULL COMMENT '城市等级：一线城市/新一线城市/二线城市/三线城市',
  `sort` int(11) DEFAULT 0 COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='城市表';

-- 2. 区域表
CREATE TABLE IF NOT EXISTS `fa_districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '区域ID',
  `city_id` int(11) NOT NULL COMMENT '所属城市ID',
  `name` varchar(50) NOT NULL COMMENT '区域名称',
  `sort` int(11) DEFAULT 0 COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_city_id` (`city_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_city_name` (`city_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='区域表';

-- 3. 科目表
CREATE TABLE IF NOT EXISTS `fa_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '科目ID',
  `name` varchar(50) NOT NULL COMMENT '科目名称',
  `category` varchar(20) DEFAULT NULL COMMENT '科目分类：文科/理科/艺体/其他',
  `sort` int(11) DEFAULT 0 COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='科目表';

-- ============================================
-- 第二步：从旧表中提取并导入基础数据
-- ============================================

-- 2.1 导入城市数据（从fa_tutor_orders中提取去重）
INSERT IGNORE INTO `fa_cities` (`name`, `level`, `sort`, `status`)
SELECT DISTINCT 
  city,
  CASE 
    WHEN city IN ('北京市', '上海市', '广州市', '深圳市') THEN '一线城市'
    WHEN city IN ('成都市', '杭州市', '重庆市', '西安市', '苏州市', '武汉市', '南京市', '天津市', '郑州市', '长沙市', '东莞市', '沈阳市', '青岛市', '合肥市', '佛山市') THEN '新一线城市'
    ELSE '其他城市'
  END as level,
  0 as sort,
  1 as status
FROM `fa_tutor_orders`
WHERE city IS NOT NULL AND city != '' AND city != '全国'
ORDER BY city;

-- 2.2 导入区域数据（从fa_tutor_orders中提取）
INSERT IGNORE INTO `fa_districts` (`city_id`, `name`, `sort`, `status`)
SELECT DISTINCT 
  c.id,
  o.district,
  0 as sort,
  1 as status
FROM `fa_tutor_orders` o
JOIN `fa_cities` c ON o.city = c.name
WHERE o.district IS NOT NULL AND o.district != '' AND o.district != '线上'
ORDER BY c.id, o.district;

-- 2.3 导入科目数据
INSERT IGNORE INTO `fa_subjects` (`name`, `category`, `sort`) VALUES
('语文', '文科', 1),
('数学', '理科', 2),
('英语', '文科', 3),
('物理', '理科', 4),
('化学', '理科', 5),
('生物', '理科', 6),
('历史', '文科', 7),
('地理', '理科', 8),
('政治', '文科', 9),
('音乐', '艺体', 10),
('美术', '艺体', 11),
('体育', '艺体', 12),
('信息技术', '其他', 13),
('科学', '理科', 14),
('全科', '其他', 15),
('陪读', '其他', 16);

-- ============================================
-- 第三步：创建新版订单表
-- ============================================

CREATE TABLE IF NOT EXISTS `fa_tutor_orders_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `old_id` int(11) DEFAULT NULL COMMENT '旧表ID（用于数据迁移追溯）',
  `content` text NOT NULL COMMENT '订单内容',
  `city_id` int(11) DEFAULT NULL COMMENT '城市ID',
  `district_id` int(11) DEFAULT NULL COMMENT '区域ID',
  `grade` varchar(50) DEFAULT NULL COMMENT '年级',
  `subject_id` int(11) DEFAULT NULL COMMENT '科目ID',
  `salary` varchar(50) DEFAULT NULL COMMENT '薪资',
  `is_urgent` tinyint(1) DEFAULT 0 COMMENT '是否加急：1是 0否',
  `is_top` tinyint(1) DEFAULT 0 COMMENT '是否置顶：1是 0否',
  `top_expire_time` timestamp NULL DEFAULT NULL COMMENT '置顶过期时间',
  `admin_id` int(11) DEFAULT NULL COMMENT '创建管理员ID',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1正常 0已删除',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_old_id` (`old_id`),
  KEY `idx_city_id` (`city_id`),
  KEY `idx_district_id` (`district_id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_is_urgent` (`is_urgent`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='家教订单表（新版）';

-- ============================================
-- 第四步：迁移旧订单数据到新表
-- ============================================

INSERT INTO `fa_tutor_orders_new` 
  (`old_id`, `content`, `city_id`, `district_id`, `grade`, `subject_id`, `salary`, `is_urgent`, `admin_id`, `status`, `create_time`, `update_time`)
SELECT 
  o.id as old_id,
  o.content,
  c.id as city_id,
  d.id as district_id,
  o.grade,
  -- 根据科目名称匹配科目ID（只取第一个匹配的科目）
  (SELECT s.id FROM fa_subjects s WHERE o.subject LIKE CONCAT('%', s.name, '%') LIMIT 1) as subject_id,
  o.salary,
  o.is_urgent,
  o.admin_id,
  1 as status,
  o.create_time,
  o.update_time
FROM `fa_tutor_orders` o
LEFT JOIN `fa_cities` c ON o.city = c.name
LEFT JOIN `fa_districts` d ON (o.district = d.name AND c.id = d.city_id)
WHERE NOT EXISTS (
  SELECT 1 FROM `fa_tutor_orders_new` n WHERE n.old_id = o.id
)
ORDER BY o.id;

-- ============================================
-- 第五步：创建邮件相关表
-- ============================================

-- 5.1 邮件订阅表
CREATE TABLE IF NOT EXISTS `fa_email_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订阅ID',
  `email` varchar(100) NOT NULL COMMENT '邮箱地址',
  `city_ids` varchar(255) DEFAULT NULL COMMENT '订阅城市ID（逗号分隔，NULL表示全部）',
  `district_ids` varchar(255) DEFAULT NULL COMMENT '订阅区域ID（逗号分隔，NULL表示全部）',
  `subject_ids` varchar(255) DEFAULT NULL COMMENT '订阅科目ID（逗号分隔，NULL表示全部）',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `verify_token` varchar(64) DEFAULT NULL COMMENT '邮箱验证令牌',
  `is_verified` tinyint(1) DEFAULT 0 COMMENT '是否已验证：1是 0否',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_is_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件订阅表';

-- 5.2 邮件发送记录表
CREATE TABLE IF NOT EXISTS `fa_email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `email` varchar(100) NOT NULL COMMENT '收件人邮箱',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `subject` varchar(255) DEFAULT NULL COMMENT '邮件主题',
  `send_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
  `status` tinyint(1) DEFAULT 1 COMMENT '发送状态：1成功 0失败',
  `error_msg` text COMMENT '错误信息',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_send_time` (`send_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件发送记录表';

-- 5.3 邮件配置表
CREATE TABLE IF NOT EXISTS `fa_email_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `smtp_host` varchar(100) DEFAULT NULL COMMENT 'SMTP服务器地址',
  `smtp_port` int(11) DEFAULT 465 COMMENT 'SMTP端口',
  `smtp_username` varchar(100) DEFAULT NULL COMMENT 'SMTP用户名',
  `smtp_password` varchar(255) DEFAULT NULL COMMENT 'SMTP密码',
  `smtp_secure` varchar(10) DEFAULT 'ssl' COMMENT '加密方式：ssl/tls',
  `from_email` varchar(100) DEFAULT NULL COMMENT '发件人邮箱',
  `from_name` varchar(100) DEFAULT NULL COMMENT '发件人名称',
  `email_template` text COMMENT '邮件模板',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件配置表';

-- 插入默认邮件配置
INSERT INTO `fa_email_config` (`id`, `from_name`, `email_template`) VALUES
(1, '家教信息平台', '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .order-info { background-color: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>新家教信息通知</h1>
        </div>
        <div class="content">
            <p>您好！</p>
            <p>根据您的订阅条件，有新的家教信息推送给您：</p>
            <div class="order-info">
                <p><strong>城市区域：</strong>{{city}} {{district}}</p>
                <p><strong>年级科目：</strong>{{grade}} {{subject}}</p>
                <p><strong>课时薪资：</strong>{{salary}}</p>
                <p><strong>详细信息：</strong></p>
                <p>{{content}}</p>
            </div>
            <p>如需查看更多信息，请访问我们的网站。</p>
        </div>
        <div class="footer">
            <p>本邮件由系统自动发送，请勿直接回复</p>
            <p>如需取消订阅，请访问网站进行设置</p>
        </div>
    </div>
</body>
</html>')
ON DUPLICATE KEY UPDATE 
  from_name = VALUES(from_name),
  email_template = VALUES(email_template);

-- ============================================
-- 第六步：添加外键约束（可选，建议在数据验证后再添加）
-- ============================================

-- 取消注释以下代码以添加外键约束
/*
ALTER TABLE `fa_districts` 
  ADD CONSTRAINT `fk_district_city` 
  FOREIGN KEY (`city_id`) REFERENCES `fa_cities` (`id`) ON DELETE CASCADE;

ALTER TABLE `fa_tutor_orders_new`
  ADD CONSTRAINT `fk_order_city` 
  FOREIGN KEY (`city_id`) REFERENCES `fa_cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_district` 
  FOREIGN KEY (`district_id`) REFERENCES `fa_districts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_subject` 
  FOREIGN KEY (`subject_id`) REFERENCES `fa_subjects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_admin` 
  FOREIGN KEY (`admin_id`) REFERENCES `fa_admin` (`id`) ON DELETE SET NULL;
*/

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 数据迁移统计
-- ============================================

SELECT '============================================' AS '';
SELECT '数据库升级迁移完成！' AS '状态';
SELECT '============================================' AS '';

SELECT '城市数据统计' AS '类型', COUNT(*) AS '数量' FROM fa_cities
UNION ALL
SELECT '区域数据统计', COUNT(*) FROM fa_districts
UNION ALL
SELECT '科目数据统计', COUNT(*) FROM fa_subjects
UNION ALL
SELECT '旧订单数据', COUNT(*) FROM fa_tutor_orders
UNION ALL
SELECT '新订单数据', COUNT(*) FROM fa_tutor_orders_new
UNION ALL
SELECT '管理员数据', COUNT(*) FROM fa_admin;

SELECT '============================================' AS '';
SELECT '请验证数据后，根据需要添加外键约束（见脚本第六步）' AS '提示';
SELECT '============================================' AS '';






