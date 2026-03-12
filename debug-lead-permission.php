<?php
/**
 * 线索编辑权限调试脚本
 * 用于排查组长编辑组员线索时的权限问题
 */

// 模拟不同场景的测试数据
$testCases = [
    [
        'name' => '场景1: 组长编辑自己的线索',
        'adminId' => 18,
        'adminRole' => 'team_leader',
        'leadAssignedAdminId' => 18,
        'leadStatus' => '待联系',
        'teamMemberIds' => [18, 29]
    ],
    [
        'name' => '场景2: 组长编辑组员的线索（待联系）',
        'adminId' => 18,
        'adminRole' => 'team_leader',
        'leadAssignedAdminId' => 29,
        'leadStatus' => '待联系',
        'teamMemberIds' => [18, 29]
    ],
    [
        'name' => '场景3: 组长编辑组员的线索（跟进中）',
        'adminId' => 18,
        'adminRole' => 'team_leader',
        'leadAssignedAdminId' => 29,
        'leadStatus' => '跟进中',
        'teamMemberIds' => [18, 29]
    ],
    [
        'name' => '场景4: 组长编辑组员的线索（已发单）',
        'adminId' => 18,
        'adminRole' => 'team_leader',
        'leadAssignedAdminId' => 29,
        'leadStatus' => '已发单',
        'teamMemberIds' => [18, 29]
    ],
    [
        'name' => '场景5: 组长编辑未分配的线索',
        'adminId' => 18,
        'adminRole' => 'team_leader',
        'leadAssignedAdminId' => 0,
        'leadStatus' => '待联系',
        'teamMemberIds' => [18, 29]
    ],
    [
        'name' => '场景6: 组长编辑其他团队的线索',
        'adminId' => 18,
        'adminRole' => 'team_leader',
        'leadAssignedAdminId' => 99,
        'leadStatus' => '待联系',
        'teamMemberIds' => [18, 29]
    ]
];

echo "=== 线索编辑权限调试 ===\n\n";

foreach ($testCases as $index => $case) {
    echo "【{$case['name']}】\n";
    echo "  当前用户ID: {$case['adminId']}\n";
    echo "  当前用户角色: {$case['adminRole']}\n";
    echo "  线索分配给: {$case['leadAssignedAdminId']}\n";
    echo "  线索状态: {$case['leadStatus']}\n";
    echo "  团队成员ID: " . json_encode($case['teamMemberIds']) . "\n";
    
    // 执行权限检查逻辑
    $hasPermission = false;
    $reason = '';
    
    $adminId = $case['adminId'];
    $adminRole = $case['adminRole'];
    $leadAssignedAdminId = $case['leadAssignedAdminId'];
    $teamMemberIds = $case['teamMemberIds'];
    
    if ($adminRole === 'super_admin') {
        $hasPermission = true;
        $reason = '超级管理员，允许编辑';
    } elseif ($adminRole === 'team_leader') {
        if ($leadAssignedAdminId == 0) {
            $hasPermission = true;
            $reason = '未分配线索，组长可编辑';
        } elseif (in_array($leadAssignedAdminId, $teamMemberIds)) {
            $hasPermission = true;
            $reason = "线索分配给团队成员（ID: {$leadAssignedAdminId}），允许编辑";
        } else {
            $reason = "线索分配给其他团队（ID: {$leadAssignedAdminId}），拒绝编辑";
        }
    } elseif ($leadAssignedAdminId == $adminId) {
        $hasPermission = true;
        $reason = '分配给自己的线索，允许编辑';
    } elseif ($leadAssignedAdminId == 0) {
        $hasPermission = true;
        $reason = '公共池线索，允许编辑';
    }
    
    echo "  权限检查结果: " . ($hasPermission ? '✓ 有权限' : '✗ 无权限') . "\n";
    echo "  原因: {$reason}\n";
    echo "\n";
}

echo "=== 关键提示 ===\n";
echo "1. 如果所有场景（除了场景6）都显示'有权限'，说明后端逻辑正确\n";
echo "2. 如果只有场景4（已发单）显示'有权限'，说明可能有其他地方的权限检查\n";
echo "3. 请检查：\n";
echo "   - 前端是否有额外的权限判断\n";
echo "   - 是否有中间件拦截\n";
echo "   - 数据库中 leader_id 字段是否正确设置\n";
echo "   - 浏览器控制台是否有错误信息\n";
?>
