-- ========================================
-- 支付系统相关表
-- 创建日期：2025-10-04
-- ========================================

USE myjiajiao;

-- 支付记录表
CREATE TABLE IF NOT EXISTS `fa_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '支付ID',
  `order_no` varchar(50) NOT NULL COMMENT '支付订单号',
  `tutor_order_id` int(11) DEFAULT NULL COMMENT '家教订单ID',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `payment_method` varchar(20) NOT NULL COMMENT '支付方式：wechat-微信，alipay-支付宝',
  `payer_name` varchar(50) NOT NULL COMMENT '支付人姓名',
  `payer_contact` varchar(100) NOT NULL COMMENT '支付人联系方式',
  `status` varchar(20) DEFAULT 'pending' COMMENT '支付状态：pending-待支付，success-成功，failed-失败',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT '第三方交易号',
  `paid_time` datetime DEFAULT NULL COMMENT '支付完成时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `tutor_order_id` (`tutor_order_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付记录表';

-- 支付配置表（微信、支付宝配置）
CREATE TABLE IF NOT EXISTS `fa_payment_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `payment_method` varchar(20) NOT NULL COMMENT '支付方式：wechat-微信，alipay-支付宝',
  `app_id` varchar(100) DEFAULT NULL COMMENT '应用ID',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `api_key` varchar(255) DEFAULT NULL COMMENT 'API密钥',
  `cert_path` varchar(255) DEFAULT NULL COMMENT '证书路径',
  `notify_url` varchar(255) DEFAULT NULL COMMENT '回调地址',
  `is_enabled` tinyint(1) DEFAULT 0 COMMENT '是否启用：0-禁用，1-启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_method` (`payment_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付配置表';

-- 插入默认配置
INSERT INTO `fa_payment_config` (`payment_method`, `is_enabled`) VALUES
('wechat', 0),
('alipay', 0)
ON DUPLICATE KEY UPDATE payment_method=payment_method;

-- 服务协议表
CREATE TABLE IF NOT EXISTS `fa_service_agreement` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '协议ID',
  `title` varchar(100) NOT NULL COMMENT '协议标题',
  `content` text NOT NULL COMMENT '协议内容',
  `version` varchar(20) DEFAULT '1.0' COMMENT '版本号',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用：0-否，1-是',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务协议表';

-- 插入默认服务协议
INSERT INTO `fa_service_agreement` (`title`, `content`, `version`) VALUES
('家教服务支付协议', 
'<div style="line-height: 1.8;">
<h3>一、服务说明</h3>
<p>1. 本平台为家长与教师提供信息发布服务。</p>
<p>2. 支付费用为信息服务费，不包含实际家教授课费用。</p>
<p>3. 支付成功后，系统将提供家长联系方式。</p>

<h3>二、支付说明</h3>
<p>1. 支付前请仔细核对订单信息。</p>
<p>2. 支付金额一经支付，不予退还。</p>
<p>3. 支持微信支付、支付宝支付。</p>

<h3>三、服务承诺</h3>
<p>1. 保证提供的联系方式真实有效。</p>
<p>2. 如信息错误，可联系客服处理。</p>
<p>3. 保护用户隐私，不泄露个人信息。</p>

<h3>四、免责声明</h3>
<p>1. 本平台仅提供信息服务，不参与实际授课。</p>
<p>2. 授课质量、费用结算等由家长与教师自行协商。</p>
<p>3. 因授课产生的纠纷，由双方自行解决。</p>

<h3>五、其他</h3>
<p>1. 本协议最终解释权归本平台所有。</p>
<p>2. 如有疑问，请联系客服。</p>
</div>', 
'1.0')
ON DUPLICATE KEY UPDATE id=id;


