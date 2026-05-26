# Host: localhost:3309  (Version: 5.7.26)
# Date: 2026-05-20 19:22:41
# Generator: MySQL-Front 5.3  (Build 4.234)

/*!40101 SET NAMES utf8 */;

#
# Structure for table "ai_config"
#

DROP TABLE IF EXISTS `ai_config`;
CREATE TABLE `ai_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `base_url` varchar(255) NOT NULL DEFAULT 'https://kuaipao.ai' COMMENT '中转服务地址',
  `api_key` varchar(512) NOT NULL DEFAULT '' COMMENT 'API Key（明文存储，注意数据库访问权限）',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='AI 接口配置';

#
# Structure for table "email_logs"
#

DROP TABLE IF EXISTS `email_logs`;
CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `email_type` varchar(50) DEFAULT NULL COMMENT '邮件类型：lead_assign-线索指派, booking-预约通知, order-订单通知, test-测试邮件',
  `recipient_email` varchar(255) NOT NULL COMMENT '收件人邮箱',
  `recipient_name` varchar(100) DEFAULT NULL COMMENT '收件人姓名',
  `subject` varchar(255) NOT NULL COMMENT '邮件主题',
  `body` text COMMENT '邮件内容',
  `related_id` int(11) DEFAULT NULL COMMENT '关联ID（线索ID、订单ID等）',
  `status` tinyint(1) DEFAULT '0' COMMENT '发送状态：0-失败, 1-成功, 2-待发送',
  `error_msg` text COMMENT '错误信息',
  `send_time` datetime DEFAULT NULL COMMENT '发送时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`recipient_email`),
  KEY `idx_type` (`email_type`),
  KEY `idx_status` (`status`),
  KEY `idx_send_time` (`send_time`),
  KEY `idx_related_id` (`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮箱发送日志表';

#
# Structure for table "fa_admin"
#

