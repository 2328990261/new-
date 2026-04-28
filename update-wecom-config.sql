-- 企业微信配置更新SQL
-- 请先查看当前配置
SELECT * FROM fa_wecom_config WHERE id = 1;

-- 如果表中没有记录，先插入一条
INSERT INTO fa_wecom_config (id, corp_id, agent_id, agent_secret, contact_secret, create_time, update_time)
VALUES (1, '', '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE id = id;

-- 更新配置（请替换为你的实际值）
UPDATE fa_wecom_config 
SET 
  corp_id = '你的企业ID',                    -- 在【我的企业】页面底部查看
  agent_id = '你的应用AgentId',              -- 在【应用管理】→应用详情页查看（纯数字）
  agent_secret = 'fiqJKcrjxzv5og9ypXfE12BEuicL-cwmzGYalOikbqM',  -- 你提供的Secret
  update_time = UNIX_TIMESTAMP()
WHERE id = 1;

-- 验证配置
SELECT 
  id,
  corp_id,
  agent_id,
  CONCAT(LEFT(agent_secret, 15), '...') as agent_secret_preview,
  CONCAT(LEFT(contact_secret, 15), '...') as contact_secret_preview,
  FROM_UNIXTIME(create_time) as create_time,
  FROM_UNIXTIME(update_time) as update_time
FROM fa_wecom_config 
WHERE id = 1;
