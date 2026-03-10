-- 修改线索表status字段的ENUM类型，添加"待联系"和"已出单"状态
-- 同时将"待跟进"改为"待联系"
-- 执行时间：根据实际情况选择
-- 注意：执行前请先备份数据库

USE myjiajiao;

-- 1. 先将status字段改为VARCHAR类型（临时）
ALTER TABLE fa_leads 
MODIFY COLUMN status VARCHAR(20) DEFAULT '待联系' COMMENT '跟进状态';

-- 2. 更新所有"待跟进"为"待联系"
UPDATE fa_leads 
SET status = '待联系' 
WHERE status = '待跟进';

-- 3. 将status字段改为新的ENUM类型（包含所有状态）
ALTER TABLE fa_leads 
MODIFY COLUMN status ENUM('待联系', '跟进中', '已发单', '已出单', '不需要', '无效') 
DEFAULT '待联系' 
COMMENT '跟进状态';

-- 4. 同样处理跟进记录表
-- 先改为VARCHAR
ALTER TABLE fa_lead_follow_logs 
MODIFY COLUMN old_status VARCHAR(20) DEFAULT NULL COMMENT '原状态';

ALTER TABLE fa_lead_follow_logs 
MODIFY COLUMN new_status VARCHAR(20) NOT NULL COMMENT '新状态';

-- 更新数据
UPDATE fa_lead_follow_logs 
SET old_status = '待联系' 
WHERE old_status = '待跟进';

UPDATE fa_lead_follow_logs 
SET new_status = '待联系' 
WHERE new_status = '待跟进';

-- 查看更新结果
SELECT '线索表status字段更新完成' as message;
SELECT status, COUNT(*) as count 
FROM fa_leads 
GROUP BY status;

SELECT '跟进记录表状态字段更新完成' as message;
SELECT new_status, COUNT(*) as count 
FROM fa_lead_follow_logs 
GROUP BY new_status;
