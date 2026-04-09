-- 新增：家长预约可辅导时间段（多组 JSON）
ALTER TABLE `fa_parent_orders`
ADD COLUMN `available_time_slots` TEXT NULL COMMENT '可辅导时间段JSON，数组结构';

-- 回滚（按需执行）
-- ALTER TABLE `fa_parent_orders` DROP COLUMN `available_time_slots`;
