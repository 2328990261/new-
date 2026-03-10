<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * API - 网站横幅控制器（用户端）
 */
class SiteBanner extends BaseController
{
    /**
     * 获取启用的横幅列表（用户端）
     */
    public function index()
    {
        try {
            $banners = Db::name('site_banners')
                ->where('status', 1)
                ->order('sort_order', 'asc')
                ->order('id', 'desc')
                ->select()
                ->toArray();
            
            return json([
                'success' => true,
                'data' => $banners
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

