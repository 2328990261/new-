<?php
namespace app\controller\api;

use app\BaseController;
use app\service\WechatMiniProgramService;
use think\Response;
use think\facade\Config;
use think\facade\Request;

/**
 * 微信小程序登录控制器
 */
class WechatMiniProgram extends BaseController
{
    protected $service;
    
    protected function initialize()
    {
        parent::initialize();
        $this->service = new WechatMiniProgramService();
    }
    
    /**
     * 微信小程序登录 - 获取openid和session_key
     * @return Response
     */
    public function login()
    {
        $code = Request::post('code');
        $superiorOpenid = Request::post('superior_openid', '');

        if (empty($code)) {
            return json([
                'code' => 400,
                'message' => '缺少code参数'
            ]);
        }

        try {
            $extraInfo = [];
            if (!empty($superiorOpenid)) {
                $extraInfo['superior_openid'] = $superiorOpenid;
            }
            $result = $this->service->login($code, $extraInfo);

            return json([
                'code' => 200,
                'message' => '登录成功',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * 使用 openid 静默登录（已登录过的用户再次打开时免手机号）
     * @return Response
     */
    public function loginWithOpenid()
    {
        $code = Request::post('code');
        $openid = Request::post('openid');
        $superiorOpenid = Request::post('superior_openid', '');

        if (empty($code) || empty($openid)) {
            return json([
                'code' => 400,
                'message' => '缺少 code 或 openid 参数'
            ]);
        }

        try {
            $result = $this->service->loginWithOpenid($code, $openid, [
                'superior_openid' => $superiorOpenid
            ]);
            return json([
                'code' => 200,
                'message' => '登录成功',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 手机号授权登录
     * @return Response
     */
    public function loginWithPhone()
    {
        $code = Request::post('code'); // 微信登录code
        $phoneCode = Request::post('phone_code'); // 手机号code（新版）
        $encryptedData = Request::post('encrypted_data'); // 加密数据（旧版）
        $iv = Request::post('iv'); // 初始向量（旧版）
        
        // 接收用户输入的昵称和头像
        $nickname = Request::post('nickname', '');
        $avatar = Request::post('avatar', '');
        $userType = Request::post('user_type', ''); // 用户类型：teacher/parent
        $inviterOpenid = Request::post('inviter_openid', ''); // 邀请人openid
        $superiorOpenid = Request::post('superior_openid', ''); // 上级管理员openid（分享预约来源）
        
        // 记录接收到的参数（调试用）
        \think\facade\Log::info('Login params received: ' . json_encode([
            'code' => $code,
            'phoneCode' => $phoneCode,
            'nickname' => $nickname,
            'avatar' => $avatar,
            'userType' => $userType,
            'inviterOpenid' => $inviterOpenid,
            'superiorOpenid' => $superiorOpenid
        ]));
        
        if (empty($code)) {
            return json([
                'code' => 400,
                'message' => '缺少code参数'
            ]);
        }
        
        try {
            // 构建额外信息
            $extraInfo = [];
            if (!empty($nickname)) {
                $extraInfo['nickname'] = $nickname;
            }
            if (!empty($avatar)) {
                $extraInfo['avatar'] = $avatar;
            }
            if (!empty($userType)) {
                $extraInfo['user_type'] = $userType;
            }
            if (!empty($inviterOpenid)) {
                $extraInfo['inviter_openid'] = $inviterOpenid;
            }
            if (!empty($superiorOpenid)) {
                $extraInfo['superior_openid'] = $superiorOpenid;
            }
            
            // 优先使用新版phone_code
            if (!empty($phoneCode)) {
                $result = $this->service->loginWithPhoneCode($code, $phoneCode, $extraInfo);
            } elseif (!empty($encryptedData) && !empty($iv)) {
                $result = $this->service->loginWithEncryptedData($code, $encryptedData, $iv, $extraInfo);
            } else {
                return json([
                    'code' => 400,
                    'message' => '缺少手机号授权参数'
                ]);
            }
            
            return json([
                'code' => 200,
                'message' => '登录成功',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('Login failed: ' . $e->getMessage());
            \think\facade\Log::error('Error file: ' . $e->getFile() . ' line: ' . $e->getLine());
            
            return json([
                'code' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 仅更新当前用户的 user_type（身份选择页“确认身份”时调用，已登录则同步到服务端）
     * 需携带 token，从 token 解析 openid，不接收客户端传的 openid 以防篡改
     * @return Response
     */
    public function updateUserType()
    {
        $auth = Request::header('Authorization', '');
        if (empty($auth) || strpos($auth, 'Bearer ') !== 0) {
            return json(['code' => 401, 'message' => '请先登录']);
        }
        $token = trim(substr($auth, 7));
        $payload = @json_decode(base64_decode($token), true);
        if (empty($payload['openid'])) {
            return json(['code' => 401, 'message' => 'token 无效']);
        }
        $openid = $payload['openid'];
        $userType = Request::post('user_type', '');
        if (!in_array($userType, ['teacher', 'parent'], true)) {
            return json(['code' => 400, 'message' => 'user_type 须为 teacher 或 parent']);
        }
        $ok = $this->service->updateUserType($openid, $userType);
        if ($ok) {
            return json(['code' => 200, 'message' => '已更新']);
        }
        return json(['code' => 500, 'message' => '更新失败，请稍后重试']);
    }
    
    /**
     * 生成小程序码
     * @return Response
     */
    public function generateQRCode()
    {
        $page = Request::post('page', 'pages/index/index');
        $scene = Request::post('scene', '');
        $width = Request::post('width', 430);
        $envVersion = Request::post('env_version', 'release');
        $isHyaline = Request::post('is_hyaline', false);
        $checkPath = Request::post('check_path', false); // 默认关闭路径检查
        
        if (empty($scene)) {
            return json([
                'code' => 400,
                'message' => '缺少scene参数'
            ]);
        }
        
        try {
            $options = [
                'width' => $width,
                'env_version' => $envVersion,
                'check_path' => $checkPath,
                'auto_color' => false,
                'is_hyaline' => $isHyaline
            ];
            
            $result = $this->service->generateQRCode($page, $scene, $options);
            
            if ($result['success']) {
                return json([
                    'code' => 200,
                    'message' => '生成成功',
                    'data' => [
                        'qrcode' => $result['data']
                    ]
                ]);
            } else {
                return json([
                    'code' => 500,
                    'message' => $result['error']
                ]);
            }
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }
}
