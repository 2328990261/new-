<?php
/**
 * 通过公众号接口补全现有用户的 unionid
 * 适用于：已关注公众号但没有 unionid 的用户
 */

echo "========================================\n";
echo "补全现有用户 UnionID\n";
echo "========================================\n\n";

// 读取配置
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

$dbHost = isset($hostMatch[1]) ? trim($hostMatch[1]) : '127.0.0.1';
$dbPort = isset($portMatch[1]) ? trim($portMatch[1]) : '3306';
$dbName = isset($dbMatch[1]) ? trim($dbMatch[1]) : '';
$dbUser = isset($userMatch[1]) ? trim($userMatch[1]) : '';
$dbPass = isset($passMatch[1]) ? trim($passMatch[1]) : '';

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

// 获取公众号配置
echo "1. 获取公众号配置\n";
echo "----------------------------------------\n";
$stmt = $pdo->query("SELECT * FROM fa_notification_config WHERE id = 1");
$config = $stmt->fetch();

if (!$config) {
    echo "✗ 公众号配置不存在\n";
    exit(1);
}

$mpAppId = $config['wechat_app_id'] ?? '';
$mpSecret = $config['wechat_app_secret'] ?? '';

if (empty($mpAppId) || empty($mpSecret)) {
    echo "✗ 公众号 AppID 或 Secret 未配置\n";
    exit(1);
}

echo "✓ 公众号 AppID: {$mpAppId}\n";
echo "✓ 公众号 Secret 已配置\n\n";

// 获取 access_token
echo "2. 获取公众号 access_token\n";
echo "----------------------------------------\n";

// 先尝试从数据库缓存获取
$cachedToken = $config['wechat_access_token'] ?? '';
$tokenExpire = $config['wechat_access_token_expire'] ?? 0;

if ($cachedToken && $tokenExpire > time()) {
    $accessToken = $cachedToken;
    echo "✓ 使用缓存的 access_token\n";
} else {
    // 重新获取
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$mpAppId}&secret={$mpSecret}";
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    
    if (isset($result['errcode']) && $result['errcode'] != 0) {
        echo "✗ 获取 access_token 失败: " . ($result['errmsg'] ?? '未知错误') . "\n";
        exit(1);
    }
    
    $accessToken = $result['access_token'] ?? '';
    $expiresIn = $result['expires_in'] ?? 7200;
    
    if (empty($accessToken)) {
        echo "✗ access_token 为空\n";
        exit(1);
    }
    
    // 更新缓存
    $expireTime = time() + $expiresIn - 300; // 提前5分钟过期
    $stmt = $pdo->prepare("UPDATE fa_notification_config SET wechat_access_token = ?, wechat_access_token_expire = ? WHERE id = 1");
    $stmt->execute([$accessToken, $expireTime]);
    
    echo "✓ 获取新的 access_token 成功\n";
}

echo "Access Token: " . substr($accessToken, 0, 20) . "...\n\n";

// 查找需要补全 unionid 的用户
echo "3. 查找需要补全 unionid 的用户\n";
echo "----------------------------------------\n";
$sql = "
    SELECT wu.id, wu.openid, wu.subscribe, u.phone, u.nickname
    FROM fa_wechat_users wu
    LEFT JOIN fa_users u ON wu.openid = u.openid
    WHERE (wu.unionid IS NULL OR wu.unionid = '')
    AND wu.subscribe = 1
    ORDER BY wu.id
";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

if (empty($users)) {
    echo "✓ 没有需要补全的用户\n\n";
    exit(0);
}

echo "找到 " . count($users) . " 个需要补全 unionid 的用户\n\n";

// 逐个获取 unionid
echo "4. 获取并补全 unionid\n";
echo "========================================\n";

$successCount = 0;
$failCount = 0;

foreach ($users as $user) {
    $openid = $user['openid'];
    $phone = $user['phone'] ?? '无';
    $nickname = $user['nickname'] ?? '无';
    
    echo "处理用户: {$nickname} ({$phone})\n";
    echo "  OpenID: {$openid}\n";
    
    // 调用公众号接口获取用户信息
    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$accessToken}&openid={$openid}&lang=zh_CN";
    $response = @file_get_contents($url);
    
    if ($response === false) {
        echo "  ✗ 请求失败\n\n";
        $failCount++;
        continue;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['errcode']) && $result['errcode'] != 0) {
        echo "  ✗ 错误: " . ($result['errmsg'] ?? '未知错误') . "\n\n";
        $failCount++;
        continue;
    }
    
    $unionid = $result['unionid'] ?? '';
    
    if (empty($unionid)) {
        echo "  ⚠ 微信未返回 unionid（可能未绑定开放平台）\n\n";
        $failCount++;
        continue;
    }
    
    echo "  ✓ 获取到 UnionID: {$unionid}\n";
    
    // 更新数据库
    try {
        $now = date('Y-m-d H:i:s');
        
        // 更新 wechat_users 表
        $stmt = $pdo->prepare("UPDATE fa_wechat_users SET unionid = ?, update_time = ? WHERE openid = ?");
        $stmt->execute([$unionid, $now, $openid]);
        
        // 更新或插入 wechat_openid_bindings 表
        $stmt = $pdo->prepare("SELECT id FROM fa_wechat_openid_bindings WHERE mini_openid = ?");
        $stmt->execute([$openid]);
        $binding = $stmt->fetch();
        
        if ($binding) {
            $stmt = $pdo->prepare("UPDATE fa_wechat_openid_bindings SET unionid = ?, update_time = ? WHERE mini_openid = ?");
            $stmt->execute([$unionid, $now, $openid]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fa_wechat_openid_bindings (mini_openid, unionid, scene_key, is_subscribed, create_time, update_time) VALUES (?, ?, ?, 1, ?, ?)");
            $stmt->execute([$openid, $unionid, '补全_' . $openid, $now, $now]);
        }
        
        echo "  ✓ 数据库更新成功\n\n";
        $successCount++;
        
    } catch (PDOException $e) {
        echo "  ✗ 数据库更新失败: " . $e->getMessage() . "\n\n";
        $failCount++;
    }
    
    // 避免请求过快
    usleep(200000); // 0.2秒
}

echo "========================================\n";
echo "补全完成\n";
echo "========================================\n\n";

echo "结果统计：\n";
echo "  成功: {$successCount} 个\n";
echo "  失败: {$failCount} 个\n";
echo "  总计: " . count($users) . " 个\n\n";

if ($successCount > 0) {
    echo "✓ 已成功补全 {$successCount} 个用户的 unionid\n";
    echo "  运行以下命令验证：\n";
    echo "  php simple-unionid-test.php\n\n";
}

if ($failCount > 0) {
    echo "⚠ 有 {$failCount} 个用户补全失败\n";
    echo "  可能原因：\n";
    echo "  - 开放平台绑定未生效\n";
    echo "  - 用户未关注公众号\n";
    echo "  - 网络问题\n\n";
}

echo "========================================\n";
