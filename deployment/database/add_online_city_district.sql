-- ============================================
-- 添加"全国"城市和"线上"区域
-- ============================================
-- 用于线上授课的家教单，城市显示为"全国"，区域显示为"线上"
-- ============================================

-- 1. 先检查是否已存在"全国"城市
-- SELECT * FROM fa_cities WHERE name = '全国';

-- 2. 添加"全国"城市（如果不存在）
INSERT INTO `fa_cities` (`name`, `code`, `province_id`, `status`, `create_time`, `update_time`)
SELECT '全国', '000000', 0, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `fa_cities` WHERE `name` = '全国');

-- 3. 获取"全国"城市的ID并添加"线上"区域
-- 注意：需要先执行上面的INSERT，然后手动获取city_id执行下面的语句
-- 或者使用存储过程/变量

-- 方法1：如果知道city_id，直接插入
-- INSERT INTO `fa_districts` (`name`, `code`, `city_id`, `status`, `create_time`, `update_time`)
-- VALUES ('线上', '000001', [city_id], 1, NOW(), NOW());

-- 方法2：使用子查询（MySQL 8.0+支持）
INSERT INTO `fa_districts` (`name`, `code`, `city_id`, `status`, `create_time`, `update_time`)
SELECT '线上', '000001', c.id, 1, NOW(), NOW()
FROM `fa_cities` c
WHERE c.name = '全国'
AND NOT EXISTS (
    SELECT 1 FROM `fa_districts` d 
    WHERE d.city_id = c.id AND d.name = '线上'
);

-- 验证
-- SELECT c.id as city_id, c.name as city_name, d.id as district_id, d.name as district_name
-- FROM fa_cities c
-- LEFT JOIN fa_districts d ON d.city_id = c.id
-- WHERE c.name = '全国';
