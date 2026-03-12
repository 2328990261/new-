<?php
// 测试管理员状态更新
require __DIR__ . '/../vendor/autoload.php';

use think\facade\Db;
use think\facade\Config;

// 加载应用
$app = new think\App();
$app->initialize();

// 测试更新 ID 32 的管理员状态
$adminId = 32;

echo "=== 测试管理员状态更新 ===\n\n";

// 1. 查询当前管理员信息
$admin = Db::name('admin')->where('id', $adminId)->find();
if (!$admin) {
    echo "管理员 ID {$adminId} 不存在\n";
    exit;
}

echo "当前管理员信息：\n";
echo "ID: {$admin['id']}\n";
echo "用户名: {$admin['username']}\n";
echo "昵称: {$admin['nickname']}\n";
echo "角色: {$admin['role']}\n";
echo "状态: {$admin['status']}\n";
echo "邮箱: " . ($admin['email'] ?: '(空)') . "\n";
echo "\n";

// 2. 尝试更新状态
$newStatus = $admin['status'] == 1 ? 0 : 1;
echo "尝试将状态从 {$admin['status']} 更新为 {$newStatus}...\n";

try {
    $result = Db::name('admin')->where('id', $adminId)->update(['status' => $newStatus]);
    echo "更新成功！影响行数: {$result}\n\n";
    
    // 3. 验证更新结果
    $updatedAdmin = Db::name('admin')->where('id', $adminId)->find();
    echo "更新后的状态: {$updatedAdmin['status']}\n";
    
    // 恢复原状态
    Db::name('admin')->where('id', $adminId)->update(['status' => $admin['status']]);
    echo "已恢复原状态\n";
    
} catch (\Exception $e) {
    echo "更新失败: " . $e->getMessage() . "\n";
}
