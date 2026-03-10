-- ============================================
-- 网站横幅表
-- 用于管理首页和其他页面的轮播横幅
-- ============================================

CREATE TABLE IF NOT EXISTS `fa_site_banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(200) DEFAULT '' COMMENT '横幅标题',
  `description` text COMMENT '横幅描述',
  `image_url` varchar(500) NOT NULL COMMENT '横幅图片URL',
  `link_url` varchar(500) DEFAULT '' COMMENT '点击跳转链接',
  `target` varchar(20) DEFAULT '_self' COMMENT '链接打开方式：_self=当前窗口, _blank=新窗口',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序值（越小越靠前）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1=启用，0=禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_sort_status` (`sort_order`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站横幅表';

-- 插入示例数据
INSERT INTO `fa_site_banners` (`title`, `description`, `image_url`, `link_url`, `sort_order`, `status`, `create_time`, `update_time`) 
VALUES 
('欢迎使用家教平台', '连接优质教师与学生', '', '', 1, 1, NOW(), NOW());

