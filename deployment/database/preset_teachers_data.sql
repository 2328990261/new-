-- ============================================
-- 预设教师数据导入脚本
-- 包含多个优质教师的详细信息和头像
-- ============================================

USE myjiajiao;

-- 确保教师表存在
CREATE TABLE IF NOT EXISTS `fa_teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '教师ID',
  `account_id` int(11) DEFAULT NULL COMMENT '关联的教师账号ID',
  `name` varchar(50) NOT NULL COMMENT '教师姓名',
  `gender` varchar(10) DEFAULT NULL COMMENT '性别：男/女',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `education` varchar(50) DEFAULT NULL COMMENT '学历：本科/硕士/博士等',
  `school` varchar(100) DEFAULT NULL COMMENT '毕业院校',
  `major` varchar(100) DEFAULT NULL COMMENT '专业',
  `hourly_rate` decimal(10,2) DEFAULT NULL COMMENT '课时费（元/小时）',
  `subject_ids` varchar(255) DEFAULT NULL COMMENT '教授科目ID列表（逗号分隔）',
  `subject_names` varchar(255) DEFAULT NULL COMMENT '教授科目名称列表（逗号分隔）',
  `district_ids` varchar(255) DEFAULT NULL COMMENT '授课区域ID列表（逗号分隔）',
  `district_names` varchar(255) DEFAULT NULL COMMENT '授课区域名称列表（逗号分隔）',
  `experience` text COMMENT '教学经历',
  `self_intro` text COMMENT '自我介绍',
  `photos` text COMMENT '照片URL列表（JSON格式）',
  `status` varchar(20) DEFAULT 'pending' COMMENT '审核状态：pending-待审核，approved-已通过，rejected-已拒绝',
  `reject_reason` text COMMENT '拒绝原因',
  `is_top` tinyint(1) DEFAULT 0 COMMENT '是否置顶：0-否，1-是',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师表';

-- 清空现有数据（可选，根据需要决定是否执行）
-- DELETE FROM `fa_teachers`;

-- 插入预设教师数据
INSERT INTO `fa_teachers` (
  `name`, `gender`, `phone`, `email`, `education`, `school`, `major`, 
  `hourly_rate`, `subject_names`, `district_names`, `experience`, `self_intro`, 
  `photos`, `status`, `is_top`, `review_time`, `reviewer_id`
) VALUES 

-- 教师1：张教授 - 数学专家
(
  '张教授', '男', '13800138001', 'zhang.prof@example.com', '博士', 
  '清华大学', '数学与应用数学', 200.00, '数学,高等数学,线性代数', 
  '海淀区,朝阳区', 
  '清华大学数学系博士毕业，从事数学教学15年，曾获得全国优秀教师称号。擅长高中数学、大学数学辅导，帮助学生提高数学思维和解题能力。',
  '我是张教授，清华大学数学系博士，拥有15年丰富教学经验。我热爱数学教育，善于将复杂的数学概念用简单易懂的方式讲解，帮助学生建立数学思维。我的教学风格严谨而不失生动，深受学生和家长喜爱。',
  '["https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师2：李老师 - 英语专家
(
  '李老师', '女', '13800138002', 'li.teacher@example.com', '硕士', 
  '北京外国语大学', '英语语言文学', 150.00, '英语,雅思,托福', 
  '西城区,东城区', 
  '北京外国语大学英语系硕士，英语专业八级，雅思8.5分。从事英语教学12年，擅长初高中英语、雅思托福培训，帮助学生提高英语综合能力。',
  '大家好，我是李老师，北外英语系硕士毕业。我热爱英语教学，拥有12年丰富经验。我注重培养学生的英语思维和实际应用能力，让英语学习变得有趣而高效。',
  '["https://images.unsplash.com/photo-1494790108755-2616b612b786?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1494790108755-2616b612b786?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师3：王老师 - 物理专家
(
  '王老师', '男', '13800138003', 'wang.teacher@example.com', '硕士', 
  '北京大学', '物理学', 180.00, '物理,高中物理,大学物理', 
  '海淀区,丰台区', 
  '北京大学物理系硕士，物理教学10年经验。擅长初高中物理教学，帮助学生理解物理概念，提高物理成绩。',
  '我是王老师，北大物理系硕士，拥有10年物理教学经验。我善于用实验和生活中的例子来解释物理现象，让抽象的物理概念变得具体可感。',
  '["https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师4：陈老师 - 化学专家
(
  '陈老师', '女', '13800138004', 'chen.teacher@example.com', '硕士', 
  '中科院化学所', '化学', 160.00, '化学,高中化学,有机化学', 
  '朝阳区,通州区', 
  '中科院化学所硕士，化学教学8年经验。擅长初高中化学教学，帮助学生掌握化学知识，提高化学成绩。',
  '大家好，我是陈老师，中科院化学所硕士毕业。我热爱化学教学，拥有8年丰富经验。我注重培养学生的化学思维和实验能力。',
  '["https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师5：刘老师 - 语文专家
(
  '刘老师', '女', '13800138005', 'liu.teacher@example.com', '硕士', 
  '北京师范大学', '汉语言文学', 140.00, '语文,作文,古诗词', 
  '海淀区,石景山区', 
  '北京师范大学汉语言文学硕士，语文教学11年经验。擅长初高中语文教学，特别是作文和古诗词鉴赏，帮助学生提高语文素养。',
  '我是刘老师，北师大汉语言文学硕士，拥有11年语文教学经验。我热爱中华文化，善于引导学生感受文字之美，提高语文综合能力。',
  '["https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师6：赵老师 - 生物专家
(
  '赵老师', '男', '13800138006', 'zhao.teacher@example.com', '硕士', 
  '中国农业大学', '生物学', 150.00, '生物,高中生物,分子生物学', 
  '海淀区,昌平区', 
  '中国农业大学生物学硕士，生物教学9年经验。擅长初高中生物教学，帮助学生理解生物知识，提高生物成绩。',
  '大家好，我是赵老师，中国农业大学生物学硕士。我热爱生物教学，拥有9年丰富经验。我善于用生动的例子来解释生物现象。',
  '["https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师7：孙老师 - 历史专家
(
  '孙老师', '女', '13800138007', 'sun.teacher@example.com', '硕士', 
  '中国人民大学', '历史学', 130.00, '历史,中国历史,世界历史', 
  '西城区,宣武区', 
  '中国人民大学历史学硕士，历史教学7年经验。擅长初高中历史教学，帮助学生理解历史知识，提高历史成绩。',
  '我是孙老师，人大历史学硕士，拥有7年历史教学经验。我热爱历史教育，善于用故事的方式让学生了解历史。',
  '["https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师8：周老师 - 地理专家
(
  '周老师', '男', '13800138008', 'zhou.teacher@example.com', '硕士', 
  '北京师范大学', '地理科学', 135.00, '地理,高中地理,自然地理', 
  '朝阳区,大兴区', 
  '北京师范大学地理科学硕士，地理教学8年经验。擅长初高中地理教学，帮助学生理解地理知识，提高地理成绩。',
  '大家好，我是周老师，北师大地理科学硕士。我热爱地理教学，拥有8年丰富经验。我善于用地图和实例来解释地理现象。',
  '["https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师9：吴老师 - 政治专家
(
  '吴老师', '女', '13800138009', 'wu.teacher@example.com', '硕士', 
  '中央财经大学', '政治学', 125.00, '政治,思想政治,马克思主义', 
  '海淀区,房山区', 
  '中央财经大学政治学硕士，政治教学6年经验。擅长初高中政治教学，帮助学生理解政治知识，提高政治成绩。',
  '我是吴老师，中央财经大学政治学硕士，拥有6年政治教学经验。我注重培养学生的政治素养和思辨能力。',
  '["https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
),

-- 教师10：郑老师 - 计算机专家
(
  '郑老师', '男', '13800138010', 'zheng.teacher@example.com', '硕士', 
  '北京理工大学', '计算机科学与技术', 220.00, '计算机,编程,信息技术', 
  '海淀区,丰台区', 
  '北京理工大学计算机科学与技术硕士，计算机教学5年经验。擅长初高中信息技术教学，帮助学生掌握计算机知识。',
  '大家好，我是郑老师，北理工计算机科学与技术硕士。我热爱计算机教育，拥有5年丰富经验。我注重培养学生的计算思维。',
  '["https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=300&h=300&fit=crop&crop=face","https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=400&h=400&fit=crop&crop=face"]',
  'approved', 1, NOW(), 1
);

-- 显示插入结果
SELECT 
  id, name, gender, education, school, major, hourly_rate, 
  subject_names, status, is_top, create_time 
FROM `fa_teachers` 
ORDER BY is_top DESC, create_time DESC;

-- 显示统计信息
SELECT 
  COUNT(*) as total_teachers,
  COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_teachers,
  COUNT(CASE WHEN is_top = 1 THEN 1 END) as top_teachers,
  AVG(hourly_rate) as avg_hourly_rate
FROM `fa_teachers`;
