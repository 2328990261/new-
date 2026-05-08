-- 检查薪酬表是否存在
SHOW TABLES LIKE 'fa_personnel_salary';

-- 如果表存在，查看表结构
DESC fa_personnel_salary;

-- 查看表中的数据
SELECT COUNT(*) as total FROM fa_personnel_salary;
