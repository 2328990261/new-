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
        
        // 将管理员信息注入到请求中
        $request->adminId = $adminId;
        $request->adminNickname = $_SESSION['admin_nickname'] ?? '';
        
        return $next($request);
    }
}

