<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;
use think\facade\Session;

/**
 * 仪表盘控制器
 */
class Dashboard extends BaseController
{
    /**
     * 获取统计数据
     */
    public function stats()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $today = date('Y-m-d');
            $monthStart = date('Y-m-01');
            
            // 今日新增
            $todayNew = Db::name('tutor_orders_new')
                ->whereDay('create_time', $today)
                ->count();
            
            // 本月新增
            $monthNew = Db::name('tutor_orders_new')
                ->where('create_time', '>=', $monthStart)
                ->count();
            
            // 有效订单数
            $validOrders = Db::name('tutor_orders_new')
                ->where('status', 1)
                ->count();
            
            // 邮箱订阅数
            $emailSubs = Db::name('email_subscriptions')
                ->where('status', 1)
                ->count();
            
            // 网站浏览人次（模拟数据，实际应从访问日志表获取）
            $pageViews = Db::name('tutor_orders_new')->count() * 10 + 1234;
            
            // 教师注册数
            $teachers = Db::name('teachers')->count();
            
            return json([
                'success' => true,
                'data' => [
                    'todayNew' => $todayNew,
                    'monthNew' => $monthNew,
                    'validOrders' => $validOrders,
                    'emailSubs' => $emailSubs,
                    'pageViews' => $pageViews,
                    'teachers' => $teachers
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取热门城市
     */
    public function hotCities()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $cities = Db::name('tutor_orders_new')
                ->alias('t')
                ->join('cities c', 't.city_id = c.id')
                ->where('t.status', 1)
                ->field('c.name, COUNT(*) as count')
                ->group('t.city_id')
                ->order('count', 'desc')
                ->limit(10)
                ->select()
                ->toArray();
            
            return json(['success' => true, 'data' => $cities]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取热门科目
     */
    public function hotSubjects()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $subjects = Db::name('tutor_orders_new')
                ->alias('t')
                ->join('subjects s', 't.subject_id = s.id')
                ->where('t.status', 1)
                ->field('s.name, COUNT(*) as count')
                ->group('t.subject_id')
                ->order('count', 'desc')
                ->limit(10)
                ->select()
                ->toArray();
            
            return json(['success' => true, 'data' => $subjects]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取客服组本月单量排名
     */
    public function adminRanking()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $monthStart = date('Y-m-01');
            
            $ranking = Db::name('tutor_orders_new')
                ->alias('t')
                ->join('admin a', 't.admin_id = a.id')
                ->where('t.create_time', '>=', $monthStart)
                ->where('a.role', 'customer_service')
                ->field('a.id, a.nickname, COUNT(*) as count')
                ->group('t.admin_id')
                ->order('count', 'desc')
                ->limit(10)
                ->select()
                ->toArray();
            
            return json(['success' => true, 'data' => $ranking]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取待办事项统计（仅客服组和组长可见，显示当前管理员的待办）
     */
    public function todoStats()
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
            
            // 只有客服组和组长才显示待办提醒
            if (!in_array($adminRole, ['customer_service', 'team_leader', 'super_admin'])) {
                return json([
                    'success' => true,
                    'data' => [
                        'pendingLeads' => 0,
                        'pendingApplications' => 0,
                        'pendingSubmissions' => 0
                    ]
                ]);
            }
            
            $pendingLeads = 0;
            $pendingApplications = 0;
            $pendingSubmissions = 0;
            
            // 1. 统计"我的线索"待跟进（状态为"待联系"，且分配给当前管理员）
            try {
                $pendingLeads = Db::name('leads')
                    ->where('status', '待联系')
                    ->where('assigned_admin_id', $adminId)
                    ->count();
            } catch (\Exception $e) {
                // 线索表查询失败，记录错误但继续
                trace('线索统计失败: ' . $e->getMessage(), 'error');
            }
            
            // 2. 统计"我的预约"待审核（状态为"pending"，且分配给当前管理员）
            try {
                $pendingApplications = Db::name('parent_orders')
                    ->where('status', 'pending')
                    ->where('admin_id', $adminId)
                    ->count();
            } catch (\Exception $e) {
                // 预约表查询失败，记录错误但继续
                trace('预约统计失败: ' . $e->getMessage(), 'error');
            }
            
            // 3. 统计"我的投递"待处理（投递到我发布的家教单的待处理投递）
            // 需要通过 tutor_id 关联 tutor_orders_new 表的 admin_id
            try {
                $pendingSubmissions = Db::name('resume_application')
                    ->alias('ra')
                    ->join('tutor_orders_new ton', 'ra.tutor_id = ton.id')
                    ->where('ra.status', 'pending')
                    ->where('ton.admin_id', $adminId)
                    ->count();
            } catch (\Exception $e) {
                // 投递表查询失败，记录错误但继续
                trace('投递统计失败: ' . $e->getMessage(), 'error');
            }
            
            return json([
                'success' => true,
                'data' => [
                    'pendingLeads' => $pendingLeads,
                    'pendingApplications' => $pendingApplications,
                    'pendingSubmissions' => $pendingSubmissions
                ]
            ]);
            
        } catch (\Exception $e) {
            // 返回详细的错误信息用于调试
            return json([
                'success' => false, 
                'error' => '获取待办事项失败：' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        }
    }
}


