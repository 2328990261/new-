-- ========================================
-- 企业微信人员管理完整配置脚本
-- ========================================

-- 步骤1：检查表是否存在
SELECT '步骤1：检查表是否存在' as step;
SHOW TABLES LIKE 'fa_wecom_config';

-- 步骤2：如果表不存在，创建表
CREATE TABLE IF NOT EXISTS `fa_wecom_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `corp_id` varchar(100) NOT NULL DEFAULT '' COMMENT '企业ID',
  `contact_secret` varchar(200) DEFAULT '' COMMENT '通讯录同步Secret',
  `agent_id` varchar(50) DEFAULT '' COMMENT '应用AgentId',
  `agent_secret` varchar(200) DEFAULT '' COMMENT '应用Secret',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信配置表';

-- 步骤3：检查 agent_secret 字段是否存在
SELECT '步骤3：检查字段' as step;
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'fa_wecom_config' 
  AND COLUMN_NAME = 'agent_secret';

-- 步骤4：如果 agent_secret 字段不存在，添加它
-- 注意：如果字段已存在，这条SQL会报错，可以忽略
ALTER TABLE `fa_wecom_config` 
ADD COLUMN `agent_secret` varchar(200) DEFAULT '' COMMENT '应用Secret' 
AFTER `agent_id`;

-- 步骤5：查看表结构
SELECT '步骤5：查看表结构' as step;
DESC fa_wecom_config;

-- 步骤6：确保有一条配置记录
INSERT INTO `fa_wecom_config` (`id`, `corp_id`, `contact_secret`, `agent_id`, `agent_secret`, `create_time`, `update_time`) 
VALUES (1, '', '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `id` = `id`;

-- 步骤7：更新配置（请替换为你的实际值）
SELECT '步骤7：更新配置（请手动修改下面的值）' as step;

UPDATE `fa_wecom_config` 
SET 
  `corp_id` = '你的企业ID',                    -- 在【我的企业】页面底部查看
  `agent_id` = '你的应用AgentId',              -- 在【应用管理】→应用详情页查看
  `agent_secret` = 'fiqJKcrjxzv5og9ypXfE12BEuicL-cwmzGYalOikbqM',  -- 你的应用Secret（建议重置后再填）
  `update_time` = UNIX_TIMESTAMP()
WHERE `id` = 1;

-- 步骤8：验证配置
SELECT '步骤8：验证配置' as step;
SELECT 
  `id`,
  `corp_id`,
  `agent_id`,
  CONCAT(LEFT(`agent_secret`, 15), '...') as agent_secret_preview,
  CONCAT(LEFT(`contact_secret`, 15), '...') as contact_secret_preview,
  FROM_UNIXTIME(`create_time`) as create_time,
  FROM_UNIXTIME(`update_time`) as update_time
FROM `fa_wecom_config` 
WHERE `id` = 1;

-- ========================================
-- 配置说明
-- ========================================
-- 
-- 1. corp_id（企业ID）：
--    - 登录 https://work.weixin.qq.com/
--    - 点击【我的企业】
--    - 滚动到底部，复制"企业ID"
--
-- 2. agent_id（应用ID）：
--    - 点击【应用管理】
--    - 选择一个应用（或创建新应用）
--    - 在应用详情页查看"AgentId"（纯数字）
--
-- 3. agent_secret（应用Secret）：
--    - 在应用详情页
--    - 点击"Secret"旁边的【查看】按钮
--    - 扫码验证后复制Secret
--    - ⚠️ 注意：你之前发给我的Secret已经暴露，建议先重置
--
-- 4. 应用权限配置（重要！）：
--    - 在应用详情页，找到【企业微信授权】或【接口权限】
--    - 开启"通讯录管理"或"访问通讯录"权限
--    - 否则无法获取人员信息
--
-- 5. 应用可见范围：
--    - 在应用详情页，找到【可见范围】
--    - 建议设置为"全公司"
--    - 或者至少包含需要管理的部门
--
-- ========================================
