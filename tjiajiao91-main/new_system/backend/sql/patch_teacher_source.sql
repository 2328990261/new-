-- 教师来源字段
-- h5: 用户端H5注册  miniprogram: 支付宝小程序注册
ALTER TABLE `fa_teachers`
  ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT 'h5' COMMENT '注册来源：h5-用户端，miniprogram-小程序' AFTER `review_note`;

-- 回填历史数据：有 openid 的是小程序，否则是 H5
UPDATE `fa_teachers` SET `source` = 'miniprogram' WHERE openid IS NOT NULL AND openid <> '';
UPDATE `fa_teachers` SET `source` = 'h5' WHERE openid IS NULL OR openid = '';
