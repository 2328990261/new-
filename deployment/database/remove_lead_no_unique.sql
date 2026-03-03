-- ============================================
-- 移除线索编号的唯一索引
-- 允许线索编号重复
-- ============================================

USE myjiajiao;

-- 删除 lead_no 的唯一索引
ALTER TABLE `fa_leads` DROP INDEX `uk_lead_no`;

-- 添加普通索引（保留索引以优化查询性能）
ALTER TABLE `fa_leads` ADD INDEX `idx_lead_no` (`lead_no`);

-- 验证
SHOW INDEX FROM `fa_leads` WHERE Key_name LIKE '%lead_no%';

/*
说明：
1. 删除了 uk_lead_no 唯一索引
2. 添加了 idx_lead_no 普通索引
3. 现在允许线索编号重复

使用场景：
- 多个平台可能有相同的编号
- 同一个线索可能被多次录入
- 编号只是参考，不作为唯一标识
*/
