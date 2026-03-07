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
            
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            return json([
                'success' => true,
                'data' => $result->items(),
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
     * 获取客服组管理员列表（用于筛选，不包含派单组和其他角色）
     * 严格过滤：只返回 role='customer_service' 且 status=1 的管理员
     * 排除所有其他角色：dispatcher、super_admin、admin 等
     */
    public function getCustomerServices()
    {
        try {
            // 严格过滤：只获取客服组成员，排除派单组、超级管理员和其他所有角色
            // 只查询 role='customer_service' 的记录（已经排除了其他所有角色）
            $customerServices = Admin::where('role', '=', 'customer_service')
                ->where('status', '=', 1)
                ->field('id,username,nickname,email,role,status,create_time')
                ->select();
            
            // 二次验证：确保返回的数据中没有任何非客服组成员
            // 只允许 role='customer_service' 的记录通过
            $filteredList = [];
            $allowedRoles = ['customer_service']; // 只允许客服组角色
            
            foreach ($customerServices as $admin) {
                // 严格检查：role 必须完全等于 'customer_service'
                $role = trim($admin->role ?? '');
                
                // 双重验证：必须是允许的角色且状态为启用
                if (in_array($role, $allowedRoles) && $admin->status == 1) {
                    $filteredList[] = $admin;
                }
            }
            
            return json([
                'success' => true,
                'data' => $filteredList
            ]);
        } catch (\Exception $e) {
            trace('获取客服组失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '获取客服组失败']);
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
            
            // 验证必填字段
            if (empty($data['username']) || empty($data['password']) || empty($data['nickname'])) {
                return json(['success' => false, 'error' => '请填写完整信息']);
            }
            
            // 客服组邮箱必填验证
            if (isset($data['role']) && $data['role'] === 'customer_service') {
                if (empty($data['email'])) {
                    return json(['success' => false, 'error' => '客服组角色必须填写邮箱']);
                }
            }
            
            // 检查用户名是否已存在
            if (Admin::where('username', $data['username'])->find()) {
                return json(['success' => false, 'error' => '用户名已存在']);
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
            
            // 客服组邮箱必填验证
            $role = isset($data['role']) ? $data['role'] : $admin->role;
            if ($role === 'customer_service') {
                $email = isset($data['email']) ? $data['email'] : $admin->email;
                if (empty($email)) {
                    return json(['success' => false, 'error' => '客服组角色必须填写邮箱']);
                }
            }
            
            // 如果有密码，由Admin模型的setPasswordAttr自动处理加密，避免双重加密
            if (!empty($data['password'])) {
                // $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }
            
            // 如果修改了用户名，检查是否重复
            if (isset($data['username']) && $data['username'] != $admin->username) {
                if (Admin::where('username', $data['username'])->find()) {
                    return json(['success' => false, 'error' => '用户名已存在']);
                }
            }
            
            $admin->save($data);
            
            return json(['success' => true, 'message' => '更新成功']);
            
        } catch (\Exception $e) {
            trace('更新管理员失败: ' . $e->getMessage(), 'error');
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
}








