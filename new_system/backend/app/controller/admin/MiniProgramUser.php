<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * 用户管理控制器
 */
class MiniProgramUser extends BaseController
{
    /**
     * 获取用户列表
     */
    public function list()
    {
        try {
            $page = (int)$this->request->param('page', 1);
            $pageSize = (int)$this->request->param('pageSize', 20);
            $keyword = trim($this->request->param('keyword', ''));
            $platform = trim($this->request->param('platform', '')); // 端口筛选
            
            // 构建查询（使用 name 方法会自动加 fa_ 前缀）
            $query = Db::name('users');
            
            // 搜索条件
            if (!empty($keyword)) {
                $query->where(function($q) use ($keyword) {
                    $q->whereOr('phone', 'like', "%{$keyword}%")
                      ->whereOr('nickname', 'like', "%{$keyword}%")
                      ->whereOr('openid', 'like', "%{$keyword}%");
                });
            }
            
            // 端口筛选
            if (!empty($platform)) {
                $query->where('platform', $platform);
            }
            
            // 分页查询
            $total = (int)$query->count();
            $list = $query->order('create_time', 'desc')
                          ->limit(($page - 1) * $pageSize, $pageSize)
                          ->select()
                          ->toArray();
            
            // 确保数据编码正确
            array_walk_recursive($list, function(&$item) {
                if (is_string($item)) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
            });
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize
                ]
            ], 200, [], ['json_encode_param' => JSON_UNESCAPED_UNICODE]);
            
        } catch (\Exception $e) {
            \think\facade\Log::error('获取用户列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取失败，请稍后重试'
            ]);
        }
    }
    
    /**
     * 获取用户详情
     */
    public function detail()
    {
        try {
            $id = $this->request->param('id');
            
            if (empty($id)) {
                return json([
                    'code' => 400,
                    'message' => '缺少用户ID'
                ]);
            }
            
            $user = Db::name('users')->where('id', $id)->find();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $user
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 更新用户信息
     */
    public function update()
    {
        try {
            $id = $this->request->param('id');
            $data = $this->request->post();
            
            if (empty($id)) {
                return json([
                    'code' => 400,
                    'message' => '缺少用户ID'
                ]);
            }
            
            $user = Db::name('users')->where('id', $id)->find();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            // 允许更新的字段
            $allowFields = ['nickname', 'phone', 'avatar'];
            $updateData = [];
            
            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                return json([
                    'code' => 400,
                    'message' => '没有可更新的数据'
                ]);
            }
            
            $updateData['update_time'] = date('Y-m-d H:i:s');
            Db::name('users')->where('id', $id)->update($updateData);
            
            return json([
                'code' => 200,
                'message' => '更新成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '更新失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 删除用户
     */
    public function delete()
    {
        try {
            $id = $this->request->param('id');
            
            if (empty($id)) {
                return json([
                    'code' => 400,
                    'message' => '缺少用户ID'
                ]);
            }
            
            $user = Db::name('users')->where('id', $id)->find();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            Db::name('users')->where('id', $id)->delete();
            
            return json([
                'code' => 200,
                'message' => '删除成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '删除失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 批量删除用户
     */
    public function batchDelete()
    {
        try {
            $ids = $this->request->post('ids');
            
            if (empty($ids) || !is_array($ids)) {
                return json([
                    'code' => 400,
                    'message' => '请选择要删除的用户'
                ]);
            }
            
            $count = Db::name('users')->whereIn('id', $ids)->delete();
            
            return json([
                'code' => 200,
                'message' => "成功删除 {$count} 个用户"
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '批量删除失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取用户统计数据
     */
    public function stats()
    {
        try {
            // 检查表是否存在
            $tableExists = Db::query("SHOW TABLES LIKE 'fa_users'");
            if (empty($tableExists)) {
                return json([
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => [
                        'totalUsers' => 0,
                        'todayUsers' => 0,
                        'weekUsers' => 0,
                        'monthUsers' => 0,
                        'miniprogramUsers' => 0,
                        'h5Users' => 0,
                        'dailyTrend' => []
                    ]
                ]);
            }
            
            $db = Db::name('users');
            
            // 总用户数
            $totalUsers = (int)$db->count();
            
            // 今日新增
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');
            $todayUsers = (int)Db::name('users')->whereBetween('create_time', [$todayStart, $todayEnd])->count();
            
            // 本周新增
            $weekStart = date('Y-m-d 00:00:00', strtotime('this week'));
            $weekEnd = date('Y-m-d 23:59:59');
            $weekUsers = (int)Db::name('users')->whereBetween('create_time', [$weekStart, $weekEnd])->count();
            
            // 本月新增
            $monthStart = date('Y-m-01 00:00:00');
            $monthEnd = date('Y-m-d 23:59:59');
            $monthUsers = (int)Db::name('users')->whereBetween('create_time', [$monthStart, $monthEnd])->count();
            
            // 小程序用户数
            $miniprogramUsers = (int)Db::name('users')->where('platform', 'miniprogram')->count();
            
            // H5用户数
            $h5Users = (int)Db::name('users')->where('platform', 'h5')->count();
            
            // 最近7天每日新增趋势
            $dailyTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $dayStart = $date . ' 00:00:00';
                $dayEnd = $date . ' 23:59:59';
                
                $count = (int)Db::name('users')
                    ->whereBetween('create_time', [$dayStart, $dayEnd])
                    ->count();
                
                $dailyTrend[] = [
                    'date' => $date,
                    'count' => $count
                ];
            }
            
            $result = [
                'totalUsers' => $totalUsers,
                'todayUsers' => $todayUsers,
                'weekUsers' => $weekUsers,
                'monthUsers' => $monthUsers,
                'miniprogramUsers' => $miniprogramUsers,
                'h5Users' => $h5Users,
                'dailyTrend' => $dailyTrend
            ];
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            \think\facade\Log::error('获取用户统计失败: ' . $e->getMessage());
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'totalUsers' => 0,
                    'todayUsers' => 0,
                    'weekUsers' => 0,
                    'monthUsers' => 0,
                    'miniprogramUsers' => 0,
                    'h5Users' => 0,
                    'dailyTrend' => []
                ]
            ]);
        }
    }
}
