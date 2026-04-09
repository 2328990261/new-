<?php
/**
 * 快速测试 UnionID 配置
 */

echo "========================================\n";
echo "UnionID 快速测试\n";
echo "========================================\n\n";

// 1. 检查目录结构
echo "1. 检查目录结构\n";
echo "----------------------------------------\n";
$backendPath = __DIR__ . '/tjiajiao91-main/new_system/backend';
if (is_dir($backendPath)) {
    echo "✓ 后端目录存在: {$backendPath}\n";
} else {
    echo "✗ 后端目录不存在: {$backendPath}\n";
    exit(1);
}

$envFile = $backendPath . '/.env';
if (file_exists($envFile)) {
    echo "✓ .env 文件存在\n";
} else {
    echo "✗ .env 文件不存在\n";
    exit(1);
}

// 2. 读取配置
echo "\n2. 读取微信配置\n";
echo "----------------------------------------\n";
$envContent = file_get_contents($envFile);
preg_match('/WECHAT_MINI_APPID\s*=\s*(.+)/', $envContent, $appidMatch);
preg_match('/WECHAT_MINI_SECRET\s*=\s*(.+)/', $envContent, $secretMatch);

$appid = isset($appidMatch[1]) ? trim($appidMatch[1]) : '';
$secret = isset($secretMatch[1]) ? trim($secretMatch[1]) : '';

if ($appid) {
    echo "✓ 小程序 AppID: {$appid}\n";
} else {
    echo "✗ 小程序 AppID 未配置\n";
}

if ($secret) {
    echo "✓ 小程序 Secret: " . substr($secret, 0, 10) . "...\n";
} else {
    echo "✗ 小程序 Secret 未配置\n";
}

if (!$appid || !$secret) {
    echo "\n请在 .env 文件中配置：\n";
    echo "WECHAT_MINI_APPID=你的小程序AppID\n";
    echo "WECHAT_MINI_SECRET=你的小程序Secret\n";
    exit(1);
}

// 3. 加载 ThinkPHP
echo "\n3. 加载 ThinkPHP\n";
echo "----------------------------------------\n";
try {
    require $backendPath . '/vendor/autoload.php';
    $app = require $backendPath . '/app/app.php';
    $app->initialize();
    echo "✓ ThinkPHP 加载成功\n";
} catch (\Exception $e) {
    echo "✗ ThinkPHP 加载失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. 检查数据库连接
echo "\n4. 检查数据库连接\n";
echo "----------------------------------------\n";
try {
    $db = \think\facade\Db::connect();
    $db->query('SELECT 1');
    echo "✓ 数据库连接成功\n";
} catch (\Exception $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. 检查数据库表
echo "\n5. 检查数据库表\n";
echo "----------------------------------------\n";
$tables = ['users', 'wechat_users', 'wechat_openid_bindings'];
foreach ($tables as $table) {
    try {
        $exists = \think\facade\Db::query("SHOW TABLES LIKE 'fa_{$table}'");
        if ($exists) {
            echo "✓ 表 fa_{$table} 存在\n";
        } else {
            echo "✗ 表 fa_{$table} 不存在\n";
        }
    } catch (\Exception $e) {
        echo "✗ 检查表 fa_{$table} 失败: " . $e->getMessage() . "\n";
    }
}

// 6. 统计 unionid 情况
echo "\n6. UnionID 统计\n";
echo "----------------------------------------\n";
try {
    $totalUsers = \think\facade\Db::name('users')->count();
    $usersWithUnionid = \think\facade\Db::name('wechat_users')
        ->where('unionid', '<>', '')
        ->whereNotNull('unionid')
        ->count();
    
    echo "总用户数: {$totalUsers}\n";
    echo "已获取 unionid 的用户数: {$usersWithUnionid}\n";
    
    if ($totalUsers > 0) {
        $percentage = round(($usersWithUnionid / $totalUsers) * 100, 2);
        echo "获取率: {$percentage}%\n";
    }
    
    // 显示最近 5 个用户的 unionid 情况
    echo "\n最近 5 个用户的 UnionID 情况：\n";
    $recentUsers = \think\facade\Db::name('users')
        ->alias('u')
        ->leftJoin('wechat_users wu', 'u.openid = wu.openid')
        ->field('u.id, u.phone, u.nickname, u.openid, wu.unionid, u.update_time')
        ->order('u.update_time', 'desc')
        ->limit(5)
        ->select()
        ->toArray();
    
    if ($recentUsers) {
        foreach ($recentUsers as $user) {
            $hasUnionid = !empty($user['unionid']) ? '✓' : '✗';
            $phone = $user['phone'] ?? '无';
            $nickname = $user['nickname'] ?? '无';
            $unionidDisplay = !empty($user['unionid']) ? substr($user['unionid'], 0, 10) . '...' : '未获取';
            
            echo sprintf(
                "[%s] ID:%d | %s | UnionID: %s\n",
                $hasUnionid,
                $user['id'],
                $phone,
                $unionidDisplay
            );
        }
    } else {
        echo "暂无用户数据\n";
    }
    
} catch (\Exception $e) {
    echo "✗ 统计失败: " . $e->getMessage() . "\n";
}

// 7. 检查公众号配置
echo "\n7. 检查公众号配置\n";
echo "----------------------------------------\n";
try {
    $notificationConfig = \think\facade\Db::name('notification_config')->where('id', 1)->find();
    if ($notificationConfig) {
        $mpAppId = $notificationConfig['wechat_app_id'] ?? '';
        $mpSecret = $notificationConfig['wechat_app_secret'] ?? '';
        
        if ($mpAppId) {
            echo "✓ 公众号 AppID: {$mpAppId}\n";
        } else {
            echo "✗ 公众号 AppID 未配置\n";
        }
        
        if ($mpSecret) {
            echo "✓ 公众号 Secret 已配置\n";
        } else {
            echo "✗ 公众号 Secret 未配置\n";
        }
        
        // 检查是否同一主体
        if ($mpAppId && $appid) {
            if ($mpAppId === $appid) {
                echo "✗ 小程序和公众号 AppID 相同（配置错误）\n";
            } else {
                echo "✓ 小程序和公众号 AppID 不同\n";
                echo "\n⚠ 重要提示：\n";
                echo "请确认小程序和公众号已绑定到同一微信开放平台\n";
                echo "访问：https://open.weixin.qq.com/\n";
            }
        }
    } else {
        echo "✗ 公众号配置表不存在\n";
    }
} catch (\Exception $e) {
    echo "✗ 查询公众号配置失败: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "测试完成\n";
echo "========================================\n\n";

echo "下一步：\n";
echo "1. 如果 unionid 获取率低，需要完成微信开放平台绑定\n";
echo "2. 访问 https://open.weixin.qq.com/ 绑定小程序和公众号\n";
echo "3. 绑定后，用户登录时会自动获取 unionid\n";
echo "4. 查看日志：tail -f tjiajiao91-main/new_system/backend/runtime/log/\$(date +%Y%m)/\$(date +%d).log\n";
echo "\n";
