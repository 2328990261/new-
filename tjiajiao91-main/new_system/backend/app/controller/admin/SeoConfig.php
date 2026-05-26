<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * SEO配置管理控制器
 */
class SeoConfig extends BaseController
{
    /**
     * 获取SEO配置列表
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $configs = Db::name('seo_config')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            return json(['success' => true, 'data' => $configs]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取单个SEO配置
     */
    public function read($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $config = Db::name('seo_config')->find($id);
            
            if (!$config) {
                return json(['success' => false, 'error' => '配置不存在']);
            }
            
            return json(['success' => true, 'data' => $config]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新SEO配置
     */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $data = $this->request->post();
        
        try {
            $config = Db::name('seo_config')->find($id);
            
            if (!$config) {
                return json(['success' => false, 'error' => '配置不存在']);
            }
            
            // 处理布尔值
            if (isset($data['is_enabled'])) {
                $data['is_enabled'] = $data['is_enabled'] ? 1 : 0;
            }
            
            Db::name('seo_config')->where('id', $id)->update($data);
            
            return json(['success' => true, 'message' => '更新成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取站点地图配置
     */
    public function sitemap()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $configs = Db::name('sitemap_config')
                ->order('priority', 'desc')
                ->select()
                ->toArray();
            
            return json(['success' => true, 'data' => $configs]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新站点地图配置
     */
    public function updateSitemap($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $data = $this->request->post();
        
        try {
            $config = Db::name('sitemap_config')->find($id);
            
            if (!$config) {
                return json(['success' => false, 'error' => '配置不存在']);
            }
            
            // 处理布尔值
            if (isset($data['is_enabled'])) {
                $data['is_enabled'] = $data['is_enabled'] ? 1 : 0;
            }
            
            Db::name('sitemap_config')->where('id', $id)->update($data);
            
            return json(['success' => true, 'message' => '更新成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 生成站点地图XML（公开接口）
     */
    public function generateSitemap()
    {
        try {
            $configs = Db::name('sitemap_config')
                ->where('is_enabled', 1)
                ->order('priority', 'desc')
                ->select()
                ->toArray();
            
            $baseUrl = $this->request->domain();
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            foreach ($configs as $config) {
                $xml .= '  <url>' . "\n";
                $xml .= '    <loc>' . htmlspecialchars($baseUrl . $config['url_path']) . '</loc>' . "\n";
                $xml .= '    <lastmod>' . date('Y-m-d', strtotime($config['lastmod'] ?: $config['update_time'])) . '</lastmod>' . "\n";
                $xml .= '    <changefreq>' . htmlspecialchars($config['changefreq']) . '</changefreq>' . "\n";
                $xml .= '    <priority>' . $config['priority'] . '</priority>' . "\n";
                $xml .= '  </url>' . "\n";
            }
            
            $xml .= '</urlset>';
            
            // 设置响应头
            header('Content-Type: application/xml; charset=utf-8');
            echo $xml;
            exit;
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '生成失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 生成robots.txt（公开接口）
     */
    public function generateRobots()
    {
        try {
            $baseUrl = $this->request->domain();
            
            $robots = "User-agent: *\n";
            $robots .= "Allow: /\n";
            $robots .= "Disallow: /admin/\n";
            $robots .= "Disallow: /api/\n";
            $robots .= "Disallow: /backend/\n";
            $robots .= "\n";
            $robots .= "Sitemap: " . $baseUrl . "/sitemap.xml\n";
            
            // 设置响应头
            header('Content-Type: text/plain; charset=utf-8');
            echo $robots;
            exit;
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '生成失败：' . $e->getMessage()]);
        }
    }
}
