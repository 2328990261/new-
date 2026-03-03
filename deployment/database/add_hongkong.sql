-- 添加香港省份、城市和18个区域
-- 执行时间：2025-10-25

-- 注意：如果省份表中已有香港，请先查询其ID并修改下面的 @province_id 值

-- 1. 添加香港省份（如果不存在）
INSERT INTO `fa_provinces` (`name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT '香港', 'HK', 1, 100, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `fa_provinces` WHERE `code` = 'HK');

-- 获取香港省份ID
SELECT @province_id := `id` FROM `fa_provinces` WHERE `code` = 'HK' LIMIT 1;

-- 2. 添加香港城市（如果不存在）
INSERT INTO `fa_cities` (`province_id`, `name`, `code`, `level`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @province_id, '香港', 'HK', '特别行政区', 1, 100, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `fa_cities` WHERE `code` = 'HK' AND `province_id` = @province_id);

-- 获取香港城市ID
SELECT @city_id := `id` FROM `fa_cities` WHERE `code` = 'HK' AND `province_id` = @province_id LIMIT 1;

-- 3. 添加香港18个区域
-- 先删除可能已存在的区域（如果需要重新插入）
-- DELETE FROM `fa_districts` WHERE `city_id` = @city_id;

-- 香港岛（4个区）
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '中西区', 'HK01', 1, 1, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK01' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '湾仔区', 'HK02', 1, 2, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK02' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '东区', 'HK03', 1, 3, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK03' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '南区', 'HK04', 1, 4, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK04' AND `city_id` = @city_id);

-- 九龙（5个区）
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '油尖旺区', 'HK05', 1, 5, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK05' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '深水埗区', 'HK06', 1, 6, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK06' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '九龙城区', 'HK07', 1, 7, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK07' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '黄大仙区', 'HK08', 1, 8, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK08' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '观塘区', 'HK09', 1, 9, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK09' AND `city_id` = @city_id);

-- 新界（9个区）
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '荃湾区', 'HK10', 1, 10, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK10' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '屯门区', 'HK11', 1, 11, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK11' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '元朗区', 'HK12', 1, 12, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK12' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '北区', 'HK13', 1, 13, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK13' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '大埔区', 'HK14', 1, 14, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK14' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '沙田区', 'HK15', 1, 15, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK15' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '西贡区', 'HK16', 1, 16, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK16' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '葵青区', 'HK17', 1, 17, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK17' AND `city_id` = @city_id);
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '离岛区', 'HK18', 1, 18, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'HK18' AND `city_id` = @city_id);

-- 验证插入结果
SELECT '=== 插入完成 ===' AS message;
SELECT CONCAT('省份ID: ', @province_id) AS province_info;
SELECT CONCAT('城市ID: ', @city_id) AS city_info;

-- 查询插入的数据
SELECT '=== 省份信息 ===' AS section;
SELECT * FROM `fa_provinces` WHERE `id` = @province_id;

SELECT '=== 城市信息 ===' AS section;
SELECT * FROM `fa_cities` WHERE `id` = @city_id;

SELECT '=== 区域信息 ===' AS section;
SELECT * FROM `fa_districts` WHERE `city_id` = @city_id ORDER BY `sort`;

-- 统计
SELECT CONCAT('共插入 ', COUNT(*), ' 个区域') AS district_count 
FROM `fa_districts` WHERE `city_id` = @city_id;

