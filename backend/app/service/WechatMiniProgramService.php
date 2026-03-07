<?php
namespace app\service;

use app\model\User;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Config;

/**
 * 微信小程序服务类
 */
class WechatMiniProgramService
{
    // 微信小程序配置（从.env或配置文件读取）
    private $appId;
    private $appSecret;
    
    public function __construct()
    {
        // 从环境变量或配置文件读取
        $this->appId = env('WECHAT_MINI_APPID', '') ?: Config::get('wechat.mini_appid', '');
        $this->appSecret = env('WECHAT_MINI_SECRET', '') ?: Config::get('wechat.mini_secret', '');
    }

    private function assertWechatMiniConfig(): void
    {
        if (empty($this->appId) || empty($this->appSecret)) {
            throw new \Exception('微信小程序配置缺失：请在后端 .env 配置 WECHAT_MINI_APPID / WECHAT_MINI_SECRET，或在 config/wechat.php 配置 mini_appid / mini_secret');
        }
    }
    
    /**
     * 微信登录 - 获取openid和session_key
     * @param string $code
     * @return array
     * @throws \Exception
     */
    public function login($code)
    {
        $this->assertWechatMiniConfig();
        // 调用微信接口获取openid和session_key
        $url = "https://api.weixin.qq.com/sns/jscode2session";
        $params = [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        
        $response = $this->httpGet($url, $params);
        $result = json_decode($response, true);
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            throw new \Exception($result['errmsg'] ?? '微信登录失败');
        }
        
        $openid = $result['openid'];
        $sessionKey = $result['session_key'];
        
        // 缓存session_key，用于后续解密手机号
        Cache::set('wechat_session_' . $openid, $sessionKey, 7200);
        
        // 检查用户是否已注册
        $user = User::where('openid', $openid)->find();
        
        if ($user) {
            // 已注册，生成token并返回
            $token = $this->generateToken($user);
            
            return [
                'openid' => $openid,
                'session_key' => $sessionKey,
                'token' => $token,
                'userInfo' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'nickname' => $user->nickname ?? '用户',
                    'avatar' => $user->avatar ?? ''
                ]
            ];
        }
        
