-- 管理员：小程序「免费找家教」提交成功页一键拨号（与 contact 字段区分）
ALTER TABLE `fa_admin`
ADD COLUMN `booking_service_phone` varchar(20) DEFAULT NULL COMMENT '预约成功页联系手机' AFTER `wechat_qrcode`;
