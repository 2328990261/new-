-- 修复后台「基础配置 → 支付配置」保存/测试微信支付时报错：
-- SQLSTATE[42S22]: Column not found: 1054 Unknown column 'app_secret' in 'field list'
--
-- 原因：线上 fa_payment_config 表缺少 app_secret 列，而后端 PaymentConfig 模型会读写该字段。
-- 请在业务库执行本脚本（表前缀为 fa_，与 config/database.php 一致）。
--
-- 若列已存在，执行会报错 Duplicate column name，可忽略。

ALTER TABLE `fa_payment_config`
  ADD COLUMN `app_secret` varchar(100) DEFAULT NULL COMMENT '应用密钥' AFTER `api_key`;