        // 未注册，只返回openid和session_key
        return [
            'openid' => $openid,
            'session_key' => $sessionKey
        ];
    }
    
    /**
     * 使用手机号code登录（新版API）
     * @param string $code 微信登录code
     * @param string $phoneCode 手机号code
     * @param array $extraInfo 额外的用户信息
     * @return array
     * @throws \Exception
     */
    public function loginWithPhoneCode($code, $phoneCode, $extraInfo = [])
    {
        // 先获取access_token
        $accessToken = $this->getAccessToken();
        
        // 调用微信接口获取手机号
        $url = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=" . $accessToken;
        $postData = json_encode(['code' => $phoneCode]);
        
        $response = $this->httpPost($url, $postData);
        $result = json_decode($response, true);
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            throw new \Exception($result['errmsg'] ?? '获取手机号失败');
        }
        
        $phoneInfo = $result['phone_info'];
        $phone = $phoneInfo['purePhoneNumber'];
        
        // 获取openid
        $loginResult = $this->login($code);
        $openid = $loginResult['openid'];
        
        // 注册或更新用户
        return $this->registerOrUpdateUser($openid, $phone, $extraInfo);
    }
    
    /**
     * 使用加密数据登录（旧版API，兼容）
     * @param string $code
     * @param string $encryptedData
     * @param string $iv
     * @param array $extraInfo 额外的用户信息
     * @return array
     * @throws \Exception
     */
    public function loginWithEncryptedData($code, $encryptedData, $iv, $extraInfo = [])
    {
        // 获取openid和session_key
        $loginResult = $this->login($code);
        $openid = $loginResult['openid'];
        $sessionKey = $loginResult['session_key'];
        
        // 解密手机号
        $phone = $this->decryptData($encryptedData, $iv, $sessionKey);
        
        // 注册或更新用户
        return $this->registerOrUpdateUser($openid, $phone, $extraInfo);
    }
    
    /**
     * 使用 openid 自动登录（无需手机号授权）
     * @param string $code 微信登录code
     * @param string $openid 用户的openid
     * @return array
     * @throws \Exception
     */
    public function loginWithOpenid($code, $openid)
    {
        // 先验证 code 是否有效，获取新的 session_key
        $loginResult = $this->login($code);
        $newOpenid = $loginResult['openid'];
        
        // 验证 openid 是否匹配
        if ($newOpenid !== $openid) {
            throw new \Exception('openid 不匹配，请重新授权登录');
        }
        
        // 查找用户
        $user = User::where('openid', $openid)->find();
        
        if (!$user) {
            throw new \Exception('用户不存在，请重新授权登录');
        }
        
        // 更新最后登录时间
        $user->update_time = date('Y-m-d H:i:s');
        $user->save();
        
        // 生成新的token
        $token = $this->generateToken($user);
        
        return [
            'token' => $token,
            'userInfo' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar ?? '',
                'openid' => $openid
            ]
        ];
    }
    
    /**
     * 注册或更新用户
     * @param string $openid
     * @param string $phone
     * @param array $extraInfo 额外的用户信息（昵称、头像等）
     * @return array
     */
    private function registerOrUpdateUser($openid, $phone, $extraInfo = [])
    {
        // 查找用户
        $user = User::where('openid', $openid)->find();
        
        $nickname = $extraInfo['nickname'] ?? '用户' . substr($phone, -4);
        $avatar = $extraInfo['avatar'] ?? '';
        $userType = $extraInfo['user_type'] ?? null;
        
        if (!$user) {
            // 新用户，创建
            $user = User::create([
                'openid' => $openid,
                'phone' => $phone,
                'nickname' => $nickname,
                'avatar' => $avatar,
                'platform' => 'miniprogram', // 标记为小程序用户
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ]);
        } else {
            // 更新手机号和其他信息
            $user->phone = $phone;
            if (!empty($nickname)) {
                $user->nickname = $nickname;
            }
            if (!empty($avatar)) {
                $user->avatar = $avatar;
            }
            $user->update_time = date('Y-m-d H:i:s');
            $user->save();
        }
        
        // 同步保存到 fa_wechat_users 表
        $this->saveToWechatUsers($openid, $phone, $nickname, $avatar, $userType, $user->id);
        
        // 生成token
        $token = $this->generateToken($user);
        
        return [
            'token' => $token,
            'userInfo' => [
                'id' => $user->id,
                'phone' => $phone,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar ?? '',
                'openid' => $openid
            ]
        ];
    }
    
    /**
     * 保存用户信息到 fa_wechat_users 表
     * @param string $openid
     * @param string $phone
     * @param string $nickname
     * @param string $avatar
     * @param string|null $userType
     * @param int|null $userId
     */
    private function saveToWechatUsers($openid, $phone, $nickname, $avatar, $userType = null, $userId = null)
    {
        try {
            $existing = Db::name('wechat_users')->where('openid', $openid)->find();
            
            $data = [
                'openid' => $openid,
                'phone' => $phone,
                'nickname' => $nickname,
                'headimgurl' => $avatar,
                'user_type' => $userType,
                'user_id' => $userId,
                'update_time' => date('Y-m-d H:i:s')
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
            // 记录错误但不影响主流程
            trace('保存到wechat_users表失败: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 解密微信加密数据
     * @param string $encryptedData
     * @param string $iv
     * @param string $sessionKey
     * @return string
     * @throws \Exception
     */
    private function decryptData($encryptedData, $iv, $sessionKey)
    {
        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, OPENSSL_RAW_DATA, $aesIV);
        
        if (!$result) {
            throw new \Exception('解密失败');
        }
        
        $dataObj = json_decode($result, true);
        
        if (!isset($dataObj['purePhoneNumber'])) {
            throw new \Exception('解密数据格式错误');
        }
        
        return $dataObj['purePhoneNumber'];
    }
    
    /**
     * 获取微信access_token
     * @return string
     * @throws \Exception
     */
    private function getAccessToken()
    {
        $this->assertWechatMiniConfig();
        // 先从缓存获取
        $cacheKey = 'wechat_access_token_' . md5($this->appId);
        $accessToken = Cache::get($cacheKey);
        
        if ($accessToken) {
            return $accessToken;
        }
        
        // 调用微信接口获取
        $url = "https://api.weixin.qq.com/cgi-bin/token";
        $params = [
            'grant_type' => 'client_credential',
            'appid' => $this->appId,
            'secret' => $this->appSecret
        ];
        
        $response = $this->httpGet($url, $params);
        $result = json_decode($response, true);
        
        if (isset($result['errcode'])) {
            // 清除可能存在的旧缓存
            Cache::delete($cacheKey);
            throw new \Exception($result['errmsg'] ?? '获取access_token失败');
        }
        
        $accessToken = $result['access_token'];
        $expiresIn = $result['expires_in'] ?? 7200;
        
        // 缓存access_token，提前5分钟过期以避免边界问题
        Cache::set($cacheKey, $accessToken, $expiresIn - 300);
        
        return $accessToken;
    }
    
    /**
     * 生成用户token
     * @param User $user
     * @return string
     */
    private function generateToken($user)
    {
        $payload = [
            'user_id' => $user->id,
            'openid' => $user->openid,
            'exp' => time() + 86400 * 30 // 30天过期
        ];
        
        // 简单的token生成，实际项目建议使用JWT
        $token = base64_encode(json_encode($payload));
        
        // 缓存token
        Cache::set('user_token_' . $token, $user->id, 86400 * 30);
        
        return $token;
    }
    
    /**
     * HTTP GET请求
     * @param string $url
     * @param array $params
     * @return string
     */
    private function httpGet($url, $params = [])
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
    /**
     * HTTP POST请求
     * @param string $url
     * @param string $data
     * @return string
     */
    private function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
    /**
     * 生成小程序码（无数量限制）
     * @param string $page 页面路径，例如：pages/index/index
     * @param string $scene 场景值，最大32个可见字符，例如：id=123
     * @param array $options 其他可选参数
     * @return array ['success' => bool, 'data' => string(base64图片) | 'error' => string]
     */
    public function generateQRCode($page, $scene, $options = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $accessToken;
            
            $postData = [
                'page' => $page,
                'scene' => $scene,
                'check_path' => $options['check_path'] ?? false, // 默认关闭路径检查，避免因路径配置问题导致生成失败
                'env_version' => $options['env_version'] ?? 'release', // release正式版, trial体验版, develop开发版
                'width' => $options['width'] ?? 430,
                'auto_color' => $options['auto_color'] ?? false,
                'is_hyaline' => $options['is_hyaline'] ?? false
            ];
            
            // 可选的线条颜色
            if (isset($options['line_color'])) {
                $postData['line_color'] = $options['line_color'];
            }
            
            $response = $this->httpPost($url, json_encode($postData));
            
            // 检查是否返回错误
            $result = json_decode($response, true);
            if (is_array($result) && isset($result['errcode'])) {
                return [
                    'success' => false,
                    'error' => $result['errmsg'] ?? '生成小程序码失败',
                    'errcode' => $result['errcode']
                ];
            }
            
            // 返回的是图片二进制数据，转为base64
            $base64Image = base64_encode($response);
            
            return [
                'success' => true,
                'data' => 'data:image/png;base64,' . $base64Image
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 生成小程序 URL Scheme（用于分享到微信外）
     * @param string $path 页面路径
     * @param string $query 查询参数
     * @param array $options 可选参数
     * @return array
     */
    public function generateUrlScheme($path, $query = '', $options = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/wxa/generatescheme?access_token=" . $accessToken;
            
            $jumpWxa = [
                'path' => $path
            ];
            
            if (!empty($query)) {
                $jumpWxa['query'] = $query;
            }
            
            $postData = [
                'jump_wxa' => $jumpWxa,
                'is_expire' => $options['is_expire'] ?? false,
                'expire_type' => $options['expire_type'] ?? 0
            ];
            
            // 如果设置了过期时间
            if (isset($options['expire_time'])) {
                $postData['expire_time'] = $options['expire_time'];
            }
            
            // 日志：记录请求参数
            error_log("=== URL Scheme 生成请求 ===");
            error_log("URL: " . $url);
            error_log("请求参数: " . json_encode($postData, JSON_UNESCAPED_UNICODE));
            
            $response = $this->httpPost($url, json_encode($postData));
            
            // 日志：记录原始响应
            error_log("原始响应: " . $response);
            
            $result = json_decode($response, true);
            
            // 日志：记录解析后的响应
            error_log("解析后响应: " . json_encode($result, JSON_UNESCAPED_UNICODE));
            
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                error_log("URL Scheme 生成失败 - errcode: " . $result['errcode'] . ", errmsg: " . ($result['errmsg'] ?? ''));
                return [
                    'success' => false,
                    'error' => $result['errmsg'] ?? '生成URL Scheme失败',
                    'errcode' => $result['errcode']
                ];
            }
            
            error_log("URL Scheme 生成成功: " . ($result['openlink'] ?? ''));
            error_log("=== URL Scheme 生成完成 ===\n");
            
            return [
                'success' => true,
                'data' => $result['openlink'] ?? ''
            ];
        } catch (\Exception $e) {
            error_log("URL Scheme 生成异常: " . $e->getMessage());
            error_log("异常堆栈: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 生成小程序 Short Link（短链接，用于微信内分享）
     * @param string $path 页面路径
     * @param string $query 查询参数
     * @param array $options 可选参数
     * @return array
     */
    public function generateShortLink($path, $query = '', $options = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/wxa/generate_shortlink?access_token=" . $accessToken;
            
            // 构建完整的页面链接 - 注意：必须以 pages/ 开头
            $pageUrl = $path;
            if (!empty($query)) {
                $pageUrl .= '?' . $query;
            }
            
            $postData = [
                'page_url' => $pageUrl,
                'is_permanent' => $options['is_permanent'] ?? true
            ];
            
            // 日志：记录请求参数
            error_log("=== Short Link 生成请求 ===");
            error_log("URL: " . $url);
            error_log("请求参数: " . json_encode($postData, JSON_UNESCAPED_UNICODE));
            
            $response = $this->httpPost($url, json_encode($postData));
            
            // 日志：记录原始响应
            error_log("原始响应: " . $response);
            
            $result = json_decode($response, true);
            
            // 日志：记录解析后的响应
            error_log("解析后响应: " . json_encode($result, JSON_UNESCAPED_UNICODE));
            
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                error_log("Short Link 生成失败 - errcode: " . $result['errcode'] . ", errmsg: " . ($result['errmsg'] ?? ''));
                return [
                    'success' => false,
                    'error' => $result['errmsg'] ?? '生成短链接失败',
                    'errcode' => $result['errcode']
                ];
            }
            
            error_log("Short Link 生成成功: " . ($result['link'] ?? ''));
            error_log("=== Short Link 生成完成 ===\n");
            
            return [
                'success' => true,
                'data' => $result['link'] ?? ''
            ];
        } catch (\Exception $e) {
            error_log("Short Link 生成异常: " . $e->getMessage());
            error_log("异常堆栈: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
