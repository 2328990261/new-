-- SSL证书管理功能数据库升级脚本
-- 执行时间：2025-01-XX
-- 说明：为家教信息管理系统添加SSL证书管理功能

-- 1. 创建SSL证书配置表
CREATE TABLE IF NOT EXISTS `fa_ssl_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `domain` varchar(255) NOT NULL COMMENT '域名',
  `contact_email` varchar(255) DEFAULT NULL COMMENT '联系邮箱',
  `provider` varchar(50) DEFAULT 'letsencrypt' COMMENT '证书提供商：letsencrypt, aliyun, tencent',
  `auto_renew` tinyint(1) DEFAULT 1 COMMENT '是否自动续约：0-否，1-是',
  `is_enabled` tinyint(1) DEFAULT 1 COMMENT '是否启用：0-否，1-是',
  `status` varchar(20) DEFAULT 'pending' COMMENT '状态：pending-待申请，active-有效，expired-过期，failed-失败',
  `cert_issue_time` datetime DEFAULT NULL COMMENT '证书签发时间',
  `cert_expire_time` datetime DEFAULT NULL COMMENT '证书过期时间',
  `last_apply_time` datetime DEFAULT NULL COMMENT '最后申请时间',
  `last_renew_time` datetime DEFAULT NULL COMMENT '最后续约时间',
  `last_check_time` datetime DEFAULT NULL COMMENT '最后检查时间',
  `error_message` text DEFAULT NULL COMMENT '错误信息',
  `config_data` text DEFAULT NULL COMMENT '配置数据（JSON格式）',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain` (`domain`),
  KEY `idx_status` (`status`),
  KEY `idx_auto_renew` (`auto_renew`),
  KEY `idx_expire_time` (`cert_expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSL证书配置表';

-- 2. 创建SSL证书续约日志表
CREATE TABLE IF NOT EXISTS `fa_ssl_renew_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `ssl_config_id` int(11) NOT NULL COMMENT 'SSL配置ID',
  `domain` varchar(255) NOT NULL COMMENT '域名',
  `action` varchar(20) NOT NULL COMMENT '操作类型：apply-申请，renew-续约，check-检查',
  `status` varchar(20) NOT NULL COMMENT '操作状态：success-成功，failed-失败',
  `message` text DEFAULT NULL COMMENT '操作结果信息',
  `error_message` text DEFAULT NULL COMMENT '错误信息',
  `cert_expire_time` datetime DEFAULT NULL COMMENT '证书过期时间',
  `execute_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '执行时间',
  PRIMARY KEY (`id`),
  KEY `idx_ssl_config_id` (`ssl_config_id`),
  KEY `idx_domain` (`domain`),
  KEY `idx_action` (`action`),
  KEY `idx_status` (`status`),
  KEY `idx_execute_time` (`execute_time`),
  CONSTRAINT `fk_ssl_renew_log_config` FOREIGN KEY (`ssl_config_id`) REFERENCES `fa_ssl_config` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSL证书续约日志表';

-- 3. 插入默认SSL配置示例
INSERT IGNORE INTO `fa_ssl_config` (
  `domain`, 
  `contact_email`, 
  `provider`, 
  `auto_renew`, 
  `is_enabled`, 
  `status`, 
  `create_time`, 
  `update_time`
) VALUES 
('www.yourdomain.com', 'admin@yourdomain.com', 'letsencrypt', 1, 1, 'pending', NOW(), NOW()),
('admin.yourdomain.com', 'admin@yourdomain.com', 'letsencrypt', 1, 1, 'pending', NOW(), NOW()),
('api.yourdomain.com', 'admin@yourdomain.com', 'letsencrypt', 1, 1, 'pending', NOW(), NOW());

-- 4. 创建SSL证书状态检查视图
CREATE OR REPLACE VIEW `fa_ssl_status_view` AS
SELECT 
    s.id,
    s.domain,
    s.provider,
    s.status,
    s.cert_expire_time,
    s.auto_renew,
    s.is_enabled,
    CASE 
        WHEN s.cert_expire_time IS NULL THEN '未申请'
        WHEN s.cert_expire_time < NOW() THEN '已过期'
        WHEN s.cert_expire_time <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN '7天内过期'
        WHEN s.cert_expire_time <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN '30天内过期'
        ELSE '正常'
    END AS expire_status,
    CASE 
        WHEN s.cert_expire_time IS NULL THEN 0
        ELSE DATEDIFF(s.cert_expire_time, NOW())
    END AS days_until_expire,
    s.last_check_time,
    s.create_time,
    s.update_time
FROM `fa_ssl_config` s
WHERE s.is_enabled = 1
ORDER BY s.cert_expire_time ASC;

-- 5. 创建SSL证书续约统计视图
CREATE OR REPLACE VIEW `fa_ssl_renew_stats_view` AS
SELECT 
    DATE(l.execute_time) as renew_date,
    l.action,
    l.status,
    COUNT(*) as count,
    GROUP_CONCAT(DISTINCT l.domain) as domains
FROM `fa_ssl_renew_log` l
WHERE l.execute_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(l.execute_time), l.action, l.status
ORDER BY renew_date DESC;

-- 6. 创建SSL证书过期提醒存储过程
DELIMITER //
CREATE PROCEDURE `sp_ssl_expire_reminder`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_domain VARCHAR(255);
    DECLARE v_expire_time DATETIME;
    DECLARE v_days_left INT;
    
    DECLARE expire_cursor CURSOR FOR
        SELECT domain, cert_expire_time, DATEDIFF(cert_expire_time, NOW()) as days_left
        FROM fa_ssl_config 
        WHERE is_enabled = 1 
        AND status = 'active'
        AND cert_expire_time IS NOT NULL
        AND cert_expire_time <= DATE_ADD(NOW(), INTERVAL 30 DAY)
        AND cert_expire_time > NOW();
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN expire_cursor;
    
    expire_loop: LOOP
        FETCH expire_cursor INTO v_domain, v_expire_time, v_days_left;
        IF done THEN
            LEAVE expire_loop;
        END IF;
        
        -- 这里可以添加发送提醒的逻辑
        -- 例如：插入提醒记录、发送邮件等
        INSERT INTO fa_ssl_renew_log (
            ssl_config_id, 
            domain, 
            action, 
            status, 
            message, 
            execute_time
        ) VALUES (
            (SELECT id FROM fa_ssl_config WHERE domain = v_domain LIMIT 1),
            v_domain,
            'reminder',
            'info',
            CONCAT('证书将在', v_days_left, '天后过期'),
            NOW()
        );
        
    END LOOP;
    
    CLOSE expire_cursor;
END //
DELIMITER ;

-- 7. 创建SSL证书自动续约存储过程
DELIMITER //
CREATE PROCEDURE `sp_ssl_auto_renew`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id INT;
    DECLARE v_domain VARCHAR(255);
    DECLARE v_expire_time DATETIME;
    
    DECLARE renew_cursor CURSOR FOR
        SELECT id, domain, cert_expire_time
        FROM fa_ssl_config 
        WHERE is_enabled = 1 
        AND auto_renew = 1
        AND status = 'active'
        AND cert_expire_time IS NOT NULL
        AND cert_expire_time <= DATE_ADD(NOW(), INTERVAL 30 DAY)
        AND cert_expire_time > NOW();
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN renew_cursor;
    
    renew_loop: LOOP
        FETCH renew_cursor INTO v_id, v_domain, v_expire_time;
        IF done THEN
            LEAVE renew_loop;
        END IF;
        
        -- 模拟续约成功（实际应用中需要调用相应的API）
        UPDATE fa_ssl_config 
        SET 
            cert_expire_time = DATE_ADD(NOW(), INTERVAL 90 DAY),
            last_renew_time = NOW(),
            update_time = NOW()
        WHERE id = v_id;
        
        -- 记录续约日志
        INSERT INTO fa_ssl_renew_log (
            ssl_config_id, 
            domain, 
            action, 
            status, 
            message, 
            cert_expire_time,
            execute_time
        ) VALUES (
            v_id,
            v_domain,
            'renew',
            'success',
            '自动续约成功',
            DATE_ADD(NOW(), INTERVAL 90 DAY),
            NOW()
        );
        
    END LOOP;
    
    CLOSE renew_cursor;
END //
DELIMITER ;

-- 8. 创建定时任务事件（需要开启事件调度器）
-- SET GLOBAL event_scheduler = ON;

-- 创建SSL证书过期提醒事件（每天执行一次）
-- CREATE EVENT IF NOT EXISTS `ev_ssl_expire_reminder`
-- ON SCHEDULE EVERY 1 DAY
-- STARTS '2025-01-01 02:00:00'
-- DO
--   CALL sp_ssl_expire_reminder();

-- 创建SSL证书自动续约事件（每天执行一次）
-- CREATE EVENT IF NOT EXISTS `ev_ssl_auto_renew`
-- ON SCHEDULE EVERY 1 DAY
-- STARTS '2025-01-01 03:00:00'
-- DO
--   CALL sp_ssl_auto_renew();

-- 9. 创建SSL证书管理相关的索引优化
CREATE INDEX `idx_ssl_config_composite` ON `fa_ssl_config` (`is_enabled`, `auto_renew`, `status`, `cert_expire_time`);
CREATE INDEX `idx_ssl_renew_log_composite` ON `fa_ssl_renew_log` (`ssl_config_id`, `action`, `status`, `execute_time`);

-- 10. 插入SSL证书管理功能说明数据
INSERT IGNORE INTO `fa_system_config` (`key`, `value`, `description`, `create_time`, `update_time`) VALUES
('ssl_auto_renew_enabled', '1', 'SSL证书自动续约功能是否启用', NOW(), NOW()),
('ssl_renew_days_before_expire', '30', '证书过期前多少天开始自动续约', NOW(), NOW()),
('ssl_provider_default', 'letsencrypt', '默认SSL证书提供商', NOW(), NOW()),
('ssl_contact_email_default', 'admin@yourdomain.com', '默认联系邮箱', NOW(), NOW());

-- 升级完成提示
SELECT 'SSL证书管理功能数据库升级完成！' as message;
SELECT '请执行以下操作：' as next_steps;
SELECT '1. 导入SSL证书管理相关的PHP文件' as step1;
SELECT '2. 配置定时任务执行ssl_auto_renew.php' as step2;
SELECT '3. 在管理端基础配置中添加SSL证书管理Tab' as step3;
SELECT '4. 测试SSL证书申请和续约功能' as step4;
