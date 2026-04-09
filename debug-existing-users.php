<?php
/**
 * 调试现有用户的 unionid 情况
 */

echo "========================================\n";
echo "现有用户 UnionID 调试\n";
echo "========================================\n\n";

// 读取数据库配置
$envFile = __DIR__ . '/tjiajiao91-main/new_system/backend/.env';
if (!file_exists($envFile)) {
    echo "✗ .env 文件不存在\n";
    exit(1);
}

$envContent = file_get_contents($envFile);
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

// 连接数据库
try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ 数据库连接成功\n\n";
} catch (PDOException $e) {
    echo "✗ 数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 查询所有用户详情
echo "1. 所有用户详细信息\n";
echo "========================================\n";
$sql = "
    SELECT 
        u.id,
        u.openid as user_openid,
        u.phone,
        u.nickname,
        u.platform,
        u.create_time,
        u.update_time,
        wu.openid as wechat_openid,
        wu.unionid,
        wu.subscribe,
        wu.create_time as wechat_create_time
    FROM fa_users u
    LEFT JOIN fa_wechat_users wu ON u.openid = wu.openid
    ORDER BY u.id
";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

if (empty($users)) {
    echo "没有找到任何用户\n\n";
} else {
    foreach ($users as $user) {
        echo "用户 ID: {$user['id']}\n";
        echo "  手机号: " . ($user['phone'] ?? '无') . "\n";
        echo "  昵称: " . ($user['nickname'] ?? '无') . "\n";
        echo "  平台: " . ($user['platform'] ?? '无') . "\n";
        echo "  用户表 OpenID: " . ($user['user_openid'] ?? '无') . "\n";
        echo "  微信表 OpenID: " . ($user['wechat_openid'] ?? '无') . "\n";
        echo "  UnionID: " . ($user['unionid'] ?? '无') . "\n";
        echo "  是否关注公众号: " . ($user['subscribe'] ? '是' : '否') . "\n";
        echo "  用户创建时间: " . ($user['create_time'] ?? '无') . "\n";
        echo "  微信记录创建时间: " . ($user['wechat_create_time'] ?? '无') . "\n";
        echo "  ---\n";
    }
}

// 检查是否有 openid 但没有 wechat_users 记录的情况
echo "\n2. 检查数据一致性\n";
echo "========================================\n";
$sql = "
    SELECT u.id, u.openid, u.phone, u.nickname
    FROM fa_users u
    WHERE u.openid IS NOT NULL AND u.openid != ''
    AND NOT EXISTS (
        SELECT 1 FROM fa_wechat_users wu WHERE wu.openid = u.openid
    )
";
$stmt = $pdo->query($sql);
$missingWechatUsers = $stmt->fetchAll();

if (empty($missingWechatUsers)) {
    echo "✓ 所有有 openid 的用户都在 wechat_users 表中\n";
} else {
    echo "⚠ 发现 " . count($missingWechatUsers) . " 个用户有 openid 但不在 wechat_users 表中：\n";
    foreach ($missingWechatUsers as $user) {
        echo "  - ID: {$user['id']}, OpenID: {$user['openid']}, 手机: {$user['phone']}\n";
    }
    echo "\n这些用户需要重新登录才能获取 unionid\n";
}

// 检查 openid 格式
echo "\n3. 检查 OpenID 格式\n";
echo "========================================\n";
$sql = "SELECT id, openid, phone FROM fa_users WHERE openid IS NOT NULL AND openid != ''";
$stmt = $pdo->query($sql);
$usersWithOpenid = $stmt->fetchAll();

foreach ($usersWithOpenid as $user) {
    $openid = $user['openid'];
    $length = strlen($openid);
    $prefix = substr($openid, 0, 1);
    
    echo "用户 ID {$user['id']} ({$user['phone']})\n";
    echo "  OpenID: {$openid}\n";
    echo "  长度: {$length}\n";
    echo "  前缀: {$prefix}\n";
    
    // 微信小程序 openid 通常以 'o' 开头，长度 28 字符
    if ($prefix === 'o' && $length === 28) {
        echo "  ✓ 格式正常（微信小程序 openid）\n";
    } else {
        echo "  ⚠ 格式异常（可能不是微信小程序 openid）\n";
    }
    echo "\n";
}

// 测试微信接口（使用一个真实的 openid）
echo "4. 测试微信接口\n";
echo "========================================\n";

if (!empty($usersWithOpenid)) {
    $testUser = $usersWithOpenid[0];
    $testOpenid = $testUser['openid'];
    
    echo "使用用户 ID {$testUser['id']} 的 openid 测试\n";
    echo "OpenID: {$testOpenid}\n\n";
    
    echo "提示：要测试微信是否返回 unionid，需要：\n";
    echo "1. 在小程序中调用 wx.login() 获取新的 code\n";
    echo "2. 使用该 code 调用后端接口\n";
    echo "3. 查看后端日志中是否有 'has_unionid: true'\n\n";
    
    echo "或者，让用户重新登录一次，然后运行：\n";
    echo "php simple-unionid-test.php\n";
    echo "查看 unionid 是否被保存\n";
}

// 检查最近的登录日志
echo "\n5. 检查最近的登录相关日志\n";
echo "========================================\n";
$logDir = __DIR__ . '/tjiajiao91-main/new_system/backend/runtime/log';
if (is_dir($logDir)) {
    echo "日志目录: {$logDir}\n";
    echo "建议查看最近的日志文件，搜索关键词：\n";
    echo "  - 'jscode2session'\n";
    echo "  - 'has_unionid'\n";
    echo "  - 'syncMiniUnionid'\n";
    echo "  - '登录成功'\n\n";
    
    echo "Windows 命令：\n";
    echo "  Get-ChildItem \"{$logDir}\" -Recurse -Filter \"*.log\" | Select-String -Pattern \"jscode2session|unionid\" | Select-Object -Last 20\n\n";
} else {
    echo "⚠ 日志目录不存在\n";
}

echo "\n========================================\n";
echo "调试完成\n";
echo "========================================\n\n";

echo "结论：\n";
echo "----------------------------------------\n";

if (empty($users)) {
    echo "❌ 系统中没有用户，无法测试\n";
    echo "   建议：在小程序中注册一个新用户\n\n";
} elseif (count($missingWechatUsers) > 0) {
    echo "⚠️  有用户缺少 wechat_users 记录\n";
    echo "   原因：这些用户是在代码改进前注册的\n";
    echo "   解决：让这些用户重新登录一次\n\n";
} else {
    echo "✓ 数据结构正常\n\n";
}

echo "下一步操作：\n";
echo "1. 让一个用户在小程序中重新登录\n";
echo "2. 登录后立即运行：php simple-unionid-test.php\n";
echo "3. 查看该用户是否获取到 unionid\n";
echo "4. 如果获取到了，说明开放平台绑定成功，代码正常工作\n";
echo "5. 如果没有获取到，查看日志文件找出原因\n\n";

echo "如果开放平台已绑定但仍未获取 unionid，可能原因：\n";
echo "- 绑定后需要等待几分钟生效\n";
echo "- 需要用户重新授权登录（不是静默登录）\n";
echo "- 检查开放平台中小程序和公众号是否真的在同一账号下\n\n";
