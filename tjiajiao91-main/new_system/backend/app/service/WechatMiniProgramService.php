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
     * 微信 code 换 openid/session_key（仅请求微信，不建用户）
     * @param string $code
     * @return array [openid, session_key]
     * @throws \Exception
     */
    private function getOpenidByCode($code)
    {
        $this->assertWechatMiniConfig();
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
        return [
            'openid' => $result['openid'],
            'session_key' => $result['session_key']
        ];
    }

    /**
     * 微信登录 - 获取 openid 和 session_key；已注册用户返回 token，未注册只返回 openid/session_key（需走手机号登录完成注册）
     * @param string $code
     * @return array
     * @throws \Exception
     */
    public function login($code, $extraInfo = [])
    {
        $this->assertWechatMiniConfig();
        $session = $this->getOpenidByCode($code);
        $openid = $session['openid'];
        $sessionKey = $session['session_key'];

        // 缓存 session_key，用于后续解密手机号
        Cache::set('wechat_session_' . $openid, $sessionKey, 7200);

        $user = User::where('openid', $openid)->find();

        if ($user) {
            // superior_openid：登录时分享的管理员 openid，仅当用户表为空时补写
            $superiorOpenid = trim((string)($extraInfo['superior_openid'] ?? ''));
            if ($superiorOpenid !== '' && $superiorOpenid !== $openid) {
                $currentSuperior = trim((string)($user->superior_openid ?? ''));
                if ($currentSuperior === '') {
                    $user->superior_openid = $superiorOpenid;
                    $user->update_time = date('Y-m-d H:i:s');
                    $user->save();
                }
            }
            if ($superiorOpenid !== '') {
                try {
                    \think\facade\Log::info('[superior_bind] login(code) 老用户（请求带了 superior）', [
                        'incoming_superior_openid' => $superiorOpenid,
                        'users.superior_openid_now' => trim((string)($user->superior_openid ?? '')) ?: '(empty)',
                    ]);
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            $token = $this->generateToken($user);
            $userType = $this->getUserTypeFromWechatUsers($openid);
            return [
                'openid' => $openid,
                'session_key' => $sessionKey,
                'token' => $token,
                'userInfo' => [
                    'id' => $user->id,
                    'phone' => $user->phone ?? '',
                    'nickname' => $user->nickname ?? '用户',
                    'avatar' => $user->avatar ?? '',
                    'openid' => $openid,
                    'user_type' => $userType
                ]
            ];
        }

        // 未注册：只返回 openid 和 session_key，需通过手机号登录接口完成注册
        return [
            'openid' => $openid,
            'session_key' => $sessionKey
        ];
    }

    /**
     * 从 fa_wechat_users 读取 user_type
     */
    private function getUserTypeFromWechatUsers($openid)
    {
        try {
            $row = Db::name('wechat_users')->where('openid', $openid)->find();
            return ($row && in_array($row['user_type'] ?? '', ['teacher', 'parent'], true))
                ? $row['user_type'] : 'parent';
        } catch (\Throwable $e) {
            return 'parent';
        }
    }

    /**
     * 使用 openid 静默登录（已注册用户免手机号验证，用于再次打开小程序）
     * @param string $code 本次微信 login 的 code
     * @param string $openid 本地保存的 openid，用于校验与 code 一致
     * @return array token + userInfo
     * @throws \Exception
     */
    public function loginWithOpenid($code, $openid, $extraInfo = [])
    {
        $session = $this->getOpenidByCode($code);
        if ($session['openid'] !== $openid) {
            throw new \Exception('openid 与本次登录不一致，请重新登录');
        }
        $user = User::where('openid', $openid)->find();
        if (!$user) {
            throw new \Exception('用户不存在，请先完成登录');
        }

        // superior_openid：登录时分享的管理员 openid，仅当用户表为空时写入一次，永不覆盖
        $superiorOpenid = trim((string)($extraInfo['superior_openid'] ?? ''));
        if ($superiorOpenid !== '' && $superiorOpenid !== $openid) {
            $current = trim((string)($user->superior_openid ?? ''));
            if ($current === '') {
                $user->superior_openid = $superiorOpenid;
                $user->update_time = date('Y-m-d H:i:s');
                $user->save();
            }
        }

        $userType = $this->getUserTypeFromWechatUsers($openid);
        $token = $this->generateToken($user);
        return [
            'token' => $token,
            'userInfo' => [
                'id' => $user->id,
                'phone' => $user->phone ?? '',
                'nickname' => $user->nickname ?? '用户',
                'avatar' => $user->avatar ?? '',
                'openid' => $openid,
                'user_type' => $userType
            ]
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
        try {
            // 先获取access_token
            $accessToken = $this->getAccessToken();
            
            // 调用微信接口获取手机号
            $url = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=" . $accessToken;
            $postData = json_encode(['code' => $phoneCode]);
            
            \think\facade\Log::info('Request phone number: url=' . $url . ', phoneCode=' . $phoneCode);
            
            $response = $this->httpPost($url, $postData);
            $result = json_decode($response, true);
            
            \think\facade\Log::info('Phone response: ' . json_encode($result));
            \think\facade\Log::info('Raw response: ' . $response);
            
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                // 如果是access_token失效，清除缓存并重试一次
                if ($result['errcode'] == 40001 || $result['errcode'] == 42001) {
                    \think\facade\Log::warning('Access token expired, clearing cache and retrying');
                    $cacheKey = 'wechat_access_token_' . md5($this->appId);
                    Cache::delete($cacheKey);
                    
                    // 重新获取access_token并重试
                    $accessToken = $this->getAccessToken();
                    $url = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=" . $accessToken;
                    $response = $this->httpPost($url, $postData);
                    $result = json_decode($response, true);
                    
                    if (isset($result['errcode']) && $result['errcode'] != 0) {
                        throw new \Exception($result['errmsg'] ?? '获取手机号失败');
                    }
                } else {
                    throw new \Exception($result['errmsg'] ?? '获取手机号失败');
                }
            }
            
            // 检查响应数据
            if (!isset($result['phone_info'])) {
                \think\facade\Log::error('phone_info field not found: ' . json_encode($result));
                throw new \Exception('Failed to get phone number: Invalid response format');
            }
            
            $phoneInfo = $result['phone_info'];
            
            if (!isset($phoneInfo['purePhoneNumber'])) {
                \think\facade\Log::error('purePhoneNumber field not found: ' . json_encode($phoneInfo));
                throw new \Exception('Failed to get phone number: Phone number field not found');
            }
            
            $phone = $phoneInfo['purePhoneNumber'];
            
            \think\facade\Log::info('Successfully got phone number: ' . $phone);
            
            // 获取openid
            $loginResult = $this->login($code);
            $openid = $loginResult['openid'];
            
            // 注册或更新用户
            return $this->registerOrUpdateUser($openid, $phone, $extraInfo);
        } catch (\Exception $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode()
            ];
            
            \think\facade\Log::error('loginWithPhoneCode failed: ' . $e->getMessage());
            \think\facade\Log::error('Error file: ' . $e->getFile() . ' line: ' . $e->getLine());
            \think\facade\Log::error('Stack trace: ' . $e->getTraceAsString());
            
            throw $e;
        }
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
        // 如果没有指定用户类型，默认为家长
        $userType = $extraInfo['user_type'] ?? 'parent';
        $inviterOpenid = $extraInfo['inviter_openid'] ?? '';
        $superiorOpenid = trim((string)($extraInfo['superior_openid'] ?? ''));
        
        $isNewUser = false;
        
        if (!$user) {
            // 新用户，创建
            $user = User::create([
                'openid' => $openid,
                'superior_openid' => ($superiorOpenid && $superiorOpenid !== $openid) ? $superiorOpenid : null,
                'phone' => $phone,
                'nickname' => $nickname,
                'avatar' => $avatar,
                'platform' => 'miniprogram', // 标记为小程序用户
                'status' => 1, // 默认启用状态
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            $isNewUser = true;
            
            // 处理邀请逻辑
            if (!empty($inviterOpenid) && $inviterOpenid !== $openid) {
                $this->handleInvitation($inviterOpenid, $openid, $user->id);
            }
        } else {
            // 检查用户状态（只检查fa_users表）
            $userStatus = $user->status ?? 1; // 如果字段不存在，默认为启用
            if ($userStatus == 0) {
                throw new \Exception('账户已被禁用，请联系管理员');
            }
            
            // 更新手机号和其他信息
            $user->phone = $phone;
            // superior_openid：仅为空时写入一次（不覆盖）
            if ($superiorOpenid !== '' && $superiorOpenid !== $openid) {
                $currentSuperior = trim((string)($user->superior_openid ?? ''));
                if ($currentSuperior === '') {
                    $user->superior_openid = $superiorOpenid;
                }
            }
            if (!empty($nickname)) {
                $user->nickname = $nickname;
            }
            if (!empty($avatar)) {
                $user->avatar = $avatar;
            }
            $user->update_time = date('Y-m-d H:i:s');
            $user->save();
        }

        // [superior_bind] 调试：新用户首登或请求带了 superior 时记一条，避免老用户反复手机号校验刷日志
        if ($isNewUser || $superiorOpenid !== '') {
            try {
                $storedSuperior = trim((string)($user->superior_openid ?? ''));
                \think\facade\Log::info('[superior_bind] registerOrUpdateUser', [
                    'is_new_user' => $isNewUser,
                    'incoming_superior_openid' => $superiorOpenid !== '' ? $superiorOpenid : '(empty)',
                    'same_as_self_ignored' => ($superiorOpenid !== '' && $superiorOpenid === $openid),
                    'users.superior_openid_after' => $storedSuperior !== '' ? $storedSuperior : '(empty)',
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        
        // 同步保存到 fa_wechat_users 表
        $this->saveToWechatUsers($openid, $phone, $nickname, $avatar, $userType, $user->id);
        
        // 按手机号匹配教师表并回写 openid/微信昵称（不依赖 user_type，只要该手机号在教师表存在就更新）
        $this->syncTeacherOpenid($openid, $phone, $nickname);
        
        // 生成token
        $token = $this->generateToken($user);
        
        return [
            'token' => $token,
            'userInfo' => [
                'id' => $user->id,
                'phone' => $phone,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar ?? '',
                'openid' => $openid,
                'user_type' => $userType
            ],
            'isNewUser' => $isNewUser
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
            
            // 如果没有指定用户类型，默认为家长
            if (empty($userType)) {
                $userType = 'parent';
            }
            
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
                // 更新时以本次登录选择的身份为准，不再用“原记录为空则强制 parent”（否则退出登录改选老师后仍被写成 parent）
                Db::name('wechat_users')->where('openid', $openid)->update($data);
            } else {
                // 新增
                $data['create_time'] = date('Y-m-d H:i:s');
                Db::name('wechat_users')->insert($data);
            }
        } catch (\Exception $e) {
            // 记录错误但不影响主流程
            trace('保存到wechat_users表失败: ' . $e->getMessage(), 'error');
            \think\facade\Log::error('保存到wechat_users表失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 教师端登录时，按手机号回写 openid、微信昵称到教师表，便于后台展示与用 openid 查用户
     * @param string $openid
     * @param string $phone
     * @param string $nickname
     */
    private function syncTeacherOpenid($openid, $phone, $nickname)
    {
        if (empty($phone)) {
            return;
        }
        try {
            $updated = Db::name('teachers')
                ->where('phone', $phone)
                ->update([
                    'openid' => $openid,
                    'wechat_nickname' => $nickname ?: null,
                    'last_login_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            if ($updated > 0) {
                \think\facade\Log::info("教师表已回写 openid: phone={$phone}, openid={$openid}");
            }
        } catch (\Exception $e) {
            \think\facade\Log::warning('教师表回写 openid 失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 仅更新 fa_wechat_users 的 user_type（用于小程序“确认身份”时已登录场景，不重新走登录接口）
     * @param string $openid
     * @param string $userType teacher|parent
     * @return bool
     */
    public function updateUserType($openid, $userType)
    {
        if (empty($openid) || empty($userType)) {
            return false;
        }
        if (!in_array($userType, ['teacher', 'parent'], true)) {
            return false;
        }
        try {
            $n = Db::name('wechat_users')
                ->where('openid', $openid)
                ->update([
                    'user_type' => $userType,
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
            return $n > 0;
        } catch (\Exception $e) {
            \think\facade\Log::warning('更新 user_type 失败: ' . $e->getMessage());
            return false;
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
            \think\facade\Log::info('Using cached access_token');
            return $accessToken;
        }
        
        // 调用微信接口获取
        $url = "https://api.weixin.qq.com/cgi-bin/token";
        $params = [
            'grant_type' => 'client_credential',
            'appid' => $this->appId,
            'secret' => $this->appSecret
        ];
        
        \think\facade\Log::info('Request wechat access_token: url=' . $url . ', appid=' . $this->appId);
        
        $response = $this->httpGet($url, $params);
        $result = json_decode($response, true);
        
        \think\facade\Log::info('Wechat access_token response: ' . json_encode($result));
        
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            // 清除可能存在的旧缓存
            Cache::delete($cacheKey);
            $errorMsg = "Failed to get access_token: errcode={$result['errcode']}, errmsg=" . ($result['errmsg'] ?? 'Unknown error');
            \think\facade\Log::error($errorMsg);
            throw new \Exception($errorMsg);
        }
        
        if (!isset($result['access_token'])) {
            \think\facade\Log::error('access_token field not found: ' . json_encode($result));
            throw new \Exception('Failed to get access_token: Invalid response format');
        }
        
        $accessToken = $result['access_token'];
        $expiresIn = $result['expires_in'] ?? 7200;
        
        // 缓存access_token，提前5分钟过期以避免边界问题
        Cache::set($cacheKey, $accessToken, $expiresIn - 300);
        
        \think\facade\Log::info('access_token cached, expires_in=' . $expiresIn);
        
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
     * 处理邀请逻辑
     * @param string $inviterOpenid 邀请人openid
     * @param string $inviteeOpenid 被邀请人openid
     * @param int $inviteeUserId 被邀请人用户ID
     */
    private function handleInvitation($inviterOpenid, $inviteeOpenid, $inviteeUserId)
    {
        try {
            // 查找邀请人
            $inviter = Db::name('users')->where('openid', $inviterOpenid)->find();
            if (!$inviter) {
                \think\facade\Log::warning('邀请人不存在: ' . $inviterOpenid);
                return;
            }
            
            // 检查是否已经有邀请记录（防止重复处理）
            $existingInvitation = Db::name('user_invitations')
                ->where('inviter_openid', $inviterOpenid)
                ->where('invitee_openid', $inviteeOpenid)
                ->find();
            
            if ($existingInvitation) {
                \think\facade\Log::info('邀请记录已存在，跳过处理');
                return;
            }
            
            // 开启事务
            Db::startTrans();
            
            try {
                // 1. 仅创建邀请记录；优惠券在「简历认证并通过审核」后由审核通过逻辑发放
                Db::name('user_invitations')->insert([
                    'inviter_user_id' => $inviter['id'],
                    'inviter_openid' => $inviterOpenid,
                    'invitee_user_id' => $inviteeUserId,
                    'invitee_openid' => $inviteeOpenid,
                    'invitation_code' => '',
                    'status' => 0, // 待认证，审核通过后改为 1
                    'is_rewarded' => 0, // 审核通过发券后改为 1
                    'create_time' => date('Y-m-d H:i:s'),
                    'verify_time' => null,
                    'reward_time' => null
                ]);
                
                Db::commit();
                \think\facade\Log::info('邀请记录已创建(待审核通过后发券): 邀请人=' . $inviterOpenid . ', 被邀请人=' . $inviteeOpenid);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \think\facade\Log::error('处理邀请失败: ' . $e->getMessage());
        }
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
