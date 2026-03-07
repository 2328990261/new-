-- ============================================
-- SEO优化配置升级脚本
-- 为系统添加SEO相关配置表
-- ============================================

USE myjiajiao;

-- 创建SEO配置表
CREATE TABLE IF NOT EXISTS `fa_seo_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `page_type` varchar(50) NOT NULL COMMENT '页面类型：home,teachers,teacher-detail,search等',
  `page_title` varchar(200) DEFAULT NULL COMMENT '页面标题',
  `page_keywords` varchar(500) DEFAULT NULL COMMENT '页面关键词',
  `page_description` varchar(1000) DEFAULT NULL COMMENT '页面描述',
  `og_title` varchar(200) DEFAULT NULL COMMENT 'Open Graph标题',
  `og_description` varchar(1000) DEFAULT NULL COMMENT 'Open Graph描述',
  `og_image` varchar(500) DEFAULT NULL COMMENT 'Open Graph图片',
  `canonical_url` varchar(500) DEFAULT NULL COMMENT '规范URL',
  `robots` varchar(100) DEFAULT 'index,follow' COMMENT 'robots指令',
  `sitemap_priority` decimal(2,1) DEFAULT 0.8 COMMENT '站点地图优先级(0.1-1.0)',
  `sitemap_changefreq` varchar(20) DEFAULT 'weekly' COMMENT '站点地图更新频率',
  `is_enabled` tinyint(1) DEFAULT 1 COMMENT '是否启用：1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_type` (`page_type`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SEO配置表';

-- 插入默认SEO配置
INSERT INTO `fa_seo_config` (
  `page_type`, `page_title`, `page_keywords`, `page_description`, 
  `og_title`, `og_description`, `og_image`, `canonical_url`, 
  `robots`, `sitemap_priority`, `sitemap_changefreq`, `is_enabled`
) VALUES 

-- 首页SEO配置
(
  'home',
  '优质家教信息平台 - 专业家教服务，让学习更高效',
  '家教,家教信息,一对一辅导,在线教育,优质教师,学习辅导,教育服务',
  '专业的家教信息平台，汇聚优质教师资源，提供一对一辅导服务。覆盖全国多个城市，涵盖各科教学，帮助学生提高学习成绩，让学习更高效。',
  '优质家教信息平台 - 专业家教服务',
  '专业的家教信息平台，汇聚优质教师资源，提供一对一辅导服务。覆盖全国多个城市，涵盖各科教学。',
  '',
  '',
  'index,follow',
  1.0,
  'daily',
  1
),

-- 教师列表页SEO配置
(
  'teachers',
  '优秀教师推荐 - 精选优质家教教师',
  '优秀教师,家教教师,教师推荐,一对一辅导,专业教师,教学经验',
  '精选优质家教教师，拥有丰富教学经验和专业背景。涵盖各科教学，提供个性化辅导服务，帮助学生提高学习成绩。',
  '优秀教师推荐 - 精选优质家教教师',
  '精选优质家教教师，拥有丰富教学经验和专业背景。涵盖各科教学，提供个性化辅导服务。',
  '',
  '',
  'index,follow',
  0.9,
  'weekly',
  1
),

-- 教师详情页SEO配置
(
  'teacher-detail',
  '{teacher_name} - 专业{subject}家教教师',
  '{teacher_name},{subject}家教,一对一辅导,专业教师,教学经验',
  '{teacher_name}，{education}学历，{experience}年教学经验。专业教授{subject}，擅长{teaching_style}，帮助学生提高学习成绩。',
  '{teacher_name} - 专业{subject}家教教师',
  '{teacher_name}，{education}学历，{experience}年教学经验。专业教授{subject}，帮助学生提高学习成绩。',
  '{teacher_photo}',
  '',
  'index,follow',
  0.8,
  'monthly',
  1
),

-- 搜索页SEO配置
(
  'search',
  '{keyword}家教信息 - 搜索结果',
  '{keyword}家教,{keyword}辅导,一对一教学,专业教师',
  '搜索{keyword}相关家教信息，找到合适的专业教师。提供一对一辅导服务，帮助学生提高{keyword}成绩。',
  '{keyword}家教信息 - 搜索结果',
  '搜索{keyword}相关家教信息，找到合适的专业教师。提供一对一辅导服务。',
  '',
  '',
  'index,follow',
  0.7,
  'weekly',
  1
),

-- 教师注册页SEO配置
(
  'teacher-register',
  '教师注册 - 加入我们成为优质家教教师',
  '教师注册,家教教师,教师招聘,教育行业,教学工作',
  '加入我们的教师团队，成为优质家教教师。提供灵活的工作时间和丰厚的报酬，让您的教学才能得到充分发挥。',
  '教师注册 - 加入我们成为优质家教教师',
  '加入我们的教师团队，成为优质家教教师。提供灵活的工作时间和丰厚的报酬。',
  '',
  '',
  'index,follow',
  0.6,
  'monthly',
  1
),

-- 城市点亮页SEO配置
(
  'city-light',
  '点亮城市 - 助力城市开通家教服务',
  '点亮城市,城市开通,家教服务,教育普及,城市发展',
  '参与城市点亮活动，助力更多城市开通优质家教服务。让每个城市的孩子都能享受到专业的教学辅导。',
  '点亮城市 - 助力城市开通家教服务',
  '参与城市点亮活动，助力更多城市开通优质家教服务。让每个城市的孩子都能享受到专业的教学辅导。',
  '',
  '',
  'index,follow',
  0.7,
  'weekly',
  1
);

-- 创建站点地图配置表
CREATE TABLE IF NOT EXISTS `fa_sitemap_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `url_path` varchar(200) NOT NULL COMMENT 'URL路径',
  `page_title` varchar(200) DEFAULT NULL COMMENT '页面标题',
  `lastmod` datetime DEFAULT NULL COMMENT '最后修改时间',
  `changefreq` varchar(20) DEFAULT 'weekly' COMMENT '更新频率',
  `priority` decimal(2,1) DEFAULT 0.8 COMMENT '优先级',
  `is_enabled` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_url_path` (`url_path`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='站点地图配置表';

-- 插入默认站点地图配置
INSERT INTO `fa_sitemap_config` (
  `url_path`, `page_title`, `lastmod`, `changefreq`, `priority`, `is_enabled`
) VALUES 
('/', '优质家教信息平台', NOW(), 'daily', 1.0, 1),
('/teachers', '优秀教师推荐', NOW(), 'weekly', 0.9, 1),
('/teacher-register', '教师注册', NOW(), 'monthly', 0.6, 1),
('/city-light', '点亮城市', NOW(), 'weekly', 0.7, 1);

-- 显示配置结果
SELECT 'SEO配置表创建完成' as message;
SELECT page_type, page_title, is_enabled FROM `fa_seo_config` ORDER BY id;
SELECT '站点地图配置表创建完成' as message;
SELECT url_path, page_title, priority, is_enabled FROM `fa_sitemap_config` ORDER BY id;
