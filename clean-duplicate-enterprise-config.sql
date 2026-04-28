-- 清理企业配置表中的重复数据
-- 只保留ID最小的那条记录

-- 查看当前数据
SELECT * FROM fa_enterprise_config WHERE delete_time IS NULL;

-- 删除重复的记录，只保留ID最小的
DELETE FROM fa_enterprise_config 
WHERE id NOT IN (
    SELECT min_id FROM (
        SELECT MIN(id) as min_id 
        FROM fa_enterprise_config 
        WHERE delete_time IS NULL
        GROUP BY corp_id
    ) AS temp
);

-- 再次查看清理后的数据
SELECT * FROM fa_enterprise_config WHERE delete_time IS NULL;
