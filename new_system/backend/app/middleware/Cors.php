<?php
namespace app\middleware;

/**
 * 跨域请求中间件
 */
class Cors
{
    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        // 获取请求来源
        $origin = $request->header('origin', '');
        
        // 允许的域名列表
        $allowedOrigins = [
            'http://localhost:5173',  // 用户端
            'http://localhost:5174',  // 管理端
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
        ];
        
        // 如果请求来源在允许列表中，则允许
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // 处理OPTIONS预检请求
        if ($request->method() == 'OPTIONS') {
            return response('', 204);
        }
        
        return $next($request);
    }
}

