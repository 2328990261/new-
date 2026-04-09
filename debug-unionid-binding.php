<?php
/**
 * UnionID 绑定诊断脚本
 * 用于排查小程序和公众号 unionid 绑定失败的问题
 */

require __DIR__ . '/tjiajiao91-main/new_system/backend/vendor/autoload.php';

// 初始化 ThinkPHP
$app = new think\App();
$app->initialize();

use think\facade\Db;
use think\facade\Config;

echo "=== UnionID 绑定诊断工具 ===\n\n";

// 1. 检查微信配置
echo "1. 检查微信配置\n";
echo str_repeat("-", 50) . "\n";

$miniAppId = env('WECHAT_MINI_APPID', '');
$miniSecret = env('WECHAT_MINI_SECRET', '');

echo "小程序 AppID: " . ($miniAppId ? $miniAppId : "未配置") . "\n";
echo "小程序 Secret: " . ($miniSecret ? "已配置" : "未配置") . "\n";

$notificationConfig = Db::name('notification_config')->find(1);
$officialAppId = $notificationConfig['wechat_app_id'] ?? '';
$officialSecret = $notificationConfig['wechat_app_secret'] ?? '';

echo "公众号 AppID: " . ($officialAppId ? $officialAppId : "未配置") . "\n";
echo "公众号 Secret: " . ($officialSecret ? "已配置" : "未配置") . "\n\n";

if (!$miniAppId || !$officialAppId) {
    echo "⚠️  警告：小程序或公众号配置不完整\n\n";
}

// 2. 检查绑定表数据
echo "2. 检查绑定表数据\n";
echo str_repeat("-", 50) . "\n";

$bindings = Db::name('wechat_openid_bindings')
    ->order('update_time', 'desc')
    ->limit(10)
    ->select()
    ->toArray();

echo "最近 10 条绑定记录：\n";
foreach ($bindings as $bind) {
    echo sprintf(
        "ID: %d | mini_openid: %s | mp_openid: %s | unionid: %s | 已关注: %s | 更新时间: %s\n",
        $bind['id'],
        $bind['mini_openid'] ?: '(空)',
        $bind['mp_openid'] ?: '(空)',
        $bind['unionid'] ?: '(空)',
        $bind['is_subscribed'] ? '是' : '否',
        $bind['update_time']
    );
}
echo "\n";

// 3. 统计问题数据
echo "3. 统计问题数据\n";
echo str_repeat("-", 50) . "\n";

$noUnionid = Db::name('wechat_openid_bindings')
    ->where('mini_openid', '<>', '')
    ->where('mp_openid', '<>', '')
    ->where(function($query) {
        $query->whereNull('unionid')->whereOr('unionid', '');
    })
    ->count();

echo "已绑定但缺少 unionid 的记录数: {$noUnionid}\n";

$noMpOpenid = Db::name('wechat_openid_bindings')
    ->where('mini_openid', '<>', '')
    ->where(function($query) {
        $query->whereNull('mp_openid')->whereOr('mp_openid', '');
    })
    ->count();

echo "有小程序 openid 但缺少公众号 openid 的记录数: {$noMpOpenid}\n\n";

// 4. 检查 wechat_users 表
echo "4. 检查 wechat_users 表\n";
echo str_repeat("-", 50) . "\n";

$usersWithUnionid = Db::name('wechat_users')
    ->where('unionid', '<>', '')
    ->whereNotNull('unionid')
    ->count();

$totalUsers = Db::name('wechat_users')->count();

echo "总用户数: {$totalUsers}\n";
echo "有 unionid 的用户数: {$usersWithUnionid}\n";
echo "缺少 unionid 的用户数: " . ($totalUsers - $usersWithUnionid) . "\n\n";

// 5. 检查最近的调试日志
echo "5. 检查最近的绑定调试日志\n";
echo str_repeat("-", 50) . "\n";

$debugLogs = Db::name('notification_logs')
    ->where('channel', 'wechat_debug')
    ->where('template_code', 'official_bind_debug')
    ->order('id', 'desc')
    ->limit(5)
    ->select()
    ->toArray();

if (empty($debugLogs)) {
    echo "未找到调试日志\n\n";
} else {
    echo "最近 5 条调试日志：\n";
    foreach ($debugLogs as $log) {
        $data = json_decode($log['send_data'], true);
        echo sprintf(
            "[%s] %s | 状态: %s | 错误: %s\n",
            $log['send_time'],
            $data['stage'] ?? '未知阶段',
            $log['status'] ? '成功' : '失败',
            $log['error_msg'] ?: '无'
        );
    }
    echo "\n";
}

// 6. 提供修复建议
echo "6. 修复建议\n";
echo str_repeat("-", 50) . "\n";

if (!$miniAppId || !$officialAppId) {
    echo "❌ 请先完成微信配置\n";
    echo "   - 小程序配置：在 .env 文件中设置 WECHAT_MINI_APPID 和 WECHAT_MINI_SECRET\n";
    echo "   - 公众号配置：在后台管理系统的通知配置中设置公众号 AppID 和 Secret\n\n";
}

if ($miniAppId && $officialAppId && $miniAppId === $officialAppId) {
    echo "⚠️  警告：小程序和公众号使用了相同的 AppID，这是不正确的\n";
    echo "   小程序和公众号应该有不同的 AppID\n\n";
}

echo "✓ 确保小程序和公众号已绑定到同一个微信开放平台账号\n";
echo "✓ 用户需要先关注公众号或在小程序中授权，才能获取 unionid\n";
echo "✓ 检查公众号服务器配置是否正确（Token、EncodingAESKey）\n";
echo "✓ 查看日志文件：runtime/log/ 目录下的日志文件\n\n";

echo "=== 诊断完成 ===\n";
