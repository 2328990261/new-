<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Admin;

/**
 * 管理员认证控制器
 */
class Auth extends BaseController
{
    /**
     * 管理员登录
     */
    public function login()
    {
        // 使用原生PHP Session
        if (session_status() === PHP_SESSION_NONE) {
            // 设置session cookie参数：24小时有效期
            session_set_cookie_params([
                'lifetime' => 86400, // 24小时
                'path' => '/',
                'domain' => '',
                'secure' => false, // 生产环境应设为true（使用HTTPS）
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
        
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        
        if (empty($username) || empty($password)) {
            return json(['success' => false, 'error' => '请提供用户名和密码']);
        }
        
        try {
            // 查询管理员
            $admin = Admin::where('username', $username)->find();
            
            if (!$admin) {
                return json(['success' => false, 'error' => '用户名或密码错误']);
            }
            
            // 验证密码
            if (!password_verify($password, $admin->password)) {
                return json(['success' => false, 'error' => '用户名或密码错误']);
            }
            
            // 使用原生Session存储
            $_SESSION['admin_id'] = $admin->id;
            $_SESSION['admin_nickname'] = $admin->nickname;
            $_SESSION['admin_username'] = $admin->username;
            $_SESSION['admin_role'] = $admin->role;
            
            // 更新最后登录时间
            $admin->last_login_time = date('Y-m-d H:i:s');
            $admin->save();
            
            return json([
                'success' => true,
                'message' => '登录成功',
                'data' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'nickname' => $admin->nickname,
                    'role' => $admin->role,
                    'session_id' => session_id()
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '登录失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 退出登录
     */
    public function logout()
    {
        // 使用原生Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        return json(['success' => true, 'message' => '已退出登录']);
    }
    
    /**
     * 检查登录状态
     */
    public function check()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $adminId = $_SESSION['admin_id'] ?? null;
        
        if ($adminId) {
            return json([
                'success' => true,
                'data' => [
                    'id' => $_SESSION['admin_id'] ?? null,
                    'username' => $_SESSION['admin_username'] ?? '',
                    'nickname' => $_SESSION['admin_nickname'] ?? ''
                ]
            ]);
        } else {
            return json(['success' => false, 'error' => '未登录']);
        }
    }
    
    /**
     * 修改密码
     */
    public function changePassword()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $adminId = $_SESSION['admin_id'] ?? null;
        if (!$adminId) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $oldPassword = $this->request->post('old_password');
        $newPassword = $this->request->post('new_password');
        
        if (empty($oldPassword) || empty($newPassword)) {
            return json(['success' => false, 'error' => '请提供旧密码和新密码']);
        }
        
        try {
            $admin = Admin::find($adminId);
            
            // 验证旧密码
            if (!password_verify($oldPassword, $admin->password)) {
                return json(['success' => false, 'error' => '旧密码错误']);
            }
            
            // 更新密码
            $admin->password = $newPassword;
            $admin->save();
            
            return json(['success' => true, 'message' => '密码修改成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '修改失败：' . $e->getMessage()]);
        }
    }
}

