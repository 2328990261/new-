-- 为投递管理表添加审核人ID字段
ALTER TABLE `fa_resume_application` 
ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID（管理员ID）' AFTER `review_time`,
ADD KEY `idx_reviewer_id` (`reviewer_id`);
