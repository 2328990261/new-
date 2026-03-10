-- ============================================
-- 管理员表完整升级SQL
-- 添加缺失字段：wechat_qrcode 和 leader_id
-- 包含备份和恢复步骤
-- ============================================

USE myjiajiao;
SET NAMES utf8mb4;

-- ============================================
-- 第1步：备份数据（必须执行）
-- ============================================

-- 删除旧备份表
DROP TABLE IF EXISTS `fa_admin_backup`;

-- 创建备份表
CREATE TABLE `fa_admin_backup` LIKE `fa_admin`;

-- 复制所有数据
INSERT INTO `fa_admin_backup` SELECT * FROM `fa_admin`;

-- 验证备份
SELECT 
    (SELECT COUNT(*) FROM `fa_admin`) AS original_count,
    (SELECT COUNT(*) FROM `fa_admin_backup`) AS backup_count;

SELECT '✅ 第1步完成：数据已备份' AS status;

-- ============================================
-- 第2步：添加 wechat_qrcode 字段
-- ============================================

ALTER TABLE `fa_admin` 
ADD COLUMN `wechat_qrcode` VARCHAR(500) DEFAULT NULL COMMENT '微信二维码URL，用于派单组和客服组成员' 
AFTER `contact`;

SELECT '✅ 第2步完成：wechat_qrcode 字段已添加' AS status;

-- ============================================
-- 第3步：添加 leader_id 字段（组长功能）
-- ============================================

ALTER TABLE `fa_admin` 
ADD COLUMN `leader_id` INT(11) DEFAULT NULL COMMENT '所属组长ID（客服组成员归属的组长）' 
AFTER `role`;

SELECT '✅ 第3步完成：leader_id 字段已添加' AS status;

-- ============================================
-- 第4步：添加索引
-- ============================================

-- 添加 role 索引（如果已存在会报错，可以忽略）
ALTER TABLE `fa_admin` ADD INDEX `idx_role` (`role`);

-- 添加 status 索引（如果已存在会报错，可以忽略）
ALTER TABLE `fa_admin` ADD INDEX `idx_status` (`status`);

-- 添加 leader_id 索引
ALTER TABLE `fa_admin` ADD INDEX `idx_leader_id` (`leader_id`);

SELECT '✅ 第4步完成：索引已添加' AS status;

-- ============================================
-- 第5步：更新 role 字段的可选值（添加 team_leader）
-- ============================================

-- 修改 role 字段，添加 team_leader 角色
ALTER TABLE `fa_admin` 
MODIFY COLUMN `role` VARCHAR(50) DEFAULT 'customer_service' 
COMMENT '角色：customer_service(客服组)、team_leader(组长)、dispatcher(派单组)、super_admin(超级管理员)';

SELECT '✅ 第5步完成：role 字段已更新' AS status;

-- ============================================
-- 第6步：验证升级结果
-- ============================================

-- 查看表结构
SHOW COLUMNS FROM `fa_admin`;

-- 验证数据完整性
SELECT 
    (SELECT COUNT(*) FROM `fa_admin`) AS current_count,
    (SELECT COUNT(*) FROM `fa_admin_backup`) AS backup_count,
    CASE 
        WHEN (SELECT COUNT(*) FROM `fa_admin`) = (SELECT COUNT(*) FROM `fa_admin_backup`) 
        THEN '✅ 数据完整' 
        ELSE '❌ 数据不一致' 
    END AS data_integrity;

-- 查看所有管理员
SELECT id, username, nickname, role, leader_id, status, email, wechat_qrcode FROM `fa_admin`;

SELECT '✅ 升级完成！' AS status;

-- ============================================
-- 回滚方案（如果升级失败）
-- ============================================

/*
如果升级失败，执行以下SQL回滚：

DROP TABLE IF EXISTS `fa_admin`;
RENAME TABLE `fa_admin_backup` TO `fa_admin`;
SELECT '✅ 已回滚到升级前状态' AS status;
*/

-- ============================================
-- 清理备份表（确认无误后执行）
-- ============================================

/*
建议保留备份表至少一周，确认系统正常后再删除：

DROP TABLE IF EXISTS `fa_admin_backup`;
SELECT '✅ 备份表已删除' AS status;
*/

-- ============================================
-- 升级说明
-- ============================================

/*
升级内容：
1. ✅ 添加 wechat_qrcode 字段 - 微信二维码URL
2. ✅ 添加 leader_id 字段 - 组长功能支持
3. ✅ 更新 role 字段 - 添加 team_leader 角色
4. ✅ 添加性能优化索引
5. ✅ 保留所有现有数据

字段说明：
- wechat_qrcode: 存储管理员的微信二维码图片URL
- leader_id: 客服组成员归属的组长ID，用于团队管理
  - 如果是组长，该字段为 NULL
  - 如果是普通客服，该字段指向其组长的 ID

角色说明：
- customer_service: 普通客服（可以有 leader_id）
- team_leader: 组长（leader_id 为 NULL）
- dispatcher: 派单组
- super_admin: 超级管理员

注意事项：
- 所有现有管理员的 leader_id 默认为 NULL（未分配组长）
- 需要在管理后台手动设置客服的组长归属
- 备份表建议保留至少一周
*/
