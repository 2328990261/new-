<?php
/**
 * 专门检查客服29和组长18的关系
 */

// 引入 ThinkPHP 框架
require_once __DIR__ . '/tjiajiao91-main/new_system/backend/vendor/autoload.php';

// 启动应用
$app = new think\App();
$app->initialize();

use app\model\Admin;

echo "=== 检查客服29的详细信息 ===\n\n";

$admin29 = Admin::find(29);

if (!$admin29) {
    echo "❌ 找不到ID为29的管理员\n";
    exit(1);
}

echo "客服29的信息：\n";
echo "  ID: {$admin29->id} (类型: " . gettype($admin29->id) . ")\n";
echo "  用户名: {$admin29->username}\n";
echo "  昵称: {$admin29->nickname}\n";
echo "  角色: {$admin29->role}\n";
echo "  leader_id: {$admin29->leader_id} (类型: " . gettype($admin29->leader_id) . ")\n";
echo "  状态: {$admin29->status}\n\n";

echo "=== 检查组长18的团队成员 ===\n\n";

$teamMemberIds = Admin::where('leader_id', 18)
    ->where('status', '=', 1)
    ->column('id');

echo "团队成员ID列表: " . json_encode($teamMemberIds) . "\n";
echo "数组类型检查:\n";
foreach ($teamMemberIds as $id) {
    echo "  - ID: {$id} (类型: " . gettype($id) . ")\n";
}

echo "\n=== 权限检查测试 ===\n\n";

// 模拟权限检查
$adminId = 18;
$leadAssignedAdminId = 29;
$teamMemberIds[] = $adminId; // 包含组长自己

echo "检查 in_array({$leadAssignedAdminId}, " . json_encode($teamMemberIds) . ")\n";
$result = in_array($leadAssignedAdminId, $teamMemberIds);
echo "结果: " . ($result ? '✓ true (有权限)' : '✗ false (无权限)') . "\n\n";

// 严格模式检查
echo "严格模式检查 in_array({$leadAssignedAdminId}, " . json_encode($teamMemberIds) . ", true)\n";
$resultStrict = in_array($leadAssignedAdminId, $teamMemberIds, true);
echo "结果: " . ($resultStrict ? '✓ true (有权限)' : '✗ false (无权限)') . "\n\n";

// 检查类型
echo "=== 类型检查 ===\n";
echo "leadAssignedAdminId 类型: " . gettype($leadAssignedAdminId) . "\n";
echo "teamMemberIds[0] 类型: " . gettype($teamMemberIds[0]) . "\n\n";

// 如果类型不匹配，尝试转换
if (gettype($leadAssignedAdminId) !== gettype($teamMemberIds[0])) {
    echo "⚠️ 警告：类型不匹配！\n";
    echo "尝试类型转换后再检查...\n";
    $leadAssignedAdminIdInt = (int)$leadAssignedAdminId;
    $result2 = in_array($leadAssignedAdminIdInt, $teamMemberIds);
    echo "转换后结果: " . ($result2 ? '✓ true' : '✗ false') . "\n";
}
?>
