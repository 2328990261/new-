-- ============================================
-- 城市点亮功能 - 添加助力相关字段
-- 创建时间: 2025-10-06
-- 功能说明: 添加 is_assist 和 inviter_identifier 字段支持助力机制
-- ============================================

USE myjiajiao;

-- 添加助力相关字段
ALTER TABLE `fa_city_lights` 
ADD COLUMN `is_assist` tinyint(1) DEFAULT 0 COMMENT '是否为助力: 0-自己点亮, 1-助力点亮' AFTER `user_contact`,
ADD COLUMN `inviter_identifier` varchar(100) DEFAULT NULL COMMENT '邀请人标识（助力时记录）' AFTER `is_assist`;

-- 添加索引以提高查询效率
ALTER TABLE `fa_city_lights`
ADD KEY `idx_is_assist` (`is_assist`),
ADD KEY `idx_inviter` (`inviter_identifier`);

-- 更新已有数据，将现有记录标记为非助力
UPDATE `fa_city_lights` SET `is_assist` = 0 WHERE `is_assist` IS NULL;

-- 显示成功信息
SELECT '✅ 助力字段添加成功！' AS message;
SELECT '📊 已添加 is_assist 和 inviter_identifier 字段' AS detail;
SELECT '🔍 已为这些字段创建索引以提升性能' AS tip;

