-- ============================================
-- 管理员分组和派单功能升级脚本
-- 功能：支持客服组和派单组
-- 创建时间: 2025-10-07
-- ============================================

USE myjiajiao;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. 升级 fa_admin 表，添加角色和联系方式字段
-- ============================================

-- 检查并添加 role 字段（角色）
SET @dbname = DATABASE();
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'role'
  ) > 0,
  'SELECT "role字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN role VARCHAR(50) DEFAULT "customer_service" COMMENT "角色：customer_service(客服组)、dispatcher(派单组)、super_admin(超级管理员)" AFTER password'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 nickname 字段（昵称）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'nickname'
  ) > 0,
  'SELECT "nickname字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN nickname VARCHAR(100) DEFAULT NULL COMMENT "昵称，用于显示" AFTER username'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 contact 字段（联系方式）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'contact'
  ) > 0,
  'SELECT "contact字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN contact VARCHAR(100) DEFAULT NULL COMMENT "联系方式（微信号），用于派单组" AFTER role'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 status 字段（状态）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'status'
  ) > 0,
  'SELECT "status字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN status TINYINT(1) DEFAULT 1 COMMENT "状态：1-启用 0-禁用" AFTER contact'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 email 字段（邮箱）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'email'
  ) > 0,
  'SELECT "email字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN email VARCHAR(100) DEFAULT NULL COMMENT "邮箱地址" AFTER status'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 last_login_time 字段（最后登录时间）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'last_login_time'
  ) > 0,
  'SELECT "last_login_time字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN last_login_time TIMESTAMP NULL DEFAULT NULL COMMENT "最后登录时间" AFTER email'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 create_time 字段（创建时间）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'create_time'
  ) > 0,
  'SELECT "create_time字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN create_time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT "创建时间" AFTER last_login_time'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 update_time 字段（更新时间）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND COLUMN_NAME = 'update_time'
  ) > 0,
  'SELECT "update_time字段已存在" AS info',
  'ALTER TABLE fa_admin ADD COLUMN update_time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "更新时间" AFTER create_time'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 2. 升级 fa_tutor_orders_new 表，添加派单相关字段
-- ============================================

-- 检查并添加 dispatcher_id 字段（派单管理员ID）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_tutor_orders_new' AND COLUMN_NAME = 'dispatcher_id'
  ) > 0,
  'SELECT "dispatcher_id字段已存在" AS info',
  'ALTER TABLE fa_tutor_orders_new ADD COLUMN dispatcher_id INT(11) DEFAULT NULL COMMENT "派单管理员ID" AFTER admin_id, ADD KEY idx_dispatcher_id (dispatcher_id)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 contact_info 字段（派单联系方式）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_tutor_orders_new' AND COLUMN_NAME = 'contact_info'
  ) > 0,
  'SELECT "contact_info字段已存在" AS info',
  'ALTER TABLE fa_tutor_orders_new ADD COLUMN contact_info VARCHAR(255) DEFAULT NULL COMMENT "派单联系方式（微信号）" AFTER dispatcher_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 检查并添加 assigned_time 字段（派单时间）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_tutor_orders_new' AND COLUMN_NAME = 'assigned_time'
  ) > 0,
  'SELECT "assigned_time字段已存在" AS info',
  'ALTER TABLE fa_tutor_orders_new ADD COLUMN assigned_time TIMESTAMP NULL DEFAULT NULL COMMENT "派单时间" AFTER contact_info'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 3. 更新现有管理员的默认角色
-- ============================================

-- 将没有设置角色的管理员默认设置为客服组
UPDATE fa_admin SET role = 'customer_service' WHERE role IS NULL OR role = '';

-- ============================================
-- 4. 插入示例派单组成员（如果需要）
-- ============================================

-- 插入示例派单组成员1（如果不存在）
INSERT INTO fa_admin (username, nickname, password, role, contact, status, email) 
SELECT 'dispatcher1', '派单员-小李', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dispatcher', 'wechat_xiaoli', 1, 'dispatcher1@example.com'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fa_admin WHERE username = 'dispatcher1');

-- 插入示例派单组成员2（如果不存在）
INSERT INTO fa_admin (username, nickname, password, role, contact, status, email) 
SELECT 'dispatcher2', '派单员-小王', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dispatcher', 'wechat_xiaowang', 1, 'dispatcher2@example.com'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fa_admin WHERE username = 'dispatcher2');

-- 插入示例派单组成员3（如果不存在）
INSERT INTO fa_admin (username, nickname, password, role, contact, status, email) 
SELECT 'dispatcher3', '派单员-小张', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dispatcher', 'wechat_xiaozhang', 1, 'dispatcher3@example.com'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fa_admin WHERE username = 'dispatcher3');

-- ============================================
-- 5. 添加索引以优化查询性能
-- ============================================

-- 为 fa_admin 表添加索引（如果不存在）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND INDEX_NAME = 'idx_role'
  ) > 0,
  'SELECT "idx_role索引已存在" AS info',
  'ALTER TABLE fa_admin ADD KEY idx_role (role)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_admin' AND INDEX_NAME = 'idx_status'
  ) > 0,
  'SELECT "idx_status索引已存在" AS info',
  'ALTER TABLE fa_admin ADD KEY idx_status (status)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 完成提示
-- ============================================
SELECT '升级完成！管理员分组和派单功能已启用。' AS message;
SELECT '默认派单员账号密码均为：password' AS notice;
SELECT '请登录后及时修改派单员的密码和联系方式！' AS warning;

