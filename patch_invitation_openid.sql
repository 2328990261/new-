-- ============================================================
-- 邀请功能表：在 tjiajiao91 中创建缺失表（若不存在）并改为 openid 邀请
-- 请在 phpMyAdmin 中先选中数据库 tjiajiao91，再执行本脚本。
-- 注意：表名、列名用反引号 ` 包裹，关键字 NULL 不要写成 HULL，COLUMN 不要写成 COLUME。
-- ============================================================

-- 1. 邀请记录表（若不存在则创建，邀请码已设为可选）
CREATE TABLE IF NOT EXISTS `fa_user_invitations` (
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

-- 2. 若表已存在，仅把邀请码改为可选（执行上面 CREATE 后，新表已是可选；旧表需执行下面一句）
ALTER TABLE `fa_user_invitations`
  MODIFY COLUMN `invitation_code` varchar(20) DEFAULT NULL COMMENT '邀请码（openid 邀请可留空）';

-- 3. 用户优惠券表（若不存在则创建）
CREATE TABLE IF NOT EXISTS `fa_user_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `openid` varchar(100) NOT NULL COMMENT '用户openid',
  `coupon_type` varchar(20) DEFAULT 'invitation' COMMENT '优惠券类型：invitation-邀请奖励',
  `coupon_amount` decimal(10,2) DEFAULT '20.00' COMMENT '优惠券金额',
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
  KEY `idx_user_id` (`user_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_status` (`status`),
  KEY `idx_coupon_type` (`coupon_type`),
  KEY `idx_related_invitation_id` (`related_invitation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户优惠券表';

-- 4. 邀请排行榜表（若不存在则创建）
CREATE TABLE IF NOT EXISTS `fa_invitation_ranking` (
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
