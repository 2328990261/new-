-- 管理员归属城市改为多城市（逗号分隔）补丁
-- 目标：
-- 1) fa_admin.city_id 由 int 改为 varchar(255)
-- 2) 自动移除 city_id 上的外键约束（若存在）
-- 3) 自动移除 city_id 上的索引（若存在，逗号分隔字段不适合普通索引）

START TRANSACTION;

-- 1. 删除 fa_admin.city_id 上的外键（兼容未知外键名）
SET @db_name = DATABASE();
SET @fk_name = NULL;

SELECT kcu.CONSTRAINT_NAME
INTO @fk_name
FROM information_schema.KEY_COLUMN_USAGE kcu
WHERE kcu.TABLE_SCHEMA = @db_name
  AND kcu.TABLE_NAME = 'fa_admin'
  AND kcu.COLUMN_NAME = 'city_id'
  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
LIMIT 1;

SET @drop_fk_sql = IF(
  @fk_name IS NULL,
  'SELECT ''no foreign key on fa_admin.city_id'' AS msg',
  CONCAT('ALTER TABLE `fa_admin` DROP FOREIGN KEY `', @fk_name, '`')
);
PREPARE stmt_drop_fk FROM @drop_fk_sql;
EXECUTE stmt_drop_fk;
DEALLOCATE PREPARE stmt_drop_fk;

-- 2. 删除 city_id 索引（兼容未知索引名；保留主键）
SET @idx_name = NULL;
SELECT s.INDEX_NAME
INTO @idx_name
FROM information_schema.STATISTICS s
WHERE s.TABLE_SCHEMA = @db_name
  AND s.TABLE_NAME = 'fa_admin'
  AND s.COLUMN_NAME = 'city_id'
  AND s.INDEX_NAME <> 'PRIMARY'
LIMIT 1;

SET @drop_idx_sql = IF(
  @idx_name IS NULL,
  'SELECT ''no index on fa_admin.city_id'' AS msg',
  CONCAT('ALTER TABLE `fa_admin` DROP INDEX `', @idx_name, '`')
);
PREPARE stmt_drop_idx FROM @drop_idx_sql;
EXECUTE stmt_drop_idx;
DEALLOCATE PREPARE stmt_drop_idx;

-- 3. 修改字段类型为 varchar，支持 "196,197,..." 存储
ALTER TABLE `fa_admin`
  MODIFY COLUMN `city_id` varchar(255) NULL COMMENT '归属城市ID，支持逗号分隔多个城市ID';

COMMIT;

-- 可选验证：
-- SHOW CREATE TABLE fa_admin;
-- SELECT id, username, role, city_id FROM fa_admin WHERE role='dispatcher';
