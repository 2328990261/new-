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
        
        if (empty($code)) {
            return json([
                'code' => 400,
                'message' => '缺少code参数'
            ]);
        }
        
        try {
            $result = $this->service->login($code);
            
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
        
        // 记录接收到的参数（调试用）
        \think\facade\Log::info('Login params received: ' . json_encode([
            'code' => $code,
            'phoneCode' => $phoneCode,
            'nickname' => $nickname,
            'avatar' => $avatar,
            'userType' => $userType
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
