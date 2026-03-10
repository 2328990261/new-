-- ============================================
-- 线索跟进记录表升级SQL
-- 添加缺失的字段：proof_images, invalid_images, tutor_title, info_fee
-- 保留所有现有数据
-- ============================================

USE myjiajiao;

-- ============================================
-- 步骤 1：添加 proof_images 字段（不需要凭证截图）
-- ============================================

ALTER TABLE `fa_lead_follow_logs` 
ADD COLUMN `proof_images` TEXT DEFAULT NULL COMMENT '不需要凭证截图（JSON数组）' 
AFTER `remark`;

-- ============================================
-- 步骤 2：添加 invalid_images 字段（无效截图）
-- ============================================

ALTER TABLE `fa_lead_follow_logs` 
ADD COLUMN `invalid_images` TEXT DEFAULT NULL COMMENT '无效截图（JSON数组）' 
AFTER `proof_images`;

-- ============================================
-- 步骤 3：添加 tutor_title 字段（家教单标题）
-- ============================================

ALTER TABLE `fa_lead_follow_logs` 
ADD COLUMN `tutor_title` VARCHAR(255) DEFAULT NULL COMMENT '家教单标题（状态为已发单时填写）' 
AFTER `invalid_images`;

-- ============================================
-- 步骤 4：添加 info_fee 字段（信息费金额）
-- ============================================

ALTER TABLE `fa_lead_follow_logs` 
ADD COLUMN `info_fee` DECIMAL(10,2) DEFAULT NULL COMMENT '信息费金额（状态为已出单时填写）' 
AFTER `tutor_title`;

-- ============================================
-- 验证：查看表结构
-- ============================================

SHOW COLUMNS FROM `fa_lead_follow_logs`;

-- ============================================
-- 验证：查看索引
-- ============================================

SHOW INDEX FROM `fa_lead_follow_logs`;

/*
升级说明：
1. ✅ 添加 proof_images 字段 - 存储不需要凭证截图（JSON格式）
2. ✅ 添加 invalid_images 字段 - 存储无效截图（JSON格式）
3. ✅ 添加 tutor_title 字段 - 存储家教单标题
4. ✅ 添加 info_fee 字段 - 存储信息费金额
5. ✅ 保留所有现有数据
6. ✅ 不做数据迁移

字段说明：
- proof_images: 当状态变更为"不需要"时，保存凭证截图的URL数组（JSON格式）
- invalid_images: 当状态变更为"无效"时，保存无效截图的URL数组（JSON格式）
- tutor_title: 当状态变更为"已发单"时，保存家教单标题
- info_fee: 当状态变更为"已出单"时，保存信息费金额

注意事项：
- 所有新字段都允许为 NULL
- 不影响现有数据
- 如果字段已存在会报错，可以忽略
*/
