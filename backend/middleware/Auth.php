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
        // ✅ 只启动Session，不重置Cookie参数
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 检查是否已登录
        $adminId = $_SESSION['admin_id'] ?? null;
        
        if (!$adminId) {
            return json([
                'success' => false,
                'error' => '未登录或登录已过期',
                'code' => 401
            ], 401);
        }
        
        // ✅ 滑动过期：每次请求时刷新 session cookie 有效期（30天）
        $this->refreshSessionCookie($request);
        
        // 将管理员信息注入到请求中
        $request->adminId = $adminId;
        $request->adminNickname = $_SESSION['admin_nickname'] ?? '';
        
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

