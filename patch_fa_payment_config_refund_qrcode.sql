-- 增量：退费关注公众号二维码（与 myjiajiao.sql 一致）
ALTER TABLE `fa_payment_config`
  ADD COLUMN `refund_follow_qrcode` varchar(512) DEFAULT NULL COMMENT '退费页关注公众号二维码图片URL' AFTER `notify_url`;
