<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Admin;

/**
 * 管理员管理控制器
 */
class AdminManage extends BaseController
{
    /**
     * 获取管理员列表
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
            $keyword = $this->request->get('keyword', '');
            $role = $this->request->get('role', '');
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            $query = Admin::order('create_time', 'desc');
            
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->whereOr([
                        ['username', 'like', '%' . $keyword . '%'],
                        ['nickname', 'like', '%' . $keyword . '%'],
                        ['email', 'like', '%' . $keyword . '%']
                    ]);
                });
            }
            
            // 角色筛选
            if ($role) {
                $query->where('role', $role);
            }
            
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            // 获取所有组长信息用于显示组长名称
            $leaderIds = array_filter(array_column($result->items(), 'leader_id'));
            $leaders = [];
            if (!empty($leaderIds)) {
                $leaderList = Admin::whereIn('id', $leaderIds)->column('nickname,username', 'id');
                foreach ($leaderList as $id => $leader) {
                    $leaders[$id] = $leader['nickname'] ?: $leader['username'];
                }
            }
            
            // 添加组长名称到每条记录
            $data = [];
            foreach ($result->items() as $item) {
                $itemArray = $item->toArray();
                $itemArray['leader_name'] = isset($leaders[$item->leader_id]) ? $leaders[$item->leader_id] : null;
                // 确保status字段为整数类型
                $itemArray['status'] = (int)$itemArray['status'];
                $data[] = $itemArray;
            }
            
            return json([
                'success' => true,
                'data' => $data,
                'total' => $result->total()
            ]);
            
        } catch (\Exception $e) {
            trace('获取管理员列表失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '获取管理员列表失败']);
        }
    }
    
    /**
     * 获取派单组管理员列表
     */
    public function getDispatchers()
    {
        try {
            $dispatchers = Admin::where('role', 'dispatcher')->select();
            return json([
                'success' => true,
                'data' => $dispatchers
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取派单组失败']);
        }
    }
    
    /**
     * 获取客服组管理员列表（用于筛选，包含超级管理员、客服和组长）
     * 返回 role='super_admin' 或 role='customer_service' 或 role='team_leader' 且 status=1 的管理员
     * 
     * 权限控制：
     * - 超级管理员：可以看到所有客服
     * - 客服组长：只能看到自己和自己的组员
     * - 普通客服：只能看到自己
     */
    public function getCustomerServices()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $adminId = $_SESSION['admin_id'];
            $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
            
            // 基础查询：只查询状态为启用的用户，且角色为客服、组长或超级管理员
            $query = Admin::where('status', '=', 1)
                ->whereIn('role', ['super_admin', 'customer_service', 'team_leader'])
                ->field('id,username,nickname,email,role,status,create_time,leader_id');
            
            // 根据当前用户角色限制可见范围
            if ($adminRole === 'team_leader') {
                // 客服组长只能看到自己和自己的组员
                $teamMemberIds = Admin::where('leader_id', $adminId)
                    ->where('status', '=', 1)
                    ->column('id');
                $teamMemberIds[] = $adminId; // 包含自己
                $query->whereIn('id', $teamMemberIds);
            } elseif ($adminRole === 'customer_service') {
                // 普通客服只能看到自己
                $query->where('id', '=', $adminId);
            }
            // 超级管理员可以看到所有（不需要额外过滤）
            
            $customerServices = $query->select();
            
            return json([
                'success' => true,
                'data' => $customerServices
            ]);
        } catch (\Exception $e) {
            trace('获取客服组失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '获取客服组失败']);
        }
    }
    
    /**
     * 获取组长列表
     */
    public function getTeamLeaders()
    {
        try {
            $teamLeaders = Admin::where('role', '=', 'team_leader')
                ->where('status', '=', 1)
                ->field('id,username,nickname')
                ->select();
            
            return json([
                'success' => true,
                'data' => $teamLeaders
            ]);
        } catch (\Exception $e) {
            trace('获取组长列表失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '获取组长列表失败']);
        }
    }
    
    /**
     * 获取所有客服和组长（不做权限过滤，用于家教信息筛选等场景）
     */
    public function getAllCustomerServices()
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['admin_id'])) {
                return json(['success' => false, 'error' => '未登录']);
            }

