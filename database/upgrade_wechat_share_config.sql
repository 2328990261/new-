-- ============================================
-- 微信分享配置升级脚本
-- 为 notification_config 表添加微信分享相关字段
-- ============================================

-- 检查并添加微信分享配置字段
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'fa_notification_config' 
     AND table_schema = DATABASE()
     AND column_name = 'wechat_share_title') > 0,
    'SELECT "wechat_share_title 字段已存在" as message',
    'ALTER TABLE `fa_notification_config` ADD COLUMN `wechat_share_title` VARCHAR(200) DEFAULT "优质家教信息平台" COMMENT "微信分享标题"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'fa_notification_config' 
     AND table_schema = DATABASE()
     AND column_name = 'wechat_share_desc') > 0,
    'SELECT "wechat_share_desc 字段已存在" as message',
    'ALTER TABLE `fa_notification_config` ADD COLUMN `wechat_share_desc` VARCHAR(500) DEFAULT "专业的家教信息平台，为您提供优质的家教服务" COMMENT "微信分享描述"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'fa_notification_config' 
     AND table_schema = DATABASE()
     AND column_name = 'wechat_share_image') > 0,
    'SELECT "wechat_share_image 字段已存在" as message',
    'ALTER TABLE `fa_notification_config` ADD COLUMN `wechat_share_image` VARCHAR(500) DEFAULT "" COMMENT "微信分享图片URL"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'fa_notification_config' 
     AND table_schema = DATABASE()
     AND column_name = 'wechat_share_enabled') > 0,
    'SELECT "wechat_share_enabled 字段已存在" as message',
    'ALTER TABLE `fa_notification_config` ADD COLUMN `wechat_share_enabled` TINYINT(1) DEFAULT 1 COMMENT "是否启用微信分享"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 更新现有配置的默认值（如果字段为空）
UPDATE `fa_notification_config` 
SET 
    `wechat_share_title` = COALESCE(NULLIF(`wechat_share_title`, ''), '优质家教信息平台'),
    `wechat_share_desc` = COALESCE(NULLIF(`wechat_share_desc`, ''), '专业的家教信息平台，为您提供优质的家教服务'),
    `wechat_share_enabled` = COALESCE(`wechat_share_enabled`, 1)
WHERE `id` = 1;

-- 如果配置记录不存在，创建默认记录
INSERT IGNORE INTO `fa_notification_config` (
    `id`, 
    `wechat_share_title`, 
    `wechat_share_desc`, 
    `wechat_share_image`, 
    `wechat_share_enabled`
) VALUES (
    1, 
    '优质家教信息平台', 
    '专业的家教信息平台，为您提供优质的家教服务', 
    '', 
    1
);

-- 显示升级结果
SELECT '微信分享配置升级完成' as message;
SELECT 
    id,
    wechat_share_title,
    wechat_share_desc,
    wechat_share_image,
    wechat_share_enabled
FROM `fa_notification_config` 
WHERE id = 1;
