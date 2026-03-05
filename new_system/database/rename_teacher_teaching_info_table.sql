-- 重命名 teacher_teaching_info 表为 fa_teacher_teaching_info
-- 执行时间：2026-03-05
-- 用途：统一表名前缀，使其符合 ThinkPHP 的命名规范

-- 检查表是否存在
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ teacher_teaching_info 表存在，准备重命名'
        ELSE '✗ teacher_teaching_info 表不存在'
    END AS status
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'teacher_teaching_info';

-- 重命名表
RENAME TABLE `teacher_teaching_info` TO `fa_teacher_teaching_info`;

-- 验证重命名结果
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ fa_teacher_teaching_info 表已创建成功'
        ELSE '✗ fa_teacher_teaching_info 表创建失败'
    END AS status
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_teacher_teaching_info';

-- 显示表结构
DESCRIBE `fa_teacher_teaching_info`;

-- 显示数据统计
SELECT 
    COUNT(*) as total_records,
    COUNT(DISTINCT teacher_id) as unique_teachers,
    COUNT(DISTINCT city_id) as unique_cities
FROM `fa_teacher_teaching_info`;
