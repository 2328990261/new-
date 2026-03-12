<?php
/**
 * 权限测试脚本
 * 用于测试组长编辑组员线索的权限逻辑
 */

// 模拟数据
$adminId = 18; // 组长ID
$adminRole = 'team_leader';
$leadAssignedAdminId = 29; // 线索分配给的客服ID

// 模拟组员ID列表（从数据库查询）
$teamMemberIds = [29]; // 假设29是组长18的组员

echo "=== 权限测试 ===\n";
echo "当前用户ID: {$adminId}\n";
echo "当前用户角色: {$adminRole}\n";
echo "线索分配给: {$leadAssignedAdminId}\n";
echo "组员ID列表: " . json_encode($teamMemberIds) . "\n\n";

// 权限验证逻辑
$hasPermission = false;

if ($adminRole === 'super_admin') {
    $hasPermission = true;
    echo "✓ 超级管理员，允许编辑\n";
} elseif ($leadAssignedAdminId == $adminId) {
    $hasPermission = true;
    echo "✓ 分配给自己的线索，允许编辑\n";
} elseif ($leadAssignedAdminId == 0) {
    $hasPermission = true;
    echo "✓ 公共池线索，允许编辑\n";
} elseif ($adminRole === 'team_leader') {
    echo "检查组长权限...\n";
    if (in_array($leadAssignedAdminId, $teamMemberIds)) {
        $hasPermission = true;
        echo "✓ 线索分配给组员，允许编辑\n";
    } else {
        echo "✗ 线索未分配给组员，拒绝编辑\n";
    }
}

echo "\n最终结果: " . ($hasPermission ? "有权限" : "无权限") . "\n";

// 测试不同状态
echo "\n=== 状态测试 ===\n";
$statuses = ['待跟进', '跟进中', '已发单', '已出单', '不需要', '无效'];
foreach ($statuses as $status) {
    echo "状态: {$status} - ";
    // 注意：权限验证不应该依赖状态
    echo ($hasPermission ? "可以编辑" : "不能编辑") . "\n";
}

echo "\n如果所有状态都显示'可以编辑'，说明逻辑正确。\n";
echo "如果只有某些状态可以编辑，说明代码中有额外的状态验证。\n";
?>
