<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\CityLight as CityLightModel;
use app\model\City as CityModel;
use think\facade\Session;

/**
 * 城市点亮管理控制器（管理端）
 */
class CityLight extends BaseController
{
    /**
     * 获取城市点亮统计列表
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
            $provinceId = $this->request->get('province_id');
            $status = $this->request->get('status');
            $keyword = $this->request->get('keyword');
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 20);
            
            // 获取统计数据
            $stats = CityLightModel::getCityStats($provinceId, $status);
            
            // 关键词搜索
            if ($keyword) {
                $stats = array_filter($stats, function($item) use ($keyword) {
                    return strpos($item['city_name'], $keyword) !== false;
                });
                $stats = array_values($stats);
            }
            
            // 计算总数
            $total = count($stats);
            
            // 分页
            $offset = ($page - 1) * $limit;
            $stats = array_slice($stats, $offset, $limit);
            
            // 添加进度信息
            foreach ($stats as &$stat) {
                $stat['progress_percent'] = round($stat['total_lights'] / 1000 * 100, 2);
                $stat['progress_text'] = $stat['total_lights'] . '/1000';
                $stat['can_open'] = $stat['total_lights'] >= 1000 && $stat['status'] == 0;
            }
            
            return json([
                'success' => true,
                'data' => $stats,
                'total' => $total,
                'page' => (int)$page,
                'limit' => (int)$limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 手动开通城市
     */
    public function openCity()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $provinceId = $this->request->post('province_id');
        $cityName = $this->request->post('city_name');
        $cityCode = $this->request->post('city_code', '');
        
        if (!$provinceId || !$cityName) {
            return json(['success' => false, 'error' => '请提供省份和城市名称']);
        }
        
        try {
            // 检查城市是否已存在
            $existingCity = CityModel::where('province_id', $provinceId)
                ->where('name', $cityName)
                ->find();
            
            if ($existingCity) {
                // 如果城市已存在，启用它
                $existingCity->status = 1;
                $existingCity->save();
            } else {
                // 创建新城市
                CityModel::create([
                    'province_id' => $provinceId,
                    'code' => $cityCode,
                    'name' => $cityName,
                    'level' => '三线城市',
                    'sort' => 999,
                    'status' => 1
                ]);
            }
            
            // 更新点亮记录状态
            CityLightModel::where('province_id', $provinceId)
                ->where('city_name', $cityName)
                ->update(['status' => 1]);
            
            return json(['success' => true, 'message' => '城市开通成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '开通失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取城市点亮用户列表
     */
    public function getLightUsers()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $provinceId = $this->request->get('province_id');
        $cityName = $this->request->get('city_name');
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        
        if (!$provinceId || !$cityName) {
            return json(['success' => false, 'error' => '请提供省份和城市名称']);
        }
        
        try {
            $query = CityLightModel::where('province_id', $provinceId)
                ->where('city_name', $cityName)
                ->order('create_time', 'desc');
            
            $total = $query->count();
            
            $list = $query->page($page, $limit)->select();
            
            return json([
                'success' => true,
                'data' => $list,
                'total' => $total,
                'page' => (int)$page,
                'limit' => (int)$limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取统计概览
     */
    public function statistics()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 总点亮城市数
            $totalCities = \think\facade\Db::name('city_lights')
                ->field('COUNT(DISTINCT CONCAT(province_id, city_name)) as count')
                ->find();
            
            // 待开通城市数（达到1000人）
            $canOpenCities = \think\facade\Db::query(
                "SELECT COUNT(*) as count FROM (
                    SELECT province_id, city_name
                    FROM fa_city_lights
                    WHERE status = 0
                    GROUP BY province_id, city_name
                    HAVING COUNT(DISTINCT user_identifier) >= 1000
                ) as t"
            );
            
            // 已开通城市数
            $openedCities = \think\facade\Db::name('city_lights')
                ->where('status', 1)
                ->field('COUNT(DISTINCT CONCAT(province_id, city_name)) as count')
                ->find();
            
            // 总点亮人次
            $totalLights = CityLightModel::count();
            
            return json([
                'success' => true,
                'data' => [
                    'total_cities' => $totalCities['count'] ?? 0,
                    'can_open_cities' => $canOpenCities[0]['count'] ?? 0,
                    'opened_cities' => $openedCities['count'] ?? 0,
                    'total_lights' => $totalLights
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}




