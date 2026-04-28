<?php
namespace app\service;

use think\facade\Db;

/**
 * 订单通知邮件收件人过滤
 *
 * 仅做邮箱合法性校验；不再提供“只发测试邮箱”的调试拦截，避免线上误配导致漏发。
 */
class OrderEmailRecipientGate
{
    public static function filter(string $email, $orderId = null): ?string
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            trace('OrderEmailRecipientGate: invalid email=' . $email . ($orderId ? (', order_id=' . $orderId) : ''), 'warning');
            return null;
        }

        // 每个邮箱每天最多 2 条通知（含 pending/sending/sent），超出则不再入队
        try {
            $start = date('Y-m-d 00:00:00');
            $end = date('Y-m-d 23:59:59');
            $count = (int) Db::name('email_queue')
                ->where('recipient_email', $email)
                ->whereIn('status', ['pending', 'sending', 'sent'])
                ->whereBetween('created_at', [$start, $end])
                ->count();
            if ($count >= 2) {
                trace(
                    'OrderEmailRecipientGate: daily quota reached, email=' . $email . ', count=' . $count
                    . ($orderId ? (', order_id=' . $orderId) : ''),
                    'info'
                );
                return null;
            }
        } catch (\Throwable $e) {
            // 限流查询失败时不阻断主流程（宁可多发，避免漏发）
            trace('OrderEmailRecipientGate: quota check failed: ' . $e->getMessage(), 'warning');
        }

        return $email;
    }
}
