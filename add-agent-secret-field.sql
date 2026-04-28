-- 为 fa_wecom_config 表添加 agent_secret 字段
-- 用于存储企业微信应用的Secret

-- 检查表结构
DESC fa_wecom_config;

-- 添加 agent_secret 字段（如果不存在）
ALTER TABLE `fa_wecom_config` 
ADD COLUMN `agent_secret` varchar(200) DEFAULT '' COMMENT '应用Secret' 
AFTER `agent_id`;

-- 验证字段已添加
DESC fa_wecom_config;

-- 查看当前配置
SELECT * FROM fa_wecom_config WHERE id = 1;
