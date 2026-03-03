-- ============================================
-- 为 fa_parent_orders 表添加 salary 字段
-- ============================================
-- 执行时间：立即执行
-- 说明：存储时薪范围的字符串格式，如 "130-150元/小时"
-- ============================================

-- 添加 salary 字段
ALTER TABLE `fa_parent_orders` 
ADD COLUMN `salary` VARCHAR(100) DEFAULT NULL COMMENT '时薪范围（如：130-150元/小时）' AFTER `budget_max`;

-- 更新现有数据，根据 budget_min 和 budget_max 生成 salary
UPDATE `fa_parent_orders` 
SET `salary` = CONCAT(budget_min, '-', budget_max, '元/小时')
WHERE budget_min > 0 AND budget_max > 0 AND (salary IS NULL OR salary = '');

-- 验证
-- SELECT id, budget_min, budget_max, salary FROM fa_parent_orders LIMIT 10;
