<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\TutorOrder;
use app\model\Admin;
use app\service\DispatcherAutoAssignService;
use think\facade\Db;

/**
 * 派单管理控制器
 */
class OrderAssign extends BaseController
{
    /**
     * 获取待派单列表
     */
    public function pending()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            // 获取未派单的订单（dispatcher_id为空或0）
            $query = TutorOrder::with(['city', 'district', 'subject', 'admin'])
                ->where('status', 1)
                ->where(function($q) {
                    $q->whereNull('dispatcher_id')
                      ->whereOr('dispatcher_id', 0);
                })
                ->order([
                    'is_urgent' => 'desc',
                    'is_top' => 'desc',
                    'create_time' => 'desc'
                ]);
            
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
            return json(['success' => false, 'error' => '获取待派单列表失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取已派单列表
     */
    public function assigned()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            // 获取已派单的订单
            $query = TutorOrder::with(['city', 'district', 'subject', 'admin', 'dispatcher'])
                ->where('status', 1)
                ->where('dispatcher_id', '>', 0)
                ->order([
                    'assigned_time' => 'desc',
                    'is_urgent' => 'desc',
                    'is_top' => 'desc'
                ]);
            
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
            return json(['success' => false, 'error' => '获取已派单列表失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 自动轮动派单
     */
    public function batchAssign()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $orderIds = $this->request->post('order_ids', []);
            
            if (empty($orderIds)) {
                return json(['success' => false, 'error' => '请选择要派单的订单']);
            }
            
            $dispatcherCount = Admin::where('role', 'dispatcher')
                ->where('status', 1)
                ->count();
            
            if ($dispatcherCount === 0) {
                return json(['success' => false, 'error' => '没有可用的派单组成员']);
            }
            
            $successCount = 0;
            $failCount = 0;
            $assignResults = [];
            
            Db::startTrans();
            
            foreach ($orderIds as $orderId) {
                try {
                    $order = TutorOrder::find($orderId);
                    if (!$order) {
                        $failCount++;
                        continue;
                    }
                    
                    if (!DispatcherAutoAssignService::assignToDispatcher($order)) {
                        $failCount++;
                        continue;
                    }
                    
                    $dispatcher = Admin::where('id', $order->dispatcher_id)
                        ->where('role', 'dispatcher')
                        ->where('status', 1)
                        ->find();
                    
                    trace("订单 {$orderId} 批量派单给: ID={$order->dispatcher_id}", 'info');
                    
                    $assignResults[] = [
                        'order_id' => $orderId,
                        'dispatcher_id' => $order->dispatcher_id,
                        'dispatcher_name' => $dispatcher ? ($dispatcher->nickname ?? $dispatcher->username) : '',
                        'dispatcher_role' => $dispatcher ? $dispatcher->role : '',
                    ];
                    
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $failCount++;
                }
            }
            
            Db::commit();
            
            return json([
                'success' => true,
                'message' => "批量派单完成，成功{$successCount}条，失败{$failCount}条",
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'assign_results' => $assignResults
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            return json(['success' => false, 'error' => '批量派单失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 单个订单派单
     */
    public function assign()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $orderId = $this->request->param('id');
            $dispatcherId = $this->request->post('dispatcher_id');
            
            if (empty($orderId) || empty($dispatcherId)) {
                return json(['success' => false, 'error' => '请提供订单ID和派单员ID']);
            }
            
            $order = TutorOrder::find($orderId);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            $dispatcher = Admin::where('id', $dispatcherId)
                ->where('role', 'dispatcher')
                ->where('status', 1)
                ->find();
            
            if (!$dispatcher) {
                return json(['success' => false, 'error' => '派单员不存在或未启用']);
            }
            
            // 更新订单派单信息
            $order->dispatcher_id = $dispatcherId;
            $order->contact_info = $dispatcher['contact'] ?? '';
            $order->assigned_time = date('Y-m-d H:i:s');
            $order->save();
            
            return json([
                'success' => true,
                'message' => '派单成功',
                'data' => [
                    'order_id' => $orderId,
                    'dispatcher_id' => $dispatcherId,
                    'dispatcher_name' => $dispatcher['nickname'] ?? $dispatcher['username'],
                    'assigned_time' => $order->assigned_time
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '派单失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 取消派单
     */
    public function cancel()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $orderId = $this->request->param('id');
            
            if (empty($orderId)) {
                return json(['success' => false, 'error' => '请提供订单ID']);
            }
            
            $order = TutorOrder::find($orderId);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            // 清除派单信息
            $order->dispatcher_id = null;
            $order->contact_info = '';
            $order->assigned_time = null;
            $order->save();
            
            return json([
                'success' => true,
                'message' => '取消派单成功'
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '取消派单失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取派单统计
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
            // 待派单数量
            $pendingCount = TutorOrder::where('status', 1)
                ->where(function($q) {
                    $q->whereNull('dispatcher_id')
                      ->whereOr('dispatcher_id', 0);
                })
                ->count();
            
            // 已派单数量
            $assignedCount = TutorOrder::where('status', 1)
                ->where('dispatcher_id', '>', 0)
                ->count();
            
            // 派单员工作量统计
            $dispatcherStats = Db::name('tutor_orders_new')
                ->alias('t')
                ->join('admin a', 't.dispatcher_id = a.id')
                ->where('t.status', 1)
                ->where('t.dispatcher_id', '>', 0)
                ->where('a.role', 'dispatcher')
                ->field('a.id, a.nickname, COUNT(*) as assigned_count')
                ->group('t.dispatcher_id')
                ->order('assigned_count', 'desc')
                ->select()
                ->toArray();
            
            return json([
                'success' => true,
                'data' => [
                    'pending_count' => $pendingCount,
                    'assigned_count' => $assignedCount,
                    'dispatcher_stats' => $dispatcherStats
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取统计信息失败：' . $e->getMessage()]);
        }
    }
}
