-- 创建网站基础配置表
CREATE TABLE IF NOT EXISTS `fa_site_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `platform_name` varchar(100) DEFAULT '家教平台' COMMENT '平台名称',
  `platform_slogan` varchar(200) DEFAULT '' COMMENT '平台标语/口号',
  `icp_number` varchar(100) DEFAULT '' COMMENT 'ICP备案号',
  `police_number` varchar(100) DEFAULT '' COMMENT '公安备案号',
  `police_link` varchar(255) DEFAULT '' COMMENT '公安备案链接',
  `copyright_info` varchar(200) DEFAULT '' COMMENT '版权信息',
  `company_name` varchar(200) DEFAULT '' COMMENT '公司名称',
  `contact_phone` varchar(50) DEFAULT '' COMMENT '联系电话',
  `contact_email` varchar(100) DEFAULT '' COMMENT '联系邮箱',
  `contact_address` varchar(255) DEFAULT '' COMMENT '联系地址',
  `logo_url` varchar(255) DEFAULT '' COMMENT 'Logo URL',
  `favicon_url` varchar(255) DEFAULT '' COMMENT 'Favicon URL',
  `meta_keywords` varchar(500) DEFAULT '' COMMENT 'SEO关键词',
  `meta_description` text COMMENT 'SEO描述',
  `statistics_code` text COMMENT '统计代码（百度统计/Google Analytics等）',
  `custom_header_code` text COMMENT '自定义头部代码',
  `custom_footer_code` text COMMENT '自定义底部代码',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1=启用，0=禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站基础配置表';

-- 插入默认配置
INSERT INTO `fa_site_config` (`id`, `platform_name`, `platform_slogan`, `icp_number`, `police_number`, `copyright_info`, `company_name`, `status`, `create_time`, `update_time`) 
VALUES (1, '家教平台', '连接优质教师与学生', '', '', '© 2024 家教平台 版权所有', '', 1, NOW(), NOW());

