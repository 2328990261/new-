<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\TutorOrder;
use app\model\Admin;
use app\service\RecognitionService;
use app\service\EmailService;
use think\facade\Session;
use think\facade\Db;

/**
 * 家教信息管理控制器（管理端）
 */
class Tutor extends BaseController
{
    /**
     * 获取家教信息列表
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
            $cityId = $this->request->get('city_id');
            $districtId = $this->request->get('district_id');
            $subjectId = $this->request->get('subject_id');
            $grade = $this->request->get('grade');
            $isUrgent = $this->request->get('is_urgent');
            $isTop = $this->request->get('is_top');
            $keyword = $this->request->get('keyword');
            $startDate = $this->request->get('start_date'); // 修改为 start_date
            $endDate = $this->request->get('end_date');     // 修改为 end_date
            $viewScope = $this->request->get('view_scope', 'mine'); // mine: 我的订单, all: 全部订单
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 20);
            
            // 构建查询条件
            $where = [['status', '=', 1]];
            
            // 根据查看范围筛选
            if ($viewScope === 'mine') {
                $where[] = ['admin_id', '=', $_SESSION['admin_id']];
            }
            if ($cityId) {
                $where[] = ['city_id', '=', $cityId];
            }
            if ($districtId) {
                $where[] = ['district_id', '=', $districtId];
            }
            if ($subjectId) {
                $where[] = ['subject_id', '=', $subjectId];
            }
            if ($grade) {
                $where[] = ['grade', 'like', '%' . $grade . '%'];
            }
            if ($isUrgent !== '' && $isUrgent !== null) {
                $where[] = ['is_urgent', '=', $isUrgent];
            }
            if ($isTop !== '' && $isTop !== null) {
                $where[] = ['is_top', '=', $isTop];
            }
            if ($keyword) {
                $where[] = ['content', 'like', '%' . $keyword . '%'];
            }
            
            // 获取总数
            $query = TutorOrder::where($where);
            if ($startDate && $endDate) {
                $query->whereBetweenTime('create_time', $startDate, $endDate);
            }
            $total = $query->count();
            
            // 获取数据列表（关联查询城市、区域、科目、省份信息）
            $query = TutorOrder::where($where)
                ->with(['city' => function($query) {
                    $query->with('province');
                }, 'district', 'subject', 'admin']);
            if ($startDate && $endDate) {
                $query->whereBetweenTime('create_time', $startDate, $endDate);
            }
            $list = $query->order(['is_top' => 'desc', 'is_urgent' => 'desc', 'create_time' => 'desc'])
                ->page($page, $limit)
                ->select();
            
            // 处理数据，确保字段不为null
            $data = [];
            foreach ($list as $item) {
                $itemArray = $item->toArray();
                // 确保数字字段不为null
                $itemArray['is_urgent'] = $itemArray['is_urgent'] ?? 0;
                $itemArray['is_top'] = $itemArray['is_top'] ?? 0;
                $itemArray['status'] = $itemArray['status'] ?? 1;
                
                // 确保关联数据不为null
                $itemArray['city'] = $itemArray['city'] ?? ['id' => 0, 'name' => '未知'];
                $itemArray['district'] = $itemArray['district'] ?? ['id' => 0, 'name' => '未知'];
                $itemArray['subject'] = $itemArray['subject'] ?? ['id' => 0, 'name' => '未知'];
                $itemArray['admin'] = $itemArray['admin'] ?? ['id' => 0, 'username' => '系统'];
                
                // 确保文本字段不为null
                $itemArray['grade'] = $itemArray['grade'] ?? '';
                $itemArray['salary'] = $itemArray['salary'] ?? '';
                $itemArray['content'] = $itemArray['content'] ?? '';
                
                $data[] = $itemArray;
            }
            
            return json([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'page' => (int)$page,
                'limit' => (int)$limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 智能识别家教信息（支持批量）
     */
    public function recognize()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $text = $this->request->post('text');
        $isBatch = $this->request->post('is_batch', false); // 是否批量识别
        
