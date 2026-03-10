-- 为中山市（ID: 211）添加24个镇街区域
-- 执行时间：2025-10-25

-- 设置中山市ID
SET @city_id = 211;

-- 添加中山市24个镇街区域
-- 主城区（5个街道）
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '石岐街道', 'ZS01', 1, 1, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS01' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '东区街道', 'ZS02', 1, 2, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS02' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '西区街道', 'ZS03', 1, 3, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS03' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '南区街道', 'ZS04', 1, 4, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS04' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '五桂山街道', 'ZS05', 1, 5, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS05' AND `city_id` = @city_id);

-- 镇（18个镇）
INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '火炬开发区', 'ZS06', 1, 6, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS06' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '黄圃镇', 'ZS07', 1, 7, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS07' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '南头镇', 'ZS08', 1, 8, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS08' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '东凤镇', 'ZS09', 1, 9, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS09' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '阜沙镇', 'ZS10', 1, 10, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS10' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '小榄镇', 'ZS11', 1, 11, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS11' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '东升镇', 'ZS12', 1, 12, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS12' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '古镇镇', 'ZS13', 1, 13, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS13' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '横栏镇', 'ZS14', 1, 14, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS14' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '三角镇', 'ZS15', 1, 15, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS15' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '民众镇', 'ZS16', 1, 16, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS16' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '南朗镇', 'ZS17', 1, 17, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS17' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '港口镇', 'ZS18', 1, 18, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS18' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '大涌镇', 'ZS19', 1, 19, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS19' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '沙溪镇', 'ZS20', 1, 20, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS20' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '三乡镇', 'ZS21', 1, 21, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS21' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '板芙镇', 'ZS22', 1, 22, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS22' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '神湾镇', 'ZS23', 1, 23, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS23' AND `city_id` = @city_id);

INSERT INTO `fa_districts` (`city_id`, `name`, `code`, `status`, `sort`, `create_time`, `update_time`) 
SELECT @city_id, '坦洲镇', 'ZS24', 1, 24, NOW(), NOW() FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `fa_districts` WHERE `code` = 'ZS24' AND `city_id` = @city_id);

-- 验证插入结果
SELECT '=== 插入完成 ===' AS message;
SELECT CONCAT('城市ID: ', @city_id) AS city_info;

-- 查询插入的区域
SELECT '=== 中山市镇街列表 ===' AS section;
SELECT * FROM `fa_districts` WHERE `city_id` = @city_id ORDER BY `sort`;

-- 统计
SELECT CONCAT('共有 ', COUNT(*), ' 个镇街') AS district_count 
FROM `fa_districts` WHERE `city_id` = @city_id;

