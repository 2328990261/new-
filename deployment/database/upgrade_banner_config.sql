-- ============================================
-- 横幅配置升级脚本
-- 为网站基础配置表添加横幅相关字段
-- ============================================

USE tjiajiao91;

-- 为 fa_site_config 表添加横幅相关字段
ALTER TABLE `fa_site_config` 
ADD COLUMN `banner_image` VARCHAR(500) DEFAULT '' COMMENT '横幅图片URL' AFTER `favicon_url`,
ADD COLUMN `banner_link` VARCHAR(500) DEFAULT '' COMMENT '横幅链接' AFTER `banner_image`,
ADD COLUMN `banner_title` VARCHAR(200) DEFAULT '' COMMENT '横幅标题' AFTER `banner_link`,
ADD COLUMN `banner_description` TEXT COMMENT '横幅描述' AFTER `banner_title`;

-- 显示升级结果
SELECT '横幅配置字段添加完成' as message;
DESCRIBE `fa_site_config`;
