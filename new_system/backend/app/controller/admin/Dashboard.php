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
}

