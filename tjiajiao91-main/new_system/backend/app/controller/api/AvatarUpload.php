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
            
            \think\facade\Log::info('3. 上传文件信息: ' . json_encode([
                'name' => $file->getOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMime()
            ]));
            
            // 验证文件类型
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMime(), $allowedTypes)) {
                return json(['code' => 400, 'message' => '不支持的文件类型']);
            }
            
            // 验证文件大小（最大2MB）
            if ($file->getSize() > 2 * 1024 * 1024) {
                return json(['code' => 400, 'message' => '文件大小不能超过2MB']);
            }
            
            // 生成文件名
            $extension = $file->extension();
            $filename = 'avatar_' . md5($openid . time()) . '.' . $extension;
            
            // 保存到服务器
            $uploadPath = 'uploads/avatars/' . date('Ym') . '/';
            $fullPath = public_path() . $uploadPath;
            
            \think\facade\Log::info('4. 保存路径信息: ' . json_encode([
                'uploadPath' => $uploadPath,
                'fullPath' => $fullPath,
                'filename' => $filename
            ]));
            
            // 创建目录
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
                \think\facade\Log::info('5. 创建目录: ' . $fullPath);
            }
            
            // 移动文件
            $filePath = $fullPath . $filename;
            if (!$file->move($fullPath, $filename)) {
                return json(['code' => 500, 'message' => '保存文件失败']);
            }
            
            \think\facade\Log::info('6. 文件保存成功: ' . $filePath);
            
            // 生成访问URL
            $request = request();
            $domain = $request->domain();
            
            // 线上环境特殊处理
            if (strpos($domain, 'localhost') === false && strpos($domain, '127.0.0.1') === false) {
                // 线上环境，返回相对路径，让前端处理完整URL
                $avatarUrl = $uploadPath . $filename;
                \think\facade\Log::info('7. 线上环境，返回相对路径: ' . $avatarUrl);
            } else {
                // 开发环境，返回完整URL
                $avatarUrl = $domain . '/' . $uploadPath . $filename;
                \think\facade\Log::info('7. 开发环境，返回完整URL: ' . $avatarUrl);
            }
            
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