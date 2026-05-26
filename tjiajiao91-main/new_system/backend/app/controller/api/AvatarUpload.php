<?php
namespace app\controller\api;

use app\BaseController;
use app\service\UploadService;
use think\facade\Db;

/**
 * 头像上传控制器
 */
class AvatarUpload extends BaseController
{
    /**
     * 上传头像
     * POST /api/avatar/upload
     */
    public function upload()
    {
        try {
            $openid = $this->request->post('openid', '');
            $userId = $this->request->post('user_id', ''); // 支持通过用户ID上传
            
            // 调试信息
            \think\facade\Log::info('=== 头像上传调试信息 ===');
            \think\facade\Log::info('1. 接收到的OpenID: ' . $openid);
            \think\facade\Log::info('1. 接收到的用户ID: ' . $userId);
            
            // 如果有用户ID但没有OpenID，通过用户ID获取OpenID
            if (empty($openid) && !empty($userId)) {
                $user = \think\facade\Db::name('users')->where('id', $userId)->find();
                if ($user) {
                    $openid = $user['openid'];
                    \think\facade\Log::info('2. 通过用户ID获取到OpenID: ' . $openid);
                }
            }
            
            if (empty($openid)) {
                return json(['code' => 400, 'message' => '缺少用户标识']);
            }
            
            // 获取上传的文件
            $file = $this->request->file('avatar');
            if (!$file) {
                return json(['code' => 400, 'message' => '未找到上传文件']);
            }

            $service = new UploadService();
            $ext = strtolower((string)$file->extension());
            $filename = 'avatar_' . md5($openid . time()) . '.' . $ext;
            $stored = $service->storeToPublicUploads(
                $file,
                'avatars',
                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                2 * 1024 * 1024,
                $filename,
                'Ym'
            );

            if (empty($stored['success'])) {
                return json(['code' => 400, 'message' => $stored['message'] ?? '上传失败']);
            }

            $relativePath = ltrim((string)($stored['data']['relative_path'] ?? ''), '/');
            $request = request();
            $domain = $request->domain();
            $isProd = (strpos($domain, 'localhost') === false && strpos($domain, '127.0.0.1') === false);
            $avatarUrl = $isProd ? $relativePath : ($domain . '/' . $relativePath);
            
            // 更新用户头像
            $this->updateUserAvatar($openid, $avatarUrl);
            
            \think\facade\Log::info('8. 最终返回的头像URL: ' . $avatarUrl);
            
            return json([
                'code' => 200,
                'message' => '头像上传成功',
                'data' => [
                    'avatar_url' => $avatarUrl
                ]
            ]);
            
        } catch (\Exception $e) {
            \think\facade\Log::error('头像上传失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '上传失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 清理临时头像URL
     * POST /api/avatar/cleanup-temp
     */
    public function cleanupTemp()
    {
        try {
            $openid = $this->request->post('openid', '');
            
            if (empty($openid)) {
                return json(['code' => 400, 'message' => '缺少用户标识']);
            }
            
            // 清理 fa_users 表中的临时头像URL
            $usersResult = Db::name('users')
                ->where('openid', $openid)
                ->where('avatar', 'like', '%tmp/%')
                ->update([
                    'avatar' => '',
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            
            // 清理 fa_wechat_users 表中的临时头像URL
            $wechatResult = Db::name('wechat_users')
                ->where('openid', $openid)
                ->where('headimgurl', 'like', '%tmp/%')
                ->update([
                    'headimgurl' => '',
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            
            return json([
                'code' => 200,
                'message' => '临时头像URL已清理',
                'data' => [
                    'users_updated' => $usersResult,
                    'wechat_updated' => $wechatResult
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '清理失败：' . $e->getMessage()]);
        }
    }
    private function updateUserAvatar($openid, $avatarUrl)
    {
        try {
            \think\facade\Log::info('9. 开始更新数据库头像URL: ' . $avatarUrl);
            
            // 更新 fa_users 表
            $usersResult = Db::name('users')
                ->where('openid', $openid)
                ->update([
                    'avatar' => $avatarUrl,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            
            \think\facade\Log::info('10. fa_users表更新结果: ' . ($usersResult ? '成功' : '失败'));
            
            // 同步更新 fa_wechat_users 表
            $wechatResult = Db::name('wechat_users')
                ->where('openid', $openid)
                ->update([
                    'headimgurl' => $avatarUrl,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                
            \think\facade\Log::info('11. fa_wechat_users表更新结果: ' . ($wechatResult ? '成功' : '失败'));
            
            // 验证更新结果
            $userInfo = Db::name('users')->where('openid', $openid)->find();
            \think\facade\Log::info('12. 验证fa_users表中的头像URL: ' . ($userInfo['avatar'] ?? '未找到'));
                
        } catch (\Exception $e) {
            // 记录错误但不影响主流程
            \think\facade\Log::error('更新用户头像失败: ' . $e->getMessage());
            trace('更新用户头像失败: ' . $e->getMessage(), 'error');
        }
    }
}