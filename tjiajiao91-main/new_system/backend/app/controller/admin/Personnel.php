<?php
namespace app\controller\admin;

use app\BaseController;
use app\service\WecomEnterpriseService;

class Personnel extends BaseController
{
    // 获取人员列表（从企业微信API）
    public function index()
    {
        // 权限检查
        // $this->checkEnterprisePermission(); // 已取消权限验证
        
        $page = input('page', 1);
        $pageSize = input('pageSize', 20);
        $employmentStatus = input('employment_status', '');
        $employmentType = input('employment_type', '');
        $keyword = input('keyword', '');
        $departmentId = input('department_id', 1); // 部门ID，默认根部门
        
        try {
            $wecomService = new WecomEnterpriseService();
            
            // 调用企业微信API获取部门成员详情
            $response = $wecomService->getUserDetailList($departmentId, true);
            
            if ($response['errcode'] != 0) {
                return json([
                    'success' => false,
                    'message' => '获取企业微信人员失败：' . ($response['errmsg'] ?? '未知错误')
                ]);
            }
            
            $allUsers = $response['userlist'] ?? [];
            
            // 前端筛选
            $filteredUsers = array_filter($allUsers, function($user) use ($employmentStatus, $employmentType, $keyword) {
                // 在职状态筛选
                if ($employmentStatus && $user['employment_status'] != $employmentStatus) {
                    return false;
                }
                
                // 雇佣类型筛选
                if ($employmentType && $user['employment_type'] != $employmentType) {
                    return false;
                }
                
                // 关键词搜索（姓名或手机号）
                if ($keyword) {
                    $match = stripos($user['name'], $keyword) !== false || 
                             stripos($user['phone'], $keyword) !== false;
                    if (!$match) {
                        return false;
                    }
                }
                
                return true;
            });
            
            // 重新索引数组
            $filteredUsers = array_values($filteredUsers);
            $total = count($filteredUsers);
            
            // 分页
            $offset = ($page - 1) * $pageSize;
            $listData = array_slice($filteredUsers, $offset, $pageSize);
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $listData,
                    'total' => $total
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '获取人员列表失败：' . $e->getMessage()
            ]);
        }
    }
    
    // 获取部门列表（用于筛选）
    public function departments()
    {
        // 权限检查
        // $this->checkEnterprisePermission(); // 已取消权限验证
        
        try {
            $wecomService = new WecomEnterpriseService();
            $response = $wecomService->getDepartmentList();
            
            if ($response['errcode'] != 0) {
                return json([
                    'success' => false,
                    'message' => '获取部门列表失败：' . ($response['errmsg'] ?? '未知错误')
                ]);
            }
            
            return json([
                'success' => true,
                'data' => $response['department'] ?? []
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '获取部门列表失败：' . $e->getMessage()
            ]);
        }
    }
    
    // 获取单个人员详情
    public function read()
    {
        // 权限检查
        // $this->checkEnterprisePermission(); // 已取消权限验证
        
        $userid = input('userid', '');
        if (!$userid) {
            return json(['success' => false, 'message' => '缺少userid参数']);
        }
        
        try {
            $wecomService = new WecomEnterpriseService();
            $response = $wecomService->getUser($userid);
            
            if ($response['errcode'] != 0) {
                return json([
                    'success' => false,
                    'message' => '获取人员详情失败：' . ($response['errmsg'] ?? '未知错误')
                ]);
            }
            
            return json([
                'success' => true,
                'data' => $response['user'] ?? null
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '获取人员详情失败：' . $e->getMessage()
            ]);
        }
    }
    
    // 创建人员（企业微信不支持通过API创建，返回提示）
    public function save()
    {
        // 权限检查
        // $this->checkEnterprisePermission(); // 已取消权限验证
        
        return json([
            'success' => false,
            'message' => '请在企业微信管理后台添加成员'
        ]);
    }
    
    // 更新人员（企业微信不支持通过API更新，返回提示）
    public function update()
    {
        // 权限检查
        // $this->checkEnterprisePermission(); // 已取消权限验证
        
        return json([
            'success' => false,
            'message' => '请在企业微信管理后台修改成员信息'
        ]);
    }
    
    // 删除人员（企业微信不支持通过API删除，返回提示）
    public function delete()
    {
        // 权限检查
        // $this->checkEnterprisePermission(); // 已取消权限验证
        
        return json([
            'success' => false,
            'message' => '请在企业微信管理后台删除成员'
        ]);
    }
    
    // 获取统计信息
    public function statistics()
    {
        // 权限检查
        // $this->checkEnterprisePermission(); // 已取消权限验证
        
        try {
            $wecomService = new WecomEnterpriseService();
            $response = $wecomService->getUserDetailList(1, true);
            
            if ($response['errcode'] != 0) {
                return json([
                    'success' => false,
                    'message' => '获取统计信息失败'
                ]);
            }
            
            $allUsers = $response['userlist'] ?? [];
            
            $onJobCount = 0;
            $offJobCount = 0;
            $fullTimeCount = 0;
            $partTimeCount = 0;
            
            foreach ($allUsers as $user) {
                if ($user['employment_status'] === '在职') {
                    $onJobCount++;
                } else {
                    $offJobCount++;
                }
                
                if ($user['employment_type'] === '全职') {
                    $fullTimeCount++;
                } else {
                    $partTimeCount++;
                }
            }
            
            return json([
                'success' => true,
                'data' => [
                    'on_job_count' => $onJobCount,
                    'off_job_count' => $offJobCount,
                    'full_time_count' => $fullTimeCount,
                    'part_time_count' => $partTimeCount,
                    'total_count' => count($allUsers)
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '获取统计信息失败：' . $e->getMessage()
            ]);
        }
    }
    
    // 检查企业管理权限
    private function checkEnterprisePermission()
    {
        // 启动session（如果尚未启动）
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 使用与Auth中间件一致的方式获取session
        $adminId = $_SESSION['admin_id'] ?? null;
        if (!$adminId) {
            return json(['success' => false, 'message' => '未登录'])->send();
            exit;
        }
        
        $admin = \app\model\Admin::find($adminId);
        if (!$admin) {
            return json(['success' => false, 'message' => '管理员不存在'])->send();
            exit;
        }
        
        // 超级管理员直接通过
        if ($admin->role === 'super_admin') {
            return true;
        }
        
        // 检查是否有企业管理权限
        if (!$admin->can_access_enterprise) {
            return json(['success' => false, 'message' => '无权访问企业管理模块'])->send();
            exit;
        }
        
        return true;
    }
}
