-- 为 fa_users 表添加 openid_secondary 字段
-- 用于支持支付宝多小程序账号关联

ALTER TABLE `fa_users` 
ADD COLUMN `openid_secondary` VARCHAR(100) NULL DEFAULT NULL COMMENT '备用openid（支持多小程序）' AFTER `openid`,
ADD INDEX `idx_openid_secondary` (`openid_secondary`);

-- 说明：
-- openid: 主 openid（第一个小程序）
-- openid_secondary: 备用 openid（第二个小程序）
-- 登录时会同时查询这两个字段
