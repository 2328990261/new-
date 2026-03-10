-- ============================================
-- 线索表升级SQL（简化版）
-- 只添加缺失的字段，保留线上所有数据
-- 不做数据迁移，不添加外键约束
-- ============================================

-- 注意：请在 phpMyAdmin 中执行，或使用有足够权限的数据库用户

-- ============================================
-- 1. 添加 channel 字段（如果不存在）
-- ============================================

-- 检查 channel 字段是否存在，如果不存在则添加
ALTER TABLE `fa_leads` 
ADD COLUMN IF NOT EXISTS `channel` ENUM('美团', '58同城', '表单', '渠道生源', '其他') DEFAULT '其他' COMMENT '线索渠道' AFTER `creator_admin_id`;

-- 添加 channel 索引（如果不存在）
ALTER TABLE `fa_leads` 
ADD INDEX IF NOT EXISTS `idx_channel` (`channel`);

-- ============================================
-- 2. 添加其他可能缺失的索引
-- ============================================

-- lead_no 唯一索引（如果不存在）
ALTER TABLE `fa_leads` 
ADD UNIQUE INDEX IF NOT EXISTS `uk_lead_no` (`lead_no`);

-- status 索引（如果不存在）
ALTER TABLE `fa_leads` 
ADD INDEX IF NOT EXISTS `idx_status` (`status`);

-- assigned_admin_id 索引（如果不存在）
ALTER TABLE `fa_leads` 
ADD INDEX IF NOT EXISTS `idx_assigned_admin` (`assigned_admin_id`);

-- city_id 索引（如果不存在）
ALTER TABLE `fa_leads` 
ADD INDEX IF NOT EXISTS `idx_city_id` (`city_id`);

-- create_time 索引（如果不存在）
ALTER TABLE `fa_leads` 
ADD INDEX IF NOT EXISTS `idx_create_time` (`create_time`);

-- ============================================
-- 完成
-- ============================================

-- 查看表结构确认
SHOW COLUMNS FROM `fa_leads`;

/*
升级说明：
1. ✅ 添加 channel 字段（线索渠道）
2. ✅ 添加相关索引优化查询性能
3. ✅ 保留所有现有数据
4. ✅ 不做数据迁移
5. ✅ 不添加外键约束

注意：
- 如果你的 MySQL 版本不支持 "IF NOT EXISTS"，请手动检查字段是否存在
- 所有现有数据都会保留
- channel 字段默认值为 "其他"
*/
