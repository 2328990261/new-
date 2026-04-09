<?php
/**
 * UnionID 获取状态诊断脚本
 * 用于检查小程序用户的 unionid 获取情况
 */

// 检测项目路径
$backendPath = __DIR__ . '/tjiajiao91-main/new_system/backend';
if (!is_dir($backendPath)) {
    echo "错误：找不到后端目录\n";
    echo "当前目录：" . __DIR__ . "\n";
    echo "请确保在项目根目录运行此脚本\n";
    exit(1);
}

require $backendPath . '/vendor/autoload.php';

// 加载 ThinkPHP
$app = require $backendPath . '/app/app.php';
$app->initialize();

use think\facade\Db;
use think\facade\Config;

echo "========================================\n";
echo "UnionID 获取状态诊断\n";
echo "========================================\n\n";

// 1. 检查微信配置
echo "1. 检查微信小程序配置\n";
echo "----------------------------------------\n";
$miniAppId = env('WECHAT_MINI_APPID', '');
$miniSecret = env('WECHAT_MINI_SECRET', '');
echo "小程序 AppID: " . ($miniAppId ? $miniAppId : '未配置') . "\n";
echo "小程序 Secret: " . ($miniSecret ? '已配置' : '未配置') . "\n\n";

// 2. 检查公众号配置
echo "2. 检查微信公众号配置\n";
echo "----------------------------------------\n";
try {
    $notificationConfig = Db::name('notification_config')->where('id', 1)->find();
    if ($notificationConfig) {
        $mpAppId = $notificationConfig['wechat_app_id'] ?? '';
        $mpSecret = $notificationConfig['wechat_app_secret'] ?? '';
        echo "公众号 AppID: " . ($mpAppId ? $mpAppId : '未配置') . "\n";
        echo "公众号 Secret: " . ($mpSecret ? '已配置' : '未配置') . "\n";
        
        // 检查是否同一开放平台
        if ($miniAppId && $mpAppId) {
            echo "\n提示：小程序和公众号必须绑定到同一个微信开放平台才能获取 unionid\n";
            echo "请访问 https://open.weixin.qq.com/ 检查绑定状态\n";
        }
    } else {
        echo "公众号配置未找到\n";
    }
} catch (\Exception $e) {
    echo "查询公众号配置失败: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. 统计用户 unionid 情况
echo "3. 用户 UnionID 统计\n";
echo "----------------------------------------\n";
try {
    $totalUsers = Db::name('users')->count();
    $usersWithUnionid = Db::name('wechat_users')
        ->where('unionid', '<>', '')
        ->whereNotNull('unionid')
        ->count();
    
    echo "总用户数: {$totalUsers}\n";
    echo "已获取 unionid 的用户数: {$usersWithUnionid}\n";
    
    if ($totalUsers > 0) {
        $percentage = round(($usersWithUnionid / $totalUsers) * 100, 2);
        echo "获取率: {$percentage}%\n";
    }
} catch (\Exception $e) {
    echo "统计失败: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. 检查绑定表状态
echo "4. 小程序-公众号绑定状态\n";
echo "----------------------------------------\n";
try {
    $totalBindings = Db::name('wechat_openid_bindings')->count();
    $bindingsWithUnionid = Db::name('wechat_openid_bindings')
        ->where('unionid', '<>', '')
        ->whereNotNull('unionid')
        ->count();
    $bindingsWithMpOpenid = Db::name('wechat_openid_bindings')
        ->where('mp_openid', '<>', '')
        ->whereNotNull('mp_openid')
        ->count();
    $subscribedBindings = Db::name('wechat_openid_bindings')
        ->where('is_subscribed', 1)
        ->count();
    
    echo "绑定记录总数: {$totalBindings}\n";
    echo "已有 unionid 的记录: {$bindingsWithUnionid}\n";
    echo "已有公众号 openid 的记录: {$bindingsWithMpOpenid}\n";
    echo "已关注公众号的记录: {$subscribedBindings}\n";
} catch (\Exception $e) {
    echo "查询绑定表失败: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. 最近登录用户的 unionid 情况
echo "5. 最近 10 个登录用户的 UnionID 情况\n";
echo "----------------------------------------\n";
try {
    $recentUsers = Db::name('users')
        ->alias('u')
        ->leftJoin('wechat_users wu', 'u.openid = wu.openid')
        ->field('u.id, u.phone, u.nickname, u.openid, wu.unionid, u.update_time')
        ->order('u.update_time', 'desc')
        ->limit(10)
        ->select()
        ->toArray();
    
    if ($recentUsers) {
        foreach ($recentUsers as $user) {
            $hasUnionid = !empty($user['unionid']) ? '✓' : '✗';
            $phone = $user['phone'] ?? '无';
            $nickname = $user['nickname'] ?? '无';
            $updateTime = $user['update_time'] ?? '无';
            
            echo sprintf(
                "[%s] ID:%d | %s | %s | UnionID: %s | 更新: %s\n",
                $hasUnionid,
                $user['id'],
                $phone,
                $nickname,
                $hasUnionid === '✓' ? substr($user['unionid'], 0, 10) . '...' : '未获取',
                $updateTime
            );
        }
    } else {
        echo "暂无用户数据\n";
    }
} catch (\Exception $e) {
    echo "查询用户失败: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. 提供解决方案
echo "========================================\n";
echo "UnionID 获取方案\n";
echo "========================================\n\n";

echo "方案一：通过微信开放平台绑定（推荐）\n";
echo "1. 访问 https://open.weixin.qq.com/\n";
echo "2. 登录并创建/进入你的开放平台账号\n";
echo "3. 将小程序和公众号都绑定到同一个开放平台账号\n";
echo "4. 绑定后，用户在小程序登录时会自动返回 unionid\n\n";

echo "方案二：通过公众号关注获取\n";
echo "1. 确保用户已关注公众号\n";
echo "2. 小程序引导用户扫描公众号二维码关注\n";
echo "3. 通过公众号事件回调获取 unionid 并绑定\n";
echo "4. 使用接口: /api/wechat/official/qrcode 生成带参二维码\n\n";

echo "方案三：通过公众号 OAuth 授权\n";
echo "1. 小程序内跳转到公众号网页授权\n";
echo "2. 用户授权后获取 unionid\n";
echo "3. 使用接口: /api/wechat/official/bind-auth-url 获取授权链接\n\n";

echo "注意事项：\n";
echo "- 只有绑定到同一开放平台的小程序和公众号才能共享 unionid\n";
echo "- 用户必须关注公众号才能通过公众号接口获取 unionid\n";
echo "- 建议优先使用方案一（开放平台绑定），这是最稳定的方式\n\n";

echo "========================================\n";
echo "诊断完成\n";
echo "========================================\n";
