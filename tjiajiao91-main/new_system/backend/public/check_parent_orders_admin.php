<?php
/**
 * 预约订单 admin_id 诊断脚本
 * 访问: http://你的域名/check_parent_orders_admin.php
 * 用于排查「归属管理员」显示问题
 */
header('Content-Type: text/html; charset=utf-8');

define('APP_PATH', __DIR__ . '/../app/');
require __DIR__ . '/../vendor/autoload.php';

$app = new \think\App(__DIR__ . '/../');
$app->initialize();

use think\facade\Db;

echo "<h2>预约订单 admin_id 分布诊断</h2>";

// 1. 统计 admin_id 分布
$distribution = Db::name('parent_orders')
    ->field('admin_id, COUNT(*) as count')
    ->group('admin_id')
    ->order('count', 'desc')
    ->select();

echo "<h3>1. admin_id 分布统计</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>admin_id</th><th>订单数量</th><th>管理员昵称</th></tr>";
foreach ($distribution as $row) {
    $admin = Db::name('admin')->where('id', $row['admin_id'])->find();
    $nickname = $admin ? ($admin['nickname'] ?? $admin['username'] ?? '-') : '(不存在或admin_id=0)';
    echo "<tr><td>{$row['admin_id']}</td><td>{$row['count']}</td><td>{$nickname}</td></tr>";
}
echo "</table>";

// 2. 最近10条订单的 admin_id
echo "<h3>2. 最近10条订单的 admin_id 详情</h3>";
$recent = Db::name('parent_orders')
    ->field('id, order_no, admin_id, create_time, booking_channel')
    ->order('create_time', 'desc')
    ->limit(10)
    ->select();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>订单号</th><th>admin_id</th><th>归属管理员</th><th>渠道</th><th>创建时间</th></tr>";
foreach ($recent as $row) {
    $admin = $row['admin_id'] > 0 
        ? Db::name('admin')->where('id', $row['admin_id'])->find() 
        : null;
    $nickname = $admin ? ($admin['nickname'] ?? $admin['username']) : '-';
    echo "<tr><td>{$row['id']}</td><td>{$row['order_no']}</td><td>{$row['admin_id']}</td><td>{$nickname}</td><td>{$row['booking_channel']}</td><td>{$row['create_time']}</td></tr>";
}
echo "</table>";

echo "<p><strong>说明：</strong>如果大部分订单 admin_id 相同，说明创建时未正确传递 admin_openid/admin_id。请确保小程序分享链接携带管理员参数，或管理后台复制链接时带 admin_id。</p>";
echo "<p>排查完成后请删除此文件。</p>";
