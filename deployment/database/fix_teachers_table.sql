-- ============================================
-- 修复教师表缺失的字段
-- 注意：如果字段已存在会报错，可以忽略
-- ============================================

-- 先查看当前表结构
SELECT '当前 fa_teachers 表结构：' AS message;
DESCRIBE `fa_teachers`;

-- ============================================
-- 添加 last_login_time 字段
-- ============================================
ALTER TABLE `fa_teachers` 
ADD COLUMN `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间';

SELECT '✓ last_login_time 字段添加成功' AS message;

-- ============================================
-- 添加 review_status 字段
-- ============================================
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_status` enum('pending','approved','rejected') DEFAULT 'pending' COMMENT '审核状态：pending-待审核，approved-已通过，rejected-已拒绝';

SELECT '✓ review_status 字段添加成功' AS message;

-- ============================================
-- 添加 teacher_type 字段
-- ============================================
ALTER TABLE `fa_teachers` 
ADD COLUMN `teacher_type` varchar(50) DEFAULT NULL COMMENT '教师类型：在校大学生、研究生、在职教师等';

SELECT '✓ teacher_type 字段添加成功' AS message;

-- ============================================
-- 添加 reviewer_id 字段（审核人ID）
-- ============================================
ALTER TABLE `fa_teachers` 
ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID';

SELECT '✓ reviewer_id 字段添加成功' AS message;

-- ============================================
-- 添加 review_time 字段（审核时间）
-- ============================================
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_time` datetime DEFAULT NULL COMMENT '审核时间';

SELECT '✓ review_time 字段添加成功' AS message;

-- ============================================
-- 添加 review_note 字段（审核备注）
-- ============================================
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_note` text COMMENT '审核备注';

SELECT '✓ review_note 字段添加成功' AS message;

-- ============================================
-- 添加相关索引
-- ============================================
ALTER TABLE `fa_teachers` 
ADD INDEX `idx_last_login_time` (`last_login_time`);

ALTER TABLE `fa_teachers` 
ADD INDEX `idx_review_status` (`review_status`);

ALTER TABLE `fa_teachers` 
ADD INDEX `idx_teacher_type` (`teacher_type`);

ALTER TABLE `fa_teachers` 
ADD INDEX `idx_reviewer_id` (`reviewer_id`);

SELECT '✓ 索引添加成功' AS message;

-- ============================================
-- 验证
-- ============================================
SELECT '修复后的表结构：' AS message;
DESCRIBE `fa_teachers`;
