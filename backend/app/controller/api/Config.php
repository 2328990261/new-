<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * API - 配置获取控制器
 */
class Config extends BaseController
{
    /**
     * 获取客服配置
     */
    public function getCustomerService()
    {
        try {
            $config = Db::name('notification_config')->find(1);
            
            $data = [];
            
            // 只返回客服微信号（如果配置了）
            if ($config && !empty($config['customer_service_wechat'])) {
                $data['wechat'] = $config['customer_service_wechat'];
            }
            
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

