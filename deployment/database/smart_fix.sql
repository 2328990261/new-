-- ============================================
-- 智能修复SQL - 根据实际情况自动调整
-- ============================================

-- 先查看当前表结构
SELECT '=== 当前表结构 ===' AS info;
DESCRIBE `fa_resume_application`;

-- ============================================
-- 使用存储过程来智能处理字段
-- ============================================

DELIMITER $$

-- 创建临时存储过程
DROP PROCEDURE IF EXISTS fix_resume_application_table$$

CREATE PROCEDURE fix_resume_application_table()
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    -- 检查 tutor_order_id 是否存在
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fa_resume_application'
    AND COLUMN_NAME = 'tutor_order_id';
    
    -- 如果存在 tutor_order_id，重命名为 tutor_id
    IF column_exists > 0 THEN
        ALTER TABLE `fa_resume_application` 
        CHANGE COLUMN `tutor_order_id` `tutor_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders_new表';
        SELECT '✓ 已重命名：tutor_order_id -> tutor_id' AS result;
    ELSE
        SELECT '✓ tutor_id 字段已存在，无需重命名' AS result;
    END IF;
    
    -- 检查 tutor_id 是否存在
    SET column_exists = 0;
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fa_resume_application'
    AND COLUMN_NAME = 'tutor_id';
    
    -- 如果 tutor_id 不存在，添加它
    IF column_exists = 0 THEN
        ALTER TABLE `fa_resume_application` 
        ADD COLUMN `tutor_id` int(11) NOT NULL COMMENT '家教订单ID，关联fa_tutor_orders_new表' AFTER `teacher_id`;
        SELECT '✓ 已添加 tutor_id 字段' AS result;
    END IF;
    
    -- 检查 reviewer_id 是否存在
    SET column_exists = 0;
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fa_resume_application'
    AND COLUMN_NAME = 'reviewer_id';
    
    -- 如果不存在，添加 reviewer_id
    IF column_exists = 0 THEN
        ALTER TABLE `fa_resume_application` 
        ADD COLUMN `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID，关联fa_admin表';
        SELECT '✓ 已添加 reviewer_id 字段' AS result;
    ELSE
        SELECT '✓ reviewer_id 字段已存在' AS result;
    END IF;
    
    -- 检查 apply_time 是否存在
    SET column_exists = 0;
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fa_resume_application'
    AND COLUMN_NAME = 'apply_time';
    
    -- 如果不存在，添加 apply_time
    IF column_exists = 0 THEN
        ALTER TABLE `fa_resume_application` 
        ADD COLUMN `apply_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '投递时间';
        SELECT '✓ 已添加 apply_time 字段' AS result;
    ELSE
        SELECT '✓ apply_time 字段已存在' AS result;
    END IF;
    
    -- 检查 review_time 是否存在
    SET column_exists = 0;
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'fa_resume_application'
    AND COLUMN_NAME = 'review_time';
    
    -- 如果不存在，添加 review_time
    IF column_exists = 0 THEN
        ALTER TABLE `fa_resume_application` 
        ADD COLUMN `review_time` datetime DEFAULT NULL COMMENT '审核时间';
        SELECT '✓ 已添加 review_time 字段' AS result;
    ELSE
        SELECT '✓ review_time 字段已存在' AS result;
    END IF;
    
END$$

DELIMITER ;

-- 执行存储过程
CALL fix_resume_application_table();

-- 删除存储过程
DROP PROCEDURE IF EXISTS fix_resume_application_table;

-- 显示最终表结构
SELECT '=== 修复后的表结构 ===' AS info;
DESCRIBE `fa_resume_application`;
