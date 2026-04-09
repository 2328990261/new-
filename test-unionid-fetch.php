<?php
/**
 * UnionID 获取测试脚本
 * 用于测试微信接口是否返回 unionid
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

echo "========================================\n";
echo "UnionID 获取测试\n";
echo "========================================\n\n";

// 获取配置
$miniAppId = env('WECHAT_MINI_APPID', '');
$miniSecret = env('WECHAT_MINI_SECRET', '');

if (empty($miniAppId) || empty($miniSecret)) {
    echo "❌ 错误：微信小程序配置未设置\n";
    echo "请在 .env 文件中配置：\n";
    echo "WECHAT_MINI_APPID=你的小程序AppID\n";
    echo "WECHAT_MINI_SECRET=你的小程序Secret\n";
    exit(1);
}

echo "✓ 小程序配置已加载\n";
echo "AppID: {$miniAppId}\n\n";

// 提示用户输入测试 code
echo "请在小程序中调用 wx.login() 获取 code，然后输入：\n";
echo "Code: ";
$code = trim(fgets(STDIN));

if (empty($code)) {
    echo "❌ 错误：code 不能为空\n";
    exit(1);
}

echo "\n正在请求微信接口...\n\n";

// 调用微信接口
$url = "https://api.weixin.qq.com/sns/jscode2session";
$params = [
    'appid' => $miniAppId,
    'secret' => $miniSecret,
    'js_code' => $code,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP 状态码: {$httpCode}\n";
echo "原始响应:\n";
echo $response . "\n\n";

$result = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ 错误：响应不是有效的 JSON\n";
    exit(1);
}

echo "========================================\n";
echo "响应解析\n";
echo "========================================\n\n";

// 检查错误
if (isset($result['errcode']) && $result['errcode'] != 0) {
    echo "❌ 微信接口返回错误\n";
    echo "错误码: {$result['errcode']}\n";
    echo "错误信息: " . ($result['errmsg'] ?? '未知错误') . "\n\n";
    
    // 常见错误说明
    $errorMessages = [
        40029 => 'code 无效或已过期，请重新获取',
        40163 => 'code 已被使用，请重新获取',
        40125 => 'AppSecret 错误，请检查配置',
        -1 => '系统繁忙，请稍后重试'
    ];
    
    if (isset($errorMessages[$result['errcode']])) {
        echo "说明: {$errorMessages[$result['errcode']]}\n";
    }
    
    exit(1);
}

// 显示结果
echo "✓ 请求成功\n\n";

$hasOpenid = isset($result['openid']) && !empty($result['openid']);
$hasSessionKey = isset($result['session_key']) && !empty($result['session_key']);
$hasUnionid = isset($result['unionid']) && !empty($result['unionid']);

echo "OpenID: " . ($hasOpenid ? '✓ ' . $result['openid'] : '✗ 未返回') . "\n";
echo "Session Key: " . ($hasSessionKey ? '✓ ' . substr($result['session_key'], 0, 10) . '...' : '✗ 未返回') . "\n";
echo "UnionID: " . ($hasUnionid ? '✓ ' . $result['unionid'] : '✗ 未返回') . "\n\n";

if (!$hasUnionid) {
    echo "========================================\n";
    echo "UnionID 未返回 - 原因分析\n";
    echo "========================================\n\n";
    
    echo "微信未返回 unionid 的可能原因：\n\n";
    
    echo "1. 小程序和公众号未绑定到同一微信开放平台\n";
    echo "   解决方案：\n";
    echo "   - 访问 https://open.weixin.qq.com/\n";
    echo "   - 完成企业认证（300元/年）\n";
    echo "   - 将小程序和公众号绑定到同一账号\n\n";
    
    echo "2. 用户从未关注过同主体的公众号\n";
    echo "   解决方案：\n";
    echo "   - 引导用户关注公众号\n";
    echo "   - 使用公众号扫码绑定功能\n";
    echo "   - 参考：UnionID获取实现方案.md\n\n";
    
    echo "3. 开放平台账号未完成认证\n";
    echo "   解决方案：\n";
    echo "   - 完成企业认证\n";
    echo "   - 个人开发者无法使用开放平台\n\n";
    
    // 检查数据库中是否有该用户的 unionid
    if ($hasOpenid) {
        echo "========================================\n";
        echo "数据库检查\n";
        echo "========================================\n\n";
        
        try {
            $openid = $result['openid'];
            
            // 检查 wechat_users 表
            $wechatUser = Db::name('wechat_users')->where('openid', $openid)->find();
            if ($wechatUser) {
                echo "✓ 在 wechat_users 表中找到该用户\n";
                if (!empty($wechatUser['unionid'])) {
                    echo "✓ 数据库中已有 unionid: {$wechatUser['unionid']}\n";
                    echo "  （可能是之前通过公众号获取的）\n";
                } else {
                    echo "✗ 数据库中也没有 unionid\n";
                }
            } else {
                echo "✗ 在 wechat_users 表中未找到该用户\n";
            }
            
            // 检查绑定表
            $binding = Db::name('wechat_openid_bindings')->where('mini_openid', $openid)->find();
            if ($binding) {
                echo "✓ 在 wechat_openid_bindings 表中找到绑定记录\n";
                if (!empty($binding['unionid'])) {
                    echo "✓ 绑定表中有 unionid: {$binding['unionid']}\n";
                } else {
                    echo "✗ 绑定表中没有 unionid\n";
                }
                if (!empty($binding['mp_openid'])) {
                    echo "✓ 已绑定公众号 openid: {$binding['mp_openid']}\n";
                    echo "  是否已关注: " . ($binding['is_subscribed'] ? '是' : '否') . "\n";
                } else {
                    echo "✗ 未绑定公众号 openid\n";
                }
            } else {
                echo "✗ 在 wechat_openid_bindings 表中未找到绑定记录\n";
            }
            
        } catch (\Exception $e) {
            echo "数据库查询失败: " . $e->getMessage() . "\n";
        }
    }
    
} else {
    echo "========================================\n";
    echo "✓ 成功获取 UnionID\n";
    echo "========================================\n\n";
    
    echo "恭喜！微信已返回 unionid，说明：\n";
    echo "1. 小程序和公众号已正确绑定到同一开放平台\n";
    echo "2. 或者用户已关注过同主体的公众号\n\n";
    
    // 保存到数据库
    if ($hasOpenid) {
        echo "是否要将此 unionid 保存到数据库？(y/n): ";
        $save = trim(fgets(STDIN));
        
        if (strtolower($save) === 'y') {
            try {
                $openid = $result['openid'];
                $unionid = $result['unionid'];
                $now = date('Y-m-d H:i:s');
                
                // 更新或插入 wechat_users
                $existing = Db::name('wechat_users')->where('openid', $openid)->find();
                if ($existing) {
                    Db::name('wechat_users')->where('openid', $openid)->update([
                        'unionid' => $unionid,
                        'update_time' => $now
                    ]);
                    echo "✓ 已更新 wechat_users 表\n";
                } else {
                    Db::name('wechat_users')->insert([
                        'openid' => $openid,
                        'unionid' => $unionid,
                        'subscribe' => 0,
                        'create_time' => $now,
                        'update_time' => $now
                    ]);
                    echo "✓ 已插入 wechat_users 表\n";
                }
                
                // 更新或插入绑定表
                $binding = Db::name('wechat_openid_bindings')->where('mini_openid', $openid)->find();
                if ($binding) {
                    Db::name('wechat_openid_bindings')->where('mini_openid', $openid)->update([
                        'unionid' => $unionid,
                        'update_time' => $now
                    ]);
                    echo "✓ 已更新 wechat_openid_bindings 表\n";
                } else {
                    Db::name('wechat_openid_bindings')->insert([
                        'mini_openid' => $openid,
                        'unionid' => $unionid,
                        'scene_key' => 'test_' . $openid,
                        'is_subscribed' => 0,
                        'create_time' => $now,
                        'update_time' => $now
                    ]);
                    echo "✓ 已插入 wechat_openid_bindings 表\n";
                }
                
                echo "\n✓ 数据保存成功\n";
                
            } catch (\Exception $e) {
                echo "❌ 保存失败: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n========================================\n";
echo "测试完成\n";
echo "========================================\n\n";

echo "下一步建议：\n";
if (!$hasUnionid) {
    echo "1. 检查微信开放平台绑定状态\n";
    echo "2. 如果无法绑定开放平台，使用公众号扫码方案\n";
    echo "3. 参考文档：UnionID获取实现方案.md\n";
    echo "4. 运行诊断脚本：php check-unionid-status.php\n";
} else {
    echo "1. 在小程序中正常使用登录功能\n";
    echo "2. 后端会自动保存 unionid\n";
    echo "3. 可以使用 unionid 关联公众号推送消息\n";
}

echo "\n";
