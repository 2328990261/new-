<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 微信网页授权控制器
 */
class WechatAuth extends BaseController
{
    /**
     * 引导用户授权（第一步）
     * 前端调用此接口获取授权URL
     */
    public function authorize()
    {
        try {
            // 获取配置
            $config = Db::name('notification_config')->find(1);
            if (!$config || !$config['wechat_app_id']) {
                return json(['success' => false, 'error' => '微信配置未完成']);
            }
            
            // 构建回调地址
            // 优先使用配置的回调域名，如果没有配置则使用当前域名
            $callbackDomain = !empty($config['wechat_callback_domain']) 
                ? rtrim($config['wechat_callback_domain'], '/') 
                : $this->request->domain();
            
            $redirectUri = $this->request->param('redirect_uri');
            if (empty($redirectUri)) {
                $redirectUri = $callbackDomain . '/api/wechat/callback';
            }
            
            $appId = $config['wechat_app_id'];
            
            // 保存state参数用于验证
            $state = md5(uniqid() . time());
            
            // 构建授权URL
            $authUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?" . http_build_query([
                'appid' => $appId,
                'redirect_uri' => urlencode($redirectUri),
                'response_type' => 'code',
                'scope' => 'snsapi_base', // 静默授权，不需要用户确认
                'state' => $state
            ]) . '#wechat_redirect';
            