DROP TABLE IF EXISTS `fa_admin`;
CREATE TABLE `fa_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(50) NOT NULL COMMENT '管理员昵称',
  `role` varchar(50) DEFAULT 'customer_service' COMMENT '角色：customer_service-客服组，dispatcher-派单组',
  `leader_id` int(11) DEFAULT NULL COMMENT '组长ID，关联admin表',
  `city_id` varchar(255) DEFAULT NULL COMMENT '归属城市ID，支持逗号分隔多个城市ID',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：0-禁用，1-启用',
  `can_access_enterprise` tinyint(1) DEFAULT '0' COMMENT '是否可访问企业管理：0-否，1-是',
  `contact` varchar(100) DEFAULT NULL COMMENT '联系方式（派单组使用）',
  `wechat_qrcode` varchar(500) DEFAULT NULL COMMENT '微信二维码URL，用于派单组成员',
  `booking_service_phone` varchar(20) DEFAULT NULL COMMENT '预约成功页联系手机',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱地址',
  `openid` varchar(100) DEFAULT NULL COMMENT '绑定的小程序用户openid',
  `bind_time` datetime DEFAULT NULL COMMENT '绑定时间',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `username` varchar(50) NOT NULL COMMENT '账号',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_openid` (`openid`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_leader_id` (`leader_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

#
# Structure for table "fa_advantage_tags"
#

DROP TABLE IF EXISTS `fa_advantage_tags`;
CREATE TABLE `fa_advantage_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) NOT NULL COMMENT '标签名称',
  `sort` int(11) DEFAULT '0' COMMENT '排序（数字越小越靠前）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COMMENT='教师优势标签表';

#
# Structure for table "fa_cities_backup"
#

DROP TABLE IF EXISTS `fa_cities_backup`;
CREATE TABLE `fa_cities_backup` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '城市ID',
  `name` varchar(50) NOT NULL COMMENT '城市名称',
  `level` varchar(20) DEFAULT NULL COMMENT '城市等级：一线城市/新一线城市/二线城市/三线城市',
  `sort` int(11) DEFAULT '0' COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COMMENT='城市表';

#
# Structure for table "fa_cities_backup_20251004"
#

DROP TABLE IF EXISTS `fa_cities_backup_20251004`;
CREATE TABLE `fa_cities_backup_20251004` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '城市ID',
  `province_id` int(11) DEFAULT NULL COMMENT '所属省份ID',
  `code` varchar(20) DEFAULT NULL COMMENT '城市行政区划代码',
  `name` varchar(50) NOT NULL COMMENT '城市名称',
  `level` varchar(20) DEFAULT NULL COMMENT '城市等级：一线城市/新一线城市/二线城市/三线城市',
  `sort` int(11) DEFAULT '0' COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_province_id` (`province_id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COMMENT='城市表';

#
# Structure for table "fa_city_light_users"
#

DROP TABLE IF EXISTS `fa_city_light_users`;
CREATE TABLE `fa_city_light_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_identifier` varchar(64) NOT NULL COMMENT '用户唯一标识',
  `user_contact` varchar(100) DEFAULT NULL COMMENT '用户联系方式',
  `total_lights` int(11) DEFAULT '0' COMMENT '总点亮城市数（包括自己点亮+助力）',
  `self_lights` int(11) DEFAULT '0' COMMENT '自己点亮的城市数',
  `assist_lights` int(11) DEFAULT '0' COMMENT '获得的助力数',
  `level` varchar(20) DEFAULT '新手' COMMENT '用户等级：新手/青铜/皇冠/荣耀',
  `level_score` int(11) DEFAULT '0' COMMENT '等级分数',
  `rank_position` int(11) DEFAULT '0' COMMENT '排行榜位置',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_identifier` (`user_identifier`),
  KEY `idx_level` (`level`),
  KEY `idx_level_score` (`level_score`),
  KEY `idx_rank_position` (`rank_position`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='城市点亮用户统计表';

#
# Structure for table "fa_city_lights"
#

DROP TABLE IF EXISTS `fa_city_lights`;
CREATE TABLE `fa_city_lights` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `province_id` int(11) NOT NULL COMMENT '省份ID',
  `city_name` varchar(50) NOT NULL COMMENT '城市名称',
  `city_code` varchar(20) DEFAULT NULL COMMENT '城市代码',
  `user_identifier` varchar(100) NOT NULL COMMENT '用户标识（IP或设备指纹）',
  `user_contact` varchar(100) DEFAULT NULL COMMENT '用户联系方式（可选）',
  `is_assist` tinyint(1) DEFAULT '0' COMMENT '是否为助力: 0-自己点亮, 1-助力点亮',
  `inviter_identifier` varchar(100) DEFAULT NULL COMMENT '邀请人标识（助力时记录）',
  `light_count` int(11) DEFAULT '1' COMMENT '点亮次数',
  `status` tinyint(1) DEFAULT '0' COMMENT '城市状态：0-未开通，1-已开通',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次点亮时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_city_user` (`province_id`,`city_name`,`user_identifier`),
  KEY `idx_province_id` (`province_id`),
  KEY `idx_city_name` (`city_name`),
  KEY `idx_city_code` (`city_code`),
  KEY `idx_user_identifier` (`user_identifier`),
  KEY `idx_status` (`status`),
  KEY `idx_is_assist` (`is_assist`),
  KEY `idx_inviter` (`inviter_identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COMMENT='城市点亮记录表';

#
# Structure for table "fa_coupon_redeem_log"
#

DROP TABLE IF EXISTS `fa_coupon_redeem_log`;
CREATE TABLE `fa_coupon_redeem_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `coupon_id` int(11) NOT NULL COMMENT '优惠券ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `openid` varchar(100) NOT NULL COMMENT '用户openid',
  `admin_id` int(11) NOT NULL COMMENT '操作管理员ID',
  `admin_name` varchar(100) DEFAULT NULL COMMENT '管理员名称',
  `coupon_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠券金额',
  `redeem_note` varchar(255) DEFAULT NULL COMMENT '兑换备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '兑换时间',
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='优惠券兑换记录表';

#
# Structure for table "fa_districts_backup_20251004"
#

DROP TABLE IF EXISTS `fa_districts_backup_20251004`;
CREATE TABLE `fa_districts_backup_20251004` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '区域ID',
  `city_id` int(11) NOT NULL COMMENT '所属城市ID',
  `code` varchar(20) DEFAULT NULL COMMENT '区域行政区划代码',
  `name` varchar(50) NOT NULL COMMENT '区域名称',
  `sort` int(11) DEFAULT '0' COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_city_id` (`city_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_city_name` (`city_id`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COMMENT='区域表';

#
# Structure for table "fa_email_queue"
#

DROP TABLE IF EXISTS `fa_email_queue`;
CREATE TABLE `fa_email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '队列ID',
  `email_type` varchar(50) NOT NULL COMMENT '邮件类型：lead_assign=线索指派',
  `recipient_email` varchar(255) NOT NULL COMMENT '收件人邮箱',
  `recipient_name` varchar(100) DEFAULT NULL COMMENT '收件人姓名',
  `subject` varchar(255) NOT NULL COMMENT '邮件主题',
  `body` text NOT NULL COMMENT '邮件内容(HTML)',
  `related_id` int(11) DEFAULT NULL COMMENT '关联ID（如线索ID）',
  `status` enum('pending','sending','sent','failed') DEFAULT 'pending' COMMENT '状态',
  `retry_count` int(11) DEFAULT '0' COMMENT '重试次数',
  `error_message` text COMMENT '错误信息',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `sent_at` datetime DEFAULT NULL COMMENT '发送时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_email_type` (`email_type`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COMMENT='邮件发送队列';

#
# Structure for table "fa_email_subscriptions"
#

DROP TABLE IF EXISTS `fa_email_subscriptions`;
CREATE TABLE `fa_email_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订阅ID',
  `email` varchar(100) NOT NULL COMMENT '邮箱地址',
  `city_ids` varchar(255) DEFAULT NULL COMMENT '订阅城市ID（逗号分隔，NULL表示全部）',
  `district_ids` varchar(255) DEFAULT NULL COMMENT '订阅区域ID（逗号分隔，NULL表示全部）',
  `subject_ids` varchar(255) DEFAULT NULL COMMENT '订阅科目ID（逗号分隔，NULL表示全部）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `verify_token` varchar(64) DEFAULT NULL COMMENT '邮箱验证令牌',
  `is_verified` tinyint(1) DEFAULT '0' COMMENT '是否已验证：1是 0否',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_is_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件订阅表';

#
# Structure for table "fa_enterprise_config"
#

DROP TABLE IF EXISTS `fa_enterprise_config`;
CREATE TABLE `fa_enterprise_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `corp_id` varchar(100) NOT NULL DEFAULT '' COMMENT '企业ID',
  `agent_id` varchar(50) NOT NULL DEFAULT '' COMMENT '应用AgentId',
  `agent_secret` varchar(255) NOT NULL DEFAULT '' COMMENT '应用Secret',
  `contacts_secret` varchar(255) NOT NULL DEFAULT '' COMMENT '通讯录Secret',
  `visible_users` text COMMENT '可见成员列表（JSON格式）',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用二维码回显',
  `userid_mapping` varchar(50) DEFAULT 'userid' COMMENT 'userid映射字段',
  `remark` text COMMENT '备注',
  `create_time` int(11) unsigned DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_corp_id` (`corp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业配置表（企业管理模块）';

#
# Structure for table "fa_expense_types"
#

DROP TABLE IF EXISTS `fa_expense_types`;
CREATE TABLE `fa_expense_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) NOT NULL COMMENT '费用类型名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=启用，0=禁用',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统内置：1=是，0=否',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='费用类型表';

#
# Structure for table "fa_invitation_ranking"
#

DROP TABLE IF EXISTS `fa_invitation_ranking`;
CREATE TABLE `fa_invitation_ranking` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '排行ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `openid` varchar(100) NOT NULL COMMENT '用户openid',
  `nickname` varchar(100) DEFAULT NULL COMMENT '用户昵称',
  `avatar_url` varchar(500) DEFAULT NULL COMMENT '用户头像',
  `total_invitations` int(11) DEFAULT '0' COMMENT '总邀请人数',
  `verified_invitations` int(11) DEFAULT '0' COMMENT '已认证邀请人数',
  `pending_invitations` int(11) DEFAULT '0' COMMENT '待认证邀请人数',
  `total_coupons_received` int(11) DEFAULT '0' COMMENT '已领取优惠券数量',
  `total_coupons_redeemed` int(11) DEFAULT '0' COMMENT '已兑换优惠券数量',
  `total_coupon_amount` decimal(10,2) DEFAULT '0.00' COMMENT '累计优惠券金额',
  `ranking_score` int(11) DEFAULT '0' COMMENT '排行分数（已认证人数）',
  `last_invite_time` datetime DEFAULT NULL COMMENT '最后邀请时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid` (`openid`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ranking_score` (`ranking_score`),
  KEY `idx_verified_invitations` (`verified_invitations`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请排行榜表';

#
# Structure for table "fa_lead_follow_logs"
#

DROP TABLE IF EXISTS `fa_lead_follow_logs`;
CREATE TABLE `fa_lead_follow_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `lead_id` int(11) NOT NULL COMMENT '线索ID',
  `old_status` varchar(20) DEFAULT NULL COMMENT '原状态',
  `new_status` varchar(20) DEFAULT NULL COMMENT '新状态',
  `remark` text COMMENT '备注',
  `proof_images` text COMMENT '不需要凭证截图数组(JSON)',
  `invalid_images` text COMMENT '无效截图数组(JSON)',
  `proof_image` varchar(255) DEFAULT NULL COMMENT '不需要凭证截图',
  `invalid_image` varchar(255) DEFAULT NULL COMMENT '无效截图',
  `operator_admin_id` int(11) DEFAULT NULL COMMENT '操作人管理员ID',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COMMENT='线索跟进记录表';

#
# Structure for table "fa_leads"
#

DROP TABLE IF EXISTS `fa_leads`;
CREATE TABLE `fa_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '线索ID',
  `lead_no` varchar(50) NOT NULL COMMENT '线索编号',
  `raw_content` text NOT NULL COMMENT '原始录入文本',
  `city_id` int(11) DEFAULT NULL COMMENT '所属城市ID',
  `district_id` int(11) DEFAULT NULL COMMENT '所属区域ID',
  `grade` varchar(50) DEFAULT NULL COMMENT '年级',
  `subject` varchar(50) DEFAULT NULL COMMENT '科目',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `contact_name` varchar(50) DEFAULT NULL COMMENT '联系人姓名',
  `assigned_admin_id` int(11) DEFAULT NULL COMMENT '指派客服ID',
  `status` enum('待联系','跟进中','已发单','已出单','不需要','无效') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '待联系' COMMENT '跟进状态',
  `tutor_content` text COMMENT '发单内容（状态为已发单时必填）',
  `tutor_title` varchar(500) DEFAULT NULL COMMENT '家教标题',
  `invalid_image` varchar(255) DEFAULT NULL COMMENT '无效图片路径（状态为无效时必填）',
  `info_fee` decimal(10,2) DEFAULT NULL COMMENT '信息费金额（状态为已出单时填写）',
  `reminder_time` datetime DEFAULT NULL COMMENT '提醒时间',
  `reminder_sent` tinyint(1) DEFAULT '0' COMMENT '提醒是否已发送：0-未发送，1-已发送',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `creator_admin_id` int(11) DEFAULT NULL COMMENT '创建人管理员ID',
  `channel` enum('美团','58同城','表单','渠道生源','其他') DEFAULT '其他' COMMENT '线索渠道',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lead_no` (`lead_no`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_admin` (`assigned_admin_id`),
  KEY `idx_city_id` (`city_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_channel` (`channel`),
  KEY `idx_info_fee` (`info_fee`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='线索主表';

#
# Structure for table "fa_mini_feedback_messages"
#

DROP TABLE IF EXISTS `fa_mini_feedback_messages`;
CREATE TABLE `fa_mini_feedback_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `feedback_id` bigint(20) unsigned NOT NULL COMMENT '关联 fa_mini_feedbacks.id',
  `sender` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user' COMMENT '发送方：user=用户, admin=管理员',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '消息内容',
  `images` text COLLATE utf8mb4_unicode_ci COMMENT '图片URL列表（JSON数组，用户消息可带图）',
  `create_time` datetime NOT NULL COMMENT '发送时间',
  PRIMARY KEY (`id`),
  KEY `idx_feedback_id` (`feedback_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='反馈对话消息表';

#
# Structure for table "fa_mini_feedbacks"
#

DROP TABLE IF EXISTS `fa_mini_feedbacks`;
CREATE TABLE `fa_mini_feedbacks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wechat' COMMENT '平台：wechat=微信小程序, alipay=支付宝小程序',
  `user_role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户角色：teacher=老师, parent=家长',
  `openid` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户openid',
  `phone` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户手机号',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '反馈内容',
  `images` text COLLATE utf8mb4_unicode_ci COMMENT '图片URL列表（JSON数组）',
  `reply_content` text COLLATE utf8mb4_unicode_ci COMMENT '回复内容',
  `reply_time` datetime DEFAULT NULL COMMENT '回复时间',
  `subscribe_notify_sent` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '首次管理员回复时订阅通知是否已成功发送：0否1是',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT '状态：pending=待处理, replied=已回复',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_role` (`user_role`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序问题反馈表';

#
# Structure for table "fa_mini_program_config"
#

DROP TABLE IF EXISTS `fa_mini_program_config`;
CREATE TABLE `fa_mini_program_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(20) NOT NULL COMMENT '平台: wechat/alipay',
  `app_id` varchar(128) NOT NULL DEFAULT '' COMMENT '小程序AppID',
  `mini_program_name` varchar(100) NOT NULL DEFAULT '' COMMENT '小程序名称',
  `app_secret_enc` text COMMENT '加密后的AppSecret',
  `phone_aes_key_enc` text COMMENT '加密后的手机号AES密钥(支付宝)',
  `env_version` varchar(20) NOT NULL DEFAULT 'release' COMMENT '环境: develop/trial/release',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '启用状态:1启用0禁用',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认配置:1是0否',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_platform_appid` (`platform`,`app_id`),
  KEY `idx_platform_enabled` (`platform`,`is_enabled`),
  KEY `idx_platform_default` (`platform`,`is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='小程序多端配置';

#
# Structure for table "fa_mini_subscribe_templates"
#

DROP TABLE IF EXISTS `fa_mini_subscribe_templates`;
CREATE TABLE `fa_mini_subscribe_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_code` varchar(64) NOT NULL COMMENT '业务代码：tutor_recommend、resume_review 等',
  `template_name` varchar(128) NOT NULL DEFAULT '' COMMENT '展示名称',
  `template_id` varchar(128) NOT NULL COMMENT '微信小程序订阅消息模板ID',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_code` (`template_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COMMENT='小程序订阅消息模板配置';

#
# Structure for table "fa_notification_config"
#

DROP TABLE IF EXISTS `fa_notification_config`;
CREATE TABLE `fa_notification_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `smtp_host` varchar(100) DEFAULT NULL COMMENT 'SMTP服务器地址',
  `smtp_port` int(11) DEFAULT '465' COMMENT 'SMTP端口',
  `smtp_username` varchar(100) DEFAULT NULL COMMENT 'SMTP用户名',
  `smtp_password` varchar(255) DEFAULT NULL COMMENT 'SMTP密码',
  `smtp_secure` varchar(10) DEFAULT 'ssl' COMMENT '加密方式：ssl/tls',
  `from_email` varchar(100) DEFAULT NULL COMMENT '发件人邮箱',
  `from_name` varchar(100) DEFAULT NULL COMMENT '发件人名称',
  `email_template` text COMMENT '邮件模板',
  `email_enabled` tinyint(1) DEFAULT '1' COMMENT '邮件通知是否启用：0-否，1-是',
  `wechat_enabled` tinyint(1) DEFAULT '0' COMMENT '微信服务号通知是否启用：0-否，1-是',
  `wechat_app_id` varchar(100) DEFAULT NULL COMMENT '微信服务号AppID',
  `wechat_app_secret` varchar(255) DEFAULT NULL COMMENT '微信服务号AppSecret',
  `wechat_token` varchar(255) DEFAULT NULL COMMENT '微信服务号Token（服务器配置）',
  `wechat_encoding_aes_key` varchar(255) DEFAULT NULL COMMENT '微信服务号消息加解密密钥',
  `wechat_access_token` text COMMENT '微信AccessToken缓存',
  `wechat_access_token_expire` int(11) DEFAULT '0' COMMENT 'AccessToken过期时间戳',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `wechat_share_title` varchar(200) DEFAULT '优质家教信息平台' COMMENT '微信分享标题',
  `wechat_share_desc` varchar(500) DEFAULT '专业的家教信息平台，为您提供优质的家教服务' COMMENT '微信分享描述',
  `wechat_share_image` varchar(500) DEFAULT '' COMMENT '微信分享图片URL',
  `wechat_share_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用微信分享',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='通知配置表（支持邮件和微信服务号）';

#
# Structure for table "fa_notification_logs"
#

DROP TABLE IF EXISTS `fa_notification_logs`;
CREATE TABLE `fa_notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `channel` varchar(20) DEFAULT 'email' COMMENT '通知渠道：email-邮件，wechat-微信',
  `receiver` varchar(255) DEFAULT NULL COMMENT '接收者标识（邮箱或OpenID）',
  `email` varchar(255) DEFAULT NULL COMMENT '邮箱地址（兼容旧数据）',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `template_code` varchar(50) DEFAULT NULL COMMENT '模板代码',
  `send_data` text COMMENT '发送数据（JSON）',
  `subject` varchar(255) DEFAULT NULL COMMENT '邮件主题',
  `send_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '发送状态：1成功 0失败',
  `error_msg` text COMMENT '错误信息',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_send_time` (`send_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知发送日志表（支持多渠道）';

#
# Structure for table "fa_notification_subscriptions"
#

DROP TABLE IF EXISTS `fa_notification_subscriptions`;
CREATE TABLE `fa_notification_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订阅ID',
  `channel` varchar(20) DEFAULT 'email' COMMENT '订阅渠道：email-邮件，wechat-微信',
  `email` varchar(255) DEFAULT NULL COMMENT '邮箱地址（邮件渠道使用）',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信OpenID（微信渠道使用）',
  `city_ids` varchar(255) DEFAULT NULL COMMENT '订阅城市ID（逗号分隔，NULL表示全部）',
  `district_ids` varchar(255) DEFAULT NULL COMMENT '订阅区域ID（逗号分隔，NULL表示全部）',
  `subject_ids` varchar(255) DEFAULT NULL COMMENT '订阅科目ID（逗号分隔，NULL表示全部）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `verify_token` varchar(64) DEFAULT NULL COMMENT '邮箱验证令牌',
  `is_verified` tinyint(1) DEFAULT '0' COMMENT '是否已验证：1是 0否',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_is_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知订阅表（支持多渠道）';

#
# Structure for table "fa_parent_orders"
#

DROP TABLE IF EXISTS `fa_parent_orders`;
CREATE TABLE `fa_parent_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `order_no` varchar(50) NOT NULL COMMENT '订单号',
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `grade` varchar(50) NOT NULL COMMENT '学员年级',
  `student_gender` varchar(10) DEFAULT NULL COMMENT '学生性别：男/女',
  `student_name` varchar(50) DEFAULT NULL COMMENT '学生昵称',
  `subject` varchar(100) NOT NULL COMMENT '辅导科目',
  `student_info` text NOT NULL COMMENT '学生情况',
  `frequency` varchar(200) NOT NULL COMMENT '辅导次数和频率',
  `duration` varchar(50) DEFAULT NULL COMMENT '上课时长：1小时/次、1.5小时/次等',
  `budget_min` int(11) DEFAULT NULL COMMENT '最低时薪（元/小时）',
  `budget_max` int(11) DEFAULT NULL COMMENT '最高时薪（元/小时）',
  `salary` varchar(100) DEFAULT NULL COMMENT '时薪范围',
  `teacher_requirement` text NOT NULL COMMENT '对老师要求',
  `teacher_type` varchar(50) DEFAULT NULL COMMENT '教师类型：大学生/在职教师/专业教师等',
  `teacher_gender` varchar(10) DEFAULT NULL COMMENT '教师性别要求：男/女/不限',
  `teacher_id` int(11) DEFAULT NULL COMMENT '预约的教师ID',
  `teaching_method` varchar(50) DEFAULT NULL COMMENT '授课方式：上门授课/线上授课',
  `address` varchar(500) NOT NULL COMMENT '授课地址',
  `province_id` int(11) DEFAULT NULL COMMENT '省份ID',
  `city_id` int(11) DEFAULT NULL COMMENT '城市ID',
  `district_id` int(11) DEFAULT NULL COMMENT '区县ID',
  `parent_name` varchar(50) NOT NULL COMMENT '家长称呼',
  `parent_contact` varchar(100) NOT NULL COMMENT '联系方式',
  `remark` text COMMENT '备注信息',
  `booking_channel` varchar(20) DEFAULT 'H5' COMMENT '预约渠道：H5/小程序',
  `user_id` int(11) DEFAULT NULL COMMENT '小程序用户ID',
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending' COMMENT '订单状态：pending-待审核，approved-已通过，rejected-已拒绝，cancelled-已取消',
  `reject_reason` text COMMENT '拒绝原因',
  `tutor_id` int(11) DEFAULT NULL COMMENT '关联的家教信息ID（审核通过后）',
  `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `available_time_slots` text COMMENT '可辅导时间段JSON，数组结构',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `admin_id` (`admin_id`),
  KEY `status` (`status`),
  KEY `create_time` (`create_time`),
  KEY `idx_booking_channel` (`booking_channel`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_teacher_id` (`teacher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COMMENT='家长预约订单表';

#
# Structure for table "fa_payment_config"
#

DROP TABLE IF EXISTS `fa_payment_config`;
CREATE TABLE `fa_payment_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `payment_method` varchar(20) NOT NULL COMMENT '支付方式：wechat-微信，alipay-支付宝',
  `scene` varchar(32) NOT NULL DEFAULT 'default' COMMENT '配置场景(default/h5/...)',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '配置名称',
  `app_id` varchar(100) DEFAULT NULL COMMENT '应用ID',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `api_key` varchar(255) DEFAULT NULL COMMENT 'API密钥',
  `app_secret` varchar(100) DEFAULT NULL COMMENT '应用密钥',
  `cert_path` varchar(255) DEFAULT NULL COMMENT '证书路径',
  `key_path` varchar(255) DEFAULT NULL COMMENT '密钥路径',
  `notify_url` varchar(255) DEFAULT NULL COMMENT '回调地址',
  `refund_follow_qrcode` varchar(255) NOT NULL DEFAULT '' COMMENT '退费页关注二维码',
  `is_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用：0-禁用，1-启用',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认(同method+scene唯一)',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_method_scene_name` (`payment_method`,`scene`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='支付配置表';

#
# Structure for table "fa_payment_method_config"
#

DROP TABLE IF EXISTS `fa_payment_method_config`;
CREATE TABLE `fa_payment_method_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(200) NOT NULL COMMENT '支付方式名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序（数字越小越靠前）',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间（软删除）',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='企业配置表';

#
# Structure for table "fa_payments"
#

DROP TABLE IF EXISTS `fa_payments`;
CREATE TABLE `fa_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '支付ID',
  `order_no` varchar(50) NOT NULL COMMENT '支付订单号',
  `tutor_order_id` int(11) DEFAULT NULL COMMENT '家教订单ID',
  `tutor_name` varchar(50) DEFAULT NULL COMMENT '家教名称（冗余字段，方便查询）',
  `teacher_name` varchar(50) DEFAULT NULL COMMENT '老师姓名（冗余字段，方便查询）',
  `contact_student` varchar(50) DEFAULT NULL COMMENT '对接的同学',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `deposit_amount` decimal(10,2) DEFAULT '0.00' COMMENT '收到押金',
  `refund_apply_amount` decimal(10,2) DEFAULT '0.00' COMMENT '申请应退金额',
  `refunded_amount` decimal(10,2) DEFAULT '0.00' COMMENT '已退金额',
  `actual_amount` decimal(10,2) DEFAULT '0.00' COMMENT '实收金额（支付金额-已退金额）',
  `payment_method` varchar(20) NOT NULL COMMENT '支付方式：wechat-微信，alipay-支付宝',
  `wechat_payment_config_id` int(11) DEFAULT NULL COMMENT '微信支付配置ID',
  `payer_name` varchar(50) NOT NULL COMMENT '支付人姓名',
  `payer_contact` varchar(100) NOT NULL COMMENT '支付人联系方式',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信支付用户openid（JSAPI等）',
  `status` varchar(20) DEFAULT 'pending' COMMENT '支付状态：pending-待支付，paid-已支付，cancelled-已取消',
  `dispatcher_id` int(11) DEFAULT NULL COMMENT '派单员ID（关联admin表）',
  `refund_status` varchar(20) DEFAULT NULL COMMENT '退款状态：pending-退款待处理，processing-处理中，rejected-退款驳回，completed-已退费',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT '第三方交易号',
  `paid_time` datetime DEFAULT NULL COMMENT '支付完成时间',
  `refund_apply_time` datetime DEFAULT NULL COMMENT '申请退款时间',
  `refund_time` datetime DEFAULT NULL COMMENT '退款完成时间',
  `customer_service` varchar(50) DEFAULT NULL COMMENT '客服人员',
  `refund_reason` text COMMENT '退款原因',
  `reject_reason` text COMMENT '驳回原因',
  `refund_voucher` text COMMENT '退款凭证（JSON格式存储多个文件）',
  `remark` text COMMENT '订单备注',
  `pay_remark` text COMMENT '支付备注（用户支付时填写）',
  `refund_remark` text COMMENT '退款审核备注',
  `is_pinned` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '管理端列表置顶',
  `pinned_at` datetime DEFAULT NULL COMMENT '置顶时间',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '软删除标记：1=已移除',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `tutor_order_id` (`tutor_order_id`),
  KEY `status` (`status`),
  KEY `idx_refund_status` (`refund_status`),
  KEY `idx_teacher_name` (`teacher_name`),
  KEY `idx_refund_apply_time` (`refund_apply_time`),
  KEY `idx_dispatcher_id` (`dispatcher_id`),
  KEY `idx_is_pinned` (`is_pinned`),
  KEY `idx_pinned_at` (`pinned_at`),
  KEY `idx_openid` (`openid`),
  KEY `idx_payer_contact` (`payer_contact`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COMMENT='支付记录表';

#
# Structure for table "fa_personnel"
#

DROP TABLE IF EXISTS `fa_personnel`;
CREATE TABLE `fa_personnel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机',
  `gender` varchar(10) DEFAULT '' COMMENT '性别（男/女）',
  `birth_date` date DEFAULT NULL COMMENT '出生日期',
  `native_place` varchar(100) DEFAULT '' COMMENT '籍贯（省/市）',
  `ethnicity` varchar(50) DEFAULT '' COMMENT '民族',
  `political_status` varchar(50) DEFAULT '' COMMENT '政治面貌',
  `id_card` varchar(30) NOT NULL DEFAULT '' COMMENT '身份证号码',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `current_address` varchar(255) DEFAULT '' COMMENT '现居住地址',
  `wechat_account` varchar(100) NOT NULL DEFAULT '' COMMENT '员工账号微信名称',
  `dept_name` varchar(100) DEFAULT '' COMMENT '所在部门',
  `position_name` varchar(100) NOT NULL DEFAULT '' COMMENT '岗位名称',
  `position_type` varchar(50) NOT NULL DEFAULT '' COMMENT '岗位类型（管理层/全职/兼职/实习生等）',
  `entry_date` date DEFAULT NULL COMMENT '入职日期',
  `employment_status` varchar(10) NOT NULL DEFAULT '在职' COMMENT '在职状态：在职/离职',
  `leave_date` date DEFAULT NULL COMMENT '离职日期',
  `leave_type` varchar(50) DEFAULT NULL COMMENT '离职类型（主动离职/被动离职/合同到期/其他）',
  `leave_reason` varchar(500) DEFAULT NULL COMMENT '离职原因',
  `leave_remark` varchar(500) DEFAULT NULL COMMENT '离职备注',
  `regularize_date` date DEFAULT NULL COMMENT '转正日期（实习/兼职转全职时填写）',
  `regularize_remark` varchar(500) DEFAULT NULL COMMENT '转正备注',
  `bank_name` varchar(100) NOT NULL DEFAULT '' COMMENT '开户行',
  `bank_card_no` varchar(50) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `photo_url` varchar(500) DEFAULT '' COMMENT '员工照片',
  `id_card_front` varchar(500) DEFAULT '' COMMENT '身份证人像面（必传）',
  `id_card_back` varchar(500) DEFAULT '' COMMENT '身份证国徽面（必传）',
  `degree_cert` varchar(500) DEFAULT '' COMMENT '学位证书',
  `graduation_cert` varchar(500) DEFAULT '' COMMENT '毕业证书',
  `resignation_cert` varchar(500) DEFAULT '' COMMENT '前公司离职证明',
  `health_report` varchar(500) DEFAULT '' COMMENT '体检报告',
  `xuexin_report` varchar(500) DEFAULT '' COMMENT '学信网电子验证报告（必传）',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '软删除标记（0=未删除，时间戳=已删除）',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_phone` (`phone`),
  KEY `idx_id_card` (`id_card`),
  KEY `idx_dept_name` (`dept_name`),
  KEY `idx_position` (`position_type`),
  KEY `idx_delete_time` (`delete_time`),
  KEY `idx_employment_status` (`employment_status`),
  KEY `idx_position_type` (`position_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='人员主表（入职登记）';

#
# Structure for table "fa_personnel_education"
#

DROP TABLE IF EXISTS `fa_personnel_education`;
CREATE TABLE `fa_personnel_education` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `personnel_id` int(10) unsigned NOT NULL COMMENT '关联 fa_personnel.id',
  `degree` varchar(50) DEFAULT '' COMMENT '学历（初中及以下/高中/中专/大专/本科/硕士/博士）',
  `school` varchar(200) DEFAULT '' COMMENT '毕业院校',
  `enroll_date` date DEFAULT NULL COMMENT '入学时间',
  `graduate_date` date DEFAULT NULL COMMENT '毕业时间',
  `major` varchar(100) DEFAULT '' COMMENT '专业',
  `academic_degree` varchar(50) DEFAULT '' COMMENT '学位（学士/硕士/博士/无）',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_personnel_id` (`personnel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='人员教育经历（一对多）';

#
# Structure for table "fa_personnel_emergency"
#

DROP TABLE IF EXISTS `fa_personnel_emergency`;
CREATE TABLE `fa_personnel_emergency` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `personnel_id` int(10) unsigned NOT NULL COMMENT '关联 fa_personnel.id',
  `name` varchar(50) DEFAULT '' COMMENT '紧急联系人姓名',
  `relation` varchar(50) DEFAULT '' COMMENT '紧急联系人关系（父亲/母亲/配偶/兄弟姐妹/朋友/其他）',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '紧急联系人手机',
  `address` varchar(255) DEFAULT '' COMMENT '紧急联系人住址',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_personnel_id` (`personnel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='人员紧急联系人（一对多）';

#
# Structure for table "fa_personnel_salary"
#

DROP TABLE IF EXISTS `fa_personnel_salary`;
CREATE TABLE `fa_personnel_salary` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `personnel_id` int(11) NOT NULL COMMENT '人员ID，关联fa_personnel表',
  `base_salary` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '基本工资',
  `performance_salary` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '绩效工资',
  `post_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '岗位津贴',
  `housing_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '住房补贴',
  `meal_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '餐补',
  `transport_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '交通补贴',
  `other_allowance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '其他补贴',
  `salary_month` varchar(7) DEFAULT NULL COMMENT '归属月份，格式 YYYY-MM',
  `provident_fund_company` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '公积金（单位）',
  `provident_fund_personal` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '公积金（个人）',
  `social_insurance_company` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '社保（单位）',
  `social_insurance_personal` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '社保（个人）',
  `total_salary` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总薪酬（自动计算）',
  `effective_date` date NOT NULL COMMENT '生效日期',
  `end_date` date DEFAULT NULL COMMENT '结束日期（NULL表示当前有效）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=有效，0=失效',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_personnel_id` (`personnel_id`),
  KEY `idx_effective_date` (`effective_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='人员薪酬表';

#
# Structure for table "fa_provinces"
#

DROP TABLE IF EXISTS `fa_provinces`;
CREATE TABLE `fa_provinces` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '省份ID',
  `code` varchar(20) DEFAULT NULL COMMENT '省份行政区划代码',
  `name` varchar(50) NOT NULL COMMENT '省份名称',
  `short_name` varchar(20) DEFAULT NULL COMMENT '简称',
  `sort` int(11) DEFAULT '0' COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COMMENT='省份表';

#
# Structure for table "fa_cities"
#

DROP TABLE IF EXISTS `fa_cities`;
CREATE TABLE `fa_cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '城市ID',
  `province_id` int(11) DEFAULT NULL COMMENT '所属省份ID',
  `code` varchar(20) DEFAULT NULL COMMENT '城市行政区划代码',
  `name` varchar(50) NOT NULL COMMENT '城市名称',
  `level` varchar(20) DEFAULT NULL COMMENT '城市等级：一线城市/新一线城市/二线城市/三线城市',
  `is_hot` tinyint(1) DEFAULT '0' COMMENT '是否热门城市：1是 0否',
  `sort` int(11) DEFAULT '0' COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_province_id` (`province_id`),
  KEY `idx_is_hot` (`is_hot`),
  CONSTRAINT `fk_city_province` FOREIGN KEY (`province_id`) REFERENCES `fa_provinces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=339 DEFAULT CHARSET=utf8mb4 COMMENT='城市表';

#
# Structure for table "fa_districts"
#

DROP TABLE IF EXISTS `fa_districts`;
CREATE TABLE `fa_districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '区域ID',
  `city_id` int(11) NOT NULL COMMENT '所属城市ID',
  `code` varchar(20) DEFAULT NULL COMMENT '区域行政区划代码',
  `name` varchar(50) NOT NULL COMMENT '区域名称',
  `sort` int(11) DEFAULT '0' COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_city_id` (`city_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_city_name` (`city_id`,`name`),
  CONSTRAINT `fk_district_city` FOREIGN KEY (`city_id`) REFERENCES `fa_cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2976 DEFAULT CHARSET=utf8mb4 COMMENT='区域表';

#
# Structure for table "fa_receipt_method_config"
#

DROP TABLE IF EXISTS `fa_receipt_method_config`;
CREATE TABLE `fa_receipt_method_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(200) NOT NULL COMMENT '收款单位名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序（数字越小越靠前）',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间（软删除）',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='收款单位配置表';

#
# Structure for table "fa_resume_application"
#

DROP TABLE IF EXISTS `fa_resume_application`;
CREATE TABLE `fa_resume_application` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '投递记录ID',
  `teacher_id` int(11) NOT NULL COMMENT '教师ID',
  `tutor_id` int(11) NOT NULL COMMENT '家教订单ID',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '状态：pending-待审核，approved-已通过，rejected-已拒绝',
  `apply_time` datetime NOT NULL COMMENT '投递时间',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID（管理员ID）',
  `admin_remark` text COMMENT '管理员备注',
  PRIMARY KEY (`id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_tutor_id` (`tutor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_apply_time` (`apply_time`),
  KEY `idx_reviewer_id` (`reviewer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COMMENT='简历投递记录表';

#
# Structure for table "fa_salary"
#

DROP TABLE IF EXISTS `fa_salary`;
CREATE TABLE `fa_salary` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `expense_date` date NOT NULL COMMENT '支出日期',
  `expense_type` varchar(50) NOT NULL DEFAULT '' COMMENT '费用类型',
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '数量',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价金额',
  `project_name` varchar(200) NOT NULL DEFAULT '' COMMENT '项目名称',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `invoice_attachment` varchar(500) DEFAULT '' COMMENT '发票附件',
  `payment_attachment` varchar(500) DEFAULT '' COMMENT '付款附件',
  `payment_status` varchar(20) NOT NULL DEFAULT '未付款' COMMENT '付款状态：未付款、已付款、部分付款',
  `invoice_status` varchar(20) NOT NULL DEFAULT '未开票' COMMENT '发票状态：未开票、已开票、已收票',
  `receipt_method` varchar(50) DEFAULT '' COMMENT '收款方式',
  `payment_method` varchar(50) DEFAULT '' COMMENT '支付方式',
  `period` varchar(20) DEFAULT '' COMMENT '所属周期',
  `remark` text COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT '0' COMMENT '删除时间（软删除）',
  PRIMARY KEY (`id`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_expense_type` (`expense_type`),
  KEY `idx_project_name` (`project_name`),
  KEY `idx_period` (`period`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_invoice_status` (`invoice_status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COMMENT='费用支出管理表';

#
# Structure for table "fa_seo_config"
#

DROP TABLE IF EXISTS `fa_seo_config`;
CREATE TABLE `fa_seo_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `page_type` varchar(50) NOT NULL COMMENT '页面类型：home,teachers,teacher-detail,search等',
  `page_title` varchar(200) DEFAULT NULL COMMENT '页面标题',
  `page_keywords` varchar(500) DEFAULT NULL COMMENT '页面关键词',
  `page_description` varchar(1000) DEFAULT NULL COMMENT '页面描述',
  `og_title` varchar(200) DEFAULT NULL COMMENT 'Open Graph标题',
  `og_description` varchar(1000) DEFAULT NULL COMMENT 'Open Graph描述',
  `og_image` varchar(500) DEFAULT NULL COMMENT 'Open Graph图片',
  `canonical_url` varchar(500) DEFAULT NULL COMMENT '规范URL',
  `robots` varchar(100) DEFAULT 'index,follow' COMMENT 'robots指令',
  `sitemap_priority` decimal(2,1) DEFAULT '0.8' COMMENT '站点地图优先级(0.1-1.0)',
  `sitemap_changefreq` varchar(20) DEFAULT 'weekly' COMMENT '站点地图更新频率',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用：1启用 0禁用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_type` (`page_type`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='SEO配置表';

#
# Structure for table "fa_service_agreement"
#

DROP TABLE IF EXISTS `fa_service_agreement`;
CREATE TABLE `fa_service_agreement` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '协议ID',
  `title` varchar(100) NOT NULL COMMENT '协议标题',
  `content` text NOT NULL COMMENT '协议内容',
  `version` varchar(20) DEFAULT '1.0' COMMENT '版本号',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用：0-否，1-是',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='服务协议表';

#
# Structure for table "fa_site_banners"
#

DROP TABLE IF EXISTS `fa_site_banners`;
CREATE TABLE `fa_site_banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `banner_scene` varchar(64) NOT NULL DEFAULT 'default' COMMENT '展示场景：default=网站通用轮播，parent_mini_home=小程序家长端首页',
  `title` varchar(200) DEFAULT '' COMMENT '横幅标题',
  `description` text COMMENT '横幅描述',
  `image_url` varchar(500) NOT NULL COMMENT '横幅图片URL',
  `link_url` varchar(500) DEFAULT '' COMMENT '点击跳转链接',
  `target` varchar(20) DEFAULT '_self' COMMENT '链接打开方式：_self=当前窗口, _blank=新窗口',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序值（越小越靠前）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1=启用，0=禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_sort_status` (`sort_order`,`status`),
  KEY `idx_banner_scene_status` (`banner_scene`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='网站横幅表';

#
# Structure for table "fa_site_config"
#

DROP TABLE IF EXISTS `fa_site_config`;
CREATE TABLE `fa_site_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `platform_name` varchar(100) DEFAULT '家教平台' COMMENT '平台名称',
  `platform_slogan` varchar(200) DEFAULT '' COMMENT '平台标语/口号',
  `icp_number` varchar(100) DEFAULT '' COMMENT 'ICP备案号',
  `police_number` varchar(100) DEFAULT '' COMMENT '公安备案号',
  `police_link` varchar(255) DEFAULT '' COMMENT '公安备案链接',
  `copyright_info` varchar(200) DEFAULT '' COMMENT '版权信息',
  `company_name` varchar(200) DEFAULT '' COMMENT '公司名称',
  `contact_phone` varchar(50) DEFAULT '' COMMENT '联系电话',
  `contact_email` varchar(100) DEFAULT '' COMMENT '联系邮箱',
  `contact_address` varchar(255) DEFAULT '' COMMENT '联系地址',
  `logo_url` varchar(255) DEFAULT '' COMMENT 'Logo URL',
  `favicon_url` varchar(255) DEFAULT '' COMMENT 'Favicon URL',
  `banner_image` varchar(500) DEFAULT '' COMMENT '横幅图片URL',
  `banner_link` varchar(500) DEFAULT '' COMMENT '横幅链接',
  `banner_title` varchar(200) DEFAULT '' COMMENT '横幅标题',
  `banner_description` text COMMENT '横幅描述',
  `meta_keywords` varchar(500) DEFAULT '' COMMENT 'SEO关键词',
  `meta_description` text COMMENT 'SEO描述',
  `statistics_code` text COMMENT '统计代码（百度统计/Google Analytics等）',
  `custom_header_code` text COMMENT '自定义头部代码',
  `custom_footer_code` text COMMENT '自定义底部代码',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1=启用，0=禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='网站基础配置表';

#
# Structure for table "fa_sitemap_config"
#

DROP TABLE IF EXISTS `fa_sitemap_config`;
CREATE TABLE `fa_sitemap_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `url_path` varchar(200) NOT NULL COMMENT 'URL路径',
  `page_title` varchar(200) DEFAULT NULL COMMENT '页面标题',
  `lastmod` datetime DEFAULT NULL COMMENT '最后修改时间',
  `changefreq` varchar(20) DEFAULT 'weekly' COMMENT '更新频率',
  `priority` decimal(2,1) DEFAULT '0.8' COMMENT '优先级',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_url_path` (`url_path`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='站点地图配置表';

#
# Structure for table "fa_ssl_config"
#

DROP TABLE IF EXISTS `fa_ssl_config`;
CREATE TABLE `fa_ssl_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '域名',
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系邮箱',
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'letsencrypt' COMMENT '证书提供商：letsencrypt, aliyun, tencent',
  `auto_renew` tinyint(1) DEFAULT '1' COMMENT '是否自动续约：0-否，1-是',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用：0-否，1-是',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '状态：pending-待申请，active-有效，expired-过期，failed-失败',
  `cert_issue_time` datetime DEFAULT NULL COMMENT '证书签发时间',
  `cert_expire_time` datetime DEFAULT NULL COMMENT '证书过期时间',
  `last_apply_time` datetime DEFAULT NULL COMMENT '最后申请时间',
  `last_renew_time` datetime DEFAULT NULL COMMENT '最后续约时间',
  `last_check_time` datetime DEFAULT NULL COMMENT '最后检查时间',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `config_data` text COLLATE utf8mb4_unicode_ci COMMENT '配置数据（JSON格式）',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_domain` (`domain`),
  KEY `idx_status` (`status`),
  KEY `idx_auto_renew` (`auto_renew`),
  KEY `idx_expire_time` (`cert_expire_time`),
  KEY `idx_ssl_config_composite` (`is_enabled`,`auto_renew`,`status`,`cert_expire_time`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSL证书配置表';

#
# Structure for table "fa_ssl_renew_log"
#

DROP TABLE IF EXISTS `fa_ssl_renew_log`;
CREATE TABLE `fa_ssl_renew_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `ssl_config_id` int(11) NOT NULL COMMENT 'SSL配置ID',
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '域名',
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作类型：apply-申请，renew-续约，check-检查',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作状态：success-成功，failed-失败',
  `message` text COLLATE utf8mb4_unicode_ci COMMENT '操作结果信息',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `cert_expire_time` datetime DEFAULT NULL COMMENT '证书过期时间',
  `execute_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '执行时间',
  PRIMARY KEY (`id`),
  KEY `idx_ssl_config_id` (`ssl_config_id`),
  KEY `idx_domain` (`domain`),
  KEY `idx_action` (`action`),
  KEY `idx_status` (`status`),
  KEY `idx_execute_time` (`execute_time`),
  KEY `idx_ssl_renew_log_composite` (`ssl_config_id`,`action`,`status`,`execute_time`),
  CONSTRAINT `fk_ssl_renew_log_config` FOREIGN KEY (`ssl_config_id`) REFERENCES `fa_ssl_config` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSL证书续约日志表';

#
# Structure for table "fa_subjects"
#

DROP TABLE IF EXISTS `fa_subjects`;
CREATE TABLE `fa_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '科目ID',
  `parent_id` int(11) DEFAULT '0' COMMENT '父级科目ID，0表示一级科目',
  `name` varchar(50) NOT NULL COMMENT '科目名称',
  `category` varchar(20) DEFAULT NULL COMMENT '科目分类：文科/理科/艺体/其他',
  `sort` int(11) DEFAULT '0' COMMENT '排序值，数字越小越靠前',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 0禁用',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_parent_name` (`parent_id`,`name`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COMMENT='科目表';

#
# Structure for table "fa_success_cases"
#

DROP TABLE IF EXISTS `fa_success_cases`;
CREATE TABLE `fa_success_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `grade` varchar(64) NOT NULL DEFAULT '' COMMENT '年级标签',
  `subject` varchar(64) NOT NULL DEFAULT '' COMMENT '科目标签',
  `theme_tag` varchar(64) NOT NULL DEFAULT '' COMMENT '主题/成果标签（可选）',
  `cover_images` text NOT NULL COMMENT '顶部展示图 URL 列表 JSON 数组',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `course_intro` text NOT NULL COMMENT '课程情况介绍',
  `student_background` text NOT NULL COMMENT '学生背景',
  `tutoring_results` text NOT NULL COMMENT '辅导成果',
  `parent_comment` text NOT NULL COMMENT '家长评语',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序（越小越靠前）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_sort_status` (`sort_order`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='成功案例';

#
# Structure for table "fa_teacher_accounts"
#

DROP TABLE IF EXISTS `fa_teacher_accounts`;
CREATE TABLE `fa_teacher_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '账号ID',
  `teacher_id` int(11) DEFAULT NULL COMMENT '关联的教师ID',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `password` varchar(255) NOT NULL COMMENT '密码（加密）',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0=未验证，1=已验证，2=已禁用',
  `last_login_time` int(11) DEFAULT NULL COMMENT '最后登录时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `teacher_id` (`teacher_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='教师账号表';

#
# Structure for table "fa_teacher_registration_progress"
#

DROP TABLE IF EXISTS `fa_teacher_registration_progress`;
CREATE TABLE `fa_teacher_registration_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '进度ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信小程序openid',
  `union_id` varchar(100) DEFAULT NULL COMMENT '微信unionid',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `current_step` int(11) DEFAULT '1' COMMENT '当前步骤',
  `form_data` text COMMENT '表单数据（JSON格式）',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间（7天后）',
  PRIMARY KEY (`id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='教师注册进度表';

#
# Structure for table "fa_teacher_teaching_info"
#

DROP TABLE IF EXISTS `fa_teacher_teaching_info`;
CREATE TABLE `fa_teacher_teaching_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `teacher_id` int(11) DEFAULT NULL COMMENT '教师ID',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信openid',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `city_id` int(11) DEFAULT NULL COMMENT '授课城市ID',
  `city_name` varchar(50) DEFAULT NULL COMMENT '授课城市名称',
  `districts` text COMMENT '授课区域，JSON格式存储 [{"id":1,"name":"朝阳区"},...]',
  `grades` text COMMENT '授课年级，JSON格式存储 ["小学一年级","小学二年级",...]',
  `subjects` text COMMENT '授课科目，JSON格式存储 ["语文","数学",...]',
  `subscribe_push` tinyint(1) DEFAULT '0' COMMENT '是否订阅家教推送 0-否 1-是',
  `wechat_notify` tinyint(1) DEFAULT '0' COMMENT '是否开启服务号通知 0-否 1-是',
  `email_notify` tinyint(1) DEFAULT '0' COMMENT '是否开启邮箱通知 0-否 1-是',
  `email` varchar(100) DEFAULT NULL COMMENT '接收通知的邮箱',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_teacher_id` (`teacher_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_city_id` (`city_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='教师授课信息表';

#
# Structure for table "fa_teacher_verification_codes"
#

DROP TABLE IF EXISTS `fa_teacher_verification_codes`;
CREATE TABLE `fa_teacher_verification_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `teacher_id` int(11) DEFAULT NULL COMMENT '教师ID（登录时记录）',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱地址',
  `code` varchar(20) NOT NULL COMMENT '验证码',
  `type` varchar(20) DEFAULT 'email' COMMENT '验证码类型：email-邮箱验证，login-登录记录，reset-密码重置',
  `is_used` tinyint(1) DEFAULT '0' COMMENT '是否已使用：0-未使用，1-已使用',
  `expire_time` int(11) DEFAULT NULL COMMENT '过期时间（Unix时间戳）',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间（Unix时间戳）',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间（Unix时间戳）',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_code` (`code`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COMMENT='教师验证码表';

#
# Structure for table "fa_teachers"
#

DROP TABLE IF EXISTS `fa_teachers`;
CREATE TABLE `fa_teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '教师ID',
  `teacher_no` int(11) NOT NULL COMMENT '对外展示编号，从1000起，如 T1000',
  `account_id` int(11) DEFAULT NULL COMMENT '关联账号ID',
  `name` varchar(50) NOT NULL COMMENT '教师姓名',
  `gender` varchar(10) DEFAULT NULL COMMENT '性别：男/女',
  `phone` varchar(20) NOT NULL COMMENT '手机号（唯一）',
  `wechat_id` varchar(100) DEFAULT NULL COMMENT '微信号',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `hometown` varchar(100) DEFAULT NULL COMMENT '籍贯',
  `teaching_years` int(11) DEFAULT '0' COMMENT '教龄（年）',
  `birth_date` varchar(10) DEFAULT NULL COMMENT '出生年月(格式:YYYY-MM)',
  `birth_year` int(11) DEFAULT NULL COMMENT '出生年份',
  `location_province` varchar(50) DEFAULT NULL COMMENT '所在省份',
  `location_city` varchar(50) DEFAULT NULL COMMENT '所在城市',
  `location_district` varchar(50) DEFAULT NULL COMMENT '所在区县',
  `location_address` varchar(200) DEFAULT NULL COMMENT '详细地址',
  `location_longitude` decimal(10,7) DEFAULT NULL COMMENT '所在地经度',
  `location_latitude` decimal(10,7) DEFAULT NULL COMMENT '所在地纬度',
  `education` varchar(50) DEFAULT NULL COMMENT '学历',
  `school` varchar(100) DEFAULT NULL COMMENT '学校名称',
  `major` varchar(100) DEFAULT NULL COMMENT '专业',
  `teacher_type` varchar(50) DEFAULT NULL COMMENT '教师类型：undergraduate-在读本科，graduate_student-在读研究生，doctoral_student-在读博士，graduated-毕业生，professional-专职老师',
  `grade_level` varchar(50) DEFAULT NULL COMMENT '年级层次：准大一到大五、研一到研三、博一到博五',
  `education_level` varchar(50) DEFAULT NULL COMMENT '学历层次：associate-大专，bachelor-本科，master-研究生，doctorate-博士',
  `hourly_rate` decimal(10,2) DEFAULT NULL COMMENT '时薪（元/小时）',
  `subject_ids` varchar(255) DEFAULT NULL COMMENT '科目ID列表（逗号分隔）',
  `subject_names` varchar(255) DEFAULT NULL COMMENT '科目名称列表（逗号分隔）',
  `district_ids` varchar(255) DEFAULT NULL COMMENT '区域ID列表（逗号分隔）',
  `district_names` varchar(255) DEFAULT NULL COMMENT '区域名称列表（逗号分隔）',
  `experience` text COMMENT '教学经历（JSON格式）',
  `self_intro` text COMMENT '自我介绍',
  `personal_advantage` text COMMENT '个人优势描述',
  `advantage_tags` varchar(500) DEFAULT NULL COMMENT '个人优势标签（JSON数组）',
  `photos` text COMMENT '照片列表（JSON格式）',
  `status` varchar(20) DEFAULT 'active' COMMENT '账号状态：active-激活，inactive-未激活，disabled-禁用',
  `real_name_verified` tinyint(1) DEFAULT '0' COMMENT '实名认证：0-未认证，1-已认证',
  `education_verified` tinyint(1) DEFAULT '0' COMMENT '学历认证：0-未认证，1-已认证',
  `teacher_verified` tinyint(1) DEFAULT '0' COMMENT '教师认证：0-未认证，1-已认证',
  `id_card_front` varchar(255) DEFAULT NULL COMMENT '身份证正面照片URL',
  `id_card_back` varchar(255) DEFAULT NULL COMMENT '身份证反面照片URL',
  `education_certificate` varchar(255) DEFAULT NULL COMMENT '学历证书照片URL',
  `teacher_certificate` varchar(255) DEFAULT NULL COMMENT '教师资格证照片URL',
  `review_status` varchar(20) DEFAULT 'pending' COMMENT '审核状态：pending-待审核，approved-已审核，rejected-已拒绝',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `reviewer_id` int(11) DEFAULT NULL COMMENT '审核人ID',
  `is_top` tinyint(1) DEFAULT '0' COMMENT '是否置顶：0-否，1-是',
  `review_note` text COMMENT '审核备注',
  `source` varchar(20) NOT NULL DEFAULT 'h5' COMMENT '注册来源：h5-用户端，miniprogram-小程序',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `wechat_nickname` varchar(100) DEFAULT NULL COMMENT '微信昵称',
  `openid` varchar(100) DEFAULT NULL COMMENT '微信openid',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  UNIQUE KEY `uk_teacher_no` (`teacher_no`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_review_status` (`review_status`),
  KEY `idx_verified` (`real_name_verified`,`education_verified`,`teacher_verified`),
  KEY `idx_teacher_type` (`teacher_type`),
  KEY `idx_wechat_id` (`wechat_id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status_review` (`status`,`review_status`),
  KEY `idx_email` (`email`),
  KEY `idx_top_create` (`is_top`,`create_time`),
  KEY `idx_hometown` (`hometown`),
  KEY `idx_location_city` (`location_city`),
  KEY `idx_teaching_years` (`teaching_years`),
  KEY `idx_birth_year` (`birth_year`),
  KEY `idx_location_coordinates` (`location_longitude`,`location_latitude`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COMMENT='教师表';

#
# Structure for table "fa_tutor_orders"
#

DROP TABLE IF EXISTS `fa_tutor_orders`;
CREATE TABLE `fa_tutor_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL COMMENT '管理员ID',
  `content` text NOT NULL COMMENT '原始内容',
  `city` varchar(50) DEFAULT NULL COMMENT '城市',
  `district` varchar(50) DEFAULT NULL COMMENT '区域',
  `grade` varchar(20) DEFAULT NULL COMMENT '年级',
  `subject` varchar(20) DEFAULT NULL COMMENT '科目',
  `salary` varchar(50) DEFAULT NULL COMMENT '薪资',
  `is_urgent` tinyint(1) DEFAULT '0' COMMENT '是否加急',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `source` varchar(50) DEFAULT 'manual' COMMENT '来源：manual-手动录入，booking-预约单转化',
  `parent_order_id` int(11) DEFAULT NULL COMMENT '关联的预约单ID',
  `salary_range` varchar(100) DEFAULT NULL COMMENT '薪酬范围字符串（如：130-150元/小时）',
  PRIMARY KEY (`id`),
  KEY `idx_city` (`city`),
  KEY `idx_district` (`district`),
  KEY `idx_grade` (`grade`),
  KEY `idx_subject` (`subject`),
  KEY `idx_create_time` (`create_time`),
  KEY `fk_admin_id` (`admin_id`),
  CONSTRAINT `fk_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `fa_admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7794 DEFAULT CHARSET=utf8mb4 COMMENT='家教订单信息表';

#
# Structure for table "fa_tutor_orders_new"
#

DROP TABLE IF EXISTS `fa_tutor_orders_new`;
CREATE TABLE `fa_tutor_orders_new` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单ID（格式：YYMMDD + 3位序号）',
  `old_id` int(11) DEFAULT NULL COMMENT '旧表ID（用于数据迁移追溯）',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '家教信息内容',
  `city_id` int(11) DEFAULT NULL COMMENT '城市ID',
  `district_id` int(11) DEFAULT NULL COMMENT '区域ID',
  `grade` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '年级',
  `subject_id` int(11) DEFAULT NULL COMMENT '科目ID',
  `salary` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '课时薪资',
  `teacher_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'student' COMMENT '老师类型：student-大学生，professional-专职老师',
  `teacher_gender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'unlimited' COMMENT '教师性别：male-男老师，female-女老师，unlimited-男女不限',
  `is_urgent` tinyint(1) DEFAULT '0' COMMENT '是否加急：1是 0否',
  `is_top` tinyint(1) DEFAULT '0' COMMENT '是否置顶：1是 0否',
  `top_expire_time` timestamp NULL DEFAULT NULL COMMENT '置顶过期时间',
  `admin_id` int(11) DEFAULT NULL COMMENT '创建管理员ID',
  `dispatcher_id` int(11) DEFAULT NULL COMMENT '派单管理员ID',
  `contact_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '派单联系方式（微信号）',
  `assigned_time` timestamp NULL DEFAULT NULL COMMENT '派单时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1正常 0已删除',
  `is_channel` tinyint(1) DEFAULT '0' COMMENT '是否是渠道单：1是 0否',
  `channel_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '渠道代号',
  `booking_channel` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'H5' COMMENT '预约渠道：H5/小程序',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_old_id` (`old_id`),
  KEY `idx_city_id` (`city_id`),
  KEY `idx_district_id` (`district_id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_is_urgent` (`is_urgent`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_dispatcher_id` (`dispatcher_id`),
  KEY `idx_teacher_type` (`teacher_type`),
  KEY `idx_is_channel` (`is_channel`),
  KEY `idx_channel_code` (`channel_code`),
  KEY `idx_teacher_gender` (`teacher_gender`),
  CONSTRAINT `fk_order_city` FOREIGN KEY (`city_id`) REFERENCES `fa_cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='家教订单表（新版）';

#
# Structure for table "fa_user_coupons"
#

DROP TABLE IF EXISTS `fa_user_coupons`;
CREATE TABLE `fa_user_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `openid` varchar(100) NOT NULL COMMENT '用户openid',
  `coupon_type` varchar(20) DEFAULT 'invitation' COMMENT '优惠券类型：invitation-邀请奖励',
  `coupon_amount` decimal(10,2) DEFAULT '20.00' COMMENT '优惠券金额',
  `coupon_code` varchar(32) NOT NULL COMMENT '优惠券码（唯一，用于查询和展示）',
  `source` varchar(50) DEFAULT NULL COMMENT '来源：inviter-邀请人奖励，invitee-被邀请人奖励',
  `related_invitation_id` int(11) DEFAULT NULL COMMENT '关联的邀请记录ID',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：0-待领取，1-已领取待兑换，2-已兑换使用，3-已过期',
  `receive_time` datetime DEFAULT NULL COMMENT '领取时间',
  `redeem_time` datetime DEFAULT NULL COMMENT '兑换时间',
  `redeem_admin_id` int(11) DEFAULT NULL COMMENT '兑换操作的管理员ID',
  `redeem_note` varchar(255) DEFAULT NULL COMMENT '兑换备注',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_coupon_code` (`coupon_code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_status` (`status`),
  KEY `idx_coupon_type` (`coupon_type`),
  KEY `idx_related_invitation_id` (`related_invitation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户优惠券表';

#
# Structure for table "fa_user_invitations"
#

DROP TABLE IF EXISTS `fa_user_invitations`;
CREATE TABLE `fa_user_invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '邀请记录ID',
  `inviter_user_id` int(11) NOT NULL COMMENT '邀请人用户ID',
  `inviter_openid` varchar(100) NOT NULL COMMENT '邀请人openid',
  `invitee_user_id` int(11) DEFAULT NULL COMMENT '被邀请人用户ID',
  `invitee_openid` varchar(100) DEFAULT NULL COMMENT '被邀请人openid',
  `invitation_code` varchar(20) DEFAULT NULL COMMENT '邀请码（openid 邀请可留空）',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：0-待认证，1-已认证',
  `is_rewarded` tinyint(1) DEFAULT '0' COMMENT '是否已发放奖励：0-未发放，1-已发放',
  `inviter_coupon_id` int(11) DEFAULT NULL COMMENT '邀请人获得的优惠券ID',
  `invitee_coupon_id` int(11) DEFAULT NULL COMMENT '被邀请人获得的优惠券ID',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '邀请时间',
  `verify_time` datetime DEFAULT NULL COMMENT '认证时间',
  `reward_time` datetime DEFAULT NULL COMMENT '奖励发放时间',
  PRIMARY KEY (`id`),
  KEY `idx_inviter_user_id` (`inviter_user_id`),
  KEY `idx_inviter_openid` (`inviter_openid`),
  KEY `idx_invitee_openid` (`invitee_openid`),
  KEY `idx_invitation_code` (`invitation_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户邀请记录表';

#
# Structure for table "fa_user_subscribe"
#

DROP TABLE IF EXISTS `fa_user_subscribe`;
CREATE TABLE `fa_user_subscribe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID（可为空）',
  `openid` varchar(64) NOT NULL COMMENT '小程序openid',
  `template_id` varchar(128) NOT NULL COMMENT '订阅消息模板ID',
  `subscribe_time` datetime NOT NULL COMMENT '订阅时间',
  `is_used` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已使用：0否1是',
  `used_time` datetime DEFAULT NULL COMMENT '使用时间',
  PRIMARY KEY (`id`),
  KEY `idx_openid_tpl_used_time` (`openid`,`template_id`,`is_used`,`subscribe_time`),
  KEY `idx_user_id_time` (`user_id`,`subscribe_time`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COMMENT='小程序订阅消息订阅记录';

#
# Structure for table "fa_users"
#

DROP TABLE IF EXISTS `fa_users`;
CREATE TABLE `fa_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `openid` varchar(100) NOT NULL COMMENT '微信openid',
  `openid_secondary` varchar(100) DEFAULT NULL COMMENT '备用openid（支持多小程序）',
  `superior_openid` varchar(100) DEFAULT NULL COMMENT '永久上级管理员openid(首次分享来源)',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `invitation_code` varchar(20) DEFAULT NULL COMMENT '邀请码',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `platform` varchar(20) DEFAULT 'miniprogram' COMMENT '用户端口：miniprogram-小程序, h5-H5端',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，0禁用',
  `user_type` varchar(20) NOT NULL DEFAULT 'parent' COMMENT 'user role: parent/teacher',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_openid` (`openid`),
  UNIQUE KEY `idx_invitation_code` (`invitation_code`),
  KEY `idx_phone` (`phone`),
  KEY `idx_superior_openid` (`superior_openid`),
  KEY `idx_openid_secondary` (`openid_secondary`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='小程序用户表';

#
# Structure for table "fa_wechat_openid_bindings"
#

DROP TABLE IF EXISTS `fa_wechat_openid_bindings`;
CREATE TABLE `fa_wechat_openid_bindings` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `mini_openid` varchar(100) DEFAULT NULL COMMENT '小程序openid',
  `mp_openid` varchar(100) DEFAULT NULL COMMENT '公众号openid',
  `unionid` varchar(100) DEFAULT NULL COMMENT '微信unionid',
  `scene_key` varchar(128) DEFAULT NULL COMMENT '二维码场景值，如 bind_xxx',
  `is_subscribed` tinyint(1) NOT NULL DEFAULT '1' COMMENT '公众号关注状态：1已关注 0已取关',
  `subscribe_time` int(11) DEFAULT NULL COMMENT '最近关注时间戳',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mini_openid` (`mini_openid`),
  UNIQUE KEY `uk_mp_openid` (`mp_openid`),
  KEY `idx_unionid` (`unionid`),
  KEY `idx_scene_key` (`scene_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='公众号与小程序openid绑定表';

#
# Structure for table "fa_wechat_templates"
#

DROP TABLE IF EXISTS `fa_wechat_templates`;
CREATE TABLE `fa_wechat_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `template_code` varchar(50) NOT NULL COMMENT '模板代码标识（如：order_notify）',
  `template_name` varchar(100) NOT NULL COMMENT '模板名称',
  `template_id` varchar(100) NOT NULL COMMENT '微信平台的模板ID',
  `title` varchar(100) DEFAULT NULL COMMENT '模板标题',
  `content` text COMMENT '模板内容示例',
  `primary_industry` varchar(50) DEFAULT NULL COMMENT '主行业',
  `deputy_industry` varchar(50) DEFAULT NULL COMMENT '副行业',
  `url` varchar(255) DEFAULT NULL COMMENT '跳转链接模板',
  `miniprogram_appid` varchar(100) DEFAULT NULL COMMENT '小程序AppID',
  `miniprogram_pagepath` varchar(255) DEFAULT NULL COMMENT '小程序页面路径',
  `color` varchar(20) DEFAULT '#173177' COMMENT '模板内容字体颜色',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用：0-否，1-是',
  `field_mapping` text COMMENT '字段映射配置（JSON格式）',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注说明',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_template_code` (`template_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='微信服务号模板消息配置表';

#
# Structure for table "fa_wechat_users"
#

DROP TABLE IF EXISTS `fa_wechat_users`;
CREATE TABLE `fa_wechat_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `openid` varchar(100) NOT NULL COMMENT '用户OpenID',
  `unionid` varchar(100) DEFAULT NULL COMMENT '用户UnionID',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `nickname` varchar(100) DEFAULT NULL COMMENT '昵称',
  `headimgurl` varchar(500) DEFAULT NULL COMMENT '头像URL',
  `subscribe` tinyint(1) DEFAULT '1' COMMENT '是否关注：0-否，1-是',
  `subscribe_time` int(11) DEFAULT NULL COMMENT '关注时间戳',
  `user_type` varchar(20) DEFAULT NULL COMMENT '用户类型：teacher-教师，admin-管理员',
  `user_id` int(11) DEFAULT NULL COMMENT '关联用户ID',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_openid` (`openid`),
  KEY `idx_user` (`user_type`,`user_id`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='微信用户关联表';

#
# Structure for table "fa_wecom_city_groups"
#

DROP TABLE IF EXISTS `fa_wecom_city_groups`;
CREATE TABLE `fa_wecom_city_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '城市ID',
  `city_name` varchar(64) NOT NULL DEFAULT '' COMMENT '城市名',
  `group_name` varchar(128) NOT NULL DEFAULT '' COMMENT '群名：【91家教】{城市}家教群',
  `chat_id_list` text COMMENT '客户群ID列表JSON（入群二维码必填）',
  `join_way_config_id` varchar(128) NOT NULL DEFAULT '' COMMENT '企业微信入群方式config_id',
  `qr_code` varchar(512) NOT NULL DEFAULT '' COMMENT '入群二维码链接/URL',
  `contact_way_config_id` varchar(128) NOT NULL DEFAULT '' COMMENT '企业微信联系我config_id',
  `contact_way_qr_code` varchar(512) NOT NULL DEFAULT '' COMMENT '联系我二维码链接/URL',
  `missing_group` tinyint(1) NOT NULL DEFAULT '0' COMMENT '缺群待处理：1=缺外部客户群，需要客服创建并回填chat_id_list',
  `request_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '缺群请求次数（contact_way 分支累计）',
  `last_request_time` datetime DEFAULT NULL COMMENT '最近一次缺群请求时间',
  `member_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '群人数（可手动维护/刷新）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0禁用 1启用',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_city` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信同城家教群';

#
# Structure for table "fa_wecom_config"
#

DROP TABLE IF EXISTS `fa_wecom_config`;
CREATE TABLE `fa_wecom_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(64) NOT NULL DEFAULT '' COMMENT '企业ID',
  `agent_id` varchar(64) NOT NULL DEFAULT '' COMMENT '应用AgentId（当前方案可不强依赖）',
  `agent_secret` varchar(200) DEFAULT '' COMMENT '应用Secret',
  `secret` varchar(255) NOT NULL DEFAULT '' COMMENT '应用Secret（回退用）',
  `contact_secret` varchar(255) NOT NULL DEFAULT '' COMMENT '客户联系secret（优先用）',
  `owner_userids` text COMMENT '成员userid列表JSON，用于拉群列表/生成联系我二维码',
  `contact_way_userid` varchar(64) NOT NULL DEFAULT '' COMMENT '缺群时联系我二维码固定成员userid',
  `join_way_scene` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1小程序插件/2二维码插件',
  `join_way_auto_create_room` tinyint(1) NOT NULL DEFAULT '1' COMMENT '群满自动建群',
  `join_way_room_base_name` varchar(128) NOT NULL DEFAULT '' COMMENT '自动建群前缀（可空）',
  `join_way_room_base_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '自动建群起始序号',
  `join_way_mark_source` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'mark_source 开关',
  `callback_token` varchar(64) NOT NULL DEFAULT '' COMMENT '客户联系回调Token',
  `callback_encoding_aes_key` varchar(64) NOT NULL DEFAULT '' COMMENT '客户联系回调EncodingAESKey',
  `welcome_after_contact_text` varchar(800) NOT NULL DEFAULT '' COMMENT '加好友后欢迎语文案（与链接卡片二选一可并存）',
  `welcome_link_title` varchar(128) NOT NULL DEFAULT '加入同城家教群' COMMENT '欢迎语链接卡片标题',
  `welcome_public_base_url` varchar(512) NOT NULL DEFAULT '' COMMENT '落地页/回调拼接用公网根地址，如 https://api.example.com',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信配置';

#
# Structure for table "lead_follow_logs"
#

DROP TABLE IF EXISTS `lead_follow_logs`;
CREATE TABLE `lead_follow_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL COMMENT '线索ID',
  `old_status` varchar(50) DEFAULT NULL COMMENT '原状态',
  `new_status` varchar(50) DEFAULT NULL COMMENT '新状态',
  `remark` text COMMENT '跟进备注',
  `proof_images` text COMMENT '不需要凭证截图数组(JSON)',
  `invalid_images` text COMMENT '无效截图数组(JSON)',
  `operator_admin_id` int(11) NOT NULL COMMENT '操作人ID',
  `proof_image` varchar(255) DEFAULT NULL COMMENT '不需要凭证截图',
  `invalid_image` varchar(255) DEFAULT NULL COMMENT '无效截图',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_operator_admin_id` (`operator_admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='线索跟进记录表';

#
# Structure for table "parent_favorite_teacher"
#

DROP TABLE IF EXISTS `parent_favorite_teacher`;
CREATE TABLE `parent_favorite_teacher` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '家长用户ID',
  `openid` varchar(100) NOT NULL COMMENT '家长微信openid',
  `phone` varchar(20) DEFAULT NULL COMMENT '家长手机号',
  `teacher_id` int(11) NOT NULL COMMENT '教师ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid_teacher` (`openid`,`teacher_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_teacher_id` (`teacher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='家长收藏教师表';

#
# Structure for table "teacher_favorite_tutor"
#

DROP TABLE IF EXISTS `teacher_favorite_tutor`;
CREATE TABLE `teacher_favorite_tutor` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `teacher_id` int(11) DEFAULT NULL COMMENT '教师ID',
  `openid` varchar(100) NOT NULL COMMENT '教师微信openid',
  `phone` varchar(20) DEFAULT NULL COMMENT '教师手机号',
  `tutor_order_id` varchar(20) NOT NULL COMMENT '家教订单ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid_tutor` (`openid`,`tutor_order_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_tutor_order_id` (`tutor_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='教师收藏家教表';

#
# Structure for table "fa_ssl_renew_stats_view"
#

DROP VIEW IF EXISTS `fa_ssl_renew_stats_view`;
CREATE VIEW `fa_ssl_renew_stats_view` AS 
  select cast(`l`.`execute_time` as date) AS `renew_date`,`l`.`action` AS `action`,`l`.`status` AS `status`,count(0) AS `count`,group_concat(distinct `l`.`domain` separator ',') AS `domains` from `fa_ssl_renew_log` `l` where (`l`.`execute_time` >= (now() - interval 30 day)) group by cast(`l`.`execute_time` as date),`l`.`action`,`l`.`status` order by `renew_date` desc;

#
# Structure for table "fa_ssl_status_view"
#

DROP VIEW IF EXISTS `fa_ssl_status_view`;
CREATE VIEW `fa_ssl_status_view` AS 
  select `s`.`id` AS `id`,`s`.`domain` AS `domain`,`s`.`provider` AS `provider`,`s`.`status` AS `status`,`s`.`cert_expire_time` AS `cert_expire_time`,`s`.`auto_renew` AS `auto_renew`,`s`.`is_enabled` AS `is_enabled`,(case when isnull(`s`.`cert_expire_time`) then '未申请' when (`s`.`cert_expire_time` < now()) then '已过期' when (`s`.`cert_expire_time` <= (now() + interval 7 day)) then '7天内过期' when (`s`.`cert_expire_time` <= (now() + interval 30 day)) then '30天内过期' else '正常' end) AS `expire_status`,(case when isnull(`s`.`cert_expire_time`) then 0 else (to_days(`s`.`cert_expire_time`) - to_days(now())) end) AS `days_until_expire`,`s`.`last_check_time` AS `last_check_time`,`s`.`create_time` AS `create_time`,`s`.`update_time` AS `update_time` from `fa_ssl_config` `s` where (`s`.`is_enabled` = 1) order by `s`.`cert_expire_time`;

#
# Structure for table "v_city_light_stats"
#

DROP VIEW IF EXISTS `v_city_light_stats`;
CREATE VIEW `v_city_light_stats` AS 
  select `cl`.`province_id` AS `province_id`,`cl`.`city_name` AS `city_name`,`cl`.`city_code` AS `city_code`,count(distinct `cl`.`user_identifier`) AS `total_lights`,`cl`.`status` AS `status`,min(`cl`.`create_time`) AS `first_light_time`,max(`cl`.`update_time`) AS `last_light_time`,(case when (`cl`.`status` = 1) then '已开通' when (count(distinct `cl`.`user_identifier`) >= 1000) then '可开通' else concat(count(distinct `cl`.`user_identifier`),'/1000') end) AS `progress_text`,round(((count(distinct `cl`.`user_identifier`) / 1000) * 100),2) AS `progress_percent` from `fa_city_lights` `cl` group by `cl`.`province_id`,`cl`.`city_name`,`cl`.`city_code`,`cl`.`status`;

#
# Trigger "trigger_auto_open_city"
#

DROP TRIGGER IF EXISTS `trigger_auto_open_city`;
CREATE DEFINER='root'@'localhost' TRIGGER `myjiajiao`.`trigger_auto_open_city` AFTER INSERT ON `myjiajiao`.`fa_city_lights`
  FOR EACH ROW BEGIN
  DECLARE light_count INT;
  DECLARE city_exists INT;
  
  
  SELECT COUNT(DISTINCT user_identifier) INTO light_count
  FROM fa_city_lights
  WHERE province_id = NEW.province_id 
    AND city_name = NEW.city_name
    AND status = 0;
  
  
  IF light_count >= 1000 THEN
    
    SELECT COUNT(*) INTO city_exists
    FROM fa_cities
    WHERE name = NEW.city_name AND province_id = NEW.province_id;
    
    
    IF city_exists = 0 THEN
      INSERT INTO fa_cities (province_id, code, name, level, sort, status, create_time)
      VALUES (
        NEW.province_id,
        NEW.city_code,
        NEW.city_name,
        '三线城市',
        999,
        1,
        NOW()
      );
    ELSE
      
      UPDATE fa_cities 
      SET status = 1, update_time = NOW()
      WHERE name = NEW.city_name AND province_id = NEW.province_id AND status = 0;
    END IF;
    
    
    UPDATE fa_city_lights
    SET status = 1, update_time = NOW()
    WHERE province_id = NEW.province_id 
      AND city_name = NEW.city_name;
  END IF;
END;
