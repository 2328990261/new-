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
     * @return array
     * @throws \Exception
     */
    public function loginWithPhoneCode($code, $phoneCode)
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
        return $this->registerOrUpdateUser($openid, $phone);
    }
    
    /**
     * 使用加密数据登录（旧版API，兼容）
     * @param string $code
     * @param string $encryptedData
     * @param string $iv
     * @return array
     * @throws \Exception
     */
    public function loginWithEncryptedData($code, $encryptedData, $iv)
    {
        // 获取openid和session_key
        $loginResult = $this->login($code);
        $openid = $loginResult['openid'];
        $sessionKey = $loginResult['session_key'];
        
        // 解密手机号
        $phone = $this->decryptData($encryptedData, $iv, $sessionKey);
        
        // 注册或更新用户
        return $this->registerOrUpdateUser($openid, $phone);
    }
    
    /**
     * 注册或更新用户
     * @param string $openid
     * @param string $phone
     * @return array
     */
    private function registerOrUpdateUser($openid, $phone)
    {
        // 查找用户
        $user = User::where('openid', $openid)->find();
        
        if (!$user) {
            // 新用户，创建
            $user = User::create([
                'openid' => $openid,
                'phone' => $phone,
                'nickname' => '用户' . substr($phone, -4),
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ]);
        } else {
            // 更新手机号
            $user->phone = $phone;
            $user->update_time = date('Y-m-d H:i:s');
            $user->save();
        }
        
        // 生成token
        $token = $this->generateToken($user);
        
        return [
            'token' => $token,
            'userInfo' => [
                'id' => $user->id,
                'phone' => $phone,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar ?? ''
            ]
        ];
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
                'check_path' => $options['check_path'] ?? true,
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
                    'error' => $result['errmsg'] ?? '生成小程序码失败'
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
}
