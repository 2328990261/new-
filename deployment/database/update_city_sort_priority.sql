-- ============================================
-- 城市Sort优先级配置脚本
-- ============================================
-- 说明：sort值越小=优先级越低（延迟匹配）
--      sort值越大=优先级越高（优先匹配）
-- 阈值：50（小于50为延迟匹配城市，大于等于50为优先匹配城市）
-- 创建时间：2025-10-27
-- ============================================

USE myjiajiao;

-- ============================================
-- 第一部分：设置延迟匹配城市（sort < 50）
-- ============================================
-- 这些城市只有在文本中明确包含城市名时才会匹配

-- 香港（sort = 1）- 需要明确包含"香港"关键词
UPDATE `fa_cities` SET `sort` = 1 
WHERE `name` = '香港' OR `code` = 'HK';

-- 澳门（sort = 2）- 需要明确包含"澳门"关键词
UPDATE `fa_cities` SET `sort` = 2 
WHERE `name` = '澳门' OR `code` = 'MO';

-- 台湾（sort = 3）- 预留，需要明确包含"台湾"关键词
UPDATE `fa_cities` SET `sort` = 3 
WHERE `name` = '台湾' OR `code` = 'TW';

-- ============================================
-- 第二部分：设置优先匹配城市（sort >= 50）
-- ============================================

-- --------------------------------------------
-- 线上全国（sort = 50）- 临界值
-- --------------------------------------------
UPDATE `fa_cities` SET `sort` = 50 WHERE `name` = '全国';

-- --------------------------------------------
-- 一线城市（sort = 100-109）
-- --------------------------------------------
UPDATE `fa_cities` SET `sort` = 100 WHERE `name` = '北京' OR `name` = '北京市';
UPDATE `fa_cities` SET `sort` = 101 WHERE `name` = '上海' OR `name` = '上海市';
UPDATE `fa_cities` SET `sort` = 102 WHERE `name` = '广州' OR `name` = '广州市';
UPDATE `fa_cities` SET `sort` = 103 WHERE `name` = '深圳' OR `name` = '深圳市';

-- --------------------------------------------
-- 新一线城市（sort = 110-129）
-- --------------------------------------------
UPDATE `fa_cities` SET `sort` = 110 WHERE `name` LIKE '%成都%';
UPDATE `fa_cities` SET `sort` = 111 WHERE `name` LIKE '%杭州%';
UPDATE `fa_cities` SET `sort` = 112 WHERE `name` LIKE '%重庆%';
UPDATE `fa_cities` SET `sort` = 113 WHERE `name` LIKE '%武汉%';
UPDATE `fa_cities` SET `sort` = 114 WHERE `name` LIKE '%西安%';
UPDATE `fa_cities` SET `sort` = 115 WHERE `name` LIKE '%天津%';
UPDATE `fa_cities` SET `sort` = 116 WHERE `name` LIKE '%苏州%';
UPDATE `fa_cities` SET `sort` = 117 WHERE `name` LIKE '%南京%';
UPDATE `fa_cities` SET `sort` = 118 WHERE `name` LIKE '%郑州%';
UPDATE `fa_cities` SET `sort` = 119 WHERE `name` LIKE '%长沙%';
UPDATE `fa_cities` SET `sort` = 120 WHERE `name` LIKE '%沈阳%';
UPDATE `fa_cities` SET `sort` = 121 WHERE `name` LIKE '%青岛%';
UPDATE `fa_cities` SET `sort` = 122 WHERE `name` LIKE '%合肥%';
UPDATE `fa_cities` SET `sort` = 123 WHERE `name` LIKE '%东莞%';
UPDATE `fa_cities` SET `sort` = 124 WHERE `name` LIKE '%大连%';

