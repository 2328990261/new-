-- 手动关联你的两个支付宝小程序账号
-- 请替换下面的值：
-- 1. YOUR_PHONE: 你的手机号
-- 2. NEW_OPENID: 第二个小程序的 openid（从报错日志或数据库新记录中获取）

-- 查看你当前的账号信息
SELECT id, openid, openid_secondary, phone, nickname 
FROM fa_users 
WHERE phone = 'YOUR_PHONE' AND platform LIKE '%alipay%';

-- 更新 openid_secondary（把第二个小程序的 openid 填进去）
UPDATE fa_users 
SET openid_secondary = 'alipay_NEW_USER_ID'
WHERE phone = 'YOUR_PHONE' AND platform LIKE '%alipay%';

-- 验证更新结果
SELECT id, openid, openid_secondary, phone, nickname 
FROM fa_users 
WHERE phone = 'YOUR_PHONE' AND platform LIKE '%alipay%';
