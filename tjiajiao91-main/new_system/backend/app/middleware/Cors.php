<?php
declare (strict_types=1);

namespace app\middleware;

use Closure;

class Cors
{
    public function handle($request, Closure $next)
    {
        $isLocal = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1']);
        
        if ($isLocal) {
            $allowedOrigins = [
                'http://localhost:3000',
                'http://localhost:3001',
                'http://localhost:3002',  // 管理端前端
                'http://127.0.0.1:3000',
                'http://127.0.0.1:3001',
                'http://127.0.0.1:3002',
            ];
        } else {
            $domain = defined('APP_DOMAIN') ? APP_DOMAIN : 'https://your-domain.com';
            $allowedOrigins = [
                $domain,
                str_replace('https://', 'https://www.', $domain),
            ];
        }
        
        $origin = $request->header('origin', '');
        
        if (in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } elseif (count($allowedOrigins) > 0) {
            header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
        }
        
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        if ($request->method(true) === 'OPTIONS') {
            return response('', 204);
        }

        return $next($request);
    }
}
