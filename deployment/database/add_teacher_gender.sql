-- 添加教师性别字段到家教订单表
-- 执行时间: 2025-10-30
-- 说明: 支持识别和筛选教师性别需求（男老师/女老师/男女不限）

USE myjiajiao;

-- 添加 teacher_gender 字段
ALTER TABLE fa_tutor_orders_new 
ADD COLUMN teacher_gender VARCHAR(20) DEFAULT 'unlimited' 
COMMENT '教师性别：male-男老师，female-女老师，unlimited-男女不限' 
AFTER teacher_type;

-- 为该字段添加索引以提升查询性能
ALTER TABLE fa_tutor_orders_new 
ADD INDEX idx_teacher_gender (teacher_gender);

-- 完成
SELECT 'teacher_gender 字段添加成功！' AS message;

