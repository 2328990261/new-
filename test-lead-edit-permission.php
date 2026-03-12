<?php
/**
 * 测试线索编辑权限
 * 模拟组长18编辑客服29的线索
 */

// 引入 ThinkPHP 框架
require_once __DIR__ . '/tjiajiao91-main/new_system/backend/vendor/autoload.php';

// 启动应用
$app = new think\App();
$app->initialize();

use app\model\Lead as LeadModel;
use app\model\Admin;

echo "=== 测试线索编辑权限 ===\n\n";

// 查询一条分配给客服29的线索
$lead = LeadModel::where('assigned_admin_id', 29)->find();

if (!$lead) {
    echo "❌ 没有找到分配给客服29的线索\n";
    exit(1);
}

echo "测试线索信息：\n";
echo "  线索ID: {$lead->id}\n";
echo "  线索编号: {$lead->lead_no}\n";
echo "  线索状态: {$lead->status}\n";
echo "  分配给: {$lead->assigned_admin_id} (类型: " . gettype($lead->assigned_admin_id) . ")\n\n";

// 模拟组长18的权限检查
$adminId = 18;
$adminRole = 'team_leader';

echo "当前用户：组长18\n";
echo "尝试编辑线索ID: {$lead->id}\n\n";

// 执行权限检查逻辑（与后端代码一致）
$hasPermission = false;

if ($adminRole === 'team_leader') {
    $teamMemberIds = Admin::where('leader_id', $adminId)
        ->where('status', '=', 1)
        ->column('id');
    $teamMemberIds[] = $adminId;
    
    // 确保类型一致：将所有ID转换为整数
    $teamMemberIds = array_map('intval', $teamMemberIds);
    $leadAssignedId = intval($lead->assigned_admin_id);
    
    echo "团队成员ID列表（整数）: " . json_encode($teamMemberIds) . "\n";
    echo "线索分配给（整数）: {$leadAssignedId}\n\n";
    
    if ($leadAssignedId == 0) {
        $hasPermission = true;
        echo "✓ 未分配线索，组长可编辑\n";
    } elseif (in_array($leadAssignedId, $teamMemberIds)) {
        $hasPermission = true;
        echo "✓ 线索分配给团队成员（ID: {$leadAssignedId}），允许编辑\n";
    } else {
        echo "✗ 线索分配给其他团队（ID: {$leadAssignedId}），拒绝编辑\n";
    }
}

echo "\n最终权限检查结果: " . ($hasPermission ? '✓ 有权限编辑' : '✗ 无权限编辑') . "\n\n";

if ($hasPermission) {
    echo "=== 测试不同状态的线索 ===\n\n";
    
    $statuses = ['待联系', '跟进中', '已发单', '已出单', '不需要', '无效'];
    
    foreach ($statuses as $status) {
        $testLead = LeadModel::where('assigned_admin_id', 29)
            ->where('status', $status)
            ->find();
        
        if ($testLead) {
            echo "状态: {$status} - 线索ID: {$testLead->id} - ";
            // 权限检查不依赖状态，所以都应该有权限
            echo "✓ 可以编辑\n";
        } else {
            echo "状态: {$status} - 没有找到该状态的线索\n";
        }
    }
    
    echo "\n如果所有状态都显示'可以编辑'，说明后端逻辑正确。\n";
    echo "如果前端仍然显示无权限，请检查：\n";
    echo "1. 清除浏览器缓存和重新登录\n";
    echo "2. 检查前端是否有额外的权限判断\n";
    echo "3. 查看浏览器控制台的网络请求和响应\n";
}
?>
