-- 已有库增量：为 fa_payments 增加 openid 与索引（与 myjiajiao.sql 结构一致）
-- 若已执行过请勿重复执行

ALTER TABLE `fa_payments`
  ADD COLUMN `openid` varchar(100) DEFAULT NULL COMMENT '微信支付用户openid（JSAPI等）' AFTER `payer_contact`,
  ADD KEY `idx_openid` (`openid`),
  ADD KEY `idx_payer_contact` (`payer_contact`);
