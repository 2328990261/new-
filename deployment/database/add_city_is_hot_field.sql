-- ============================================
-- 为城市表添加 is_hot 字段
-- 用于标记热门城市，热门城市在下拉框中优先显示
-- ============================================

-- 添加 is_hot 字段（如果字段已存在会报错，可忽略）
ALTER TABLE `fa_cities` 
ADD COLUMN `is_hot` tinyint(1) DEFAULT 0 COMMENT '是否热门城市：1是 0否' AFTER `level`;

-- 添加索引提升查询性能
ALTER TABLE `fa_cities` 
ADD INDEX `idx_is_hot` (`is_hot`);

-- 可选：将一些主要城市设为热门（根据实际需要调整）
-- UPDATE `fa_cities` SET `is_hot` = 1 WHERE `name` IN ('北京', '上海', '广州', '深圳', '杭州', '南京', '成都', '重庆', '武汉', '西安');