-- --------------------------------------------
-- 二线城市（sort = 130-199）
-- --------------------------------------------
UPDATE `fa_cities` SET `sort` = 130 WHERE `name` LIKE '%佛山%';
UPDATE `fa_cities` SET `sort` = 131 WHERE `name` LIKE '%宁波%';
UPDATE `fa_cities` SET `sort` = 132 WHERE `name` LIKE '%无锡%';
UPDATE `fa_cities` SET `sort` = 133 WHERE `name` LIKE '%福州%';
UPDATE `fa_cities` SET `sort` = 134 WHERE `name` LIKE '%厦门%';
UPDATE `fa_cities` SET `sort` = 135 WHERE `name` LIKE '%济南%';
UPDATE `fa_cities` SET `sort` = 136 WHERE `name` LIKE '%哈尔滨%';
UPDATE `fa_cities` SET `sort` = 137 WHERE `name` LIKE '%昆明%';
UPDATE `fa_cities` SET `sort` = 138 WHERE `name` LIKE '%南昌%';
UPDATE `fa_cities` SET `sort` = 139 WHERE `name` LIKE '%石家庄%';
UPDATE `fa_cities` SET `sort` = 140 WHERE `name` LIKE '%长春%';
UPDATE `fa_cities` SET `sort` = 141 WHERE `name` LIKE '%南宁%';
UPDATE `fa_cities` SET `sort` = 142 WHERE `name` LIKE '%贵阳%';
UPDATE `fa_cities` SET `sort` = 143 WHERE `name` LIKE '%太原%';
UPDATE `fa_cities` SET `sort` = 144 WHERE `name` LIKE '%兰州%';
UPDATE `fa_cities` SET `sort` = 145 WHERE `name` LIKE '%珠海%';
UPDATE `fa_cities` SET `sort` = 146 WHERE `name` LIKE '%惠州%';
UPDATE `fa_cities` SET `sort` = 147 WHERE `name` LIKE '%中山%';
UPDATE `fa_cities` SET `sort` = 148 WHERE `name` LIKE '%温州%';
UPDATE `fa_cities` SET `sort` = 149 WHERE `name` LIKE '%烟台%';

-- --------------------------------------------
-- 三线城市及其他（sort = 200-500）
-- --------------------------------------------
-- 批量更新所有level为"三线城市"且sort小于50的城市
UPDATE `fa_cities` SET `sort` = 200 WHERE `level` = '三线城市' AND `sort` < 50;

-- 批量更新所有没有level且sort小于50的城市（设置为较低优先级）
UPDATE `fa_cities` SET `sort` = 300 WHERE (`level` IS NULL OR `level` = '') AND `sort` < 50;

-- ============================================
-- 第三部分：验证设置
-- ============================================

-- 查看延迟匹配城市（sort < 50）
SELECT `id`, `name`, `code`, `level`, `sort` 
FROM `fa_cities` 
WHERE `sort` < 50 AND `status` = 1
ORDER BY `sort` ASC;

-- 查看优先匹配城市（sort >= 50）前20个
SELECT `id`, `name`, `code`, `level`, `sort` 
FROM `fa_cities` 
WHERE `sort` >= 50 AND `status` = 1
ORDER BY `sort` ASC
LIMIT 20;

-- 统计各sort范围的城市数量
SELECT 
    CASE 
        WHEN `sort` < 50 THEN '延迟匹配（<50）'
        WHEN `sort` >= 50 AND `sort` < 100 THEN '临界值（50-99）'
        WHEN `sort` >= 100 AND `sort` < 200 THEN '高优先级（100-199）'
        ELSE '普通优先级（>=200）'
    END AS `优先级范围`,
    COUNT(*) AS `城市数量`
FROM `fa_cities`
WHERE `status` = 1
GROUP BY 
    CASE 
        WHEN `sort` < 50 THEN '延迟匹配（<50）'
        WHEN `sort` >= 50 AND `sort` < 100 THEN '临界值（50-99）'
        WHEN `sort` >= 100 AND `sort` < 200 THEN '高优先级（100-199）'
        ELSE '普通优先级（>=200）'
    END
ORDER BY MIN(`sort`) ASC;
