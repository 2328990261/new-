<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * 网站基础配置管理
 */
class SiteConfig extends BaseController
{
    /**
     * 获取网站基础配置
     */
    public function getConfig()
    {
        try {
            $config = Db::name('site_config')->find(1);
            
            if (!$config) {
                // 如果不存在，返回默认配置
                $config = [
                    'id' => 1,
                    'platform_name' => '家教平台',
                    'platform_slogan' => '',
                    'icp_number' => '',
                    'police_number' => '',
                    'police_link' => '',
                    'copyright_info' => '© 2024 家教平台 版权所有',
                    'company_name' => '',
                    'contact_phone' => '',
                    'contact_email' => '',
                    'contact_address' => '',
                    'logo_url' => '',
                    'favicon_url' => '',
                    'banner_image' => '',
                    'banner_link' => '',
                    'banner_title' => '',
                    'banner_description' => '',
                    'meta_keywords' => '',
                    'meta_description' => '',
                    'statistics_code' => '',
                    'custom_header_code' => '',
                    'custom_footer_code' => '',
                    'status' => 1
                ];
            }
            
            return json([
                'success' => true,
                'data' => $config
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 更新网站基础配置
     */
    public function updateConfig()
    {
        try {
            $data = $this->request->param();
            
            // 过滤允许更新的字段
            $allowFields = [
                'platform_name', 'platform_slogan', 'icp_number', 'police_number', 
                'police_link', 'copyright_info', 'company_name', 'contact_phone', 
                'contact_email', 'contact_address', 'logo_url', 'favicon_url',
                'banner_image', 'banner_link', 'banner_title', 'banner_description',
                'meta_keywords', 'meta_description', 'statistics_code',
                'custom_header_code', 'custom_footer_code', 'status'
            ];
            
            $updateData = [];
            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                return json([
                    'success' => false,
                    'error' => '没有要更新的数据'
                ]);
            }
            
            $updateData['update_time'] = date('Y-m-d H:i:s');
            
            // 检查配置是否存在
            $exists = Db::name('site_config')->find(1);
            
            if ($exists) {
                // 更新
                Db::name('site_config')->where('id', 1)->update($updateData);
            } else {
                // 创建
                $updateData['id'] = 1;
                $updateData['create_time'] = date('Y-m-d H:i:s');
                Db::name('site_config')->insert($updateData);
            }
            
            return json([
                'success' => true,
                'message' => '保存成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

