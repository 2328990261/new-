-- ============================================
-- 协议管理表 - 支持富文本编辑
-- ============================================
-- 执行时间：立即执行
-- 执行位置：PHPStudy -> phpMyAdmin -> SQL 标签
-- 数据库：myjiajiao
-- ============================================

-- 创建协议表
CREATE TABLE IF NOT EXISTS `fa_agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '协议ID',
  `type` varchar(50) NOT NULL COMMENT '协议类型：user_agreement用户协议，privacy_policy隐私政策',
  `title` varchar(200) NOT NULL COMMENT '协议标题',
  `content` longtext NOT NULL COMMENT '协议内容（富文本HTML）',
  `plain_content` longtext COMMENT '纯文本内容（用于搜索）',
  `version` varchar(20) DEFAULT '1.0' COMMENT '版本号',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0禁用，1启用',
  `is_current` tinyint(1) DEFAULT 0 COMMENT '是否为当前版本：0否，1是',
  `effective_time` datetime DEFAULT NULL COMMENT '生效时间',
  `creator_id` int(11) DEFAULT NULL COMMENT '创建者ID',
  `updater_id` int(11) DEFAULT NULL COMMENT '更新者ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_current` (`is_current`),
  KEY `idx_effective_time` (`effective_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='协议管理表';

-- 插入默认协议数据
INSERT INTO `fa_agreements` (`type`, `title`, `content`, `plain_content`, `version`, `status`, `is_current`, `effective_time`) VALUES
('user_agreement', '用户服务协议', 
'<h2>用户服务协议</h2>
<p><strong>更新时间：2024年12月11日</strong></p>
<h3>1. 服务条款的确认和接纳</h3>
<p>欢迎使用小萌家教助手！本协议是您与我们之间关于使用本服务的法律协议。使用本服务即表示您同意遵守本协议的所有条款。</p>
<h3>2. 服务内容</h3>
<p>我们提供的服务包括但不限于：</p>
<ul>
<li>家教需求发布和匹配</li>
<li>教师资源推荐</li>
<li>在线预约和管理</li>
<li>客服咨询服务</li>
<li>相关信息查询</li>
</ul>',
'用户服务协议 更新时间：2024年12月11日 1. 服务条款的确认和接纳 欢迎使用小萌家教助手！本协议是您与我们之间关于使用本服务的法律协议。使用本服务即表示您同意遵守本协议的所有条款。',
'1.0', 1, 1, NOW()),

('privacy_policy', '隐私政策', 
'<h2>隐私政策</h2>
<p><strong>更新时间：2024年12月11日</strong></p>
<h3>1. 信息收集</h3>
<p>我们会收集您在使用小程序时提供的信息，包括但不限于：</p>
<ul>
<li>基本信息：微信昵称、头像等</li>
<li>联系信息：手机号码</li>
<li>预约信息：学生年级、科目、地址等</li>
<li>设备信息：设备型号、操作系统版本等</li>
</ul>
<h3>2. 信息使用</h3>
<p>我们收集您的信息用于：</p>
<ul>
<li>提供家教预约服务</li>
<li>匹配合适的教师资源</li>
<li>改善用户体验</li>
<li>客服支持和问题解决</li>
</ul>',
'隐私政策 更新时间：2024年12月11日 1. 信息收集 我们会收集您在使用小程序时提供的信息，包括但不限于：基本信息：微信昵称、头像等 联系信息：手机号码',
'1.0', 1, 1, NOW());

-- ============================================
-- 执行完成提示
-- ============================================
SELECT '协议管理表创建完成！' as message;
SELECT '已插入默认协议数据' as info;