<?php
/**
 * 修复客服的 leader_id 设置
 * 将客服29分配给组长18
 */

// 引入 ThinkPHP 框架
require_once __DIR__ . '/tjiajiao91-main/new_system/backend/vendor/autoload.php';

// 启动应用
$app = new think\App();
$app->initialize();

use app\model\Admin;

echo "=== 修复客服的组长分配 ===\n\n";

// 查询客服29的当前信息
$admin29 = Admin::find(29);

if (!$admin29) {
    echo "❌ 错误：找不到ID为29的客服\n";
    exit(1);
}

echo "客服29的当前信息：\n";
echo "  ID: {$admin29->id}\n";
echo "  用户名: {$admin29->username}\n";
echo "  昵称: {$admin29->nickname}\n";
echo "  角色: {$admin29->role}\n";
echo "  当前上级ID: " . ($admin29->leader_id ?: '无') . "\n\n";

// 询问是否要将客服29分配给组长18
echo "是否要将客服29分配给组长18？\n";
echo "这将允许组长18编辑客服29的所有线索。\n\n";

echo "请手动执行以下SQL语句来修复：\n\n";
echo "UPDATE `admin` SET `leader_id` = 18 WHERE `id` = 29;\n\n";

echo "或者，如果客服29应该属于其他组长，请设置正确的 leader_id。\n";
echo "当前系统中的组长列表：\n";

$teamLeaders = Admin::where('role', 'team_leader')
    ->where('status', 1)
    ->field('id, username, nickname')
    ->select();

foreach ($teamLeaders as $leader) {
    echo "  - ID: {$leader->id}, 用户名: {$leader->username}, 昵称: {$leader->nickname}\n";
}

echo "\n=== 自动修复选项 ===\n";
echo "如果你确定要将客服29分配给组长18，请取消注释下面的代码并重新运行：\n\n";
echo "/*\n";
echo "\$admin29->leader_id = 18;\n";
echo "\$admin29->save();\n";
echo "echo \"✓ 已将客服29分配给组长18\\n\";\n";
echo "*/\n";

// 取消下面的注释来自动修复
/*
$admin29->leader_id = 18;
$admin29->save();
echo "\n✓ 已将客服29分配给组长18\n";
echo "现在组长18应该可以编辑客服29的所有线索了。\n";
*/
?>
