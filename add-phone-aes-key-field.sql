-- 为 fa_mini_program_config 表添加手机号 AES 密钥字段
-- 用于支持多个支付宝小程序，每个小程序有独立的手机号解密密钥

ALTER TABLE `fa_mini_program_config`
ADD COLUMN `phone_aes_key_enc` TEXT NULL COMMENT '加密后的手机号AES密钥(支付宝)' AFTER `app_secret_enc`;

-- 说明：
-- 1. 该字段仅支付宝小程序使用
-- 2. 存储的是加密后的 AES 密钥（与 app_secret_enc 使用相同的加密方式）
-- 3. 如果该字段为空，则回退到 .env 文件中的 ALIPAY_PHONE_AES_KEY
