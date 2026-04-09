-- =============================================================================
-- fa_success_cases 成功案例表（删表重建，执行后该表数据全部清空，请先备份）
-- 与当前后端 / 小程序字段一致：年级、科目、主题标签、顶部图 JSON、标题、四段正文
-- TEXT 列请勿写 DEFAULT ''，避免部分 MySQL 版本报错
-- =============================================================================

DROP TABLE IF EXISTS `fa_success_cases`;

CREATE TABLE `fa_success_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `grade` varchar(64) NOT NULL DEFAULT '' COMMENT '年级标签',
  `subject` varchar(64) NOT NULL DEFAULT '' COMMENT '科目标签',
  `theme_tag` varchar(64) NOT NULL DEFAULT '' COMMENT '主题/成果标签（可选）',
  `cover_images` text NOT NULL COMMENT '顶部展示图 URL 列表 JSON 数组',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `course_intro` text NOT NULL COMMENT '课程情况介绍',
  `student_background` text NOT NULL COMMENT '学生背景',
  `tutoring_results` text NOT NULL COMMENT '辅导成果',
  `parent_comment` text NOT NULL COMMENT '家长评语',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序（越小越靠前）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_sort_status` (`sort_order`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='成功案例';
