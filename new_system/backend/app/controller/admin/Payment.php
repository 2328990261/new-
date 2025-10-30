<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Payment as PaymentModel;
use app\model\PaymentConfig;
use app\model\ServiceAgreement;
use app\model\TutorOrder;
use app\service\WechatPayService;

/**
 * 支付管理控制器
 */
class Payment extends BaseController
{
    /**
     * 获取支付配置列表
     */
    public function getConfig()
    {
        try {
            $configs = PaymentConfig::select()->toArray();
            
            // 隐藏敏感信息
            foreach ($configs as &$config) {
                if (!empty($config['api_key'])) {
                    $config['api_key'] = substr($config['api_key'], 0, 6) . '******';
                }
            }
            
            return json(['success' => true, 'data' => $configs]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新支付配置
     */
    public function updateConfig()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $data = $this->request->post();
            $id = $data['id'] ?? 0;
            
            if (!$id) {
                return json(['success' => false, 'error' => '缺少配置ID']);
            }
            
            $config = PaymentConfig::find($id);
            if (!$config) {
                return json(['success' => false, 'error' => '配置不存在']);
            }
            
            // 如果api_key是隐藏的格式，则不更新
            if (isset($data['api_key']) && strpos($data['api_key'], '******') !== false) {
                unset($data['api_key']);
            }
            
            $config->save($data);
            
            return json(['success' => true, 'message' => '更新成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取支付记录列表
     */
    public function list()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['code' => 401, 'message' => '未登录']);
        }
        
        try {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            // 筛选条件
            $tutorName = $this->request->get('tutor_name', '');
            $teacherName = $this->request->get('teacher_name', '');
            $status = $this->request->get('status', '');
            $refundStatus = $this->request->get('refund_status', '');
            $customerService = $this->request->get('customer_service', '');
            $payTimeStart = $this->request->get('pay_time_start', '');
            $payTimeEnd = $this->request->get('pay_time_end', '');
            $refundTimeStart = $this->request->get('refund_time_start', '');
            $refundTimeEnd = $this->request->get('refund_time_end', '');
            $refundApplyTimeStart = $this->request->get('refund_apply_time_start', '');
            $refundApplyTimeEnd = $this->request->get('refund_apply_time_end', '');
            $amountMin = $this->request->get('amount_min', '');
            $amountMax = $this->request->get('amount_max', '');
            
            $query = PaymentModel::order('create_time', 'desc');
            
            // 家教名称
            if ($tutorName) {
                $query->where('tutor_name', 'like', '%' . $tutorName . '%');
            }
            
            // 老师姓名
            if ($teacherName) {
                $query->where('teacher_name', 'like', '%' . $teacherName . '%');
            }
            
            // 支付状态
            if ($status) {
                $query->where('status', $status);
            }
            
            // 退款状态
            if ($refundStatus) {
                $query->where('refund_status', $refundStatus);
            }
            
            // 客服人员
            if ($customerService) {
                $query->where('customer_service', $customerService);
            }
            
            // 支付时间范围
            if ($payTimeStart && $payTimeEnd) {
                $query->whereBetweenTime('paid_time', $payTimeStart, $payTimeEnd);
            }
            
            // 退款时间范围
            if ($refundTimeStart && $refundTimeEnd) {
                $query->whereBetweenTime('refund_time', $refundTimeStart, $refundTimeEnd);
            }
            
            // 申请退款时间范围
            if ($refundApplyTimeStart && $refundApplyTimeEnd) {
                $query->whereBetweenTime('refund_apply_time', $refundApplyTimeStart, $refundApplyTimeEnd);
            }
            
            // 金额范围
            if ($amountMin !== '') {
                $query->where('amount', '>=', $amountMin);
            }
            if ($amountMax !== '') {
                $query->where('amount', '<=', $amountMax);
            }
            
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $result->items(),
                    'total' => $result->total()
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取统计数据
     */
    public function statistics()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['code' => 401, 'message' => '未登录']);
        }
        
        try {
            // 获取筛选条件（与list方法相同）
            $tutorName = $this->request->get('tutor_name', '');
            $teacherName = $this->request->get('teacher_name', '');
            $status = $this->request->get('status', '');
            $refundStatus = $this->request->get('refund_status', '');
            $customerService = $this->request->get('customer_service', '');
            $payTimeStart = $this->request->get('pay_time_start', '');
            $payTimeEnd = $this->request->get('pay_time_end', '');
            $refundTimeStart = $this->request->get('refund_time_start', '');
            $refundTimeEnd = $this->request->get('refund_time_end', '');
            $refundApplyTimeStart = $this->request->get('refund_apply_time_start', '');
            $refundApplyTimeEnd = $this->request->get('refund_apply_time_end', '');
            $amountMin = $this->request->get('amount_min', '');
            $amountMax = $this->request->get('amount_max', '');
            
            $query = PaymentModel::where('status', 'paid');
            
            // 应用筛选条件
            if ($tutorName) {
                $query->where('tutor_name', 'like', '%' . $tutorName . '%');
            }
            if ($teacherName) {
                $query->where('teacher_name', 'like', '%' . $teacherName . '%');
            }
            if ($refundStatus) {
                $query->where('refund_status', $refundStatus);
            }
            if ($customerService) {
                $query->where('customer_service', $customerService);
            }
            if ($payTimeStart && $payTimeEnd) {
                $query->whereBetweenTime('paid_time', $payTimeStart, $payTimeEnd);
            }
            if ($refundTimeStart && $refundTimeEnd) {
                $query->whereBetweenTime('refund_time', $refundTimeStart, $refundTimeEnd);
            }
            if ($refundApplyTimeStart && $refundApplyTimeEnd) {
                $query->whereBetweenTime('refund_apply_time', $refundApplyTimeStart, $refundApplyTimeEnd);
            }
            if ($amountMin !== '') {
                $query->where('amount', '>=', $amountMin);
            }
            if ($amountMax !== '') {
                $query->where('amount', '<=', $amountMax);
            }
            
            // 统计数据
            $totalPaidAmount = $query->sum('amount') ?: 0;
            $totalRefundedAmount = $query->sum('refunded_amount') ?: 0;
            $totalActualAmount = $query->sum('actual_amount') ?: 0;
            $totalCount = $query->count();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'total_paid_amount' => round($totalPaidAmount, 2),
                    'total_refunded_amount' => round($totalRefundedAmount, 2),
                    'total_actual_amount' => round($totalActualAmount, 2),
                    'total_count' => $totalCount
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取支付详情
     */
    public function detail($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['code' => 401, 'message' => '未登录']);
        }
        
        try {
            $payment = PaymentModel::with(['tutorOrder'])->find($id);
            
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            
            return json(['code' => 200, 'message' => '获取成功', 'data' => $payment]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 处理退款申请
     */
    public function processRefund()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['code' => 401, 'message' => '未登录']);
        }
        
        try {
            $id = $this->request->post('id/d', 0);
            $refundAmount = $this->request->post('refund_amount/f', 0);
            $remark = $this->request->post('remark', '');
            $autoRefund = $this->request->post('auto_refund/d', 1); // 是否自动退款（调用微信API）
            
            if (!$id) {
                return json(['code' => 400, 'message' => '缺少支付记录ID']);
            }
            
            $payment = PaymentModel::find($id);
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            
            if ($payment->status !== 'paid') {
                return json(['code' => 400, 'message' => '该支付记录不可退款']);
            }
            
            if ($refundAmount <= 0) {
                return json(['code' => 400, 'message' => '退款金额必须大于0']);
            }
            
            if ($refundAmount > ($payment->amount - $payment->refunded_amount)) {
                return json(['code' => 400, 'message' => '退款金额不能超过可退金额']);
            }
            
            // 如果启用自动退款且支付方式是微信
            $refundResult = null;
            if ($autoRefund && $payment->payment_method === 'wechat') {
                try {
                    $wechatService = new WechatPayService();
                    
                    // 生成退款单号
                    $refundNo = PaymentModel::generateRefundNo();
                    
                    // 调用微信退款API
                    $refundResult = $wechatService->refund([
                        'order_no' => $payment->order_no,
                        'transaction_id' => $payment->transaction_id,
                        'refund_no' => $refundNo,
                        'total_amount' => $payment->amount,
                        'refund_amount' => $refundAmount,
                        'refund_reason' => $remark ?: '管理员处理退款'
                    ]);
                    
                    if (!$refundResult['success']) {
                        return json([
                            'code' => 500,
                            'message' => '微信退款失败：' . $refundResult['message']
                        ]);
                    }
                } catch (\Exception $e) {
                    return json([
                        'code' => 500,
                        'message' => '调用微信退款API失败：' . $e->getMessage()
                    ]);
                }
            }
            
            // 更新退款信息
            $payment->refunded_amount = $payment->refunded_amount + $refundAmount;
            $payment->actual_amount = $payment->amount - $payment->refunded_amount;
            $payment->refund_time = date('Y-m-d H:i:s');
            $payment->refund_status = 'completed';
            $payment->customer_service = $_SESSION['admin_username'] ?? '';
            if ($remark) {
                $payment->remark = $remark;
            }
            
            // 如果有微信退款结果，记录退款单号
            if ($refundResult && $refundResult['success']) {
                $payment->refund_reason = '微信退款单号：' . $refundResult['data']['refund_id'];
            }
            
            $payment->save();
            
            return json([
                'code' => 200,
                'message' => '退款成功',
                'data' => [
                    'wechat_refund' => $refundResult ? $refundResult['success'] : false,
                    'refund_info' => $refundResult['data'] ?? null
                ]
            ]);
        } catch (\Exception $e) {
            trace('退款失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '退款失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 驳回退款申请
     */
    public function rejectRefund()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['code' => 401, 'message' => '未登录']);
        }
        
        try {
            $id = $this->request->post('id/d', 0);
            $rejectReason = $this->request->post('reject_reason', '');
            
            if (!$id) {
                return json(['code' => 400, 'message' => '缺少支付记录ID']);
            }
            
            if (!$rejectReason) {
                return json(['code' => 400, 'message' => '请填写驳回原因']);
            }
            
            $payment = PaymentModel::find($id);
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            
            if ($payment->refund_status !== 'pending') {
                return json(['code' => 400, 'message' => '该记录不在待处理状态']);
            }
            
            // 更新退款状态
            $payment->refund_status = 'rejected';
            $payment->reject_reason = $rejectReason;
            $payment->customer_service = $_SESSION['admin_username'] ?? '';
            $payment->save();
            
            return json(['code' => 200, 'message' => '驳回成功']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '驳回失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 查看退款详情
     */
    public function refundDetail($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['code' => 401, 'message' => '未登录']);
        }
        
        try {
            $payment = PaymentModel::find($id);
            
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'id' => $payment->id,
                    'order_no' => $payment->order_no,
                    'tutor_name' => $payment->tutor_name,
                    'teacher_name' => $payment->teacher_name,
                    'amount' => $payment->amount,
                    'refunded_amount' => $payment->refunded_amount,
                    'actual_amount' => $payment->actual_amount,
                    'refund_status' => $payment->refund_status,
                    'refund_reason' => $payment->refund_reason,
                    'reject_reason' => $payment->reject_reason,
                    'refund_apply_time' => $payment->refund_apply_time,
                    'refund_time' => $payment->refund_time,
                    'customer_service' => $payment->customer_service,
                    'remark' => $payment->remark
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 支付回调处理
     */
    public function notify()
    {
        try {
            // 这里实现支付回调逻辑
            // 需要根据不同的支付方式处理
            $method = $this->request->post('method', 'wechat');
            
            // TODO: 实现具体的支付回调逻辑
            // 1. 验证签名
            // 2. 更新订单状态
            // 3. 返回确认信息
            
            return json(['success' => true]);
        } catch (\Exception $e) {
            trace('支付回调失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取服务协议
     */
    public function getAgreement()
    {
        try {
            $agreement = ServiceAgreement::getActive();
            
            if (!$agreement) {
                return json(['success' => false, 'error' => '服务协议不存在']);
            }
            
            return json(['success' => true, 'data' => $agreement]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新服务协议
     */
    public function updateAgreement()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $data = $this->request->post();
            $id = $data['id'] ?? 0;
            
            if (!$id) {
                return json(['success' => false, 'error' => '缺少协议ID']);
            }
            
            $agreement = ServiceAgreement::find($id);
            if (!$agreement) {
                return json(['success' => false, 'error' => '协议不存在']);
            }
            
            $agreement->save($data);
            
            return json(['success' => true, 'message' => '更新成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 测试支付配置
     */
    public function testConfig()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $method = $this->request->post('method', 'wechat');
            
            if ($method === 'wechat') {
                // 测试微信支付配置
                $wechatService = new WechatPayService();
                $result = $wechatService->testConfig();
                return json($result);
            } elseif ($method === 'alipay') {
                // TODO: 实现支付宝测试
                return json([
                    'success' => false,
                    'message' => '支付宝测试功能待开发'
                ]);
            } else {
                return json([
                    'success' => false,
                    'error' => '不支持的支付方式'
                ]);
            }
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '测试失败：' . $e->getMessage()
            ]);
        }
    }
}
