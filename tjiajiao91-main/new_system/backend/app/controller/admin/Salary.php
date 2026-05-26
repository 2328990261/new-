<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Salary as SalaryModel;
use think\facade\Filesystem;

class Salary extends BaseController
{
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
            throw new \Exception('未登录');
        }
        
        $admin = \app\model\Admin::find($adminId);
        if (!$admin) {
            throw new \Exception('管理员不存在');
        }
        
        // 超级管理员直接通过
        if ($admin->role === 'super_admin') {
            return true;
        }
        
        // 检查是否有企业管理权限
        if (!$admin->can_access_enterprise) {
            throw new \Exception('无权访问企业管理模块');
        }
        
        return true;
    }
    
    // 获取费用支出列表
    public function index()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            $page = input('page', 1);
            $pageSize = input('pageSize', 20);
            $expenseType = input('expense_type', '');
            $projectName = input('project_name', '');
            $period = input('period', '');
            $paymentStatus = input('payment_status', '');
            $invoiceStatus = input('invoice_status', '');
            $startDate = input('start_date', '');
            $endDate = input('end_date', '');
            $keyword = input('keyword', '');
            
            $query = SalaryModel::order('expense_date', 'desc')->order('id', 'desc');
            
            if ($expenseType) {
                $query->where('expense_type', $expenseType);
            }
            if ($projectName) {
                $query->where('project_name', 'like', "%{$projectName}%");
            }
            if ($period) {
                $query->where('period', $period);
            }
            if ($paymentStatus) {
                $query->where('payment_status', $paymentStatus);
            }
            if ($invoiceStatus) {
                $query->where('invoice_status', $invoiceStatus);
            }
            if ($startDate) {
                $query->where('expense_date', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('expense_date', '<=', $endDate);
            }
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->whereOr('project_name', 'like', "%{$keyword}%")
                      ->whereOr('expense_type', 'like', "%{$keyword}%")
                      ->whereOr('remark', 'like', "%{$keyword}%");
                });
            }
            
            $list = $query->paginate([
                'list_rows' => $pageSize,
                'page' => $page
            ]);
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $list->items(),
                    'total' => $list->total()
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 创建费用支出记录
    public function save()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            $data = request()->post();
            
            // 前端已经通过单独的上传接口上传了文件，这里直接使用URL
            // 不需要再处理文件上传
            
            // 确保必填字段存在
            if (empty($data['expense_date'])) {
                return json(['success' => false, 'message' => '请选择支出日期']);
            }
            if (empty($data['expense_type'])) {
                return json(['success' => false, 'message' => '请选择费用类型']);
            }
            if (empty($data['project_name'])) {
                return json(['success' => false, 'message' => '请输入项目名称']);
            }
            if (!isset($data['amount']) || $data['amount'] === '') {
                return json(['success' => false, 'message' => '请输入金额']);
            }
            
            // 设置默认值
            if (!isset($data['quantity'])) {
                $data['quantity'] = 0;
            }
            if (!isset($data['unit_price'])) {
                $data['unit_price'] = 0;
            }
            if (!isset($data['invoice_attachment'])) {
                $data['invoice_attachment'] = '';
            }
            if (!isset($data['payment_attachment'])) {
                $data['payment_attachment'] = '';
            }
            if (!isset($data['receipt_method'])) {
                $data['receipt_method'] = '';
            }
            if (!isset($data['payment_method'])) {
                $data['payment_method'] = '';
            }
            if (!isset($data['period'])) {
                $data['period'] = '';
            }
            if (!isset($data['remark'])) {
                $data['remark'] = '';
            }
            
            $expense = SalaryModel::create($data);
            return json(['success' => true, 'message' => '创建成功', 'data' => $expense]);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 更新费用支出记录
    public function update()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            $id = input('id');
            
            // PUT请求需要特殊处理参数获取
            $data = request()->param();
            
            // 移除id字段，避免更新主键
            unset($data['id']);
            
            $expense = SalaryModel::find($id);
            if (!$expense) {
                return json(['success' => false, 'message' => '费用记录不存在']);
            }
            
            // 前端已经通过单独的上传接口上传了文件，这里直接使用URL
            // 不需要再处理文件上传
            
            $expense->save($data);
            return json(['success' => true, 'message' => '更新成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 删除费用支出记录
    public function delete()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            // DELETE请求从路由参数中获取ID
            $id = request()->param('id');
            
            if (!$id) {
                return json(['success' => false, 'message' => '缺少ID参数']);
            }
            
            $expense = SalaryModel::find($id);
            if (!$expense) {
                return json(['success' => false, 'message' => '费用记录不存在']);
            }
            
            $expense->delete();
            return json(['success' => true, 'message' => '删除成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 获取统计信息
    public function statistics()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            // 当月支出（根据expense_date字段）
            $currentMonth = date('Y-m');
            $currentMonthStart = $currentMonth . '-01';
            $currentMonthEnd = date('Y-m-t', strtotime($currentMonthStart));
            
            // 使用原生SQL确保正确计算
            $currentMonthAmount = SalaryModel::where('expense_date', '>=', $currentMonthStart)
                ->where('expense_date', '<=', $currentMonthEnd)
                ->where('delete_time', 0)  // 排除软删除的记录
                ->sum('amount');
            
            // 总计支出
            $totalAmount = SalaryModel::where('delete_time', 0)->sum('amount');
            
            // 其他统计
            $lastMonth = date('Y-m', strtotime('-1 month'));
            $lastMonthStart = $lastMonth . '-01';
            $lastMonthEnd = date('Y-m-t', strtotime($lastMonthStart));
            
            $lastMonthAmount = SalaryModel::where('expense_date', '>=', $lastMonthStart)
                ->where('expense_date', '<=', $lastMonthEnd)
                ->where('delete_time', 0)
                ->sum('amount');
            
            $unpaidCount = SalaryModel::where('payment_status', '未付款')
                ->where('delete_time', 0)
                ->count();
            $paidCount = SalaryModel::where('payment_status', '已付款')
                ->where('delete_time', 0)
                ->count();
            $uninvoicedCount = SalaryModel::where('invoice_status', '未开票')
                ->where('delete_time', 0)
                ->count();
            
            // 确保返回数字类型
            $currentMonthAmount = floatval($currentMonthAmount);
            $totalAmount = floatval($totalAmount);
            $lastMonthAmount = floatval($lastMonthAmount);
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'currentMonthAmount' => $currentMonthAmount,
                    'totalAmount' => $totalAmount,
                    'lastMonthAmount' => $lastMonthAmount,
                    'unpaidCount' => $unpaidCount,
                    'paidCount' => $paidCount,
                    'uninvoicedCount' => $uninvoicedCount
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 上传附件
    public function uploadAttachment()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            $file = request()->file('file');
            if (!$file) {
                return json(['success' => false, 'message' => '请选择文件']);
            }
            
            $savename = Filesystem::disk('public')->putFile('expense', $file);
            $url = '/storage/' . $savename;
            
            return json([
                'success' => true,
                'message' => '上传成功',
                'data' => ['url' => $url]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // 获取历史收款单位列表
    public function getReceiptMethods()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            // 查询所有不为空的收款单位，去重并按使用频率排序
            $methods = SalaryModel::where('receipt_method', '<>', '')
                ->where('receipt_method', 'not null')
                ->where('delete_time', 0)
                ->field('receipt_method, COUNT(*) as count')
                ->group('receipt_method')
                ->order('count', 'desc')
                ->limit(50)  // 限制返回50个最常用的
                ->select()
                ->toArray();
            
            // 提取收款单位列表
            $list = array_column($methods, 'receipt_method');
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => $list
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 获取历史支付方式列表
    public function getPaymentMethods()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            // 查询所有不为空的支付方式，去重并按使用频率排序
            $methods = SalaryModel::where('payment_method', '<>', '')
                ->where('payment_method', 'not null')
                ->where('delete_time', 0)
                ->field('payment_method, COUNT(*) as count')
                ->group('payment_method')
                ->order('count', 'desc')
                ->limit(50)  // 限制返回50个最常用的
                ->select()
                ->toArray();
            
            // 提取支付方式列表
            $list = array_column($methods, 'payment_method');
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => $list
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 获取支出数据面板
    public function dataPanel()
    {
        try {
            // $this->checkEnterprisePermission(); // 已取消权限验证
            
            $startDate = input('start_date', '');
            $endDate = input('end_date', '');
            
            // 如果没有传日期，默认最近30天
            if (!$startDate || !$endDate) {
                $endDate = date('Y-m-d');
                $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            
            // 核心指标
            $query = SalaryModel::where('delete_time', 0)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate);
            
            $totalAmount = $query->sum('amount');
            $paidAmount = SalaryModel::where('delete_time', 0)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->where('payment_status', '已付款')
                ->sum('amount');
            $unpaidAmount = SalaryModel::where('delete_time', 0)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->where('payment_status', '未付款')
                ->sum('amount');
            $invoicedAmount = SalaryModel::where('delete_time', 0)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->where('invoice_status', '已收票')
                ->sum('amount');
            $recordCount = $query->count();
            
            // 计算趋势（与上一周期对比）
            $days = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;
            $prevStartDate = date('Y-m-d', strtotime($startDate) - $days * 86400);
            $prevEndDate = date('Y-m-d', strtotime($endDate) - $days * 86400);
            
            $prevTotalAmount = SalaryModel::where('delete_time', 0)
                ->where('expense_date', '>=', $prevStartDate)
                ->where('expense_date', '<=', $prevEndDate)
                ->sum('amount');
            
            $totalAmountTrend = $prevTotalAmount > 0 ? 
                round((($totalAmount - $prevTotalAmount) / $prevTotalAmount) * 100, 1) : 0;
            
            // 费用类型占比数据（饼图）
            $expenseTypeData = SalaryModel::where('delete_time', 0)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->field('expense_type, SUM(amount) as total')
                ->group('expense_type')
                ->order('total', 'desc')
                ->select()
                ->toArray();
            
            // 趋势数据（折线图）
            $trendData = $this->getTrendData($startDate, $endDate);
            
            // 付款状态分布
            $statusData = [
                ['name' => '已付款', 'value' => floatval($paidAmount)],
                ['name' => '未付款', 'value' => floatval($unpaidAmount)],
            ];
            
            // 表格数据（按日期汇总）
            $tableData = SalaryModel::where('delete_time', 0)
                ->where('expense_date', '>=', $startDate)
                ->where('expense_date', '<=', $endDate)
                ->field('expense_date as date, SUM(amount) as totalAmount, COUNT(*) as recordCount')
                ->group('expense_date')
                ->order('expense_date', 'desc')
                ->select()
                ->toArray();
            
            return json([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'metrics' => [
                        'totalAmount' => floatval($totalAmount),
                        'totalAmountTrend' => $totalAmountTrend,
                        'paidAmount' => floatval($paidAmount),
                        'unpaidAmount' => floatval($unpaidAmount),
                        'invoicedAmount' => floatval($invoicedAmount),
                        'recordCount' => $recordCount,
                    ],
                    'expenseTypeData' => $expenseTypeData,
                    'trendData' => $trendData,
                    'statusData' => $statusData,
                    'tableData' => $tableData,
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 获取趋势数据
    private function getTrendData($startDate, $endDate)
    {
        $dates = [];
        $amounts = [];
        $paidAmounts = [];
        $unpaidAmounts = [];
        
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $dates[] = $date;
            
            $dayAmount = SalaryModel::where('delete_time', 0)
                ->where('expense_date', $date)
                ->sum('amount');
            $amounts[] = floatval($dayAmount);
            
            $dayPaidAmount = SalaryModel::where('delete_time', 0)
                ->where('expense_date', $date)
                ->where('payment_status', '已付款')
                ->sum('amount');
            $paidAmounts[] = floatval($dayPaidAmount);
            
            $dayUnpaidAmount = SalaryModel::where('delete_time', 0)
                ->where('expense_date', $date)
                ->where('payment_status', '未付款')
                ->sum('amount');
            $unpaidAmounts[] = floatval($dayUnpaidAmount);
            
            $current = strtotime('+1 day', $current);
        }
        
        return [
            'dates' => $dates,
            'amounts' => $amounts,
            'paidAmounts' => $paidAmounts,
            'unpaidAmounts' => $unpaidAmounts,
        ];
    }
}
