<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * API - 网站配置控制器
 */
class SiteConfig extends BaseController
{
    /**
     * 获取网站基础配置（用户端）
     */
    public function getConfig()
    {
        try {
            $config = Db::name('site_config')->find(1);
            
            if (!$config) {
                return json([
                    'success' => false,
                    'error' => '配置不存在'
                ]);
            }
            
            // 只返回用户端需要的配置
            $data = [
                'banner_image' => $config['banner_image'] ?? '',
                'banner_link' => $config['banner_link'] ?? '',
                'banner_title' => $config['banner_title'] ?? '',
                'banner_description' => $config['banner_description'] ?? '',
            ];
            
            return json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
