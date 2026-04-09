<?php
/**
 * 简单的 UnionID 测试（直接连接数据库）
 */

echo "========================================\n";
echo "UnionID 简单测试\n";
echo "========================================\n\n";

// 1. 读取数据库配置
echo "1. 读取配置\n";
echo "----------------------------------------\n";
$envFile = __DIR__ . '/tjiajiao91-main/new_system/backend/.env';
if (!file_exists($envFile)) {
    echo "✗ .env 文件不存在: {$envFile}\n";
    exit(1);
}

$envContent = file_get_contents($envFile);

// 解析配置
preg_match('/DB_HOST\s*=\s*(.+)/', $envContent, $hostMatch);
preg_match('/DB_PORT\s*=\s*(.+)/', $envContent, $portMatch);
preg_match('/DB_DATABASE\s*=\s*(.+)/', $envContent, $dbMatch);
preg_match('/DB_USERNAME\s*=\s*(.+)/', $envContent, $userMatch);
preg_match('/DB_PASSWORD\s*=\s*(.+)/', $envContent, $passMatch);
preg_match('/WECHAT_MINI_APPID\s*=\s*(.+)/', $envContent, $appidMatch);
preg_match('/WECHAT_MINI_SECRET\s*=\s*(.+)/', $envContent, $secretMatch);

$dbHost = isset($hostMatch[1]) ? trim($hostMatch[1]) : '127.0.0.1';
$dbPort = isset($portMatch[1]) ? trim($portMatch[1]) : '3306';
$dbName = isset($dbMatch[1]) ? trim($dbMatch[1]) : '';
$dbUser = isset($userMatch[1]) ? trim($userMatch[1]) : '';
$dbPass = isset($passMatch[1]) ? trim($passMatch[1]) : '';
$appid = isset($appidMatch[1]) ? trim($appidMatch[1]) : '';
$secret = isset($secretMatch[1]) ? trim($secretMatch[1]) : '';

echo "数据库主机: {$dbHost}:{$dbPort}\n";
echo "数据库名称: {$dbName}\n";
echo "数据库用户: {$dbUser}\n";
echo "小程序 AppID: {$appid}\n";
echo "小程序 Secret: " . ($secret ? substr($secret, 0, 10) . '...' : '未配置') . "\n";

// 2. 连接数据库
echo "\n2. 连接数据库\n";
echo "----------------------------------------\n";
try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ 数据库连接成功\n";
} catch (PDOException $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. 检查表结构
echo "\n3. 检查数据库表\n";
echo "----------------------------------------\n";
$tables = ['users', 'wechat_users', 'wechat_openid_bindings'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'fa_{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✓ 表 fa_{$table} 存在\n";
            
            // 检查 unionid 字段
            if ($table !== 'users') {
                $stmt = $pdo->query("SHOW COLUMNS FROM fa_{$table} LIKE 'unionid'");
                if ($stmt->rowCount() > 0) {
                    echo "  ✓ unionid 字段存在\n";
                } else {
                    echo "  ✗ unionid 字段不存在\n";
                }
            }
        } else {
            echo "✗ 表 fa_{$table} 不存在\n";
        }
    } catch (PDOException $e) {
        echo "✗ 检查表失败: " . $e->getMessage() . "\n";
    }
}

