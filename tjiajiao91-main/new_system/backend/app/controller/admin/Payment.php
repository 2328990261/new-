<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\Payment as PaymentModel;
use app\model\PaymentConfig;
use app\model\Admin;
use think\facade\Db;
use think\facade\Log;
use app\service\WechatPayService;

class Payment extends BaseController
{
    /**
     * 获取支付列表
     */
    public function list()
    {
        try {
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 10);
            
            $query = PaymentModel::with(['dispatcher'])->order('paid_time', 'desc');
            
            // 筛选条件
            $tutorName = $this->request->param('tutor_name');
            if ($tutorName) {
                $query->where('tutor_name', 'like', "%{$tutorName}%");
            }
            
            $teacherName = $this->request->param('teacher_name');
            if ($teacherName) {
                $query->where('teacher_name', 'like', "%{$teacherName}%");
            }
            
            $status = $this->request->param('status');
            if ($status) {
                $query->where('status', $status);
            }
            
            // 退款状态筛选
            $refundStatus = $this->request->param('refund_status');
            if ($refundStatus) {
                $query->where('refund_status', $refundStatus);
            }
            
            // 金额范围筛选
            $amountMin = $this->request->param('amount_min');
            if ($amountMin) {
                $query->where('amount', '>=', $amountMin);
            }
            
            $amountMax = $this->request->param('amount_max');
            if ($amountMax) {
                $query->where('amount', '<=', $amountMax);
            }
            
            // 支付时间筛选
            $payTimeStart = $this->request->param('pay_time_start');
            $payTimeEnd = $this->request->param('pay_time_end');
            if ($payTimeStart && $payTimeEnd) {
                $query->whereBetweenTime('paid_time', $payTimeStart, $payTimeEnd);
            }
            
            // 退款时间筛选
            $refundTimeStart = $this->request->param('refund_time_start');
            $refundTimeEnd = $this->request->param('refund_time_end');
            if ($refundTimeStart && $refundTimeEnd) {
                $query->whereBetweenTime('refund_time', $refundTimeStart, $refundTimeEnd);
            }
            
            // 申请退款时间筛选
            $refundApplyTimeStart = $this->request->param('refund_apply_time_start');
            $refundApplyTimeEnd = $this->request->param('refund_apply_time_end');
            if ($refundApplyTimeStart && $refundApplyTimeEnd) {
                $query->whereBetweenTime('refund_apply_time', $refundApplyTimeStart, $refundApplyTimeEnd);
            }
            
            // 派单员筛选
            $dispatcherId = $this->request->param('dispatcher_id');
            if ($dispatcherId) {
                $query->where('dispatcher_id', $dispatcherId);
            }
            
            $list = $query->paginate(['list_rows' => $limit, 'page' => $page]);
            
            // 计算实收金额
            $items = $list->items();
            foreach ($items as &$item) {
                $item['actual_amount'] = $item['amount'] - $item['refunded_amount'];
                if (empty($item['contact_student']) && !empty($item['dispatcher'])) {
                    $item['contact_student'] = $item['dispatcher']['nickname'] ?: ($item['dispatcher']['username'] ?? '');
                }
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $items,
                    'total' => $list->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取支付列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取统计数据
     */
    public function statistics()
    {
        try {
            $query = PaymentModel::where('status', 'success');
            
            // 应用相同的筛选条件
            $tutorName = $this->request->param('tutor_name');
            if ($tutorName) {
                $query->where('tutor_name', 'like', "%{$tutorName}%");
            }
            
            $teacherName = $this->request->param('teacher_name');
            if ($teacherName) {
                $query->where('teacher_name', 'like', "%{$teacherName}%");
            }
            
            $refundStatus = $this->request->param('refund_status');
            if ($refundStatus) {
                $query->where('refund_status', $refundStatus);
            }
            
            $amountMin = $this->request->param('amount_min');
            if ($amountMin) {
                $query->where('amount', '>=', $amountMin);
            }
            
            $amountMax = $this->request->param('amount_max');
            if ($amountMax) {
                $query->where('amount', '<=', $amountMax);
            }
            
            $payTimeStart = $this->request->param('pay_time_start');
            $payTimeEnd = $this->request->param('pay_time_end');
            if ($payTimeStart && $payTimeEnd) {
                $query->whereBetweenTime('paid_time', $payTimeStart, $payTimeEnd);
            }
            
            $refundTimeStart = $this->request->param('refund_time_start');
            $refundTimeEnd = $this->request->param('refund_time_end');
            if ($refundTimeStart && $refundTimeEnd) {
                $query->whereBetweenTime('refund_time', $refundTimeStart, $refundTimeEnd);
            }
            
            $refundApplyTimeStart = $this->request->param('refund_apply_time_start');
            $refundApplyTimeEnd = $this->request->param('refund_apply_time_end');
            if ($refundApplyTimeStart && $refundApplyTimeEnd) {
                $query->whereBetweenTime('refund_apply_time', $refundApplyTimeStart, $refundApplyTimeEnd);
            }
            
            $dispatcherId = $this->request->param('dispatcher_id');
            if ($dispatcherId) {
                $query->where('dispatcher_id', $dispatcherId);
            }
            
            // 统计数据
            $totalPaidAmount = $query->sum('amount');
            $totalRefundedAmount = $query->sum('refunded_amount');
            $totalActualAmount = $totalPaidAmount - $totalRefundedAmount;
            $totalCount = $query->count();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'total_paid_amount' => number_format($totalPaidAmount, 2, '.', ''),
                    'total_refunded_amount' => number_format($totalRefundedAmount, 2, '.', ''),
                    'total_actual_amount' => number_format($totalActualAmount, 2, '.', ''),
                    'total_count' => $totalCount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取统计数据失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取派单员列表
     */
    public function dispatchers()
    {
        try {
            // 获取所有管理员作为派单员
            $dispatchers = Admin::field('id,nickname,username')
                ->where('status', 1)
                ->select();
            
            return json([
                'code' => 200,
                'success' => true,
                'message' => '获取成功',
                'data' => $dispatchers
            ]);
        } catch (\Exception $e) {
            Log::error('获取派单员列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'success' => false,
                'message' => '获取失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 获取支付详情
     */
    public function read()
    {
        try {
            $id = $this->request->param('id');
            $payment = PaymentModel::with(['dispatcher'])->find($id);
            
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            
            // 计算实收金额
            $payment['actual_amount'] = $payment['amount'] - $payment['refunded_amount'];
            if (empty($payment['contact_student']) && !empty($payment['dispatcher'])) {
                $payment['contact_student'] = $payment['dispatcher']['nickname'] ?: ($payment['dispatcher']['username'] ?? '');
            }
            
            return json(['code' => 200, 'message' => '获取成功', 'data' => $payment]);
        } catch (\Exception $e) {
            Log::error('获取支付详情失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 处理退款
     */
    public function processRefund()
    {
        try {
            $id = $this->request->post('id');
            $refundAmount = (float)$this->request->post('refund_amount');
            $remark = $this->request->post('remark', '');
            
            $payment = PaymentModel::find($id);
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            
            // 检查退款状态
            if ($payment->refund_status !== 'pending') {
                return json(['code' => 400, 'message' => '该订单不是待处理状态']);
            }
            
            // 检查退款金额
            $canRefundAmount = $payment->amount - $payment->refunded_amount;
            if ($refundAmount > $canRefundAmount) {
                return json(['code' => 400, 'message' => '退款金额超过可退金额']);
            }

            if ($refundAmount <= 0) {
                return json(['code' => 400, 'message' => '退款金额必须大于0']);
            }

            // 用户已填申请金额时，实际退款不得超过申请（防止前端误传或篡改多退）
            $applyAmt = (float)($payment->refund_apply_amount ?? 0);
            if ($applyAmt > 0 && $refundAmount > $applyAmt + 0.0001) {
                return json([
                    'code' => 400,
                    'message' => '退款金额不能超过用户申请金额 ¥' . number_format($applyAmt, 2, '.', ''),
                ]);
            }
            
            // 微信支付必须先调用微信退款接口，成功后再落库
            $refundExtraRemark = '';
            if (($payment->payment_method ?? '') === 'wechat') {
                $wechatService = new WechatPayService();
                $refundNo = 'REF' . date('YmdHis') . str_pad((string)$payment->id, 6, '0', STR_PAD_LEFT);
                $refundRes = $wechatService->refund([
                    'order_no' => $payment->order_no,
                    'refund_no' => $refundNo,
                    'total_amount' => (float)$payment->amount,
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $remark ?: '管理员处理退款',
                    'transaction_id' => $payment->transaction_id ?? ''
                ]);

                if (empty($refundRes['success'])) {
                    $msg = $refundRes['message'] ?? '微信退款失败';
                    Log::error('管理员退款调用微信接口失败: ' . $msg . '，order_no=' . ($payment->order_no ?? ''));
                    return json(['code' => 500, 'message' => '微信退款失败：' . $msg]);
                }

                $wechatRefundId = $refundRes['data']['refund_id'] ?? '';
                $refundExtraRemark = $wechatRefundId ? ('微信退款单号:' . $wechatRefundId) : ('商户退款单号:' . $refundNo);
            }

            Db::startTrans();
            try {
                // 更新退款信息
                $payment->refund_status = 'completed';
                $payment->refunded_amount = $payment->refunded_amount + $refundAmount;
                $payment->refund_time = date('Y-m-d H:i:s');

                $remarkParts = [];
                if ($remark) {
                    $remarkParts[] = $remark;
                }
                if ($refundExtraRemark) {
                    $remarkParts[] = $refundExtraRemark;
                }
                if (!empty($remarkParts)) {
                    $payment->remark = implode(' | ', $remarkParts);
                }
                $payment->save();
                Db::commit();
            } catch (\Throwable $txe) {
                Db::rollback();
                throw $txe;
            }
            
            return json(['code' => 200, 'message' => '退款成功']);
        } catch (\Exception $e) {
            Log::error('处理退款失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '退款失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 驳回退款
     */
    public function rejectRefund()
    {
        try {
            $id = $this->request->post('id');
            $rejectReason = $this->request->post('reject_reason');
            
            if (!$rejectReason) {
                return json(['code' => 400, 'message' => '请填写驳回原因']);
            }
            
            $payment = PaymentModel::find($id);
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            
            // 检查退款状态
            if ($payment->refund_status !== 'pending') {
                return json(['code' => 400, 'message' => '该订单不是待处理状态']);
            }
            
            // 更新退款状态
            $payment->refund_status = 'rejected';
            $payment->reject_reason = $rejectReason;
            $payment->save();
            
            return json(['code' => 200, 'message' => '驳回成功']);
        } catch (\Exception $e) {
            Log::error('驳回退款失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '驳回失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 测试支付配置
     * POST /admin/api/payments/config/test
     */
    public function testConfig()
    {
        try {
            $method = $this->request->post('payment_method', 'wechat');
            
            if ($method === 'wechat') {
                $wechatService = new \app\service\WechatPayService();
                $result = $wechatService->testConfig();
                
                return json([
                    'code' => $result['success'] ? 200 : 500,
                    'message' => $result['message'],
                    'data' => $result
                ]);
            } elseif ($method === 'alipay') {
                return json([
                    'code' => 500,
                    'message' => '支付宝配置测试功能开发中'
                ]);
            }
            
            return json([
                'code' => 400,
                'message' => '不支持的支付方式'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '测试失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取配置
     */
    public function getConfig()
    {
        try {
            $wechat = PaymentConfig::where('payment_method', 'wechat')->find();
            $alipay = PaymentConfig::where('payment_method', 'alipay')->find();
            
            return json([
                'success' => true,
                'data' => [
                    'wechat' => $wechat ?: [],
                    'alipay' => $alipay ?: []
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新配置
     */
    public function updateConfig()
    {
        try {
            $data = $this->request->post();
            
            if (isset($data['wechat'])) {
                $wechat = PaymentConfig::where('payment_method', 'wechat')->find();
                if ($wechat) {
                    $wechat->save($data['wechat']);
                } else {
                    $data['wechat']['payment_method'] = 'wechat';
                    PaymentConfig::create($data['wechat']);
                }
            }
            
            if (isset($data['alipay'])) {
                $alipay = PaymentConfig::where('payment_method', 'alipay')->find();
                if ($alipay) {
                    $alipay->save($data['alipay']);
                } else {
                    $data['alipay']['payment_method'] = 'alipay';
                    PaymentConfig::create($data['alipay']);
                }
            }
            
            return json(['success' => true, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
