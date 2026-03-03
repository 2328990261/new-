-- 为邮件订阅表添加年级段字段
-- 数据库: myjiajiao
-- 创建时间: 2025-02-22

USE myjiajiao;

-- 添加年级段字段
ALTER TABLE `fa_email_subscriptions` 
ADD COLUMN `grade_levels` varchar(255) DEFAULT NULL COMMENT '订阅年级段（逗号分隔，NULL表示全部）' AFTER `subject_ids`;

-- 完成
SELECT '邮件订阅表已添加年级段字段！' AS message;
