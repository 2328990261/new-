<?php
namespace app\controller\api;

use app\BaseController;
use app\model\Payment as PaymentModel;
use app\model\PaymentConfig;
use app\model\ServiceAgreement;
use app\model\TutorOrder;
use app\service\WechatPayService;
use think\facade\Db;
use think\facade\Validate;

/**
 * 支付API控制器（用户端）
 */
class Payment extends BaseController
{
    /**
     * 搜索订单（支持订单ID和标题搜索）
     * GET /api/payment/search
     */
    public function search()
    {
        try {
            $keyword = $this->request->get('keyword', '');
            
            if (!$keyword) {
                return json(['success' => false, 'error' => '请输入搜索关键词']);
            }
            
            // 查询有效订单
            $query = TutorOrder::with(['city', 'district', 'subject', 'dispatcher'])
                ->where('status', 1);
            
            // 支持ID或内容搜索
            $query->where(function($q) use ($keyword) {
                $q->whereOr([
                    ['id', '=', $keyword],
                    ['content', 'like', '%' . $keyword . '%'],
                    ['grade', 'like', '%' . $keyword . '%'],
                ]);
            });
            
            $list = $query->limit(10)->select();
            
            // 格式化返回数据
            $data = [];
            foreach ($list as $item) {
                $data[] = [
                    'id' => $item->id,
                    'order_id' => $item->id,
                    'title' => $this->generateTitle($item),
                    'grade' => $item->grade,
                    'subject' => $item->subject ? $item->subject->name : '',
                    'city' => $item->city ? $item->city->name : '',
                    'district' => $item->district ? $item->district->name : '',
                    'salary' => $item->salary,
                    'dispatcher_name' => $item->dispatcher ? $item->dispatcher->nickname : '',
                    'dispatcher_contact' => $item->contact_info ?? '',
                    'content' => $item->content,
                ];
            }
            
            return json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            trace('搜索订单失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '搜索失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 查询订单详情
     * GET /api/payment/query
     */
    public function query()
    {
        try {
            $orderId = $this->request->get('order_id', '');
            
            if (!$orderId) {
                return json(['success' => false, 'error' => '请输入订单ID']);
            }
            
            $order = TutorOrder::with(['city', 'district', 'subject', 'dispatcher'])
                ->where('id', $orderId)
                ->where('status', 1)
                ->find();
            
            if (!$order) {
                return json(['success' => false, 'error' => '订单不存在或已下架']);
            }
            
            // 检查是否已支付
            $payment = PaymentModel::where('tutor_order_id', $orderId)
                ->where('status', 'success')
                ->find();
            
            $data = [
                'id' => $order->id,
                'order_id' => $order->id,
                'title' => $this->generateTitle($order),
                'grade' => $order->grade,
                'subject' => $order->subject ? $order->subject->name : '',
                'city' => $order->city ? $order->city->name : '',
                'district' => $order->district ? $order->district->name : '',
                'salary' => $order->salary,
                'content' => $order->content,
                'dispatcher_name' => $order->dispatcher ? $order->dispatcher->nickname : '',
                'dispatcher_contact' => $order->contact_info ?? '',
                'status' => $payment ? 'paid' : 'pending',
                'payment_info' => $payment ? [
                    'order_no' => $payment->order_no,
                    'amount' => $payment->amount,
                    'paid_time' => $payment->paid_time,
                ] : null
            ];
            
            return json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            trace('查询订单失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '查询失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 创建支付订单
     * POST /api/payment/create
     */
    public function create()
    {
        try {
            $data = $this->request->post();
            
            // 数据验证
            $validate = Validate::rule([
                'tutor_order_id'  => 'require|number',
                'amount'          => 'require|float|gt:0',
                'payment_method'  => 'require|in:wechat,alipay',
                'payer_name'      => 'require',
                'payer_contact'   => 'require',
            ])->message([
                'tutor_order_id.require'  => '订单ID不能为空',
                'amount.require'          => '支付金额不能为空',
                'amount.gt'               => '支付金额必须大于0',
                'payment_method.require'  => '请选择支付方式',
                'payment_method.in'       => '支付方式不正确',
                'payer_name.require'      => '支付人姓名不能为空',
                'payer_contact.require'   => '联系方式不能为空',
            ]);
            
            if (!$validate->check($data)) {
                return json(['success' => false, 'error' => $validate->getError()]);
            }
            
            // 检查订单是否存在
            $tutorOrder = TutorOrder::where('id', $data['tutor_order_id'])
                ->where('status', 1)
                ->find();
            
            if (!$tutorOrder) {
                return json(['success' => false, 'error' => '订单不存在或已下架']);
            }
            
            // 检查是否已支付
            $existPayment = PaymentModel::where('tutor_order_id', $data['tutor_order_id'])
                ->where('status', 'success')
                ->find();
            
            if ($existPayment) {
                return json(['success' => false, 'error' => '该订单已支付']);
            }
            
            // 检查支付方式是否可用
            if (!PaymentConfig::isEnabled($data['payment_method'])) {
                return json(['success' => false, 'error' => '该支付方式暂未开通']);
            }
            
            // 生成支付订单号
            $orderNo = PaymentModel::generateOrderNo();
            
            // 创建支付记录
            $payment = PaymentModel::create([
                'order_no' => $orderNo,
                'tutor_order_id' => $data['tutor_order_id'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payer_name' => $data['payer_name'],
                'payer_contact' => $data['payer_contact'],
                'status' => 'pending'
            ]);
            
            // 调用支付接口（模拟支付或真实支付）
            if ($data['payment_method'] === 'wechat') {
                $wechatService = new WechatPayService();
                
                // 判断是否使用模拟支付（通过配置或参数）
                $useMock = $this->request->param('mock', true); // 默认使用模拟支付
                
                // 获取支付类型：jsapi 或 native
                $tradeType = $this->request->param('trade_type', 'native');
                
                if ($useMock) {
                    // 使用模拟支付
                    $payResult = $wechatService->createMockPayment([
                        'order_no' => $orderNo,
                        'amount' => $data['amount'],
                        'body' => '家教信息服务费'
                    ]);
                } else {
                    // 使用真实微信支付
                    if ($tradeType === 'jsapi') {
                        // JSAPI支付（微信内）
                        $openid = $this->request->param('openid', '');
                        if (empty($openid)) {
                            $payment->delete();
                            return json([
                                'success' => false,
                                'error' => 'JSAPI支付需要用户openid，请先完成微信授权'
                            ]);
                        }
                        
                        $payResult = $wechatService->createJsapiPayment([
                            'order_no' => $orderNo,
                            'amount' => $data['amount'],
                            'body' => '家教信息服务费',
                            'openid' => $openid
                        ]);
                    } else {
                        // Native扫码支付（默认）
                        $payResult = $wechatService->createNativePayment([
                            'order_no' => $orderNo,
                            'amount' => $data['amount'],
                            'body' => '家教信息服务费'
                        ]);
                    }
                }
                
                if ($payResult['success']) {
                    return json([
                        'success' => true,
                        'message' => '支付订单创建成功',
                        'data' => array_merge([
                            'payment_id' => $payment->id,
                            'tutor_order_id' => $data['tutor_order_id'],
                        ], $payResult['data'])
                    ]);
                } else {
                    // 支付创建失败，删除支付记录
                    $payment->delete();
                    return json([
                        'success' => false,
                        'error' => $payResult['message'] ?? '创建支付订单失败'
                    ]);
                }
            } elseif ($data['payment_method'] === 'alipay') {
                // TODO: 支付宝支付
                return json([
                    'success' => false,
                    'error' => '支付宝支付功能开发中'
                ]);
            }
            
            return json([
                'success' => false,
                'error' => '不支持的支付方式'
            ]);
            
        } catch (\Exception $e) {
            trace('创建支付订单失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取服务协议
     * GET /api/payment/agreement
     */
    public function agreement()
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
     * 模拟支付页面（扫码后访问）
     * GET /api/payment/mock-pay
     */
    public function mockPay()
    {
        try {
            $orderNo = $this->request->get('order_no', '');
            
            if (!$orderNo) {
                return json(['success' => false, 'error' => '订单号不能为空']);
            }
            
            $payment = PaymentModel::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return json(['success' => false, 'error' => '支付订单不存在']);
            }
            
            if ($payment->status === 'success') {
                return json([
                    'success' => false,
                    'message' => '订单已支付',
                    'data' => [
                        'order_no' => $orderNo,
                        'status' => 'success',
                        'paid_time' => $payment->paid_time
                    ]
                ]);
            }
            
            // 返回支付信息，前端可以显示支付页面
            return json([
                'success' => true,
                'data' => [
                    'order_no' => $orderNo,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payer_name' => $payment->payer_name,
                    'status' => $payment->status,
                    'create_time' => $payment->create_time
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 模拟支付成功（测试用）
     * POST /api/payment/mock-success
     */
    public function mockSuccess()
    {
        try {
            $orderNo = $this->request->post('order_no', '');
            
            if (!$orderNo) {
                return json(['success' => false, 'error' => '订单号不能为空']);
            }
            
            $wechatService = new WechatPayService();
            $result = $wechatService->mockPaymentSuccess($orderNo);
            
            return json($result);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 查询支付状态
     * GET /api/payment/status
     */
    public function status()
    {
        try {
            $orderNo = $this->request->get('order_no', '');
            
            if (!$orderNo) {
                return json(['success' => false, 'error' => '订单号不能为空']);
            }
            
            $payment = PaymentModel::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return json(['success' => false, 'error' => '支付订单不存在']);
            }
            
            return json([
                'success' => true,
                'data' => [
                    'order_no' => $payment->order_no,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'transaction_id' => $payment->transaction_id,
                    'paid_time' => $payment->paid_time,
                    'create_time' => $payment->create_time
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 生成订单标题
     */
    private function generateTitle($order)
    {
        $parts = [];
        
        if ($order->city) {
            $parts[] = $order->city->name;
        }
        if ($order->district) {
            $parts[] = $order->district->name;
        }
        if ($order->grade) {
            $parts[] = $order->grade;
        }
        if ($order->subject) {
            $parts[] = $order->subject->name;
        }
        
        $title = implode(' ', $parts);
        
        // 如果标题为空，使用订单ID
        return $title ?: '订单 #' . $order->id;
    }
}

