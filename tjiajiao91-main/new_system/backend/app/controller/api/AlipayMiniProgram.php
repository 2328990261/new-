<?php

namespace app\controller\api;

use app\BaseController;
use app\model\User;
use app\service\MiniProgramConfigService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;

class AlipayMiniProgram extends BaseController
{
    public function login()
    {
        try {
            $authCode = (string)Request::post('code', '');
            if ($authCode === '') {
                return json(['code' => 400, 'message' => '缺少 code 参数']);
            }

            $nickname = (string)Request::post('nickname', '');
            $avatar = (string)Request::post('avatar', '');
            $superiorOpenid = trim((string)Request::post('superior_openid', ''));
            $appId = trim((string)Request::post('app_id', '')); // 接收前端传来的 AppID
            
            // 调试日志
            error_log("支付宝登录 - 接收到的参数: app_id={$appId}, code=" . substr($authCode, 0, 20) . "...");

            $oauth = $this->exchangeAuthCode($authCode, $appId);
            // 支付宝新版 API 返回 open_id，旧版返回 user_id，兼容两者
            $alipayUserId = (string)($oauth['open_id'] ?? $oauth['user_id'] ?? '');
            if ($alipayUserId === '') {
                return json(['code' => 500, 'message' => '支付宝登录失败：未获取到 open_id 或 user_id']);
            }

            $openid = 'alipay_' . $alipayUserId;
            
            // 支持多小程序：同时查询 openid 和 openid_secondary
            $user = User::where(function($query) use ($openid) {
                $query->where('openid', $openid)
                      ->whereOr('openid_secondary', $openid);
            })->find();
            
            $isNewUser = false;

            if (!$user) {
                $user = User::create([
                    'openid' => $openid,
                    'superior_openid' => ($superiorOpenid !== '' && $superiorOpenid !== $openid) ? $superiorOpenid : null,
                    'phone' => '',
                    'nickname' => $nickname !== '' ? $nickname : ('支付宝用户' . substr($alipayUserId, -4)),
                    'avatar' => $avatar,
                    'platform' => 'alipay_miniprogram',
                    'status' => 1,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ]);
                $isNewUser = true;
            } else {
                $update = [
                    'platform' => 'alipay_miniprogram',
                    'update_time' => date('Y-m-d H:i:s'),
                ];
                if ($nickname !== '') {
                    $update['nickname'] = $nickname;
                }
                if ($avatar !== '') {
                    $update['avatar'] = $avatar;
                }
                if ($superiorOpenid !== '' && $superiorOpenid !== $openid && trim((string)($user->superior_openid ?? '')) === '') {
                    $update['superior_openid'] = $superiorOpenid;
                }
                $user->save($update);
            }

            // 如果是新用户，或用户尚未绑定手机号，则不返回 token
            // 让前端走「验证手机号」流程后再自动登录
            $hasPhone = trim((string)($user->phone ?? '')) !== '';
            $needPhoneAuth = $isNewUser || !$hasPhone;
            // 对前端语义：只要还没绑定手机号，就当作“新用户流程”（需要去选身份）
            $isNewUserForClient = $isNewUser || !$hasPhone;

            if ($needPhoneAuth) {
                return json([
                    'code' => 200,
                    'message' => '需要手机号验证',
                    'data' => [
                        'isNewUser' => $isNewUserForClient,
                        'openid' => $openid,
                    ],
                ]);
            }

            return json([
                'code' => 200,
                'message' => '登录成功',
                'data' => [
                    'token' => $this->generateToken($user),
                    'isNewUser' => $isNewUser,
                    'userInfo' => [
                        'id' => $user->id,
                        'openid' => $openid,
                        'phone' => $user->phone ?? '',
                        'nickname' => $user->nickname ?? '用户',
                        'avatar' => $user->avatar ?? '',
                        'user_type' => 'parent',
                        'platform' => 'alipay_miniprogram',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '支付宝登录失败: ' . $e->getMessage()]);
        }
    }

    public function loginWithOpenid()
    {
        try {
            $openid = trim((string)Request::post('openid', ''));
            if ($openid === '') {
                return json(['code' => 400, 'message' => '缺少 openid 参数']);
            }
            $user = User::where('openid', $openid)->find();
            if (!$user) {
                return json(['code' => 404, 'message' => '用户不存在，请先登录']);
            }
            return json([
                'code' => 200,
                'message' => '登录成功',
                'data' => [
                    'token' => $this->generateToken($user),
                    'userInfo' => [
                        'id' => $user->id,
                        'openid' => $openid,
                        'phone' => $user->phone ?? '',
                        'nickname' => $user->nickname ?? '用户',
                        'avatar' => $user->avatar ?? '',
                        'user_type' => 'parent',
                        'platform' => $user->platform ?? 'alipay_miniprogram',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '登录失败: ' . $e->getMessage()]);
        }
    }

    public function loginWithPhone()
    {
        try {
            $authCode = (string)Request::post('code', '');
            if ($authCode === '') {
                return json(['code' => 400, 'message' => '缺少 code 参数']);
            }

            // 支付宝敏感信息：前端会传加密后的报文和签名
            $encryptedData = (string)Request::post('encrypted_data', '');
            if ($encryptedData === '') {
                return json(['code' => 400, 'message' => '缺少 encrypted_data 参数']);
            }
            $sign = (string)Request::post('sign', '');

            $nickname = (string)Request::post('nickname', '');
            $avatar = (string)Request::post('avatar', '');
            $superiorOpenid = trim((string)Request::post('superior_openid', ''));
            $userType = (string)Request::post('user_type', 'parent');
            $appId = trim((string)Request::post('app_id', '')); // 接收前端传来的 AppID
            
            if (!in_array($userType, ['teacher', 'parent'], true)) {
                $userType = 'parent';
            }

            $oauth = $this->exchangeAuthCode($authCode, $appId);
            // 支付宝新版 API 返回 open_id，旧版返回 user_id，兼容两者
            $alipayUserId = (string)($oauth['open_id'] ?? $oauth['user_id'] ?? '');
            if ($alipayUserId === '') {
                return json(['code' => 500, 'message' => '支付宝登录失败：未获取到 open_id 或 user_id']);
            }

            $openid = 'alipay_' . $alipayUserId;

            $phone = $this->decryptAlipayPhone($encryptedData, $sign);
            if (trim($phone) === '') {
                return json(['code' => 500, 'message' => '支付宝手机号解密失败']);
            }

            // 支持多小程序：同时查询 openid 和 openid_secondary
$user = User::where(function($query) use ($openid) {
    $query->where('openid', $openid)->whereOr('openid_secondary', $openid);
})->find();

$wasWithoutPhone = false;
$isNewUser = false;

// 情况1：找到了用户（通过 openid 或 openid_secondary）
if ($user) {
    $wasWithoutPhone = trim((string)($user->phone ?? '')) === '';
    
    $update = [
        'phone' => $phone,
        'platform' => 'alipay_miniprogram',
        'update_time' => date('Y-m-d H:i:s'),
    ];
    if ($nickname !== '') {
        $update['nickname'] = $nickname;
    }
    if ($avatar !== '') {
        $update['avatar'] = $avatar;
    }
    if ($superiorOpenid !== '' && $superiorOpenid !== $openid && trim((string)($user->superior_openid ?? '')) === '') {
        $update['superior_openid'] = $superiorOpenid;
    }
    $user->save($update);
    
    if ($wasWithoutPhone) {
        $isNewUser = true;
    }
}
// 情况2：没找到用户，但手机号已存在（可能是另一个小程序的账号）
else {
    $existingUser = User::where('phone', $phone)
        ->where('platform', 'like', '%alipay%')
        ->find();
    
    if ($existingUser) {
        // 将新 openid 存到 openid_secondary
        $user = $existingUser;
        $update = [
            'platform' => 'alipay_miniprogram',
            'update_time' => date('Y-m-d H:i:s'),
        ];
        
        // 如果 openid_secondary 为空，就存进去
        if (trim((string)($user->openid_secondary ?? '')) === '') {
            $update['openid_secondary'] = $openid;
        }
        
        if ($nickname !== '') {
            $update['nickname'] = $nickname;
        }
        if ($avatar !== '') {
            $update['avatar'] = $avatar;
        }
        
        $user->save($update);
        $isNewUser = false; // 不是新用户
    }
    // 情况3：完全新用户
    else {
        $user = User::create([
            'openid' => $openid,
            'superior_openid' => ($superiorOpenid !== '' && $superiorOpenid !== $openid) ? $superiorOpenid : null,
            'phone' => $phone,
            'nickname' => $nickname !== '' ? $nickname : ('支付宝用户' . substr($alipayUserId, -4)),
            'avatar' => $avatar,
            'platform' => 'alipay_miniprogram',
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        $isNewUser = true;
    }
}

            return json([
                'code' => 200,
                'message' => '手机号验证成功，登录成功',
                'data' => [
                    'token' => $this->generateToken($user),
                    'isNewUser' => $isNewUser,
                    'userInfo' => [
                        'id' => $user->id,
                        'openid' => $openid,
                        'phone' => $user->phone ?? $phone,
                        'nickname' => $user->nickname ?? '用户',
                        'avatar' => $user->avatar ?? '',
                        'user_type' => $userType,
                        'platform' => 'alipay_miniprogram',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '支付宝手机号登录失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 解密支付宝敏感信息手机号
     * @param string $encryptedData 加密后的报文（前端传入 response.response）
     * @param string $sign 签名（前端传入 response.sign）
     * @return string 明文手机号
     */
    private function decryptAlipayPhone(string $encryptedData, string $sign): string
    {
        // 可选验签开关：默认不强制（先把功能跑通）
        $verifySign = (bool)env('ALIPAY_PHONE_VERIFY_SIGN', false);

        // AES 密钥：优先从当前请求的小程序配置中获取，否则从环境变量获取
        $aesKeyBase64 = '';
        
        // 尝试从请求中获取 app_id，然后查询配置
        $appId = trim((string)request()->post('app_id', ''));
        if ($appId !== '') {
            try {
                $cfg = (new MiniProgramConfigService())->getRuntimeConfig('alipay', $appId);
                $aesKeyBase64 = (string)($cfg['phone_aes_key'] ?? '');
            } catch (\Throwable $e) {
                // 配置获取失败，继续使用环境变量
            }
        }
        
        // 如果配置中没有，回退到环境变量
        if ($aesKeyBase64 === '') {
            $aesKeyBase64 = (string)env('ALIPAY_PHONE_AES_KEY', '');
        }
        
        if ($aesKeyBase64 === '') {
            throw new \RuntimeException('缺少 ALIPAY_PHONE_AES_KEY 配置（请在小程序配置或环境变量中设置）');
        }

        // 公钥（验签用）：接口内容加签方式-获取
        $publicKeyPem = (string)env('ALIPAY_PHONE_ALIPAY_PUBLIC_KEY', '');
        if ($verifySign && $publicKeyPem !== '' && $sign !== '') {
            $this->verifyAlipaySign($encryptedData, $sign, $publicKeyPem);
        }

        $cipherText = base64_decode($encryptedData, true);
        if ($cipherText === false) {
            throw new \RuntimeException('encrypted_data base64 解码失败');
        }

        $aesKey = base64_decode($aesKeyBase64, true);
        if ($aesKey === false || $aesKey === '') {
            throw new \RuntimeException('ALIPAY_PHONE_AES_KEY base64 解码失败');
        }

        // 支付宝敏感信息：IV 全为 0
        $iv = str_repeat("\0", 16);

        // 支付宝敏感信息 AES 算法依赖于 key 长度
        // - 16 bytes => AES-128-CBC
        // - 32 bytes => AES-256-CBC
        $keyLen = strlen($aesKey);
        if ($keyLen === 16) {
            $algo = 'AES-128-CBC';
        } elseif ($keyLen === 32) {
            $algo = 'AES-256-CBC';
        } else {
            throw new \RuntimeException('ALIPAY_PHONE_AES_KEY 解码后的长度不正确（期望 16 或 32 bytes）');
        }
        $plain = openssl_decrypt($cipherText, $algo, $aesKey, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new \RuntimeException('AES 解密失败');
        }

        $plain = trim((string)$plain);
        $json = json_decode($plain, true);
        if (is_array($json) && isset($json['mobile'])) {
            return (string)$json['mobile'];
        }

        // 兜底：直接从明文里提取手机号
        if (preg_match('/1[3-9]\\d{9}/', $plain, $m)) {
            return $m[0];
        }

        return '';
    }

    /**
     * 支付宝敏感信息验签（RSA2）
     */
    private function verifyAlipaySign(string $encryptedData, string $sign, string $publicKeyPem): void
    {
        $signature = base64_decode($sign, true);
        if ($signature === false) {
            throw new \RuntimeException('sign base64 解码失败');
        }

        // 支付宝加密报文验签时：需要对密文内容前后加双引号（有些实现要求）
        $signContent = $encryptedData;
        if (strpos(ltrim($encryptedData), '{') !== 0) {
            $signContent = '"' . $encryptedData . '"';
        }

        $ok = openssl_verify($signContent, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            throw new \RuntimeException('验签失败');
        }
    }

    public function updateUserType()
    {
        try {
            $auth = Request::header('Authorization', '');
            if (empty($auth) || strpos($auth, 'Bearer ') !== 0) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            $token = trim(substr($auth, 7));
            $payload = @json_decode(base64_decode($token), true);
            if (empty($payload['openid'])) {
                return json(['code' => 401, 'message' => 'token 无效']);
            }
            $openid = (string)$payload['openid'];
            $userType = Request::post('user_type', '');
            if (!in_array($userType, ['teacher', 'parent'], true)) {
                return json(['code' => 400, 'message' => 'user_type 须为 teacher 或 parent']);
            }

            // 支付宝端：直接写入 fa_users.user_type（不依赖 fa_wechat_users）
            Db::name('users')->where('openid', $openid)->update([
                'user_type' => $userType,
                'update_time' => date('Y-m-d H:i:s'),
            ]);

            return json(['code' => 200, 'message' => '已更新']);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '更新失败: ' . $e->getMessage()]);
        }
    }

    public function runtimeConfig()
    {
        try {
            $cfg = (new MiniProgramConfigService())->getRuntimeConfig('alipay');
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'platform' => 'alipay',
                    'app_id' => $cfg['app_id'],
                    'env_version' => $cfg['env_version'],
                    'source' => $cfg['source'],
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '获取失败: ' . $e->getMessage()]);
        }
    }

    private function exchangeAuthCode(string $authCode, string $appId = ''): array
    {
        error_log("exchangeAuthCode - 传入的 appId: {$appId}");
        
        $cfg = (new MiniProgramConfigService())->getRuntimeConfig('alipay', $appId);
        
        error_log("exchangeAuthCode - 获取到的配置: app_id={$cfg['app_id']}, source={$cfg['source']}");
        
        $appId = (string)($cfg['app_id'] ?? '');
        $privateKeyRaw = (string)($cfg['app_secret'] ?? '');
        if ($appId === '' || $privateKeyRaw === '') {
            throw new \RuntimeException('支付宝小程序配置缺失，请在后台配置 app_id 与私钥');
        }

        // 格式化私钥：自动添加 BEGIN/END 标记
        $privateKey = str_replace("\\n", "\n", $privateKeyRaw);
        if (strpos($privateKey, 'BEGIN') === false) {
            $cleaned = preg_replace('/\s+/', '', $privateKey);
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($cleaned, 64, "\n") . "-----END PRIVATE KEY-----";
        }
        
        // 验证私钥是否可用
        $keyResource = openssl_pkey_get_private($privateKey);
        if ($keyResource === false) {
            throw new \RuntimeException('支付宝私钥格式错误，无法加载：' . openssl_error_string());
        }
        openssl_free_key($keyResource);

        $params = [
            'app_id' => $appId,
            'method' => 'alipay.system.oauth.token',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'grant_type' => 'authorization_code',
            'code' => $authCode,
        ];

        $params['sign'] = $this->sign($params, $privateKey);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://openapi.alipay.com/gateway.do');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $this->configureCurlSsl($ch);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('请求支付宝失败: ' . $err);
        }
        
        $json = json_decode((string)$resp, true);
        if ($json === null) {
            throw new \RuntimeException('支付宝返回数据解析失败: ' . $resp);
        }
        
        $body = $json['alipay_system_oauth_token_response'] ?? [];
        
        if (empty($body) || (isset($body['code']) && $body['code'] !== '10000')) {
            $code = $body['code'] ?? 'unknown';
            $msg = $body['sub_msg'] ?? $body['msg'] ?? '未知错误';
            $subCode = $body['sub_code'] ?? '';
            
            // 详细错误信息
            $errorDetail = "支付宝 OAuth 失败 [code: {$code}";
            if ($subCode !== '') {
                $errorDetail .= ", sub_code: {$subCode}";
            }
            $errorDetail .= "]: {$msg}";
            
            // 记录完整响应用于调试
            error_log("支付宝登录失败详情: " . json_encode($json, JSON_UNESCAPED_UNICODE));
            
            throw new \RuntimeException($errorDetail);
        }
        
        return $body;
    }

    private function sign(array $params, string $privateKey): string
    {
        unset($params['sign']);
        ksort($params);
        $parts = [];
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null) {
                $parts[] = $k . '=' . $v;
            }
        }
        $data = implode('&', $parts);
        $ok = openssl_sign($data, $sign, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new \RuntimeException('支付宝请求签名失败');
        }
        return base64_encode($sign);
    }

    /**
     * 统一配置 cURL SSL 校验策略：
     * - 默认严格校验（生产安全）
     * - 本地开发可通过 env 临时关闭（ALIPAY_SSL_VERIFY=false）
     * - 或指定 CA 证书路径（ALIPAY_CURL_CA_BUNDLE）
     */
    private function configureCurlSsl($ch): void
    {
        $verify = (bool)env('ALIPAY_SSL_VERIFY', true);
        if (!$verify) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            return;
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $caBundle = (string)env('ALIPAY_CURL_CA_BUNDLE', '');
        if ($caBundle === '') {
            $caBundle = (string)env('CURL_CA_BUNDLE', '');
        }
        if ($caBundle === '') {
            $caBundle = (string)env('SSL_CERT_FILE', '');
        }
        if ($caBundle !== '' && is_file($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        }
    }

    private function generateToken(User $user): string
    {
        $payload = [
            'user_id' => $user->id,
            'openid' => $user->openid,
            'exp' => time() + 86400 * 30,
        ];
        $token = base64_encode(json_encode($payload));
        Cache::set('user_token_' . $token, $user->id, 86400 * 30);
        return $token;
    }
}
