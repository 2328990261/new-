-- 修复企业配置表的时间字段
-- 将时间字段改为 unsigned int，create_time 和 update_time 默认值为 0

ALTER TABLE `fa_enterprise_config` 
  MODIFY COLUMN `create_time` int(11) unsigned DEFAULT 0 COMMENT '创建时间',
  MODIFY COLUMN `update_time` int(11) unsigned DEFAULT 0 COMMENT '更新时间',
  MODIFY COLUMN `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间';
