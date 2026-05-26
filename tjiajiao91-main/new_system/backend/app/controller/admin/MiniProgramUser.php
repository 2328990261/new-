<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;
use think\facade\Config;

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
            
            // 确保status字段存在
            $this->ensureStatusField();
            
            // 构建查询：优先读取 fa_users.user_type（支付宝端会写入这里）
            // 兼容历史：若 fa_users.user_type 为空，则回退到 fa_wechat_users.user_type
            $query = Db::name('users')
                ->alias('u')
                ->leftJoin('wechat_users w', 'u.openid = w.openid')
                ->field("u.*, IFNULL(NULLIF(u.user_type,''), w.user_type) AS user_type");
            
            // 搜索条件
            if (!empty($keyword)) {
                $query->where(function($q) use ($keyword) {
                    $q->whereOr('u.phone', 'like', "%{$keyword}%")
                      ->whereOr('u.nickname', 'like', "%{$keyword}%")
                      ->whereOr('u.openid', 'like', "%{$keyword}%");
                });
            }
            
            // 端口筛选
            if (!empty($platform)) {
                $query->where('u.platform', $platform);
            }
            
            // 分页查询
            $total = (int)$query->count();
            $list = $query->order('u.create_time', 'desc')
                          ->limit(($page - 1) * $pageSize, $pageSize)
                          ->select()
                          ->toArray();
            
            // 处理头像URL和默认状态
            foreach ($list as &$item) {
                if (!empty($item['avatar'])) {
                    $item['avatar'] = $this->formatAvatarUrl($item['avatar'], $item['nickname'] ?? '');
                }
                // 统一 status：1 启用，0 禁用；缺失或 null 视为 1
                $item['status'] = $this->normalizeStatus($item['status'] ?? null);
            }
            
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
            
            // 确保status字段存在
            $this->ensureStatusField();
            
            // 关联查询获取用户类型（同上：优先 users.user_type）
            $user = Db::name('users')
                ->alias('u')
                ->leftJoin('wechat_users w', 'u.openid = w.openid')
                ->field("u.*, IFNULL(NULLIF(u.user_type,''), w.user_type) AS user_type")
                ->where('u.id', $id)
                ->find();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            // 处理头像URL
            if (!empty($user['avatar'])) {
                $user['avatar'] = $this->formatAvatarUrl($user['avatar'], $user['nickname'] ?? '');
            }
            
            $user['status'] = $this->normalizeStatus($user['status'] ?? null);
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['user' => $user]
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
            
            // 获取用户信息（包含openid）
            $user = Db::name('users')->where('id', $id)->find();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            // 开启事务
            Db::startTrans();
            
            try {
                // 更新fa_users表的字段
                $userAllowFields = ['nickname', 'phone', 'avatar'];
                $userUpdateData = [];
                
                foreach ($userAllowFields as $field) {
                    if (isset($data[$field])) {
                        $userUpdateData[$field] = $data[$field];
                    }
                }
                
                if (!empty($userUpdateData)) {
                    $userUpdateData['update_time'] = date('Y-m-d H:i:s');
                    Db::name('users')->where('id', $id)->update($userUpdateData);
                }
                
                // 更新 user_type：优先写入 fa_users（支付宝端/统一口径）
                if (isset($data['user_type'])) {
                    $openid = $user['openid'];

                    Db::name('users')->where('id', $id)->update([
                        'user_type' => $data['user_type'],
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);
                }
                
                Db::commit();
                
                return json([
                    'code' => 200,
                    'message' => '更新成功'
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
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
            
            // 各端小程序用户数（兼容历史 miniprogram）
            $wechatMiniUsers = (int)Db::name('users')
                ->whereIn('platform', ['wechat_miniprogram', 'miniprogram'])
                ->count();
            $alipayMiniUsers = (int)Db::name('users')
                ->where('platform', 'alipay_miniprogram')
                ->count();
            $miniprogramUsers = $wechatMiniUsers + $alipayMiniUsers;
            
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
                'wechatMiniUsers' => $wechatMiniUsers,
                'alipayMiniUsers' => $alipayMiniUsers,
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
                    'wechatMiniUsers' => 0,
                    'alipayMiniUsers' => 0,
                    'h5Users' => 0,
                    'dailyTrend' => []
                ]
            ]);
        }
    }
    
    /**
     * 格式化头像URL
     * @param string $avatar
     * @param string $nickname
     * @return string
     */
    private function formatAvatarUrl($avatar, $nickname = '')
    {
        if (empty($avatar)) {
            return '';
        }
        
        // 如果是微信小程序的临时文件路径，返回空（使用默认头像）
        if (strpos($avatar, 'http://tmp/') === 0 || strpos($avatar, 'tmp/') === 0) {
            return '';
        }
        
        // 如果是有效的HTTP URL，直接返回
        if (strpos($avatar, 'http://') === 0 || strpos($avatar, 'https://') === 0) {
            return $avatar;
        }
        
        // 如果是相对路径（uploads开头），转换为完整URL
        if (strpos($avatar, 'uploads/') === 0) {
            $request = request();
            $domain = $request->domain();
            
            // 线上环境特殊处理
            if (strpos($domain, 'localhost') === false && strpos($domain, '127.0.0.1') === false) {
                // 线上环境，直接返回相对路径，让前端处理域名
                return $avatar;
            }
            
            return $domain . '/' . $avatar;
        }
        
        // 其他情况，假设是相对路径
        $request = request();
        $domain = $request->domain();
        
        // 线上环境特殊处理
        if (strpos($domain, 'localhost') === false && strpos($domain, '127.0.0.1') === false) {
            // 线上环境，直接返回相对路径
            return ltrim($avatar, '/');
        }
        
        return $domain . '/' . ltrim($avatar, '/');
    }
    
    /**
     * 切换用户状态（启用/禁用）
     * PUT 或 POST /admin/api/mini-users/{id}/toggle-status
     * 更新 fa_users 表的 status 字段：1=启用，0=禁用
     */
    public function toggleStatus()
    {
        try {
            $id = $this->request->param('id');
            if ($id === '' || $id === null) {
                return json([
                    'code' => 400,
                    'message' => '缺少用户ID'
                ]);
            }
            $id = (int) $id;
            if ($id <= 0) {
                return json([
                    'code' => 400,
                    'message' => '用户ID无效'
                ]);
            }
            
            $user = Db::name('users')->where('id', $id)->find();
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            $this->ensureStatusField();
            
            $currentStatus = $this->normalizeStatus($user['status'] ?? null);
            $newStatus = $currentStatus === 1 ? 0 : 1;
            
            $affected = Db::name('users')->where('id', $id)->update([
                'status' => $newStatus,
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            if ($affected === 0) {
                return json([
                    'code' => 500,
                    'message' => '更新失败，请确认 fa_users 表存在 status 字段'
                ]);
            }
            
            $statusText = $newStatus === 1 ? '启用' : '禁用';
            return json([
                'code' => 200,
                'message' => "用户状态已{$statusText}",
                'data' => [
                    'status' => $newStatus,
                    'id' => $id
                ]
            ], 200, [], ['json_encode_param' => JSON_UNESCAPED_UNICODE]);
            
        } catch (\Exception $e) {
            \think\facade\Log::error('toggleStatus failed: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '操作失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 统一 status 为 0 或 1，null/空 视为 1（启用）
     */
    private function normalizeStatus($value)
    {
        if ($value === null || $value === '') {
            return 1;
        }
        return (int) $value ? 1 : 0;
    }
    
    /**
     * 确保 fa_users 表有 status 字段（表名使用配置前缀）
     */
    private function ensureStatusField()
    {
        try {
            $prefix = Config::get('database.connections.mysql.prefix', 'fa_');
            $table = $prefix . 'users';
            $columns = Db::query("SHOW COLUMNS FROM `{$table}` LIKE 'status'");
            if (empty($columns)) {
                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1启用，0禁用' AFTER `platform`");
                \think\facade\Log::info("已为 {$table} 表添加 status 字段");
            }
        } catch (\Exception $e) {
            \think\facade\Log::error('添加 status 字段失败: ' . $e->getMessage());
        }
    }
}