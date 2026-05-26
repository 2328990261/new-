-- 添加企业管理访问权限字段到管理员表
ALTER TABLE `fa_admin` 
ADD COLUMN `can_access_enterprise` tinyint(1) DEFAULT '0' COMMENT '是否可访问企业管理：0-否，1-是' AFTER `status`;

-- 给超级管理员默认开启权限
UPDATE `fa_admin` SET `can_access_enterprise` = 1 WHERE `role` = 'super_admin';
