<?php
namespace app;

// 应用请求对象类
class Request extends \think\Request
{
    /**
     * 判断是否HTTPS
     * @return bool
     */
    public function isHttps(): bool
    {
        $https = $this->server('HTTPS', '');
        if ($https && is_string($https) && strtolower($https) !== 'off') {
            return true;
        }
        
        $proto = $this->server('HTTP_X_FORWARDED_PROTO', '');
        if ($proto && is_string($proto) && $proto === 'https') {
            return true;
        }
        
        $frontHttps = $this->server('HTTP_FRONT_END_HTTPS', '');
        if ($frontHttps && is_string($frontHttps) && strtolower($frontHttps) !== 'off') {
            return true;
        }
        
        $port = $this->server('SERVER_PORT', 0);
        if (is_numeric($port) && intval($port) == 443) {
            return true;
        }
        
        // 宝塔默认使用HTTPS，如果以上都判断不出来，返回true
        return true;
    }
}
