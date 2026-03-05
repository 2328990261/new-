<?php
require __DIR__ . '/vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use think\facade\Db;

try {
    // 查询 demo 订单
    $payment = Db::table('fa_payments')
        ->where('tutor_name', 'demo')
        ->find();
    
    if ($payment) {
        echo "=== Demo 测试订单信息 ===\n\n";
        echo "订单号：" . $payment['order_no'] . "\n";
        echo "家教名称：" . $payment['tutor_name'] . "\n";
        echo "支付金额：¥" . $payment['amount'] . "\n";
        echo "支付人姓名：" . ($payment['payer_name'] ?: '未填写') . "\n";
        echo "支付人联系方式：" . $payment['payer_contact'] . "\n";
        echo "支付时间：" . ($payment['paid_time'] ?: $payment['create_time']) . "\n";
        echo "已退金额：¥" . ($payment['refunded_amount'] ?: '0.00') . "\n";
        echo "退款状态：" . ($payment['refund_status'] ?: '无退款') . "\n";
        echo "\n";
        echo "--- 测试用数据 ---\n";
        echo "订单号：" . $payment['order_no'] . "\n";
        echo "联系方式：" . $payment['payer_contact'] . "\n";
        echo "可退金额：¥" . ($payment['amount'] - ($payment['refunded_amount'] ?: 0)) . "\n";
        echo "\n";
    } else {
        echo "未找到 demo 订单\n";
    }
    
} catch (\Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
}
