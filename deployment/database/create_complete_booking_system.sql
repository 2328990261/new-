-- ============================================
-- 预约订单和小程序用户管理完整表格构建SQL
-- ============================================
-- 执行时间：立即执行
-- 执行位置：PHPStudy -> phpMyAdmin -> SQL 标签
-- 数据库：myjiajiao
-- ============================================

-- ============================================
-- 1. 创建小程序用户表 (users)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `openid` varchar(100) NOT NULL COMMENT '微信openid',
  `unionid` varchar(100) DEFAULT NULL COMMENT '微信unionid',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL',
  `gender` tinyint(1) DEFAULT 0 COMMENT '性别：0未知，1男，2女',
  `city` varchar(50) DEFAULT NULL COMMENT '城市',
  `province` varchar(50) DEFAULT NULL COMMENT '省份',
  `country` varchar(50) DEFAULT NULL COMMENT '国家',
  `language` varchar(20) DEFAULT 'zh_CN' COMMENT '语言',
  `session_key` varchar(100) DEFAULT NULL COMMENT '会话密钥',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) DEFAULT 0 COMMENT '登录次数',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0禁用，1正常',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_unionid` (`unionid`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小程序用户表';

-- ============================================
-- 2. 创建预约订单表 (parent_orders) - 如果不存在
-- ============================================
CREATE TABLE IF NOT EXISTS `fa_parent_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `admin_id` int(11) DEFAULT 0 COMMENT '管理员ID，0表示小程序预约',
  
  -- 学生信息
  `grade` varchar(20) DEFAULT NULL COMMENT '年级',
  `student_gender` varchar(10) DEFAULT NULL COMMENT '学生性别：男/女',
  `subject` varchar(50) DEFAULT NULL COMMENT '科目',
  `student_info` text COMMENT '学生情况描述',
  
  -- 上课安排
  `frequency` varchar(50) DEFAULT NULL COMMENT '上课频率：每周几次',
  `duration` varchar(50) DEFAULT NULL COMMENT '上课时长：1小时/次、1.5小时/次等',
  `teaching_method` varchar(50) DEFAULT NULL COMMENT '授课方式：上门授课/线上授课',
  
  -- 薪资预算
  `salary` varchar(100) DEFAULT NULL COMMENT '时薪范围字符串',
  `budget_min` int(11) DEFAULT NULL COMMENT '最低时薪（元/小时）',
  `budget_max` int(11) DEFAULT NULL COMMENT '最高时薪（元/小时）',
  
  -- 教师要求
  `teacher_requirement` text COMMENT '教师要求描述',
  `teacher_type` varchar(50) DEFAULT NULL COMMENT '教师类型：大学生/在职教师/专业教师等',
  `teacher_gender` varchar(10) DEFAULT NULL COMMENT '教师性别要求：男/女/不限',
  
  -- 地址信息
  `address` varchar(255) DEFAULT NULL COMMENT '详细地址',
  `province_id` int(11) DEFAULT NULL COMMENT '省份ID',
  `city_id` int(11) DEFAULT NULL COMMENT '城市ID',
  `district_id` int(11) DEFAULT NULL COMMENT '区县ID',
  
  -- 联系信息
  `parent_name` varchar(50) DEFAULT NULL COMMENT '家长姓名',
  `parent_contact` varchar(50) DEFAULT NULL COMMENT '家长联系方式',
  
  -- 订单状态
  `status` varchar(20) DEFAULT 'pending' COMMENT '状态：pending待审核，approved已通过，rejected已拒绝',
  `reject_reason` text COMMENT '拒绝原因',
  `remark` text COMMENT '备注',
  
  -- 预约渠道
  `booking_channel` varchar(20) DEFAULT 'H5' COMMENT '预约渠道：H5/小程序',
  `user_id` int(11) DEFAULT NULL COMMENT '小程序用户ID',
  
  -- 时间字段
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `approve_time` datetime DEFAULT NULL COMMENT '审核时间',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_no` (`order_no`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_booking_channel` (`booking_channel`),
  KEY `idx_grade_subject` (`grade`, `subject`),
  KEY `idx_teacher_type` (`teacher_type`),
  KEY `idx_teaching_method` (`teaching_method`),
  KEY `idx_province_city` (`province_id`, `city_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_approve_time` (`approve_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='家长预约订单表';

-- ============================================
-- 3. 创建订单状态变更日志表
-- ============================================
CREATE TABLE IF NOT EXISTS `fa_order_status_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `old_status` varchar(20) DEFAULT NULL COMMENT '原状态',
  `new_status` varchar(20) NOT NULL COMMENT '新状态',
  `operator_id` int(11) DEFAULT NULL COMMENT '操作员ID',
  `operator_type` varchar(20) DEFAULT 'admin' COMMENT '操作员类型：admin管理员，system系统',
  `reason` text COMMENT '变更原因',
  `remark` text COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_operator` (`operator_id`, `operator_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单状态变更日志表';

-- ============================================
-- 4. 创建用户登录日志表
-- ============================================
CREATE TABLE IF NOT EXISTS `user_login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信openid',
  `login_type` varchar(20) DEFAULT 'wechat' COMMENT '登录类型：wechat微信',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
  `session_key` varchar(100) DEFAULT NULL COMMENT '会话密钥',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0失败，1成功',
  `error_msg` varchar(255) DEFAULT NULL COMMENT '错误信息',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户登录日志表';

-- ============================================
-- 5. 创建用户行为日志表
-- ============================================
CREATE TABLE IF NOT EXISTS `user_behavior_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信openid',
  `action` varchar(50) NOT NULL COMMENT '行为类型：view_page浏览页面，submit_order提交订单等',
  `page_path` varchar(255) DEFAULT NULL COMMENT '页面路径',
  `params` json DEFAULT NULL COMMENT '参数数据',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_action` (`action`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为日志表';

-- ============================================
-- 6. 创建地区表（如果不存在）
-- ============================================
CREATE TABLE IF NOT EXISTS `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '地区ID',
  `name` varchar(50) NOT NULL COMMENT '地区名称',
  `parent_id` int(11) DEFAULT 0 COMMENT '父级ID，0为顶级',
  `level` tinyint(1) NOT NULL COMMENT '级别：1省份，2城市，3区县',
  `code` varchar(20) DEFAULT NULL COMMENT '地区编码',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0禁用，1启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_level` (`level`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='地区表';

-- ============================================
-- 7. 添加外键约束（可选，根据需要启用）
-- ============================================
-- ALTER TABLE `fa_parent_orders` 
-- ADD CONSTRAINT `fk_parent_orders_user_id` 
-- FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ALTER TABLE `fa_order_status_logs` 
-- ADD CONSTRAINT `fk_order_status_logs_order_id` 
-- FOREIGN KEY (`order_id`) REFERENCES `fa_parent_orders` (`id`) ON DELETE CASCADE;

-- ALTER TABLE `user_login_logs` 
-- ADD CONSTRAINT `fk_user_login_logs_user_id` 
-- FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- ALTER TABLE `user_behavior_logs` 
-- ADD CONSTRAINT `fk_user_behavior_logs_user_id` 
-- FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ============================================
-- 8. 插入一些基础数据
-- ============================================

-- 插入基础地区数据（示例）
INSERT IGNORE INTO `regions` (`id`, `name`, `parent_id`, `level`, `code`) VALUES
(1, '北京市', 0, 1, '110000'),
(2, '上海市', 0, 1, '310000'),
(3, '广东省', 0, 1, '440000'),
(4, '浙江省', 0, 1, '330000'),
(5, '江苏省', 0, 1, '320000'),
(101, '东城区', 1, 3, '110101'),
(102, '西城区', 1, 3, '110102'),
(103, '朝阳区', 1, 3, '110105'),
(104, '丰台区', 1, 3, '110106'),
(105, '石景山区', 1, 3, '110107'),
(201, '黄浦区', 2, 3, '310101'),
(202, '徐汇区', 2, 3, '310104'),
(203, '长宁区', 2, 3, '310105'),
(204, '静安区', 2, 3, '310106'),
(301, '广州市', 3, 2, '440100'),
(302, '深圳市', 3, 2, '440300'),
(303, '珠海市', 3, 2, '440400'),
(30101, '越秀区', 301, 3, '440103'),
(30102, '海珠区', 301, 3, '440105'),
(30103, '荔湾区', 301, 3, '440103'),
(30201, '罗湖区', 302, 3, '440303'),
(30202, '福田区', 302, 3, '440304'),
(30203, '南山区', 302, 3, '440305');

-- ============================================
-- 9. 创建视图（便于查询）
-- ============================================

-- 创建订单详情视图
CREATE OR REPLACE VIEW `v_order_details` AS
SELECT 
    o.id,
    o.order_no,
    o.grade,
    o.student_gender,
    o.subject,
    o.student_info,
    o.frequency,
    o.duration,
    o.teaching_method,
    o.salary,
    o.budget_min,
    o.budget_max,
    o.teacher_requirement,
    o.teacher_type,
    o.teacher_gender,
    o.address,
    o.parent_name,
    o.parent_contact,
    o.status,
    o.reject_reason,
    o.remark,
    o.booking_channel,
    o.create_time,
    o.update_time,
    o.approve_time,
    u.nickname as user_nickname,
    u.phone as user_phone,
    u.avatar as user_avatar,
    p.name as province_name,
    c.name as city_name,
    d.name as district_name,
    CONCAT_WS(' ', p.name, c.name, d.name) as full_address
FROM `fa_parent_orders` o
LEFT JOIN `users` u ON o.user_id = u.id
LEFT JOIN `regions` p ON o.province_id = p.id
LEFT JOIN `regions` c ON o.city_id = c.id
LEFT JOIN `regions` d ON o.district_id = d.id;

-- 创建用户统计视图
CREATE OR REPLACE VIEW `v_user_stats` AS
SELECT 
    u.id,
    u.openid,
    u.phone,
    u.nickname,
    u.avatar,
    u.gender,
    u.city,
    u.province,
    u.last_login_time,
    u.login_count,
    u.status,
    u.create_time,
    u.update_time,
    COUNT(o.id) as order_count,
    COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN o.status = 'approved' THEN 1 END) as approved_orders,
    COUNT(CASE WHEN o.status = 'rejected' THEN 1 END) as rejected_orders
FROM `users` u
LEFT JOIN `fa_parent_orders` o ON u.id = o.user_id
GROUP BY u.id;

-- ============================================
-- 10. 创建存储过程（便于数据操作）
-- ============================================

DELIMITER ;;

-- 创建订单状态变更存储过程
CREATE PROCEDURE `sp_change_order_status`(
    IN p_order_id INT,
    IN p_new_status VARCHAR(20),
    IN p_operator_id INT,
    IN p_operator_type VARCHAR(20),
    IN p_reason TEXT
)
BEGIN
    DECLARE v_old_status VARCHAR(20);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- 获取原状态
    SELECT status INTO v_old_status FROM fa_parent_orders WHERE id = p_order_id;
    
    -- 更新订单状态
    UPDATE fa_parent_orders 
    SET status = p_new_status,
        approve_time = CASE WHEN p_new_status IN ('approved', 'rejected') THEN NOW() ELSE approve_time END,
        update_time = NOW()
    WHERE id = p_order_id;
    
    -- 记录状态变更日志
    INSERT INTO fa_order_status_logs (
        order_id, old_status, new_status, operator_id, operator_type, reason, create_time
    ) VALUES (
        p_order_id, v_old_status, p_new_status, p_operator_id, p_operator_type, p_reason, NOW()
    );
    
    COMMIT;
END;;

-- 创建用户登录记录存储过程
CREATE PROCEDURE `sp_record_user_login`(
    IN p_user_id INT,
    IN p_openid VARCHAR(100),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT,
    IN p_session_key VARCHAR(100)
)
BEGIN
    -- 记录登录日志
    INSERT INTO user_login_logs (
        user_id, openid, login_type, ip_address, user_agent, login_time, session_key, status
    ) VALUES (
        p_user_id, p_openid, 'wechat', p_ip_address, p_user_agent, NOW(), p_session_key, 1
    );
    
    -- 更新用户最后登录时间和登录次数
    UPDATE users 
    SET last_login_time = NOW(),
        login_count = login_count + 1,
        session_key = p_session_key,
        update_time = NOW()
    WHERE id = p_user_id;
END;;

DELIMITER ;

-- ============================================
-- 执行完成提示
-- ============================================
SELECT '预约订单和小程序用户管理表格构建完成！' as message;
SELECT '已创建以下表格：' as info;
SELECT 'users - 小程序用户表' as table1;
SELECT 'fa_parent_orders - 预约订单表' as table2;
SELECT 'fa_order_status_logs - 订单状态日志表' as table3;
SELECT 'user_login_logs - 用户登录日志表' as table4;
SELECT 'user_behavior_logs - 用户行为日志表' as table5;
SELECT 'regions - 地区表' as table6;
SELECT 'v_order_details - 订单详情视图' as view1;
SELECT 'v_user_stats - 用户统计视图' as view2;

-- ============================================
-- 验证表结构
-- ============================================
-- 执行以下命令验证表是否创建成功：
-- SHOW TABLES LIKE '%users%';
-- SHOW TABLES LIKE '%parent_orders%';
-- SHOW TABLES LIKE '%logs%';
-- DESC users;
-- DESC fa_parent_orders;
-- SELECT * FROM v_order_details LIMIT 1;
-- SELECT * FROM v_user_stats LIMIT 1;