<?php
namespace app\controller\api;

use app\BaseController;
use app\model\Payment;
use app\service\EmailService;
use app\service\UploadService;
use app\service\WechatNotificationService;
use think\facade\Db;
use think\facade\Log;

/**
 * 用户端退款申请API
 */
class RefundApi extends BaseController
{
    /**
     * 退费页：关注公众号二维码等门禁配置（公开）
     * GET /api/refund/gate-config
     */
    public function gateConfig()
    {
        try {
            $row = Db::name('payment_config')->where('payment_method', 'wechat')->find();
            $raw = $row['refund_follow_qrcode'] ?? '';
            $qrcodeUrl = '';
            if ($raw !== null && $raw !== '') {
                $qrcodeUrl = preg_match('#^https?://#i', (string) $raw)
                    ? (string) $raw
                    : rtrim((string) $this->request->domain(), '/') . '/' . ltrim((string) $raw, '/');
            }

            return json([
                'success' => true,
                'data' => [
                    'qrcode_url' => $qrcodeUrl !== '' ? $qrcodeUrl : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('refund gate-config: ' . $e->getMessage());

            return json(['success' => false, 'message' => '读取配置失败']);
        }
    }

    /**
     * 当前 openid 是否已关注公众号（与 OAuth 同源：notification_config）
     * GET /api/refund/subscribe-status?openid=xxx
     */
    public function subscribeStatus()
    {
        try {
            $openid = trim((string) $this->request->get('openid', ''));
            if ($openid === '') {
                return json(['success' => false, 'message' => '缺少 openid']);
            }
            if (strlen($openid) > 100) {
                $openid = mb_substr($openid, 0, 100, 'UTF-8');
            }

            $res = WechatNotificationService::getUserInfo($openid);
            if (empty($res['success'])) {
                return json([
                    'success' => false,
                    'message' => $res['message'] ?? '无法获取关注状态，请检查公众号服务器 IP 白名单与接口权限',
                ]);
            }

            $data = $res['data'] ?? [];
            $sub = (int) ($data['subscribe'] ?? 0);

            return json([
                'success' => true,
                'data' => [
                    'subscribed' => $sub === 1,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('subscribe-status: ' . $e->getMessage());

            return json(['success' => false, 'message' => '查询失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取支付记录（用于申请退款）
     * 支持 order_no；或仅 openid（微信内主路径）；或 phone/mobile（兼容）
     */
    public function getPaymentByOrderNo()
    {
        try {
            // 列表查询（openid/手机号多笔）仅展示近 30 天；按订单号精确查询不受限
            $refundListSince = date('Y-m-d H:i:s', strtotime('-30 days'));

            $orderNo = trim((string)$this->request->get('order_no', ''));
            $phoneRaw = (string)($this->request->get('phone', '') ?: $this->request->get('mobile', ''));
            $phone = preg_replace('/\D/', '', $phoneRaw);
            $openidParam = trim((string)$this->request->get('openid', ''));

            if ($orderNo !== '') {
                $payment = Payment::where('order_no', $orderNo)->find();
                if (!$payment) {
                    return json(['success' => false, 'message' => '未找到匹配的支付记录']);
                }
                $err = $this->assertPaymentRefundable($payment);
                if ($err !== null) {
                    return json(['success' => false, 'message' => $err]);
                }
                return json([
                    'success' => true,
                    'data' => array_merge($this->paymentToRefundSummary($payment), ['need_select' => false]),
                ]);
            }

            // 仅 openid：微信内退费主路径（不依赖手机号）
            if ($phone === '' && $openidParam !== '') {
                if (strlen($openidParam) > 100) {
                    $openidParam = mb_substr($openidParam, 0, 100, 'UTF-8');
                }
                $listQuery = Payment::where('openid', $openidParam)
                    ->whereIn('status', ['paid', 'success'])
                    ->whereRaw('COALESCE(refunded_amount, 0) < amount')
                    ->whereRaw('COALESCE(paid_time, create_time) >= ?', [$refundListSince]);

                $list = $listQuery->order('paid_time', 'desc')
                    ->order('id', 'desc')
                    ->select();

                if ($list->isEmpty()) {
                    return json([
                        'success' => false,
                        'message' => '近一个月内未找到可退款的支付记录。仅支持在当前公众号内完成支付且已记录微信身份的订单；更早订单或扫码未绑定请联系客服。',
                    ]);
                }

                if ($list->count() === 1) {
                    $payment = $list[0];

                    return json([
                        'success' => true,
                        'data' => array_merge($this->paymentToRefundSummary($payment), ['need_select' => false]),
                    ]);
                }

                $payments = [];
                foreach ($list as $p) {
                    $payments[] = $this->paymentToRefundSummary($p);
                }

                return json([
                    'success' => true,
                    'data' => [
                        'need_select' => true,
                        'payments' => $payments,
                    ],
                ]);
            }

            if ($phone === '') {
                return json(['success' => false, 'message' => '请提供订单号，或在微信内授权后查询']);
            }
            if (!preg_match('/^1\d{10}$/', $phone)) {
                return json(['success' => false, 'message' => '请填写正确的11位手机号']);
            }

            $openid = trim((string)$this->request->get('openid', ''));

            $listQuery = Payment::where('payer_contact', $phone)
                ->whereIn('status', ['paid', 'success'])
                ->whereRaw('COALESCE(refunded_amount, 0) < amount');

            // 有 openid（通常微信内）：只查该微信账号支付的订单，避免仅凭手机号看到他人订单
            if ($openid !== '') {
                if (strlen($openid) > 100) {
                    $openid = mb_substr($openid, 0, 100, 'UTF-8');
                }
                $listQuery->where('openid', $openid);
            } else {
                // 未授权微信（如普通浏览器）：不返回已绑定 openid 的订单，仅可查询扫码/H5 等未落库 openid 的记录
                $listQuery->whereRaw('(openid IS NULL OR openid = ?)', ['']);
            }

            $listQuery->whereRaw('COALESCE(paid_time, create_time) >= ?', [$refundListSince]);

            $list = $listQuery->order('paid_time', 'desc')
                ->order('id', 'desc')
                ->select();

            if ($list->isEmpty()) {
                return json([
                    'success' => false,
                    'message' => $openid !== ''
                        ? '近一个月内未找到与当前微信一致的可退款订单，请确认或联系客服'
                        : '近一个月内该手机号下暂无可申请退款的已支付订单（若您在微信内支付，请在微信内打开本页并授权）',
                ]);
            }

            if ($list->count() === 1) {
                $payment = $list[0];
                return json([
                    'success' => true,
                    'data' => array_merge($this->paymentToRefundSummary($payment), ['need_select' => false]),
                ]);
            }

            $payments = [];
            foreach ($list as $p) {
                $payments[] = $this->paymentToRefundSummary($p);
            }

            return json([
                'success' => true,
                'data' => [
                    'need_select' => true,
                    'payments' => $payments,
                ],
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
            $orderNo = trim((string)$this->request->param('order_no', ''));
            $refundAmount = (float)$this->request->param('refund_amount', 0);
            $refundReason = (string)$this->request->param('refund_reason', '');
            $refundVoucher = (string)$this->request->param('refund_voucher', '');
            $receivedAmountRaw = trim((string)$this->request->param('received_amount', ''));
            $queryPhone = preg_replace('/\D/', '', (string)$this->request->param('query_phone', ''));
            if ($queryPhone === '') {
                $queryPhone = preg_replace('/\D/', '', (string)(
                    $this->request->param('phone', '') ?: $this->request->param('mobile', '')
                ));
            }
            
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

            // 收到课酬：非必填；有值时做基础数值校验
            $receivedAmount = null;
            if ($receivedAmountRaw !== '') {
                if (!is_numeric($receivedAmountRaw)) {
                    return json([
                        'success' => false,
                        'message' => '收到课酬金额格式不正确'
                    ]);
                }
                $receivedAmount = (float)$receivedAmountRaw;
                if ($receivedAmount < 0) {
                    return json([
                        'success' => false,
                        'message' => '收到课酬金额不能小于0'
                    ]);
                }
            }
            
            // 查询支付记录
            $payment = Payment::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return json([
                    'success' => false,
                    'message' => '未找到匹配的支付记录'
                ]);
            }

            if ($queryPhone !== '' && (string)$payment->payer_contact !== $queryPhone) {
                return json([
                    'success' => false,
                    'message' => '手机号与所选支付订单不匹配，请重新查询',
                ]);
            }

            $queryOpenid = trim((string)$this->request->param('query_openid', ''));
            $storedOpenid = trim((string)($payment->openid ?? ''));
            if ($storedOpenid !== '') {
                if ($queryOpenid === '' || $storedOpenid !== $queryOpenid) {
                    return json([
                        'success' => false,
                        'message' => '该订单需在微信内使用支付时的同一账号提交退款申请',
                    ]);
                }
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

            if ($receivedAmount !== null && $receivedAmount > (float)$payment->amount) {
                return json([
                    'success' => false,
                    'message' => '收到课酬金额不能超过信息费金额：¥' . $payment->amount
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

    /**
     * 单笔支付是否可申请退款；通过返回 null，否则返回错误文案
     */
    private function assertPaymentRefundable($payment): ?string
    {
        if (!in_array($payment->status, ['paid', 'success'])) {
            return '该订单未支付或已取消，无法申请退款';
        }
        $refunded = (float)($payment->refunded_amount ?? 0);
        if ($refunded >= $payment->amount) {
            return '该订单已全额退款';
        }
        return null;
    }

    /**
     * 退款页展示用的支付摘要
     */
    private function paymentToRefundSummary($payment): array
    {
        $refunded = (float)($payment->refunded_amount ?? 0);
        $canRefundAmount = (float)$payment->amount - $refunded;

        return [
            'id' => $payment->id,
            'order_no' => $payment->order_no,
            'tutor_name' => $payment->tutor_name,
            'amount' => $payment->amount,
            'refunded_amount' => $payment->refunded_amount,
            'can_refund_amount' => $canRefundAmount,
            'paid_time' => $payment->paid_time,
            'refund_status' => $payment->refund_status,
            'refund_status_text' => $this->getRefundStatusText($payment->refund_status),
            'payer_contact' => $payment->payer_contact,
            'payer_name' => $payment->payer_name ?? '',
            'teacher_name' => $payment->teacher_name ?? '',
            'contact_student' => $payment->contact_student ?? '',
        ];
    }
}