// 4. 统计 unionid 情况
echo "\n4. UnionID 统计\n";
echo "----------------------------------------\n";
try {
    // 总用户数
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fa_users");
    $totalUsers = $stmt->fetch()['count'];
    
    // 有 unionid 的用户数
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fa_wechat_users WHERE unionid IS NOT NULL AND unionid != ''");
    $usersWithUnionid = $stmt->fetch()['count'];
    
    echo "总用户数: {$totalUsers}\n";
    echo "已获取 unionid 的用户数: {$usersWithUnionid}\n";
    
    if ($totalUsers > 0) {
        $percentage = round(($usersWithUnionid / $totalUsers) * 100, 2);
        echo "获取率: {$percentage}%\n";
        
        if ($percentage < 50) {
            echo "\n⚠ 警告：unionid 获取率较低\n";
            echo "可能原因：小程序未绑定微信开放平台\n";
        } elseif ($percentage >= 90) {
            echo "\n✓ 很好：unionid 获取率很高\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ 统计失败: " . $e->getMessage() . "\n";
}

// 5. 显示最近用户的 unionid 情况
echo "\n5. 最近 10 个用户的 UnionID 情况\n";
echo "----------------------------------------\n";
try {
    $sql = "
        SELECT 
            u.id, 
            u.phone, 
            u.nickname, 
            u.openid, 
            wu.unionid, 
            u.update_time
        FROM fa_users u
        LEFT JOIN fa_wechat_users wu ON u.openid = wu.openid
        ORDER BY u.update_time DESC
        LIMIT 10
    ";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();
    
    if ($users) {
        foreach ($users as $user) {
            $hasUnionid = !empty($user['unionid']) ? '✓' : '✗';
            $phone = $user['phone'] ?? '无';
            $nickname = $user['nickname'] ?? '无';
            $unionidDisplay = !empty($user['unionid']) ? substr($user['unionid'], 0, 10) . '...' : '未获取';
            $updateTime = $user['update_time'] ?? '无';
            
            echo sprintf(
                "[%s] ID:%d | %s | %s | UnionID: %s | 更新: %s\n",
                $hasUnionid,
                $user['id'],
                $phone,
                $nickname,
                $unionidDisplay,
                $updateTime
            );
        }
    } else {
        echo "暂无用户数据\n";
    }
    
} catch (PDOException $e) {
    echo "✗ 查询失败: " . $e->getMessage() . "\n";
}

// 6. 检查绑定表
echo "\n6. 绑定表统计\n";
echo "----------------------------------------\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fa_wechat_openid_bindings");
    $totalBindings = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fa_wechat_openid_bindings WHERE unionid IS NOT NULL AND unionid != ''");
    $bindingsWithUnionid = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fa_wechat_openid_bindings WHERE mp_openid IS NOT NULL AND mp_openid != ''");
    $bindingsWithMp = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM fa_wechat_openid_bindings WHERE is_subscribed = 1");
    $subscribedBindings = $stmt->fetch()['count'];
    
    echo "绑定记录总数: {$totalBindings}\n";
    echo "已有 unionid 的记录: {$bindingsWithUnionid}\n";
    echo "已有公众号 openid 的记录: {$bindingsWithMp}\n";
    echo "已关注公众号的记录: {$subscribedBindings}\n";
    
} catch (PDOException $e) {
    echo "✗ 查询失败: " . $e->getMessage() . "\n";
}

// 7. 检查公众号配置
echo "\n7. 公众号配置\n";
echo "----------------------------------------\n";
try {
    $stmt = $pdo->query("SELECT * FROM fa_notification_config WHERE id = 1");
    $config = $stmt->fetch();
    
    if ($config) {
        $mpAppId = $config['wechat_app_id'] ?? '';
        $mpSecret = $config['wechat_app_secret'] ?? '';
        
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
            }
        }
    } else {
        echo "✗ 公众号配置不存在\n";
    }
} catch (PDOException $e) {
    echo "✗ 查询失败: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "测试完成\n";
echo "========================================\n\n";

echo "结论和建议：\n";
echo "----------------------------------------\n";

if ($usersWithUnionid > 0) {
    echo "✓ 已有部分用户获取到 unionid\n";
    echo "  说明：代码逻辑正常，可以保存 unionid\n\n";
} else {
    echo "✗ 暂无用户获取到 unionid\n";
    echo "  可能原因：\n";
    echo "  1. 小程序未绑定微信开放平台\n";
    echo "  2. 用户未关注公众号\n";
    echo "  3. 还没有用户登录过\n\n";
}

echo "下一步操作：\n";
echo "1. 确认小程序是否已绑定微信开放平台\n";
echo "   访问：https://open.weixin.qq.com/\n";
echo "   查看：管理中心 -> 公众账号/小程序\n\n";

echo "2. 测试登录时是否返回 unionid\n";
echo "   - 在小程序中登录\n";
echo "   - 查看日志：tjiajiao91-main/new_system/backend/runtime/log/\n";
echo "   - 搜索关键词：has_unionid\n\n";

echo "3. 如果微信返回了 unionid\n";
echo "   - 代码会自动保存到数据库\n";
echo "   - 无需任何额外开发\n\n";

echo "4. 如果微信未返回 unionid\n";
echo "   - 需要完成开放平台绑定\n";
echo "   - 或使用公众号扫码绑定方案\n\n";

echo "========================================\n";