        if (empty($text)) {
            return json(['success' => false, 'error' => '请提供要识别的内容']);
        }
        
        try {
            $recognitionService = new RecognitionService();
            
            if ($isBatch) {
                // 批量识别
                $results = $recognitionService->batchRecognize($text);
                
                // 分析识别结果
                $recognized = []; // 识别成功的
                $unrecognized = []; // 识别失败的
                $duplicates = []; // 重复的
                
                foreach ($results as $index => $result) {
                    if ($result && ($result['city_id'] || $result['subject_id'] || $result['grade'] || $result['salary'])) {
                        // 有识别到内容，检查是否重复
                        $content = $result['content'];
                        
                        // 简单的重复检查：查询相似内容（前500个字符）
                        $contentPrefix = mb_substr($content, 0, 500);
                        $similarOrder = TutorOrder::where('content', 'like', $contentPrefix . '%')
                            ->where('status', 1)
                            ->find();
                        
                        if ($similarOrder) {
                            // 发现重复
                            $duplicates[] = [
                                'index' => $index + 1,
                                'content' => $content,
                                'reason' => '内容与已有订单重复',
                                'similar_id' => $similarOrder->id
                            ];
                        } else {
                            // 识别成功且不重复，补充名称信息
                            if ($result['city_id']) {
                                $city = \app\model\City::find($result['city_id']);
                                $result['city_name'] = $city ? $city->name : '';
                            }
                            if ($result['district_id']) {
                                $district = \app\model\District::find($result['district_id']);
                                $result['district_name'] = $district ? $district->name : '';
                            }
                            if ($result['subject_id']) {
                                $subject = \app\model\Subject::find($result['subject_id']);
                                $result['subject_name'] = $subject ? $subject->name : '';
                            }
                            
                            $recognized[] = [
                                'index' => $index + 1,
                                'content' => $content,
                                'result' => $result,
                                'has_city' => !empty($result['city_id']),
                                'has_district' => !empty($result['district_id']),
                                'has_subject' => !empty($result['subject_id']),
                                'has_grade' => !empty($result['grade']),
                                'has_salary' => !empty($result['salary'])
                            ];
                        }
                    } else {
                        // 没有识别到内容，需要人工确认
                        $unrecognized[] = [
                            'index' => $index + 1,
                            'content' => $result['content'] ?? '',
                            'reason' => '未识别到有效信息'
                        ];
                    }
                }
                
                return json([
                    'success' => true, 
                    'is_batch' => true,
                    'data' => [
                        'recognized' => $recognized,
                        'unrecognized' => $unrecognized,
                        'duplicates' => $duplicates,
                        'total' => count($results),
                        'recognized_count' => count($recognized),
                        'unrecognized_count' => count($unrecognized),
                        'duplicate_count' => count($duplicates)
                    ]
                ]);
            } else {
                // 单个识别
                $result = $recognitionService->recognizeSingle($text);
                return json(['success' => true, 'is_batch' => false, 'data' => $result]);
            }
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '识别失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 创建家教信息
     */
    public function save()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $data = $this->request->post();
        
        // 验证必填字段（使用trim避免空格字符串）
        if (!isset($data['content']) || trim($data['content']) === '') {
            return json(['success' => false, 'error' => '请输入家教信息内容']);
        }
        
        $data['admin_id'] = $_SESSION['admin_id'];
        
        try {
            // 生成自定义订单ID
            $data['id'] = TutorOrder::generateOrderId();
            
            $order = TutorOrder::create($data);
            
            // 自动派单：轮动分配给派单组成员
            $this->autoAssignOrder($order);
            
            // 发送邮件通知
            if ($order->id) {
                $emailService = new EmailService();
                $emailService->sendOrderNotification($order->id);
            }
            
            return json(['success' => true, 'message' => '创建成功', 'data' => $order]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量创建（智能识别后批量保存）
     */
    public function batchCreate()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $orders = $this->request->post('orders');
        
        if (empty($orders) || !is_array($orders)) {
            return json(['success' => false, 'error' => '请提供订单数据']);
        }
        
        try {
            $adminId = $_SESSION['admin_id'];
            $successCount = 0;
            $failCount = 0;
            $orderIds = [];
            
            Db::startTrans();
            
            foreach ($orders as $orderData) {
                try {
                    $orderData['admin_id'] = $adminId;
                    // 生成自定义订单ID
                    $orderData['id'] = TutorOrder::generateOrderId();
                    $order = TutorOrder::create($orderData);
                    
                    // 自动派单：轮动分配给派单组成员
                    $this->autoAssignOrder($order);
                    
                    $orderIds[] = $order->id;
                    $successCount++;
                } catch (\Exception $e) {
                    $failCount++;
                }
            }
            
            Db::commit();
            
            // 批量发送邮件通知
            $emailService = new EmailService();
            foreach ($orderIds as $orderId) {
                $emailService->sendOrderNotification($orderId);
            }
            
            return json([
                'success' => true,
                'message' => "批量创建完成，成功{$successCount}条，失败{$failCount}条",
                'success_count' => $successCount,
                'fail_count' => $failCount
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            return json(['success' => false, 'error' => '批量创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新家教信息
     */
    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $data = $this->request->post();
        
        // 如果更新content字段，验证不为空（排除布尔值false的干扰）
        if (array_key_exists('content', $data) && trim($data['content']) === '') {
            return json(['success' => false, 'error' => '家教信息内容不能为空']);
        }
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            $order->save($data);
            return json(['success' => true, 'message' => '更新成功', 'data' => $order]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除家教信息（软删除）
     */
    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            $order->status = 0;
            $order->save();
            
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量删除
     */
    public function batchDelete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $ids = $this->request->post('ids');
        
        if (empty($ids) || !is_array($ids)) {
            return json(['success' => false, 'error' => '请提供要删除的ID']);
        }
        
        try {
            TutorOrder::whereIn('id', $ids)->update(['status' => 0]);
            return json(['success' => true, 'message' => '批量删除成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '批量删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量复制
     */
    public function batchCopy()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $ids = $this->request->post('ids');
        
        if (empty($ids) || !is_array($ids)) {
            return json(['success' => false, 'error' => '请提供要复制的ID']);
        }
        
        try {
            $orders = TutorOrder::whereIn('id', $ids)
                ->select();
            
            $copyText = [];
            foreach ($orders as $order) {
                // 直接使用原始内容
                $copyText[] = $order->content;
            }
            
            return json([
                'success' => true,
                'data' => implode("\n\n", $copyText)
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '批量复制失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 设置置顶
     */
    public function setTop()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $isTop = $this->request->post('is_top');
        $hours = $this->request->post('hours', 24); // 默认置顶24小时
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            $order->is_top = $isTop;
            if ($isTop) {
                $order->top_expire_time = date('Y-m-d H:i:s', time() + $hours * 3600);
            } else {
                $order->top_expire_time = null;
            }
            $order->save();
            
            return json(['success' => true, 'message' => '置顶设置成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 设置加急
     */
    public function setUrgent()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $isUrgent = $this->request->post('is_urgent');
        
        try {
            $order = TutorOrder::find($id);
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在']);
            }
            
            $order->is_urgent = $isUrgent;
            $order->save();
            
            return json(['success' => true, 'message' => '加急设置成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '操作失败：' . $e->getMessage()]);
        }
    }
    
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
            // 总订单数
            $totalCount = TutorOrder::where('status', 1)->count();
            
            // 今日新增
            $todayCount = TutorOrder::where('status', 1)
                ->whereTime('create_time', 'today')
                ->count();
            
            // 本月新增
            $monthCount = TutorOrder::where('status', 1)
                ->whereTime('create_time', 'month')
                ->count();
            
            // 平均课时费
            $avgSalary = Db::name('tutor_orders_new')
                ->where('status', 1)
                ->where('salary', '<>', '')
                ->avg('salary');
            
            // 热门区域 TOP5
            $hotDistricts = Db::name('tutor_orders_new')
                ->alias('o')
                ->join('districts d', 'o.district_id = d.id')
                ->where('o.status', 1)
                ->field('d.name, COUNT(*) as count')
                ->group('o.district_id')
                ->order('count', 'desc')
                ->limit(5)
                ->select();
            
            // 热门科目 TOP5
            $hotSubjects = Db::name('tutor_orders_new')
                ->alias('o')
                ->join('subjects s', 'o.subject_id = s.id')
                ->where('o.status', 1)
                ->field('s.name, COUNT(*) as count')
                ->group('o.subject_id')
                ->order('count', 'desc')
                ->limit(5)
                ->select();
            
            return json([
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'today_count' => $todayCount,
                    'month_count' => $monthCount,
                    'avg_salary' => round($avgSalary, 2),
                    'hot_districts' => $hotDistricts,
                    'hot_subjects' => $hotSubjects
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 自动派单：轮动分配给派单组成员
     */
    private function autoAssignOrder($order)
    {
        try {
            // 获取状态开启的派单组成员
            $dispatchers = Admin::where('role', 'dispatcher')
                ->where('status', 1) // 假设有status字段表示启用状态
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            if (empty($dispatchers)) {
                // 没有派单组成员，记录日志但不影响订单创建
                trace('没有可用的派单组成员，订单ID: ' . $order->id, 'info');
                return;
            }
            
            // 获取当前派单员的工作量，选择工作量最少的
            $dispatcherWorkloads = [];
            foreach ($dispatchers as $dispatcher) {
                $workload = TutorOrder::where('dispatcher_id', $dispatcher['id'])
                    ->where('status', 1)
                    ->where('assigned_time', '>=', date('Y-m-d 00:00:00')) // 今天的工作量
                    ->count();
                
                $dispatcherWorkloads[] = [
                    'id' => $dispatcher['id'],
                    'nickname' => $dispatcher['nickname'] ?? $dispatcher['username'],
                    'contact_info' => $dispatcher['contact'] ?? '',
                    'workload' => $workload
                ];
            }
            
            // 按工作量排序，选择工作量最少的
            usort($dispatcherWorkloads, function($a, $b) {
                return $a['workload'] - $b['workload'];
            });
            
            $selectedDispatcher = $dispatcherWorkloads[0];
            
            // 更新订单派单信息
            $order->dispatcher_id = $selectedDispatcher['id'];
            $order->contact_info = $selectedDispatcher['contact_info'];
            $order->assigned_time = date('Y-m-d H:i:s');
            $order->save();
            
            trace('订单自动派单成功，订单ID: ' . $order->id . '，派单员: ' . $selectedDispatcher['nickname'], 'info');
            
        } catch (\Exception $e) {
            // 派单失败不影响订单创建，只记录错误
            trace('自动派单失败，订单ID: ' . $order->id . '，错误: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 获取各城市订单数量统计
     */
    public function cityStats()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 查询各城市的订单数量（只统计启用状态的订单）
            $cityStats = Db::name('tutor_orders_new')
                ->alias('o')
                ->leftJoin('cities c', 'o.city_id = c.id')
                ->where('o.status', 1)
                ->field('o.city_id, c.name as city_name, COUNT(*) as count')
                ->group('o.city_id')
                ->order('count', 'desc')
                ->select();
            
            return json([
                'success' => true,
                'data' => $cityStats
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取城市统计失败：' . $e->getMessage()
            ]);
        }
    }
}

