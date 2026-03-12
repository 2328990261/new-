<?php
/**
 * 检查数据库中的 leader_id 设置
 */

// 引入 ThinkPHP 框架
require_once __DIR__ . '/tjiajiao91-main/new_system/backend/vendor/autoload.php';

// 启动应用
$app = new think\App();
$app->initialize();

// 使用模型查询
use app\model\Admin;

echo "=== 检查管理员表中的 leader_id 设置 ===\n\n";

// 查询所有客服和组长
$admins = Admin::where('status', 1)
    ->whereIn('role', ['customer_service', 'team_leader', 'super_admin'])
    ->field('id, username, nickname, role, leader_id')
    ->select();

echo "ID\t用户名\t\t昵称\t\t角色\t\t\t上级ID\n";
echo str_repeat('-', 80) . "\n";

foreach ($admins as $admin) {
    $roleText = [
        'super_admin' => '超级管理员',
        'team_leader' => '客服组长',
        'customer_service' => '客服'
    ][$admin->role] ?? $admin->role;
    
    printf(
        "%d\t%-15s\t%-15s\t%-20s\t%s\n",
        $admin->id,
        $admin->username,
        $admin->nickname ?: '-',
        $roleText,
        $admin->leader_id ?: '无'
    );
}

echo "\n=== 检查组长18的团队成员 ===\n\n";

$teamMembers = Admin::where('leader_id', 18)
    ->where('status', 1)
    ->field('id, username, nickname, role')
    ->select();

if ($teamMembers->isEmpty()) {
    echo "⚠️ 警告：组长18没有任何团队成员！\n";
    echo "这可能是问题的根源。请检查数据库中客服的 leader_id 字段是否正确设置为 18。\n";
} else {
    echo "组长18的团队成员：\n";
    foreach ($teamMembers as $member) {
        echo "  - ID: {$member->id}, 用户名: {$member->username}, 昵称: {$member->nickname}, 角色: {$member->role}\n";
    }
}

echo "\n=== 检查线索分配情况 ===\n\n";

use app\model\Lead as LeadModel;

// 查询分配给ID为29的客服的线索
$leads = LeadModel::where('assigned_admin_id', 29)
    ->field('id, lead_no, status, assigned_admin_id')
    ->limit(5)
    ->select();

if ($leads->isEmpty()) {
    echo "没有找到分配给客服29的线索\n";
} else {
    echo "分配给客服29的线索（前5条）：\n";
    foreach ($leads as $lead) {
        echo "  - 线索ID: {$lead->id}, 编号: {$lead->lead_no}, 状态: {$lead->status}, 分配给: {$lead->assigned_admin_id}\n";
    }
}
?>
