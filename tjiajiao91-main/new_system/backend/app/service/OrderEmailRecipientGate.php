<?php
namespace app\service;

use think\facade\Config;

/**
 * 订单通知邮件收件人过滤（调试模式：只发给指定邮箱，其它邮箱不入队）
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

        if (!Config::get('app.order_email_debug_only', false)) {
            return $email;
        }

        $target = trim((string) Config::get('app.order_email_debug_target', ''));
        if ($target === '' || !filter_var($target, FILTER_VALIDATE_EMAIL)) {
            trace('OrderEmailRecipientGate: debug_only enabled but target invalid, skip all', 'warning');
            return null;
        }

        if (strcasecmp($email, $target) !== 0) {
            trace(
                'OrderEmailRecipientGate: debug_only skip, from=' . $email . ', only=' . $target
                . ($orderId ? (', order_id=' . $orderId) : ''),
                'info'
            );
            return null;
        }

        return $email;
    }
}
