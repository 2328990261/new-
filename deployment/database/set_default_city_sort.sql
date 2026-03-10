-- ============================================
-- 批量设置大陆城市的默认sort值
-- ============================================
-- 目的：将所有sort值过小的大陆城市设置为优先匹配（sort >= 50）
-- 执行时间：2025-10-27
-- ============================================

USE myjiajiao;

-- ============================================
-- 第一步：查看当前sort值分布（执行前）
-- ============================================
SELECT 
    CASE 
        WHEN sort < 50 THEN 'sort < 50 (延迟匹配)'
        WHEN sort >= 50 THEN 'sort >= 50 (优先匹配)'
    END AS '当前分组',
    COUNT(*) AS '城市数量'
FROM fa_cities
WHERE status = 1
GROUP BY 
    CASE 
        WHEN sort < 50 THEN 'sort < 50 (延迟匹配)'
        WHEN sort >= 50 THEN 'sort >= 50 (优先匹配)'
    END;

-- ============================================
-- 第二步：批量更新大陆城市的sort值
-- ============================================

-- 2.1 先确保特殊区域保持低sort值（延迟匹配）
UPDATE fa_cities SET sort = 1 WHERE name = '香港' OR code = 'HK';
UPDATE fa_cities SET sort = 2 WHERE name = '澳门' OR code = 'MO';
UPDATE fa_cities SET sort = 3 WHERE name = '台湾' OR code = 'TW';

-- 2.2 将所有其他城市（sort < 50的大陆城市）根据level设置合理的sort值
-- 一线城市 → sort = 100-109
UPDATE fa_cities 
SET sort = 100 
WHERE level = '一线城市' 
  AND sort < 50 
  AND name NOT IN ('香港', '澳门', '台湾');

-- 新一线城市 → sort = 110-129
UPDATE fa_cities 
SET sort = 110 
WHERE level = '新一线城市' 
  AND sort < 50 
  AND name NOT IN ('香港', '澳门', '台湾');

-- 二线城市 → sort = 130-199
UPDATE fa_cities 
SET sort = 130 
WHERE level = '二线城市' 
  AND sort < 50 
  AND name NOT IN ('香港', '澳门', '台湾');

-- 三线城市 → sort = 200-299
UPDATE fa_cities 
SET sort = 200 
WHERE level = '三线城市' 
  AND sort < 50 
  AND name NOT IN ('香港', '澳门', '台湾');

-- 特别行政区（除了香港澳门外，如果有其他） → sort = 300
UPDATE fa_cities 
SET sort = 300 
WHERE level = '特别行政区' 
  AND sort < 50 
  AND name NOT IN ('香港', '澳门', '台湾');

-- 2.3 处理没有level或level为空的城市 → sort = 300（普通优先级）
UPDATE fa_cities 
SET sort = 300 
WHERE (level IS NULL OR level = '' OR level NOT IN ('一线城市', '新一线城市', '二线城市', '三线城市', '特别行政区'))
  AND sort < 50 
  AND name NOT IN ('香港', '澳门', '台湾');

-- 2.4 确保"全国"设置为临界值
UPDATE fa_cities SET sort = 50 WHERE name = '全国';

-- ============================================
-- 第三步：验证更新结果
-- ============================================

-- 查看更新后的分布
SELECT 
    CASE 
        WHEN sort < 50 THEN 'sort < 50 (延迟匹配)'
        WHEN sort >= 50 THEN 'sort >= 50 (优先匹配)'
    END AS '更新后分组',
    COUNT(*) AS '城市数量'
FROM fa_cities
WHERE status = 1
GROUP BY 
    CASE 
        WHEN sort < 50 THEN 'sort < 50 (延迟匹配)'
        WHEN sort >= 50 THEN 'sort >= 50 (优先匹配)'
    END;

-- 查看延迟匹配的城市（应该只有香港、澳门、台湾）
SELECT id, name, code, level, sort 
FROM fa_cities 
WHERE sort < 50 AND status = 1
ORDER BY sort ASC;

-- 查看各level的sort设置情况
SELECT 
    level,
    MIN(sort) AS 'sort最小值',
    MAX(sort) AS 'sort最大值',
    COUNT(*) AS '城市数量'
FROM fa_cities
WHERE status = 1
GROUP BY level
ORDER BY MIN(sort) ASC;

-- ============================================
-- 第四步：检查是否有遗漏的城市
-- ============================================

-- 查找所有可能被遗漏的城市（sort仍然 < 50但不是特殊区域）
SELECT id, name, code, level, sort 
FROM fa_cities 
WHERE sort < 50 
  AND status = 1 
  AND name NOT IN ('香港', '澳门', '台湾')
  AND code NOT IN ('HK', 'MO', 'TW');

-- 如果上面的查询有结果，说明有城市没有被正确设置，需要手动处理

