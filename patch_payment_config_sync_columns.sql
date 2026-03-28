-- 一次性补齐支付配置表字段（避免逐个报 Unknown column）
-- 适用于表前缀为 fa_ 的环境
-- 执行后可再次在后台「基础配置 -> 支付配置」保存/测试

SET @db := DATABASE();

-- app_secret
SET @exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'fa_payment_config'
    AND COLUMN_NAME = 'app_secret'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `fa_payment_config` ADD COLUMN `app_secret` varchar(100) DEFAULT NULL COMMENT ''应用密钥'' AFTER `api_key`',
  'SELECT ''skip app_secret''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- cert_path
SET @exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'fa_payment_config'
    AND COLUMN_NAME = 'cert_path'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `fa_payment_config` ADD COLUMN `cert_path` varchar(255) DEFAULT NULL COMMENT ''证书路径'' AFTER `app_secret`',
  'SELECT ''skip cert_path''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- key_path
SET @exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'fa_payment_config'
    AND COLUMN_NAME = 'key_path'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `fa_payment_config` ADD COLUMN `key_path` varchar(255) DEFAULT NULL COMMENT ''密钥路径'' AFTER `cert_path`',
  'SELECT ''skip key_path''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- notify_url
SET @exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'fa_payment_config'
    AND COLUMN_NAME = 'notify_url'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `fa_payment_config` ADD COLUMN `notify_url` varchar(255) DEFAULT NULL COMMENT ''支付回调地址'' AFTER `key_path`',
  'SELECT ''skip notify_url''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- is_enabled
SET @exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'fa_payment_config'
    AND COLUMN_NAME = 'is_enabled'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `fa_payment_config` ADD COLUMN `is_enabled` tinyint(1) DEFAULT 0 COMMENT ''是否启用：0-禁用，1-启用'' AFTER `notify_url`',
  'SELECT ''skip is_enabled''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- create_time
SET @exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'fa_payment_config'
    AND COLUMN_NAME = 'create_time'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `fa_payment_config` ADD COLUMN `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT ''创建时间'' AFTER `is_enabled`',
  'SELECT ''skip create_time''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- update_time
SET @exists := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'fa_payment_config'
    AND COLUMN_NAME = 'update_time'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE `fa_payment_config` ADD COLUMN `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT ''更新时间'' AFTER `create_time`',
  'SELECT ''skip update_time''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

