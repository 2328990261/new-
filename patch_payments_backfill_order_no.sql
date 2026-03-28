-- 支付订单号排查与补齐脚本
-- 适用于表前缀 fa_ 的环境

-- 1) 先看最新支付记录是否带 order_no
SELECT id, order_no, transaction_id, status, create_time, paid_time
FROM fa_payments
ORDER BY id DESC
LIMIT 30;

-- 2) 看空订单号数量
SELECT COUNT(*) AS empty_order_no_count
FROM fa_payments
WHERE order_no IS NULL OR TRIM(order_no) = '';

-- 3) 补齐历史空订单号（仅补空值，不影响已有）
-- 规则：PAYLEGACY + yyyymmddHHMMSS + 6位id
UPDATE fa_payments
SET order_no = CONCAT(
  'PAYLEGACY',
  DATE_FORMAT(COALESCE(create_time, NOW()), '%Y%m%d%H%i%s'),
  LPAD(id, 6, '0')
)
WHERE order_no IS NULL OR TRIM(order_no) = '';

-- 4) 再检查一次
SELECT id, order_no, transaction_id, status, create_time, paid_time
FROM fa_payments
ORDER BY id DESC
LIMIT 30;
