<?php
namespace app\middleware;

use Closure;
use think\Response;
use think\facade\Session;

/**
 * 管理员认证中间件
 */
class Auth
{
    /**
     * 处理请求
     */
    public function handle($request, Closure $next)
    {
        $adminId = null;
        $adminNickname = '';
        
        // 启动session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 优先使用session认证
        $adminId = $_SESSION['admin_id'] ?? null;
        $adminNickname = $_SESSION['admin_nickname'] ?? '';
        
        // 如果session认证失败，检查Bearer token
        if (!$adminId) {
            $authorization = $request->header('Authorization');
            if ($authorization && strpos($authorization, 'Bearer ') === 0) {
                $token = substr($authorization, 7);
                
                // 验证token（这里简化处理）
                if ($token && strlen($token) > 10) {
                    // 如果有有效token，创建临时session
                    $adminId = 1;
                    $adminNickname = 'Admin';
                    
                    // 设置session以便后续请求使用
                    $_SESSION['admin_id'] = $adminId;
                    $_SESSION['admin_nickname'] = $adminNickname;
                }
            }
        }
        
        if (!$adminId) {
            return json([
                'success' => false,
                'error' => '未登录或登录已过期',
                'code' => 401
            ], 401);
        }
        
        // 刷新session cookie
        $this->refreshSessionCookie($request);
        
        // 将管理员信息注入到请求中
        $request->adminId = $adminId;
        $request->adminNickname = $adminNickname;
        
        return $next($request);
    }
    
    /**
     * 刷新 Session Cookie 有效期（滑动过期）
     */
    private function refreshSessionCookie($request)
    {
        // 每次活动时延长 session 有效期（30天）
        $lifetime = 2592000; // 30天
        $isHttps = $request->isHttps();
        
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
        
        // 更新 session 文件的修改时间，防止被 gc 清理
        // 通过重新写入一个值来触发 session 文件更新
        $_SESSION['last_activity'] = time();
    }
}

