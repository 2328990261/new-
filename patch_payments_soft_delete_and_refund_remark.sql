-- 支付单：增加软删除字段 + 退款备注字段
-- 目的：
-- 1) 支付管理支持“软删除”（仅隐藏前端列表，不物理删除）
-- 2) 将“退款审核备注”与“交易/管理端备注”拆分，避免互相覆盖

ALTER TABLE `fa_payments`
  ADD COLUMN `refund_remark` TEXT NULL COMMENT '退款审核备注' AFTER `remark`,
  ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '软删除标记：1=已移除' AFTER `pinned_at`,
  ADD COLUMN `deleted_at` DATETIME NULL COMMENT '软删除时间' AFTER `is_deleted`;

-- 可选：如果你希望历史数据在退款详情里还能看到旧备注，可手工迁移一次
-- UPDATE `fa_payments` SET `refund_remark` = `remark` WHERE `refund_remark` IS NULL AND `remark` IS NOT NULL AND `remark` <> '';

