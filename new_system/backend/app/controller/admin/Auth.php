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
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $remember = $this->request->post('remember', false);
        
        if (empty($username) || empty($password)) {
            return json(['success' => false, 'error' => '请提供用户名和密码']);
        }
        
        // ✅ 只在登录时设置一次 Session Cookie 参数
        if (session_status() === PHP_SESSION_NONE) {
            // 根据remember参数设置不同的有效期
            // 勾选"长期保持登录"：30天，不勾选：7天
            $lifetime = $remember ? 2592000 : 604800; // 30天 = 2592000秒，7天 = 604800秒
            $isHttps = $this->request->isHttps();
            
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',           // ⚠️ 整个域名有效
                'domain' => '',          // 空表示当前域名
                'secure' => $isHttps,    // HTTPS下启用
                'httponly' => true,      // 防止JS访问
                'samesite' => 'Lax'      // 防止CSRF
            ]);
            
            session_start();
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
            
            // ✅ 存储到原生 Session
            $_SESSION['admin_id'] = $admin->id;
            $_SESSION['admin_nickname'] = $admin->nickname;
            $_SESSION['admin_username'] = $admin->username;
            $_SESSION['admin_role'] = $admin->role;
            $_SESSION['login_time'] = time();  // 记录登录时间
            
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
                    'role' => $admin->role
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 清空所有Session数据
        $_SESSION = [];
        
        // 删除Session Cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // 销毁Session
        session_destroy();
        
        return json(['success' => true, 'message' => '已退出登录']);
    }
    
    /**
     * 检查登录状态
     */
    public function check()
    {
        // ✅ 只启动Session，不重置Cookie参数
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $adminId = $_SESSION['admin_id'] ?? null;
        
        if ($adminId) {
            // ✅ 滑动过期：每次检查时刷新 session cookie 有效期
            $this->refreshSessionCookie();
            
            return json([
                'success' => true,
                'data' => [
                    'id' => $_SESSION['admin_id'] ?? null,
                    'username' => $_SESSION['admin_username'] ?? '',
                    'nickname' => $_SESSION['admin_nickname'] ?? '',
                    'role' => $_SESSION['admin_role'] ?? 'customer_service'
                ]
            ]);
        } else {
            return json(['success' => false, 'error' => '未登录'], 401);
        }
    }
    
    /**
     * 刷新 Session Cookie 有效期（滑动过期）
     */
    private function refreshSessionCookie()
    {
        // 每次活动时延长 session 有效期（30天）
        $lifetime = 2592000; // 30天
        $isHttps = $this->request->isHttps();
        
        // 重新设置 cookie
        setcookie(
            session_name(),
            session_id(),
            [
                'expires' => time() + $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
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

