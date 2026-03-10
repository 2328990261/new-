-- ============================================
-- 为 fa_tutor_orders_new 表添加字段
-- ============================================
-- 执行时间：立即执行
-- 说明：添加来源标识、薪酬范围等字段
-- ============================================

-- 1. 添加来源字段
ALTER TABLE `fa_tutor_orders_new` 
ADD COLUMN `source` VARCHAR(50) DEFAULT 'manual' COMMENT '来源：manual-手动录入，booking-预约单转化';

-- 2. 添加关联预约单ID字段
ALTER TABLE `fa_tutor_orders_new` 
ADD COLUMN `parent_order_id` INT(11) DEFAULT NULL COMMENT '关联的预约单ID';

-- 3. 添加薪酬范围字符串字段
ALTER TABLE `fa_tutor_orders_new` 
ADD COLUMN `salary_range` VARCHAR(100) DEFAULT NULL COMMENT '薪酬范围字符串（如：130-150元/小时）';
