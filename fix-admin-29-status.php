<?php
/**
 * 修复客服29的状态
 * 将状态从 0（禁用）改为 1（启用）
 */

// 引入 ThinkPHP 框架
require_once __DIR__ . '/tjiajiao91-main/new_system/backend/vendor/autoload.php';

// 启动应用
$app = new think\App();
$app->initialize();

use app\model\Admin;

echo "=== 修复客服29的状态 ===\n\n";

$admin29 = Admin::find(29);

if (!$admin29) {
    echo "❌ 找不到ID为29的管理员\n";
    exit(1);
}

echo "客服29的当前状态: {$admin29->status}\n";

if ($admin29->status == 0) {
    echo "检测到状态为禁用，正在修复...\n\n";
    
    $admin29->status = 1;
    $admin29->save();
    
    echo "✓ 已将客服29的状态改为启用（1）\n\n";
    
    // 验证修复
    echo "=== 验证修复结果 ===\n\n";
    
    $teamMemberIds = Admin::where('leader_id', 18)
        ->where('status', '=', 1)
        ->column('id');
    $teamMemberIds[] = 18; // 包含组长自己
    
    echo "组长18的团队成员ID列表: " . json_encode($teamMemberIds) . "\n";
    
    if (in_array('29', $teamMemberIds) || in_array(29, $teamMemberIds)) {
        echo "✓ 客服29现在在团队成员列表中了！\n";
        echo "✓ 组长18现在应该可以编辑客服29的所有线索了。\n";
    } else {
        echo "❌ 客服29仍然不在团队成员列表中，可能还有其他问题。\n";
    }
} else {
    echo "客服29的状态已经是启用状态，无需修复。\n";
}
?>
