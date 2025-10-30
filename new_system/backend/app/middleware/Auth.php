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
        // 使用原生PHP Session
        if (session_status() === PHP_SESSION_NONE) {
            // 设置session cookie参数：24小时有效期
            session_set_cookie_params([
                'lifetime' => 86400, // 24小时
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
        
        // 检查是否已登录（使用原生Session）
        $adminId = $_SESSION['admin_id'] ?? null;
        $adminNickname = $_SESSION['admin_nickname'] ?? null;
        
        // 调试信息
        $debugInfo = [
            'session_id' => session_id(),
            'session_name' => session_name(),
            'session_status' => session_status(),
            'has_admin_id' => !empty($adminId),
            'has_cookie' => isset($_COOKIE[session_name()]),
            'cookie_value' => $_COOKIE[session_name()] ?? 'no cookie',
            'all_session' => $_SESSION ?? [],
        ];
        
        if (!$adminId || !$adminNickname) {
            return json([
                'success' => false,
                'error' => '未登录或登录已过期',
                'code' => 401,
                'debug' => $debugInfo // 调试用
            ], 401);
        }
        
        // 将管理员信息注入到请求中
        $request->adminId = $adminId;
        $request->adminNickname = $adminNickname;
        
        return $next($request);
    }
}

