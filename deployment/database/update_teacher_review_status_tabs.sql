-- 更新教师审核状态标签页
-- 将tab从"全部、待审核、已审核"改为"全部、待审核、审核通过、审核拒绝"
-- 这个脚本主要是确保review_status字段的值正确

USE myjiajiao;

-- 首先检查表是否存在
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ fa_teachers表存在'
        ELSE '✗ fa_teachers表不存在，请检查数据库'
    END as table_check
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers';

-- 检查review_status字段是否存在
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ review_status字段已存在'
        ELSE '✗ review_status字段不存在，请先运行upgrade_teacher_certification.sql'
    END as field_check
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teachers' 
  AND COLUMN_NAME = 'review_status';

-- 查看当前review_status的值分布
SELECT 
    review_status,
    COUNT(*) as count,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM fa_teachers), 2), '%') as percentage
FROM fa_teachers
GROUP BY review_status
ORDER BY count DESC;

-- 说明：
-- review_status字段的可能值：
-- 1. 'pending' - 待审核（对应新的"待审核"tab）
-- 2. 'approved' - 审核通过（对应新的"审核通过"tab）
-- 3. 'rejected' - 审核拒绝（对应新的"审核拒绝"tab）

-- 如果有其他值，可以根据需要进行数据清理
-- 例如，如果有空值或其他状态，可以设置为pending
UPDATE fa_teachers 
SET review_status = 'pending' 
WHERE review_status IS NULL OR review_status = '' OR review_status NOT IN ('pending', 'approved', 'rejected');

-- 验证更新后的数据
SELECT 
    '更新后的状态分布：' as info;
    
SELECT 
    review_status,
    COUNT(*) as count,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM fa_teachers), 2), '%') as percentage
FROM fa_teachers
GROUP BY review_status
ORDER BY 
    CASE review_status
        WHEN 'pending' THEN 1
        WHEN 'approved' THEN 2
        WHEN 'rejected' THEN 3
        ELSE 4
    END;

-- 完成提示
SELECT '✓ 教师审核状态标签页更新完成！' as message;
SELECT '新的标签页结构：' as info;
SELECT '  1. 全部 - 显示所有教师' as tab;
SELECT '  2. 待审核 - review_status = pending' as tab;
SELECT '  3. 审核通过 - review_status = approved' as tab;
SELECT '  4. 审核拒绝 - review_status = rejected' as tab;
