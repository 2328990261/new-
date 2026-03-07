-- ============================================
-- 为管理员表添加微信二维码字段
-- 功能：支持派单组和客服组成员上传微信二维码
-- 创建时间: 2025-01-XX
-- ============================================

USE myjiajiao;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. 为 fa_admin 表添加 wechat_qrcode 字段
-- ============================================

-- 检查并添加 wechat_qrcode 字段（微信二维码URL）
SET @dbname = DATABASE();
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'wechat_qrcode'
  ) > 0,
  'SELECT "wechat_qrcode字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN wechat_qrcode VARCHAR(500) DEFAULT NULL COMMENT "微信二维码URL，用于派单组和客服组成员" AFTER contact'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 查看表结构确认
SHOW COLUMNS FROM `fa_admin` WHERE Field = 'wechat_qrcode';

SELECT '✅ 微信二维码字段添加完成！' AS status;

SET FOREIGN_KEY_CHECKS = 1;

