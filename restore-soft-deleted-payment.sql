-- 恢复被软删除的支付记录

-- 1. 查看被软删除的记录
SELECT 
    id,
    order_no,
    status,
    refund_status,
    amount,
    is_deleted,
    deleted_at,
    refund_apply_time
FROM fa_payment 
WHERE is_deleted = 1
ORDER BY deleted_at DESC;

-- 2. 恢复指定的记录（替换 YOUR_ORDER_NO 为实际订单号）
-- UPDATE fa_payment 
-- SET is_deleted = 0, deleted_at = NULL 
-- WHERE order_no = 'YOUR_ORDER_NO';

-- 3. 或者恢复所有被软删除的退款待处理记录
-- UPDATE fa_payment 
-- SET is_deleted = 0, deleted_at = NULL 
-- WHERE is_deleted = 1 
--   AND refund_status = 'pending';

-- 4. 批量恢复所有被软删除的记录（谨慎使用！）
-- UPDATE fa_payment 
-- SET is_deleted = 0, deleted_at = NULL 
-- WHERE is_deleted = 1;
