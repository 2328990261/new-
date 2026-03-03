-- 创建优势标签表（可选）
-- 如果需要后台管理标签，可以执行此脚本

USE myjiajiao;

-- 创建优势标签表
CREATE TABLE IF NOT EXISTS `fa_advantage_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) NOT NULL COMMENT '标签名称',
  `sort` int(11) DEFAULT 0 COMMENT '排序（数字越小越靠前）',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师优势标签表';

-- 插入默认标签数据
INSERT INTO `fa_advantage_tags` (`name`, `sort`, `status`) VALUES
('耐心细致', 1, 1),
('经验丰富', 2, 1),
('因材施教', 3, 1),
('提分快', 4, 1),
('善于沟通', 5, 1),
('责任心强', 6, 1),
('方法独特', 7, 1),
('亲和力强', 8, 1),
('严格要求', 9, 1),
('激发兴趣', 10, 1),
('重点突出', 11, 1),
('举一反三', 12, 1),
('循序渐进', 13, 1),
('查漏补缺', 14, 1),
('培养习惯', 15, 1)
ON DUPLICATE KEY UPDATE 
  `sort` = VALUES(`sort`),
  `status` = VALUES(`status`);

-- 验证数据
SELECT * FROM fa_advantage_tags ORDER BY sort;
