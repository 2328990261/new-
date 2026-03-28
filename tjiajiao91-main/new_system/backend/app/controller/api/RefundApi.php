<?php
namespace app\controller\api;

use app\BaseController;
use app\model\Payment;
use app\service\EmailService;
use app\service\UploadService;
use think\facade\Log;

/**
 * 用户端退款申请API
 */
class RefundApi extends BaseController
{
    /**
     * 获取支付记录（用于申请退款）
     */
    public function getPaymentByOrderNo()
    {
        try {
            $orderNo = $this->request->get('order_no', '');
            
            if (!$orderNo) {
                return json([
                    'success' => false,
                    'message' => '请提供订单号'
                ]);
            }
            
            // 查询支付记录
            $payment = Payment::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return json([
                    'success' => false,
                    'message' => '未找到匹配的支付记录'
                ]);
            }
            
            // 检查支付状态
            if (!in_array($payment->status, ['paid', 'success'])) {
                return json([
                    'success' => false,
                    'message' => '该订单未支付或已取消，无法申请退款'
                ]);
            }
            
            // 检查是否已全额退款
            if ($payment->refunded_amount >= $payment->amount) {
                return json([
                    'success' => false,
                    'message' => '该订单已全额退款'
                ]);
            }
            
            // 计算可退金额
            $canRefundAmount = $payment->amount - $payment->refunded_amount;
            
            return json([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                    'order_no' => $payment->order_no,
                    'tutor_name' => $payment->tutor_name,
                    'amount' => $payment->amount,
                    'refunded_amount' => $payment->refunded_amount,
                    'can_refund_amount' => $canRefundAmount,
                    'paid_time' => $payment->paid_time,
                    'refund_status' => $payment->refund_status,
                    'refund_status_text' => $this->getRefundStatusText($payment->refund_status)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取支付记录失败: ' . $e->getMessage());
            return json([
                'success' => false,
                'message' => '查询失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 申请退款
     */
    public function applyRefund()
    {
        try {
            $orderNo = $this->request->post('order_no', '');
            $refundAmount = $this->request->post('refund_amount/f', 0);
            $refundReason = $this->request->post('refund_reason', '');
            $refundVoucher = $this->request->post('refund_voucher', ''); // JSON字符串
            
            // 验证参数
            if (!$orderNo) {
                return json([
                    'success' => false,
                    'message' => '请提供订单号'
                ]);
            }
            
            if ($refundAmount <= 0) {
                return json([
                    'success' => false,
                    'message' => '退款金额必须大于0'
                ]);
            }
            
            if (!$refundReason) {
                return json([
                    'success' => false,
                    'message' => '请填写退款原因'
                ]);
            }
            
            // 查询支付记录
            $payment = Payment::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return json([
                    'success' => false,
                    'message' => '未找到匹配的支付记录'
                ]);
            }
            
            // 检查支付状态
            if (!in_array($payment->status, ['paid', 'success'])) {
                return json([
                    'success' => false,
                    'message' => '该订单未支付或已取消，无法申请退款'
                ]);
            }
            
            // 检查退款状态
            if ($payment->refund_status === 'pending') {
                return json([
                    'success' => false,
                    'message' => '您已提交过退款申请，请等待处理'
                ]);
            }
            
            if ($payment->refund_status === 'processing') {
                return json([
                    'success' => false,
                    'message' => '退款正在处理中，请耐心等待'
                ]);
            }
            
            // 检查退款金额
            $canRefundAmount = $payment->amount - $payment->refunded_amount;
            if ($refundAmount > $canRefundAmount) {
                return json([
                    'success' => false,
                    'message' => '退款金额不能超过可退金额：¥' . $canRefundAmount
                ]);
            }
            
            // 更新退款信息
            $payment->refund_status = 'pending';
            $payment->refund_apply_amount = $refundAmount;
            $payment->refund_apply_time = date('Y-m-d H:i:s');
            $payment->refund_reason = $refundReason;
            
            // 保存退款凭证
            if ($refundVoucher) {
                $payment->refund_voucher = $refundVoucher;
            }
            
            $payment->save();
            
            // 发送退款申请通知邮件给管理员
            try {
                $this->sendRefundNotification($payment);
            } catch (\Exception $e) {
                Log::error('发送退款通知邮件失败: ' . $e->getMessage());
            }
            
            return json([
                'success' => true,
                'message' => '退款申请已提交，我们会在1-3个工作日内处理',
                'data' => [
                    'order_no' => $payment->order_no,
                    'refund_amount' => $refundAmount,
                    'apply_time' => $payment->refund_apply_time
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('申请退款失败: ' . $e->getMessage());
            return json([
                'success' => false,
                'message' => '申请失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 上传退款凭证
     */
    public function uploadVoucher()
    {
        try {
            $file = $this->request->file('file');
            
            if (!$file) {
                return json([
                    'success' => false,
                    'message' => '请选择要上传的文件'
                ]);
            }
            
            $uploadService = new UploadService();
            $result = $uploadService->uploadRefundVoucher($file);
            
            return json($result);
        } catch (\Exception $e) {
            Log::error('上传退款凭证失败: ' . $e->getMessage());
            return json([
                'success' => false,
                'message' => '上传失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 查询退款状态
     */
    public function queryRefundStatus()
    {
        try {
            $orderNo = $this->request->get('order_no', '');
            
            if (!$orderNo) {
                return json([
                    'success' => false,
                    'message' => '请提供订单号'
                ]);
            }
            
            // 查询支付记录
            $payment = Payment::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return json([
                    'success' => false,
                    'message' => '未找到匹配的支付记录'
                ]);
            }
            
            return json([
                'success' => true,
                'data' => [
                    'order_no' => $payment->order_no,
                    'amount' => $payment->amount,
                    'refunded_amount' => $payment->refunded_amount,
                    'refund_status' => $payment->refund_status,
                    'refund_status_text' => $this->getRefundStatusText($payment->refund_status),
                    'refund_apply_amount' => $payment->refund_apply_amount,
                    'refund_apply_time' => $payment->refund_apply_time,
                    'refund_time' => $payment->refund_time,
                    'refund_reason' => $payment->refund_reason,
                    'reject_reason' => $payment->reject_reason,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('查询退款状态失败: ' . $e->getMessage());
            return json([
                'success' => false,
                'message' => '查询失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 发送退款申请通知
     */
    private function sendRefundNotification($payment)
    {
        try {
            $emailService = new EmailService();
            
            // 获取管理员邮箱列表（这里可以从配置或数据库中读取）
            $adminEmails = config('app.admin_notification_emails', []);
            
            if (empty($adminEmails)) {
                Log::warning('未配置管理员通知邮箱');
                return;
            }
            
            $subject = '【退款申请】订单号：' . $payment->order_no;
            $content = '
            <div style="font-family: Arial, sans-serif; line-height: 1.6;">
                <h2 style="color: #f56c6c;">新的退款申请</h2>
                <p>您有一笔新的退款申请需要处理：</p>
                <table style="border-collapse: collapse; width: 100%; margin: 20px 0;">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; background: #f5f5f5; width: 150px;"><strong>订单号：</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">' . $payment->order_no . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; background: #f5f5f5;"><strong>家教名称：</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">' . ($payment->tutor_name ?: '-') . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; background: #f5f5f5;"><strong>支付金额：</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">¥' . $payment->amount . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; background: #f5f5f5;"><strong>申请退款金额：</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd; color: #f56c6c;"><strong>¥' . $payment->refund_apply_amount . '</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; background: #f5f5f5;"><strong>申请时间：</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">' . $payment->refund_apply_time . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; background: #f5f5f5;"><strong>退款原因：</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">' . nl2br(htmlspecialchars($payment->refund_reason)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd; background: #f5f5f5;"><strong>联系方式：</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">' . $payment->payer_contact . '</td>
                    </tr>
                </table>
                <p style="margin-top: 30px;">
                    <a href="' . request()->domain() . '/admin/#/payment" 
                       style="display: inline-block; padding: 12px 24px; background: #409eff; color: white; text-decoration: none; border-radius: 4px;">
                        立即处理
                    </a>
                </p>
                <p style="color: #999; font-size: 12px; margin-top: 30px;">
                    此邮件由系统自动发送，请勿回复。
                </p>
            </div>
            ';
            
            foreach ($adminEmails as $email) {
                $emailService->send($email, $subject, $content);
            }
            
            Log::info('退款申请通知邮件已发送: ' . $payment->order_no);
        } catch (\Exception $e) {
            Log::error('发送退款通知邮件失败: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 获取退款状态文本
     */
    private function getRefundStatusText($status)
    {
        $statusMap = [
            'pending' => '退款待处理',
            'processing' => '退款处理中',
            'rejected' => '退款已驳回',
            'completed' => '退款已完成'
        ];
        return $statusMap[$status] ?? '无退款';
    }
}