            return json([
                'success' => true,
                'data' => [
                    'auth_url' => $authUrl,
                    'state' => $state
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 授权回调处理（第二步）
     * 微信会重定向到这里，带上code参数
     */
    public function callback()
    {
        try {
            $code = $this->request->param('code');
            $state = $this->request->param('state');
            
            if (empty($code)) {
                return $this->renderResult(false, '授权失败：未获取到code');
            }
            
            // 获取配置
            $config = Db::name('notification_config')->find(1);
            if (!$config) {
                return $this->renderResult(false, '微信配置未完成');
            }
            
            // 用code换取access_token和openid
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?" . http_build_query([
                'appid' => $config['wechat_app_id'],
                'secret' => $config['wechat_app_secret'],
                'code' => $code,
                'grant_type' => 'authorization_code'
            ]);
            
            $result = file_get_contents($url);
            $data = json_decode($result, true);
            
            if (isset($data['errcode'])) {
                return $this->renderResult(false, '获取OpenID失败：' . ($data['errmsg'] ?? '未知错误'));
            }
            
            $openid = $data['openid'];
            $accessToken = $data['access_token'];
            
            // 获取用户信息（可选，如果使用snsapi_userinfo）
            $userInfo = $this->getUserInfo($accessToken, $openid);
            
            // 保存或更新用户信息
            $this->saveWechatUser($openid, $userInfo);
            
            // 返回成功页面
            return $this->renderResult(true, '授权成功', [
                'openid' => $openid,
                'nickname' => $userInfo['nickname'] ?? ''
            ]);
            
        } catch (\Exception $e) {
            return $this->renderResult(false, '授权失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取用户详细信息（需要snsapi_userinfo授权）
     */
    private function getUserInfo($accessToken, $openid)
    {
        try {
            $url = "https://api.weixin.qq.com/sns/userinfo?" . http_build_query([
                'access_token' => $accessToken,
                'openid' => $openid,
                'lang' => 'zh_CN'
            ]);
            
            $result = file_get_contents($url);
            $data = json_decode($result, true);
            
            if (isset($data['errcode'])) {
                return [];
            }
            
            return $data;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 保存微信用户信息
     */
    private function saveWechatUser($openid, $userInfo = [])
    {
        try {
            $existing = Db::name('wechat_users')->where('openid', $openid)->find();
            
            $data = [
                'openid' => $openid,
                'nickname' => $userInfo['nickname'] ?? '',
                'headimgurl' => $userInfo['headimgurl'] ?? '',
                'subscribe' => 1,
                'subscribe_time' => time()
            ];
            
            if ($existing) {
                // 更新
                Db::name('wechat_users')->where('openid', $openid)->update($data);
            } else {
                // 新增
                $data['create_time'] = date('Y-m-d H:i:s');
                Db::name('wechat_users')->insert($data);
            }
            
        } catch (\Exception $e) {
            trace('保存微信用户信息失败: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 渲染结果页面
     */
    private function renderResult($success, $message, $data = [])
    {
        $openid = $data['openid'] ?? '';
        $nickname = $data['nickname'] ?? '';
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信授权</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .success { color: #52c41a; }
        .error { color: #ff4d4f; }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        .message {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .info-item {
            margin: 8px 0;
            font-size: 14px;
            color: #666;
        }
        .info-label {
            font-weight: bold;
            color: #333;
        }
        .openid {
            background: #fff;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            color: #1890ff;
            margin-top: 5px;
        }
        .btn {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .tip {
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
HTML;
        
        if ($success) {
            $html .= <<<HTML
        <div class="icon success">✅</div>
        <h1>授权成功</h1>
        <p class="message">{$message}</p>
        <div class="info">
            <div class="info-item">
                <span class="info-label">昵称：</span>{$nickname}
            </div>
            <div class="info-item">
                <span class="info-label">OpenID：</span>
                <div class="openid">{$openid}</div>
            </div>
        </div>
        <p class="tip">您的微信已绑定成功，后续可以接收通知消息。</p>
        <a href="javascript:WeixinJSBridge.call('closeWindow')" class="btn">关闭</a>
HTML;
        } else {
            $html .= <<<HTML
        <div class="icon error">❌</div>
        <h1>授权失败</h1>
        <p class="message">{$message}</p>
        <p class="tip">请稍后重试或联系客服</p>
        <a href="javascript:history.back()" class="btn">返回</a>
HTML;
        }
        
        $html .= <<<HTML
    </div>
    <script>
        // 3秒后自动关闭（如果在微信中）
        if (typeof WeixinJSBridge !== 'undefined') {
            setTimeout(function() {
                WeixinJSBridge.call('closeWindow');
            }, 3000);
        }
        
        // 保存OpenID到localStorage供前端使用
        if ('{$success}' === '1' && '{$openid}') {
            localStorage.setItem('wechat_openid', '{$openid}');
            localStorage.setItem('wechat_nickname', '{$nickname}');
        }
    </script>
</body>
</html>
HTML;
        
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
    
    /**
     * 检查用户是否已授权
     */
    public function checkAuth()
    {
        try {
            $openid = $this->request->param('openid');
            
            if (empty($openid)) {
                return json(['success' => false, 'authorized' => false]);
            }
            
            $user = Db::name('wechat_users')->where('openid', $openid)->find();
            
            return json([
                'success' => true,
                'authorized' => !empty($user),
                'data' => $user ?: null
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 模拟授权（仅测试用）
     * 用于本地开发测试，无需真实微信授权
     */
    public function mockAuth()
    {
        // 仅在调试模式下可用
        if (!config('app.app_debug')) {
            return json(['success' => false, 'error' => '此接口仅在开发环境可用']);
        }
        
        try {
            // 获取参数或生成随机值
            $openid = $this->request->param('openid');
            if (empty($openid)) {
                $openid = 'oTest_' . date('YmdHis') . '_' . substr(md5(uniqid()), 0, 8);
            }
            
            $nickname = $this->request->param('nickname') ?: '测试用户' . rand(1000, 9999);
            
            // 保存到数据库
            $this->saveWechatUser($openid, [
                'nickname' => $nickname,
                'headimgurl' => ''
            ]);
            
            // 返回成功页面
            return $this->renderResult(true, '模拟授权成功（测试模式）', [
                'openid' => $openid,
                'nickname' => $nickname
            ]);
            
        } catch (\Exception $e) {
            return $this->renderResult(false, '模拟授权失败：' . $e->getMessage());
        }
    }
}

