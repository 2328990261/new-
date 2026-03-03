-- ============================================
-- 分步修复教师表 - 每次只添加一个字段
-- 如果某个字段已存在会报错，继续执行下一条即可
-- ============================================

-- 步骤1：添加 teacher_type 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `teacher_type` varchar(50) DEFAULT NULL COMMENT '教师类型';

-- 步骤2：添加 last_login_time 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间';

-- 步骤3：添加 review_status 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_status` enum('pending','approved','rejected') DEFAULT 'pending' COMMENT '审核状态';

-- 步骤4：添加 reviewer_id 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID';

-- 步骤5：添加 review_time 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_time` datetime DEFAULT NULL COMMENT '审核时间';

-- 步骤6：添加 review_note 字段
ALTER TABLE `fa_teachers` 
ADD COLUMN `review_note` text COMMENT '审核备注';

-- 步骤7：添加索引
ALTER TABLE `fa_teachers` ADD INDEX `idx_teacher_type` (`teacher_type`);
ALTER TABLE `fa_teachers` ADD INDEX `idx_last_login_time` (`last_login_time`);
ALTER TABLE `fa_teachers` ADD INDEX `idx_review_status` (`review_status`);
ALTER TABLE `fa_teachers` ADD INDEX `idx_reviewer_id` (`reviewer_id`);

-- 验证
SELECT '✓ 所有字段添加完成' AS message;
DESCRIBE `fa_teachers`;