            try {
                $adminId = $_SESSION['admin_id'];
                $adminRole = $_SESSION['admin_role'] ?? 'customer_service';

                // 基础查询：获取所有启用的客服和组长
                $query = Admin::where('status', '=', 1)
                    ->whereIn('role', ['customer_service', 'team_leader'])
                    ->field('id,username,nickname,email,role,status,create_time,leader_id')
                    ->order('create_time', 'desc');

                // 如果是组长，只能看到自己组内的客服（包含自己）
                if ($adminRole === 'team_leader') {
                    $teamMemberIds = Admin::where('leader_id', $adminId)
                        ->where('status', '=', 1)
                        ->column('id');
                    $teamMemberIds[] = $adminId; // 包含自己
                    $query->whereIn('id', $teamMemberIds);
                }
                // 超级管理员可以看到所有（不需要额外过滤）

                $customerServices = $query->select();

                return json([
                    'success' => true,
                    'data' => $customerServices
                ]);
            } catch (\Exception $e) {
                trace('获取所有客服失败: ' . $e->getMessage(), 'error');
                return json(['success' => false, 'error' => '获取所有客服失败']);
            }
        }
    
    /**
     * 批量设置归属组长
     */
    public function batchUpdateLeader()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        // 只有超级管理员可以批量设置
        $adminRole = $_SESSION['admin_role'] ?? 'customer_service';
        if ($adminRole !== 'super_admin') {
            return json(['success' => false, 'error' => '无权限执行此操作']);
        }
        
        try {
            $adminIds = $this->request->put('admin_ids', []);
            $leaderId = $this->request->put('leader_id', 0);
            
            if (empty($adminIds) || !is_array($adminIds)) {
                return json(['success' => false, 'error' => '请选择要设置的管理员']);
            }
            
            // 验证组长是否存在（如果不是取消归属）
            if ($leaderId > 0) {
                $leader = Admin::where('id', $leaderId)->where('role', 'team_leader')->find();
                if (!$leader) {
                    return json(['success' => false, 'error' => '所选组长不存在或不是组长角色']);
                }
            }
            
            // 批量更新
            $updateData = ['leader_id' => $leaderId > 0 ? $leaderId : null];
            Admin::whereIn('id', $adminIds)
                ->where('role', 'customer_service') // 只更新客服角色
                ->update($updateData);
            
            return json([
                'success' => true,
                'message' => '批量设置成功'
            ]);
            
        } catch (\Exception $e) {
            trace('批量设置组长失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '批量设置失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 添加管理员
     */
    public function save()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $data = $this->request->post();
            $role = $data['role'] ?? 'customer_service';
            $normalizedCityIds = $this->normalizeDispatcherCityIds($data['city_id'] ?? null);
            $data['city_id'] = ($role === 'dispatcher' && !empty($normalizedCityIds))
                ? implode(',', $normalizedCityIds)
                : null;
            
            // 验证必填字段
            if (empty($data['username']) || empty($data['password']) || empty($data['nickname'])) {
                return json(['success' => false, 'error' => '请填写完整信息']);
            }

            // OpenID 必填（避免空字符串命中 uk_openid 唯一索引）
            $openidValue = trim((string)($data['openid'] ?? ''));
            if ($openidValue === '') {
                return json(['success' => false, 'error' => 'OpenID不能为空']);
            }
            $openidTokens = Admin::splitOpenids($openidValue);
            if (empty($openidTokens)) {
                return json(['success' => false, 'error' => 'OpenID不能为空']);
            }
            // 保存时使用原始格式（允许逗号分隔多个 openid），但先做 trim
            $data['openid'] = $openidValue;
            
            // 客服组邮箱必填验证
            if ($role === 'customer_service') {
                if (empty($data['email'])) {
                    return json(['success' => false, 'error' => '客服组角色必须填写邮箱']);
                }
            }
            if ($role === 'dispatcher' && empty($normalizedCityIds)) {
                return json(['success' => false, 'error' => '派单组角色必须选择归属城市']);
            }
            
            // 检查用户名是否已存在
            if (Admin::where('username', $data['username'])->find()) {
                return json(['success' => false, 'error' => '用户名已存在']);
            }
            
            // OpenID 唯一性检查（支持逗号分隔多个 openid）
            if (Admin::hasOpenidConflict($data['openid'])) {
                return json(['success' => false, 'error' => '该OpenID已被其他管理员绑定']);
            }
            
            // 密码加密由Admin模型的setPasswordAttr自动处理，避免双重加密
            // $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            Admin::create($data);
            
            return json(['success' => true, 'message' => '添加成功']);
            
        } catch (\Exception $e) {
            trace('添加管理员失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '添加管理员失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新管理员
     */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $admin = Admin::find($id);
            if (!$admin) {
                return json(['success' => false, 'error' => '管理员不存在']);
            }
            
            $data = $this->request->put();
            
            // 添加调试日志
            trace('更新管理员 ID: ' . $id, 'info');
            trace('接收到的数据: ' . json_encode($data), 'info');
            trace('当前管理员信息: ' . json_encode($admin->toArray()), 'info');
            
            // 处理 openid：空字符串转为 null，避免唯一索引冲突
            if (isset($data['openid']) && $data['openid'] === '') {
                $data['openid'] = null;
            }
            
            // 客服组邮箱必填验证
            $role = isset($data['role']) ? $data['role'] : $admin->role;
            if ($role === 'customer_service') {
                $email = isset($data['email']) ? $data['email'] : $admin->email;
                if (empty($email)) {
                    return json(['success' => false, 'error' => '客服组角色必须填写邮箱']);
                }
            }
            if ($role === 'dispatcher') {
                $cityIdsForValidate = array_key_exists('city_id', $data)
                    ? $this->normalizeDispatcherCityIds($data['city_id'])
                    : $this->normalizeDispatcherCityIds($admin->city_id ?? null);
                if (empty($cityIdsForValidate)) {
                    return json(['success' => false, 'error' => '派单组角色必须选择归属城市']);
                }
            }

            // 归属城市：仅派单组生效，其他角色强制清空
            if (array_key_exists('city_id', $data) || array_key_exists('role', $data)) {
                $cityIds = isset($data['city_id'])
                    ? $this->normalizeDispatcherCityIds($data['city_id'])
                    : $this->normalizeDispatcherCityIds($admin->city_id ?? null);
                $data['city_id'] = ($role === 'dispatcher' && !empty($cityIds))
                    ? implode(',', $cityIds)
                    : null;
            }
            
            // 密码处理：为空表示不修改密码
            if (isset($data['password'])) {
                // 如果密码为空字符串或null，则不修改密码
                if (empty($data['password']) || trim($data['password']) === '') {
                    unset($data['password']);
                }
                // 如果有密码，由Admin模型的setPasswordAttr自动处理加密，避免双重加密
            }
            
            // 如果修改了用户名，检查是否重复
            if (isset($data['username']) && $data['username'] != $admin->username) {
                if (Admin::where('username', $data['username'])->find()) {
                    return json(['success' => false, 'error' => '用户名已存在']);
                }
            }
            
            // 如果修改了openid，检查是否重复（支持逗号分隔多个openid）
            if (isset($data['openid']) && !empty($data['openid']) && $data['openid'] != $admin->openid) {
                if (Admin::hasOpenidConflict($data['openid'], (int) $admin->id)) {
                    return json(['success' => false, 'error' => '该OpenID已被其他管理员绑定']);
                }
            }
            
            $result = $admin->save($data);
            
            // 添加调试日志
            trace('保存结果: ' . ($result ? 'true' : 'false'), 'info');
            trace('更新后的管理员信息: ' . json_encode($admin->toArray()), 'info');
            
            return json(['success' => true, 'message' => '更新成功']);
            
        } catch (\Exception $e) {
            trace('更新管理员失败: ' . $e->getMessage(), 'error');
            trace('错误堆栈: ' . $e->getTraceAsString(), 'error');
            return json(['success' => false, 'error' => '更新管理员失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除管理员
     */
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 不能删除自己
            if ($id == $_SESSION['admin_id']) {
                return json(['success' => false, 'error' => '不能删除当前登录的管理员']);
            }
            
            $admin = Admin::find($id);
            if (!$admin) {
                return json(['success' => false, 'error' => '管理员不存在']);
            }
            
            $admin->delete();
            
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\Exception $e) {
            trace('删除管理员失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '删除管理员失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取客服统计数据（今日单量和累计单量）
     */
    public function getAdminStats()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $adminIds = $this->request->get('admin_ids', '');
            
            // 如果没有传入admin_ids，则获取所有有订单的管理员ID（不限角色）
            if ($adminIds) {
                $adminIds = explode(',', $adminIds);
            } else {
                // 从订单表中获取所有不同的admin_id
                $adminIds = \app\model\TutorOrder::where('status', 1)
                    ->where('admin_id', '>', 0)
                    ->group('admin_id')
                    ->column('admin_id');
            }
            
            $stats = [];
            $today = date('Y-m-d');
            
            foreach ($adminIds as $adminId) {
                // 获取今日单量
                $todayCount = \app\model\TutorOrder::where('admin_id', $adminId)
                    ->whereTime('create_time', 'today')
                    ->where('status', 1)
                    ->count();
                
                // 获取累计单量
                $totalCount = \app\model\TutorOrder::where('admin_id', $adminId)
                    ->where('status', 1)
                    ->count();
                
                $stats[$adminId] = [
                    'admin_id' => $adminId,
                    'today_count' => $todayCount,
                    'total_count' => $totalCount
                ];
            }
            
            return json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            trace('获取客服统计失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '获取客服统计失败']);
        }
    }
    
    /**
     * 更新管理员的微信二维码
     */
    public function updateWechatQrcode($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $admin = Admin::find($id);
            if (!$admin) {
                return json(['success' => false, 'error' => '管理员不存在']);
            }
            
            // 允许派单组和客服组成员上传二维码
            if (!in_array($admin->role, ['dispatcher', 'customer_service'])) {
                return json(['success' => false, 'error' => '只有派单组和客服组成员可以上传微信二维码']);
            }
            
            $qrcodeUrl = $this->request->post('wechat_qrcode', '');
            
            // 清空二维码：传入空字符串或null
            if ($qrcodeUrl === '' || $qrcodeUrl === null) {
                $admin->wechat_qrcode = null;
            } else {
                $admin->wechat_qrcode = $qrcodeUrl;
            }
            
            $admin->save();
            
            return json([
                'success' => true,
                'message' => '更新成功',
                'data' => [
                    'wechat_qrcode' => $admin->wechat_qrcode
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('更新微信二维码失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 绑定小程序用户
     */
    public function bindMiniUser($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $admin = Admin::find($id);
            if (!$admin) {
                return json(['success' => false, 'error' => '管理员不存在']);
            }
            
            $openid = $this->request->post('openid', '');
            
            if (empty($openid)) {
                return json(['success' => false, 'error' => '请输入小程序用户openid']);
            }
            
            // 验证 openid 是否存在于用户表
            $user = \think\facade\Db::name('users')->where('openid', $openid)->find();
            if (!$user) {
                return json(['success' => false, 'error' => '该openid对应的小程序用户不存在']);
            }
            
            // 调用模型方法绑定
            $admin->bindMiniUser($openid);
            
            return json([
                'success' => true,
                'message' => '绑定成功',
                'data' => [
                    'openid' => $admin->openid,
                    'bind_time' => $admin->bind_time,
                    'user_info' => [
                        'nickname' => $user['nickname'] ?? '',
                        'phone' => $user['phone'] ?? '',
                        'avatar' => $user['avatar'] ?? ''
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('绑定小程序用户失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 解绑小程序用户
     */
    public function unbindMiniUser($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $admin = Admin::find($id);
            if (!$admin) {
                return json(['success' => false, 'error' => '管理员不存在']);
            }
            
            if (empty($admin->openid)) {
                return json(['success' => false, 'error' => '该管理员未绑定小程序用户']);
            }
            
            // 调用模型方法解绑
            $admin->unbindMiniUser();
            
            return json([
                'success' => true,
                'message' => '解绑成功'
            ]);
            
        } catch (\Exception $e) {
            trace('解绑小程序用户失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '解绑失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取管理员绑定的小程序用户信息
     */
    public function getMiniUserInfo($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $admin = Admin::find($id);
            if (!$admin) {
                return json(['success' => false, 'error' => '管理员不存在']);
            }
            
            if (empty($admin->openid)) {
                return json([
                    'success' => true,
                    'data' => null,
                    'message' => '未绑定小程序用户'
                ]);
            }
            
            // 查询绑定的用户信息（兼容管理员配置多个openid时取第一个）
            $openidTokens = \app\model\Admin::splitOpenids($admin->openid);
            $primaryOpenid = $openidTokens[0] ?? '';
            $user = $primaryOpenid !== ''
                ? \think\facade\Db::name('users')->where('openid', $primaryOpenid)->find()
                : null;
            
            if (!$user) {
                return json([
                    'success' => true,
                    'data' => null,
                    'message' => '绑定的用户不存在'
                ]);
            }
            
            return json([
                'success' => true,
                'data' => [
                    'openid' => $admin->openid,
                    'bind_time' => $admin->bind_time,
                    'user_info' => [
                        'id' => $user['id'],
                        'nickname' => $user['nickname'] ?? '',
                        'phone' => $user['phone'] ?? '',
                        'avatar' => $user['avatar'] ?? '',
                        'platform' => $user['platform'] ?? '',
                        'create_time' => $user['create_time'] ?? ''
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取绑定用户信息失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '获取失败：' . $e->getMessage()]);
        }
    }

    /**
     * 归一化派单员归属城市（支持数组或逗号分隔字符串）
     */
    private function normalizeDispatcherCityIds($cityValue): array
    {
        if (is_array($cityValue)) {
            $raw = $cityValue;
        } else {
            $raw = explode(',', (string)($cityValue ?? ''));
        }
        $ids = array_map('intval', $raw);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });
        return array_values(array_unique($ids));
    }
}








