-- ============================================
-- 只添加剩余缺失的字段
-- 跳过已存在的 last_login_time
-- ============================================

-- 添加 teacher_type 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `teacher_type` varchar(50) DEFAULT NULL COMMENT '教师类型';

-- 添加 review_status 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_status` enum('pending','approved','rejected') DEFAULT 'pending' COMMENT '审核状态';

-- 添加 reviewer_id 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID';

-- 添加 review_time 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_time` datetime DEFAULT NULL COMMENT '审核时间';

-- 添加 review_note 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_note` text COMMENT '审核备注';

-- 添加索引
ALTER TABLE `fa_teachers` ADD INDEX `idx_teacher_type` (`teacher_type`);
ALTER TABLE `fa_teachers` ADD INDEX `idx_review_status` (`review_status`);
ALTER TABLE `fa_teachers` ADD INDEX `idx_reviewer_id` (`reviewer_id`);

-- 验证
SELECT '✓ 所有缺失字段添加完成' AS message;
DESCRIBE `fa_teachers`;
