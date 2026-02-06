<?php
namespace app\controller\api;

use app\BaseController;
use app\model\CityLight as CityLightModel;
use app\model\Province;
use think\facade\Request;

/**
 * 城市点亮控制器（用户端）
 */
class CityLight extends BaseController
{
    /**
     * 获取未开通城市列表（按城市等级分类）
     */
    public function unopenedCities()
    {
        try {
            // 查询所有启用的城市
            $cities = \think\facade\Db::name('cities')
                ->alias('c')
                ->join('provinces p', 'c.province_id = p.id', 'LEFT')
                ->where('c.status', 1)
                ->field('c.id, c.name as city_name, c.province_id, p.name as province_name, c.code, c.level')
                ->order('c.sort', 'asc')
                ->select()
                ->toArray();
            
            $result = [
                '一线城市' => [],
                '新一线城市' => [],
                '二线城市' => [],
                '三线城市' => [],
                '其他城市' => []
            ];
            
            foreach ($cities as $city) {
                // 检查该城市是否有订单
                $orderCount = \think\facade\Db::name('tutor_orders_new')
                    ->where('city_id', $city['id'])
                    ->where('status', 1)
                    ->count();
                
                // 只返回没有订单的城市
                if ($orderCount == 0) {
                    // 检查该城市的点亮人数
                    $lightCount = \think\facade\Db::query(
                        "SELECT COUNT(DISTINCT user_identifier) as count 
                         FROM fa_city_lights 
                         WHERE province_id = ? AND city_name = ?",
                        [$city['province_id'], $city['city_name']]
                    );
                    
                    $totalLights = $lightCount[0]['count'] ?? 0;
                    
                    $cityData = [
                        'city_id' => $city['id'],
                        'city_name' => $city['city_name'],
                        'province_id' => $city['province_id'],
                        'province_name' => $city['province_name'],
                        'city_code' => $city['code'],
                        'level' => $city['level'],
                        'total_lights' => $totalLights,
                        'progress_percent' => round($totalLights / 1000 * 100, 2),
                        'is_lighted' => $totalLights > 0
                    ];
                    
                    $level = $city['level'] ?: '其他城市';
                    if (!isset($result[$level])) {
                        $result[$level] = [];
                    }
                    $result[$level][] = $cityData;
                }
            }
            
            // 过滤空分类并格式化
            $formattedResult = [];
            foreach ($result as $level => $cities) {
                if (!empty($cities)) {
                    $formattedResult[] = [
                        'level' => $level,
                        'cities' => $cities
                    ];
                }
            }
            
            return json(['success' => true, 'data' => $formattedResult]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 点亮城市（支持助力机制和3城市限制）
     */
    public function lightCity()
    {
        $provinceId = $this->request->post('province_id');
        $cityName = $this->request->post('city_name');
        $cityCode = $this->request->post('city_code', '');
        $userContact = $this->request->post('user_contact', '');
        $inviterIdentifier = $this->request->post('inviter', ''); // 邀请人标识（助力）
        
        // 验证必填字段
        if (!$provinceId || !$cityName) {
            return json(['success' => false, 'error' => '请提供省份和城市名称']);
        }
        
        try {
            // 获取用户标识（IP地址 + User-Agent 的hash）
            $userIdentifier = $this->getUserIdentifier();
            
            // 检查是否已点亮该城市
            $exists = CityLightModel::where([
                ['province_id', '=', $provinceId],
                ['city_name', '=', $cityName],
                ['user_identifier', '=', $userIdentifier]
            ])->find();
            
            if ($exists) {
                $progress = CityLightModel::getCityProgress($provinceId, $cityName);
                return json([
                    'success' => false, 
                    'error' => '您已经点亮过该城市了',
                    'progress' => $progress
                ]);
            }
            
            // 判断是否为助力
            $isAssist = !empty($inviterIdentifier) && $inviterIdentifier !== $userIdentifier;
            
            // 如果不是助力，检查该用户已点亮的城市数量（限制3个）
            if (!$isAssist) {
                $userLightCount = \think\facade\Db::query(
                    "SELECT COUNT(DISTINCT CONCAT(province_id, '-', city_name)) as count
                     FROM fa_city_lights
                     WHERE user_identifier = ? AND is_assist = 0",
                    [$userIdentifier]
                );
                
                $count = $userLightCount[0]['count'] ?? 0;
                
                if ($count >= 3) {
                    return json([
                        'success' => false,
                        'error' => '您已达到点亮上限（3个城市）',
                        'tip' => '可以分享给好友助力，继续提升等级！',
                        'limit_reached' => true
                    ]);
                }
            }
            
            // 创建点亮记录
            $light = CityLightModel::create([
                'province_id' => $provinceId,
                'city_name' => $cityName,
                'city_code' => $cityCode,
                'user_identifier' => $userIdentifier,
                'user_contact' => $userContact,
                'inviter_identifier' => $isAssist ? $inviterIdentifier : null,
                'is_assist' => $isAssist ? 1 : 0,
                'light_count' => 1,
                'status' => 0
            ]);
            
            // 手动更新用户统计（因为无法使用触发器）
            $this->updateUserStats($userIdentifier, $userContact);
            if ($isAssist && $inviterIdentifier) {
                $this->updateUserStats($inviterIdentifier, null);
            }
            
            // 获取最新进度
            $progress = CityLightModel::getCityProgress($provinceId, $cityName);
            
            // 获取用户等级信息
            $userStats = $this->getUserStatsData($userIdentifier);
            
            // 判断是否达到开通条件
            if ($progress['can_open']) {
                return json([
                    'success' => true,
                    'message' => '恭喜！该城市已达到开通条件，即将为您开通！',
                    'progress' => $progress,
                    'user_stats' => $userStats,
                    'auto_opened' => true
                ]);
            }
            
            $message = $isAssist ? '助力成功！' : '点亮成功！';
            if (!$isAssist) {
                $remaining = 3 - ($userStats['self_lights'] ?? 0);
                if ($remaining > 0) {
                    $message .= " 还可以点亮 {$remaining} 个城市";
                }
            }
            
            return json([
                'success' => true,
                'message' => $message,
                'progress' => $progress,
                'user_stats' => $userStats,
                'auto_opened' => false
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '点亮失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取用户统计信息
     */
    public function getUserStats()
    {
        try {
            $userIdentifier = $this->getUserIdentifier();
            $stats = $this->getUserStatsData($userIdentifier);
            
            return json(['success' => true, 'data' => $stats]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取排行榜
     */
    public function getRanking()
    {
        try {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 50);
            
            // 查询排行榜
            $ranking = \think\facade\Db::query(
                "SELECT 
                    user_identifier,
                    user_contact,
                    total_lights,
                    self_lights,
                    assist_lights,
                    level,
                    level_score,
                    @rank := @rank + 1 AS rank_position
                FROM fa_city_light_users,
                     (SELECT @rank := 0) r
                ORDER BY level_score DESC, total_lights DESC, create_time ASC
                LIMIT ?, ?",
                [($page - 1) * $limit, $limit]
            );
            
            // 获取总数
            $total = \think\facade\Db::name('city_light_users')->count();
            
            // 获取当前用户排名
            $userIdentifier = $this->getUserIdentifier();
            $userRank = $this->getUserRank($userIdentifier);
            
            return json([
                'success' => true,
                'data' => $ranking,
                'total' => $total,
                'page' => (int)$page,
                'limit' => (int)$limit,
                'user_rank' => $userRank
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取用户统计数据（内部方法）
     */
    private function getUserStatsData($userIdentifier)
    {
        $user = \think\facade\Db::name('city_light_users')
            ->where('user_identifier', $userIdentifier)
            ->find();
        
        if (!$user) {
            return [
                'total_lights' => 0,
                'self_lights' => 0,
                'assist_lights' => 0,
                'level' => '新手',
                'level_score' => 0,
                'rank_position' => 0,
                'can_light_more' => true,
                'remaining_lights' => 3
            ];
        }
        
        return [
            'total_lights' => $user['total_lights'],
            'self_lights' => $user['self_lights'],
            'assist_lights' => $user['assist_lights'],
            'level' => $user['level'],
            'level_score' => $user['level_score'],
            'rank_position' => $this->getUserRank($userIdentifier),
            'can_light_more' => $user['self_lights'] < 3,
            'remaining_lights' => max(0, 3 - $user['self_lights'])
        ];
    }
    
    /**
     * 获取用户排名
     */
    private function getUserRank($userIdentifier)
    {
        $result = \think\facade\Db::query(
            "SELECT rank_position 
             FROM (
               SELECT 
                 user_identifier,
                 @rank := @rank + 1 AS rank_position
               FROM fa_city_light_users,
                    (SELECT @rank := 0) r
               ORDER BY level_score DESC, total_lights DESC, create_time ASC
             ) ranked
             WHERE user_identifier = ?",
            [$userIdentifier]
        );
        
        return $result[0]['rank_position'] ?? 0;
    }
    
    /**
     * 获取城市点亮进度
     */
    public function getCityProgress()
    {
        $provinceId = $this->request->get('province_id');
        $cityName = $this->request->get('city_name');
        
        if (!$provinceId || !$cityName) {
            return json(['success' => false, 'error' => '请提供省份和城市名称']);
        }
        
        try {
            $progress = CityLightModel::getCityProgress($provinceId, $cityName);
            $userIdentifier = $this->getUserIdentifier();
            $hasLighted = CityLightModel::hasLighted($provinceId, $cityName, $userIdentifier);
            
            return json([
                'success' => true,
                'data' => array_merge($progress, [
                    'has_lighted' => $hasLighted
                ])
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取热门点亮城市（TOP 10）
     */
    public function hotLightCities()
    {
        try {
            $cities = CityLightModel::getCityStats(null, 0); // 只获取未开通的
            
            // 只返回前10个
            $cities = array_slice($cities, 0, 10);
            
            // 添加进度百分比
            foreach ($cities as &$city) {
                $city['progress_percent'] = round($city['total_lights'] / 1000 * 100, 2);
                $city['progress_text'] = $city['total_lights'] . '/1000';
            }
            
            return json(['success' => true, 'data' => $cities]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 搜索城市（包括有订单和无订单的城市）
     */
    public function searchCity()
    {
        $keyword = $this->request->get('keyword');
        
        if (!$keyword) {
            return json(['success' => false, 'error' => '请提供搜索关键词']);
        }
        
        try {
            // 搜索所有城市
            $cities = \think\facade\Db::name('cities')
                ->alias('c')
                ->join('provinces p', 'c.province_id = p.id', 'LEFT')
                ->where('c.name', 'like', '%' . $keyword . '%')
                ->where('c.status', 1)
                ->field('c.id, c.name as city_name, c.province_id, p.name as province_name')
                ->select()
                ->toArray();
            
            $withOrders = [];
            $withoutOrders = [];
            
            foreach ($cities as $city) {
                // 检查该城市是否有订单
                $orderCount = \think\facade\Db::name('tutor_orders_new')
                    ->where('city_id', $city['id'])
                    ->where('status', 1)
                    ->count();
                
                // 检查该城市的点亮人数
                $lightCount = CityLightModel::where('province_id', $city['province_id'])
                    ->where('city_name', $city['city_name'])
                    ->count('DISTINCT user_identifier');
                
                $cityData = [
                    'id' => $city['id'],
                    'city_name' => $city['city_name'],
                    'province_id' => $city['province_id'],
                    'province_name' => $city['province_name'],
                    'order_count' => $orderCount,
                    'total_lights' => $lightCount
                ];
                
                if ($orderCount > 0) {
                    $withOrders[] = $cityData;
                } else {
                    $withoutOrders[] = $cityData;
                }
            }
            
            // 合并结果
            $result = [
                'opened' => $withOrders,     // 有订单的城市（已开通）
                'unopened' => $withoutOrders  // 无订单的城市（未开通）
            ];
            
            return json(['success' => true, 'data' => $result]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取用户唯一标识
     */
    private function getUserIdentifier()
    {
        $ip = Request::ip();
        $userAgent = Request::header('user-agent');
        return md5($ip . $userAgent);
    }
    
    /**
     * 更新用户统计信息（手动更新，替代触发器）
     */
    private function updateUserStats($userIdentifier, $userContact = null)
    {
        try {
            // 计算该用户自己点亮的城市数（去重）
            $selfCount = \think\facade\Db::query(
                "SELECT COUNT(DISTINCT CONCAT(province_id, '-', city_name)) as count
                 FROM fa_city_lights
                 WHERE user_identifier = ? AND is_assist = 0",
                [$userIdentifier]
            );
            $selfLights = $selfCount[0]['count'] ?? 0;
            
            // 计算该用户获得的助力数（作为邀请人）
            $assistCount = \think\facade\Db::name('city_lights')
                ->where('inviter_identifier', $userIdentifier)
                ->where('is_assist', 1)
                ->count();
            
            // 总点亮数
            $totalLights = $selfLights + $assistCount;
            
            // 计算等级和分数
            // 自己点亮: 10分/城市，助力: 5分/次
            $levelScore = $selfLights * 10 + $assistCount * 5;
            
            // 根据分数确定等级
            if ($levelScore >= 100) {
                $level = '荣耀';
            } elseif ($levelScore >= 50) {
                $level = '皇冠';
            } elseif ($levelScore >= 20) {
                $level = '青铜';
            } else {
                $level = '新手';
            }
            
            // 更新或插入用户统计
            $existingUser = \think\facade\Db::name('city_light_users')
                ->where('user_identifier', $userIdentifier)
                ->find();
            
            if ($existingUser) {
                // 更新
                \think\facade\Db::name('city_light_users')
                    ->where('user_identifier', $userIdentifier)
                    ->update([
                        'user_contact' => $userContact ?: $existingUser['user_contact'],
                        'total_lights' => $totalLights,
                        'self_lights' => $selfLights,
                        'assist_lights' => $assistCount,
                        'level' => $level,
                        'level_score' => $levelScore,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
            } else {
                // 插入
                \think\facade\Db::name('city_light_users')->insert([
                    'user_identifier' => $userIdentifier,
                    'user_contact' => $userContact,
                    'total_lights' => $totalLights,
                    'self_lights' => $selfLights,
                    'assist_lights' => $assistCount,
                    'level' => $level,
                    'level_score' => $levelScore,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            // 记录错误但不影响主流程
            trace('更新用户统计失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }
}

