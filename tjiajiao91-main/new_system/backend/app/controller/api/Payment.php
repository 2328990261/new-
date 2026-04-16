<?php
namespace app\controller\api;

use app\BaseController;
use app\model\Payment as PaymentModel;
use app\model\PaymentConfig;
use app\model\ServiceAgreement;
use app\model\TutorOrder;
use app\service\WechatPayService;
use think\facade\Db;
use think\facade\Env;
use think\facade\Log;
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
            // 合并 POST 与原始 JSON（部分代理/环境下仅其一可用；避免 staff_id/staff_name 丢失）
            $post = $this->request->post();
            if (!is_array($post)) {
                $post = [];
            }
            $rawJson = json_decode((string) $this->request->getInput(), true);
            $data = is_array($rawJson) ? array_merge($post, $rawJson) : $post;

            // 兼容前端 camelCase
            if (!isset($data['staff_id']) && isset($data['staffId'])) {
                $data['staff_id'] = $data['staffId'];
            }
            if (!isset($data['staff_name']) && isset($data['staffName'])) {
                $data['staff_name'] = $data['staffName'];
            }
            if (!isset($data['real_name']) && isset($data['realName'])) {
                $data['real_name'] = $data['realName'];
            }
            if (!isset($data['tutor_info']) && isset($data['tutorInfo'])) {
                $data['tutor_info'] = $data['tutorInfo'];
            }
            if (!isset($data['payer_contact']) && isset($data['phoneNumber'])) {
                $data['payer_contact'] = $data['phoneNumber'];
            }
            // 支付备注（选填）：写入 pay_remark（不要写入 remark，remark 是管理员订单备注）
            if (!isset($data['pay_remark']) && isset($data['payment_remark'])) {
                $data['pay_remark'] = $data['payment_remark'];
            }
            if (!isset($data['pay_remark']) && isset($data['paymentRemark'])) {
                $data['pay_remark'] = $data['paymentRemark'];
            }
            if (!isset($data['pay_remark']) && isset($data['payRemark'])) {
                $data['pay_remark'] = $data['payRemark'];
            }
            if (!isset($data['wechat_scene']) && isset($data['wechatScene'])) {
                $data['wechat_scene'] = $data['wechatScene'];
            }
            if (!isset($data['wechat_payment_scene']) && isset($data['wechatPaymentScene'])) {
                $data['wechat_payment_scene'] = $data['wechatPaymentScene'];
            }

            // 手机号可选：仅认 payer_contact / mobile / phone（用户端 H5 已不再传手机）
            // 规范化后若非完整 11 位大陆号则置空，避免误带字段、半截数字等触发「请填写正确的11位手机号」
            $rawPhone = (string)($data['payer_contact'] ?? $data['mobile'] ?? $data['phone'] ?? '');
            $data['payer_contact'] = preg_replace('/\D/', '', $rawPhone);
            if ($data['payer_contact'] === '' || !preg_match('/^1\d{10}$/', $data['payer_contact'])) {
                $data['payer_contact'] = '';
            }
            
            // 记录请求数据用于调试
            trace('创建支付订单请求: ' . json_encode($data, JSON_UNESCAPED_UNICODE), 'info');
            
            // 兼容新旧参数格式
            // 新格式: real_name, tutor_info, staff_id
            // 旧格式: tutor_order_id, payer_name, payer_contact
            if (isset($data['real_name'])) {
                $data['payer_name'] = $data['real_name'];
            }
            if (isset($data['tutor_info'])) {
                $data['tutor_name'] = $data['tutor_info'];
            }
            if (isset($data['staff_id']) && $data['staff_id'] !== '' && $data['staff_id'] !== null) {
                $data['dispatcher_id'] = $data['staff_id'];
            }
            
            // 数据验证
            $validate = Validate::rule([
                'amount'          => 'require|float|gt:0',
                'payment_method'  => 'require|in:wechat,alipay',
                'payer_name'      => 'require',
            ])->message([
                'amount.require'          => '支付金额不能为空',
                'amount.gt'               => '支付金额必须大于0',
                'payment_method.require'  => '请选择支付方式',
                'payment_method.in'       => '支付方式不正确',
                'payer_name.require'      => '支付人姓名不能为空',
            ]);
            
            if (!$validate->check($data)) {
                return json([
                    'code' => 400,
                    'message' => $validate->getError()
                ]);
            }

            // 如果有tutor_order_id,检查订单是否存在
            if (isset($data['tutor_order_id']) && $data['tutor_order_id']) {
                $tutorOrder = TutorOrder::where('id', $data['tutor_order_id'])
                    ->where('status', 1)
                    ->find();
                
                if (!$tutorOrder) {
                    return json([
                        'code' => 404,
                        'message' => '订单不存在或已下架'
                    ]);
                }
                
                // 检查是否已支付
                $existPayment = PaymentModel::where('tutor_order_id', $data['tutor_order_id'])
                    ->where('status', 'success')
                    ->find();
                
                if ($existPayment) {
                    return json([
                        'code' => 400,
                        'message' => '该订单已支付'
                    ]);
                }
            }
            
            // 微信：先解析场景与配置行，与 fa_payment_config.scene 对齐，并写入 wechat_payment_config_id
            $wechatConfig = null;
            $tradeTypeForWechat = null;
            if (($data['payment_method'] ?? '') === 'wechat') {
                $tradeTypeForWechat = $this->detectPaymentType();
                // 可选：与 trade_type 解耦，强制选用 fa_payment_config.scene（专用 H5 页传 h5）
                $sceneParam = trim((string)($data['wechat_scene'] ?? $data['wechat_payment_scene'] ?? ''));
                if ($sceneParam === 'h5' || $sceneParam === 'default') {
                    $wxScene = $sceneParam;
                } else {
                    $wxScene = ($tradeTypeForWechat === 'h5') ? 'h5' : 'default';
                }
                $wechatConfig = PaymentConfig::getConfigRow('wechat', $wxScene);
                if (!$wechatConfig) {
                    return json([
                        'code' => 500,
                        'message' => '微信支付未配置或不可用（场景：' . $wxScene . '）',
                    ]);
                }
            }

            // 生成支付订单号
            $orderNo = PaymentModel::generateOrderNo();
            
            // 准备支付记录数据
            $openidParam = trim((string)($this->request->param('openid', '') ?: ($data['openid'] ?? '')));
            if ($openidParam !== '' && strlen($openidParam) > 100) {
                $openidParam = mb_substr($openidParam, 0, 100, 'UTF-8');
            }

            $paymentData = [
                'order_no' => $orderNo,
                'tutor_order_id' => $data['tutor_order_id'] ?? null,
                'tutor_name' => $data['tutor_name'] ?? null,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payer_name' => $data['payer_name'],
                // 约定：老师姓名与支付人姓名一致（前端支付页填写的姓名必须写入两字段，避免各页面取值不一致）
                'teacher_name' => $data['payer_name'],
                'payer_contact' => $data['payer_contact'],
                'status' => 'pending'
            ];
            if ($openidParam !== '') {
                $paymentData['openid'] = $openidParam;
            }
            if ($wechatConfig) {
                $paymentData['wechat_payment_config_id'] = $wechatConfig->id;
            }
            
            // 只有当dispatcher_id字段存在时才添加
            if (isset($data['dispatcher_id'])) {
                $paymentData['dispatcher_id'] = (int) $data['dispatcher_id'];
            }

            // 未传派单客服时，从家教订单上继承 dispatcher_id（预约单支付常见）
            $dispatcherId = (int) ($paymentData['dispatcher_id'] ?? 0);
            $tutorOrderId = $paymentData['tutor_order_id'] ?? null;
            if ($dispatcherId <= 0 && $tutorOrderId !== null && $tutorOrderId !== '') {
                $ord = TutorOrder::where('id', $tutorOrderId)->field('dispatcher_id')->find();
                if ($ord && !empty($ord['dispatcher_id'])) {
                    $dispatcherId = (int) $ord['dispatcher_id'];
                    $paymentData['dispatcher_id'] = $dispatcherId;
                }
            }

            // 对接同学：写入展示名（与 Db::name(admin) 一致，避免模型与表配置不一致时查不到人）
            $contactStudent = '';
            if ($dispatcherId > 0) {
                $admRow = Db::name('admin')->where('id', $dispatcherId)->field('nickname,username')->find();
                if ($admRow) {
                    $contactStudent = (string) (($admRow['nickname'] ?? '') !== '' ? $admRow['nickname'] : ($admRow['username'] ?? ''));
                }
            }
            // 展示名：支持多种键名（前端可能传 staff_name / staffName / contact_student）
            $staffName = '';
            foreach (['staff_name', 'staffName', 'contact_student'] as $_snKey) {
                if (!isset($data[$_snKey])) {
                    continue;
                }
                $cand = trim((string) $data[$_snKey]);
                if ($cand !== '') {
                    $staffName = $cand;
                    break;
                }
            }
            if ($contactStudent === '' && $staffName !== '') {
                $contactStudent = $staffName;
            }
            // 仅有 dispatcher_id、库里查不到昵称时，至少写入可辨认占位，避免 contact_student 全空
            if ($contactStudent === '' && $dispatcherId > 0) {
                $contactStudent = '客服#' . $dispatcherId;
            }
            if ($contactStudent !== '') {
                $paymentData['contact_student'] = mb_substr($contactStudent, 0, 50, 'UTF-8');
            }

            // 支付备注（用户端选填）：写入 pay_remark
            $payRemark = trim((string)($data['pay_remark'] ?? ''));
            if ($payRemark !== '') {
                $paymentData['pay_remark'] = mb_substr($payRemark, 0, 500, 'UTF-8');
            }

            if (isset($paymentData['dispatcher_id']) && (int) $paymentData['dispatcher_id'] <= 0) {
                unset($paymentData['dispatcher_id']);
            }
            
            // 创建支付记录
            $payment = PaymentModel::create($paymentData);

            // 再次用 Db 写入对接信息：防止 ORM 与表字段缓存不一致时 insert 静默丢掉 contact_student / dispatcher_id
            $persistPatch = [];
            if (!empty($paymentData['contact_student'])) {
                $persistPatch['contact_student'] = $paymentData['contact_student'];
            }
            if (!empty($paymentData['dispatcher_id'])) {
                $persistPatch['dispatcher_id'] = (int) $paymentData['dispatcher_id'];
            }
            if (!empty($paymentData['openid'])) {
                $persistPatch['openid'] = $paymentData['openid'];
            }
            if (!empty($paymentData['teacher_name'])) {
                $persistPatch['teacher_name'] = $paymentData['teacher_name'];
            }
            if (!empty($paymentData['payer_name'])) {
                $persistPatch['payer_name'] = $paymentData['payer_name'];
            }
            if (!empty($paymentData['pay_remark'])) {
                $persistPatch['pay_remark'] = $paymentData['pay_remark'];
            }
            if ($persistPatch !== [] && $payment && $payment->id) {
                $persistPatch['update_time'] = date('Y-m-d H:i:s');
                Db::name('payments')->where('id', (int) $payment->id)->update($persistPatch);
                $payment->contact_student = $persistPatch['contact_student'] ?? $payment->contact_student;
                if (isset($persistPatch['dispatcher_id'])) {
                    $payment->dispatcher_id = $persistPatch['dispatcher_id'];
                }
            }

            trace('创建支付订单落库: id=' . ($payment->id ?? '') . ' contact_student=' . ($paymentData['contact_student'] ?? '') . ' dispatcher_id=' . ($paymentData['dispatcher_id'] ?? ''), 'info');
            
            // 调用微信支付接口
            if ($data['payment_method'] === 'wechat') {
                $wechatService = new WechatPayService($wechatConfig);
                $tradeType = $tradeTypeForWechat;

                trace('检测到的支付类型: ' . $tradeType, 'info');
                
                // 使用真实微信支付
                if ($tradeType === 'jsapi') {
                    // JSAPI支付（微信内）
                    if (!method_exists($wechatService, 'createJsapiPayment')) {
                        $payment->delete();
                        return json([
                            'code' => 500,
                            'message' => 'JSAPI支付功能暂未开通，请联系客服'
                        ]);
                    }
                    
                    $openid = $this->request->param('openid', '');
                    if (empty($openid)) {
                        $payment->delete();
                        return json([
                            'code' => 400,
                            'message' => 'JSAPI支付需要用户openid，请先完成微信授权'
                        ]);
                    }
                    
                    $payResult = $wechatService->createJsapiPayment([
                        'order_no' => $orderNo,
                        'amount' => $data['amount'],
                        'body' => '家教信息服务费',
                        'openid' => $openid
                    ]);
                } elseif ($tradeType === 'h5') {
                    // H5支付（手机浏览器）
                    if (!method_exists($wechatService, 'createH5Payment')) {
                        $payment->delete();
                        return json([
                            'code' => 500,
                            'message' => 'H5支付功能暂未开通，请联系客服'
                        ]);
                    }
                    
                    $redirectUrl = $this->request->param('redirect_url', '');
                    if (empty($redirectUrl)) {
                        // 默认跳转到支付成功页面
                        $redirectUrl = request()->domain() . '/payment-success';
                    }
                    
                    $payResult = $wechatService->createH5Payment([
                        'order_no' => $orderNo,
                        'amount' => $data['amount'],
                        'body' => '家教信息服务费',
                        'redirect_url' => $redirectUrl
                    ]);
                } else {
                    // Native扫码支付（PC端或默认）
                    if (!method_exists($wechatService, 'createNativePayment')) {
                        $payment->delete();
                        return json([
                            'code' => 500,
                            'message' => 'Native支付功能暂未开通，请联系客服'
                        ]);
                    }
                    
                    $payResult = $wechatService->createNativePayment([
                        'order_no' => $orderNo,
                        'amount' => $data['amount'],
                        'body' => '家教信息服务费'
                    ]);
                }
                
                if ($payResult['success']) {
                    $merged = array_merge([
                        'payment_id' => $payment->id,
                        'tutor_order_id' => $data['tutor_order_id'] ?? null,
                        'pay_mode' => $tradeType === 'jsapi' ? 'jsapi' : ($tradeType === 'h5' ? 'h5_mweb' : 'native'),
                    ], $payResult['data']);
                    if ($this->isPaymentDebugEnabled()) {
                        $merged['payment_debug'] = [
                            'wechat_scene' => $wxScene ?? '',
                            'wechat_payment_config_id' => (int) ($wechatConfig->id ?? 0),
                            'trade_type' => $tradeTypeForWechat,
                        ];
                    }
                    return json([
                        'code' => 200,
                        'message' => '支付订单创建成功',
                        'data' => $merged,
                    ]);
                } else {
                    // 真实支付失败，尝试使用二维码支付作为回退
                    trace('真实支付失败，回退到二维码支付: ' . $payResult['message'], 'warning');
                    
                    // 使用Native二维码支付作为回退方案（与当前下单同一套商户配置，避免 h5/多商户时下错单）
                    $fallbackResult = null;
                    if (method_exists($wechatService, 'createNativePayment')) {
                        $fallbackSvc = new WechatPayService($wechatConfig);
                        $fallbackResult = $fallbackSvc->createNativePayment([
                            'order_no' => $orderNo,
                            'amount' => $data['amount'],
                            'body' => '家教信息服务费'
                        ]);
                    }
                    
                    if ($fallbackResult && $fallbackResult['success']) {
                        $primaryErr = (string) ($payResult['message'] ?? '未知错误');
                        $mergedFb = array_merge([
                            'payment_id' => $payment->id,
                            'tutor_order_id' => $data['tutor_order_id'] ?? null,
                            'pay_mode' => 'native_fallback',
                            'jsapi_failed_reason' => $primaryErr,
                        ], $fallbackResult['data']);
                        if ($this->isPaymentDebugEnabled()) {
                            $mergedFb['payment_debug'] = [
                                'wechat_scene' => $wxScene ?? '',
                                'wechat_payment_config_id' => (int) ($wechatConfig->id ?? 0),
                                'trade_type' => $tradeTypeForWechat,
                                'note' => 'JSAPI/H5 主通道失败，已回退 Native 扫码；请查看 jsapi_failed_reason',
                            ];
                        }
                        return json([
                            'code' => 200,
                            'message' => '支付订单创建成功（主通道失败，已改用扫码支付）',
                            'data' => $mergedFb,
                            'error_detail' => 'JSAPI/主通道失败：' . $primaryErr,
                        ]);
                    } else {
                        // 二维码支付也失败了，删除支付记录并返回详细错误
                        $payment->delete();
                        $primaryError = $payResult['message'] ?? '未知错误';
                        $fallbackError = is_array($fallbackResult) ? ($fallbackResult['message'] ?? '未知错误') : '回退支付未执行';
                        $debugMessage = '主支付失败：' . $primaryError . '；回退支付失败：' . $fallbackError;
                        trace('支付创建失败: ' . $debugMessage, 'error');
                        return json([
                            'code' => 500,
                            'message' => '支付服务暂时不可用，请联系客服处理',
                            'error_detail' => $debugMessage
                        ]);
                    }
                }
            } elseif ($data['payment_method'] === 'alipay') {
                // 支付宝支付功能待实现
                return json([
                    'code' => 500,
                    'message' => '支付宝支付功能开发中'
                ]);
            }
            
            return json([
                'code' => 500,
                'message' => '不支持的支付方式'
            ]);
            
        } catch (\Exception $e) {
            trace('创建支付订单失败: ' . $e->getMessage(), 'error');
            trace('错误堆栈: ' . $e->getTraceAsString(), 'error');
            return json([
                'code' => 500,
                'message' => '创建失败：' . $e->getMessage(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
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
     * 查询支付状态
     * GET /api/payment/status
     */
    public function status()
    {
        try {
            $orderNo = $this->request->get('order_no', '');
            
            if (!$orderNo) {
                return json(['code' => 400, 'message' => '订单号不能为空']);
            }
            
            $payment = PaymentModel::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付订单不存在']);
            }

            // 待支付时向微信查单并同步（补偿异步 notify 未到达的情况）
            if ($payment->status === 'pending' && $payment->payment_method === 'wechat') {
                try {
                    $wx = WechatPayService::forPayment($payment);
                    $wx->syncPaymentIfWechatPaid($payment);
                    $payment = PaymentModel::where('order_no', $orderNo)->find();
                } catch (\Throwable $e) {
                    Log::warning('支付状态查单同步跳过: ' . $e->getMessage());
                }
            }
            
            return json([
                'code' => 200,
                'message' => '查询成功',
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
            return json(['code' => 500, 'message' => '查询失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 手动确认支付（用于本地测试）
     * POST /api/payment/manual-confirm
     */
    public function manualConfirm()
    {
        try {
            $orderNo = $this->request->post('order_no', '');
            
            if (!$orderNo) {
                return json(['code' => 400, 'message' => '订单号不能为空']);
            }
            
            $payment = PaymentModel::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付订单不存在']);
            }
            
            if ($payment->status === 'success') {
                return json(['code' => 200, 'message' => '订单已支付']);
            }
            
            // 更新支付状态
            $payment->status = 'success';
            $payment->transaction_id = 'MANUAL_' . time();
            $payment->paid_time = date('Y-m-d H:i:s');
            $payment->save();
            
            trace('手动确认支付成功: ' . $orderNo, 'info');
            
            return json([
                'code' => 200,
                'message' => '支付确认成功',
                'data' => [
                    'order_no' => $payment->order_no,
                    'status' => $payment->status,
                    'paid_time' => $payment->paid_time
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '确认失败：' . $e->getMessage()]);
        }
    }

    /**
     * 微信支付异步回调
     * POST /api/payment/notify
     */
    public function notify()
    {
        // 允许浏览器直接访问做连通性检查
        if (!$this->request->isPost()) {
            return response('payment notify endpoint ok', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        try {
            $rawBody = file_get_contents('php://input');
            if (empty($rawBody)) {
                return response($this->wechatNotifyReply('FAIL', 'EMPTY_BODY'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }

            $xml = @simplexml_load_string($rawBody, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml === false) {
                return response($this->wechatNotifyReply('FAIL', 'INVALID_XML'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }

            $data = json_decode(json_encode($xml), true);
            if (!is_array($data)) {
                return response($this->wechatNotifyReply('FAIL', 'INVALID_DATA'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }

            // 先检查微信通信层
            if (($data['return_code'] ?? '') !== 'SUCCESS') {
                Log::warning('微信支付回调通信失败: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
                return response($this->wechatNotifyReply('FAIL', 'RETURN_FAIL'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }

            // 验签：按回调里的 appid 匹配对应配置行（多商户/多场景）
            $appId = isset($data['appid']) ? trim((string) $data['appid']) : '';
            $cfg = null;
            if ($appId !== '') {
                $cfg = PaymentConfig::where('payment_method', 'wechat')->where('app_id', $appId)->find();
            }
            if (!$cfg) {
                $cfg = PaymentConfig::getConfigRow('wechat', 'default');
            }
            $wechatService = new WechatPayService($cfg);
            if (!$wechatService->verifySign($data)) {
                Log::error('微信支付回调验签失败: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
                return response($this->wechatNotifyReply('FAIL', 'SIGN_ERROR'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }

            $orderNo = $data['out_trade_no'] ?? '';
            if ($orderNo === '') {
                return response($this->wechatNotifyReply('FAIL', 'OUT_TRADE_NO_EMPTY'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }

            $payment = PaymentModel::where('order_no', $orderNo)->find();
            if (!$payment) {
                Log::error('微信支付回调未找到支付订单: ' . $orderNo);
                return response($this->wechatNotifyReply('FAIL', 'ORDER_NOT_FOUND'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }

            // 业务成功时更新订单；失败则仅记录
            if (($data['result_code'] ?? '') === 'SUCCESS') {
                if ($payment->status !== 'success') {
                    $payment->status = 'success';
                    $payment->transaction_id = $data['transaction_id'] ?? ($payment->transaction_id ?? '');
                    $payment->paid_time = date('Y-m-d H:i:s');
                    $payment->save();
                    Log::info('微信支付回调更新成功: ' . $orderNo);
                }
                return response($this->wechatNotifyReply('SUCCESS', 'OK'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
            }

            Log::warning('微信支付回调业务失败: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            return response($this->wechatNotifyReply('SUCCESS', 'OK'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        } catch (\Throwable $e) {
            Log::error('微信支付回调处理异常: ' . $e->getMessage());
            return response($this->wechatNotifyReply('FAIL', 'SERVER_ERROR'), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        }
    }

    /**
     * 生成微信回调响应 XML
     */
    private function wechatNotifyReply($returnCode = 'SUCCESS', $returnMsg = 'OK')
    {
        return '<xml>'
            . '<return_code><![CDATA[' . $returnCode . ']]></return_code>'
            . '<return_msg><![CDATA[' . $returnMsg . ']]></return_msg>'
            . '</xml>';
    }
    
    /**
     * 获取派单客服列表
     * GET /api/dispatchers
     */
    public function dispatchers()
    {
        try {
            // 可对接人员：派单组 + 客服组 + 组长（与支付页「派单客服」一致；原仅 dispatcher 时客服账号不会出现在列表）
            $dispatchers = Db::name('admin')
                ->where('status', 1)
                ->whereIn('role', ['dispatcher', 'customer_service', 'team_leader'])
                ->field('id,username,nickname,contact,status')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            // 如果没有数据，返回测试数据
            $out = [];
            foreach ($dispatchers as $row) {
                $out[] = [
                    'id' => (int) $row['id'],
                    'username' => $row['username'] ?? '',
                    'nickname' => $row['nickname'] ?? '',
                    'name' => ($row['nickname'] ?? '') !== '' ? $row['nickname'] : ($row['username'] ?? ''),
                    'contact' => $row['contact'] ?? '',
                    'status' => (int) ($row['status'] ?? 1),
                ];
            }
            $dispatchers = $out;

            if (empty($dispatchers)) {
                $dispatchers = [
                    [
                        'id' => 1,
                        'username' => 'admin1',
                        'nickname' => '客服小王',
                        'name' => '客服小王',
                        'contact' => '13800138001',
                        'status' => 1
                    ],
                    [
                        'id' => 2,
                        'username' => 'admin2',
                        'nickname' => '客服小李',
                        'name' => '客服小李',
                        'contact' => '13800138002',
                        'status' => 1
                    ],
                    [
                        'id' => 3,
                        'username' => 'admin3',
                        'nickname' => '客服小张',
                        'name' => '客服小张',
                        'contact' => '13800138003',
                        'status' => 1
                    ]
                ];
            }
            
            return json(['success' => true, 'data' => $dispatchers]);
        } catch (\Exception $e) {
            trace('获取派单客服失败: ' . $e->getMessage(), 'error');
            // 即使出错也返回测试数据
            $dispatchers = [
                [
                    'id' => 1,
                    'username' => 'admin1',
                    'nickname' => '客服小王',
                    'name' => '客服小王',
                    'contact' => '13800138001',
                    'status' => 1
                ],
                [
                    'id' => 2,
                    'username' => 'admin2',
                    'nickname' => '客服小李',
                    'name' => '客服小李',
                    'contact' => '13800138002',
                    'status' => 1
                ]
            ];
            return json(['success' => true, 'data' => $dispatchers]);
        }
    }
    
    /**
     * 搜索家教单
     * GET /api/tutor-orders/search
     */
    public function searchTutorOrders()
    {
        try {
            $keyword = $this->request->get('keyword', '');
            
            if (!$keyword) {
                return json(['success' => false, 'error' => '请输入搜索关键词']);
            }
            
            // 查询有效家教单
            $query = TutorOrder::with(['city', 'district', 'subject', 'dispatcher'])
                ->where('status', 1);
            
            // 支持订单号或内容搜索
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
                    'content' => $item->content,
                    'default_amount' => $item->default_amount ?? 0, // 默认金额
                    'dispatcher_id' => $item->dispatcher ? $item->dispatcher->id : null, // 派单客服ID
                    'dispatcher_name' => $item->dispatcher ? ($item->dispatcher->nickname ?: $item->dispatcher->username) : '', // 派单客服昵称
                ];
            }
            
            return json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            trace('搜索家教单失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '搜索失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取服务协议
     * GET /api/agreement
     */
    public function getAgreement()
    {
        try {
            $agreement = ServiceAgreement::getActive();
            
            if (!$agreement) {
                // 返回默认协议内容
                $defaultContent = '<h3>接单协议</h3>
                <p>1. 用户在使用本平台服务时，应遵守相关法律法规。</p>
                <p>2. 支付成功后，用户将获得相应的家教信息。</p>
                <p>3. 平台保证信息的真实性和有效性。</p>
                <p>4. 如有问题，请联系客服处理。</p>';
                
                return json(['success' => true, 'data' => ['content' => $defaultContent]]);
            }
            
            return json(['success' => true, 'data' => $agreement]);
        } catch (\Exception $e) {
            trace('获取协议失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '获取协议失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取微信网页静默授权URL（用于JSAPI）
     * GET /api/payment/wechat-oauth-url
     */
    public function wechatOauthUrl()
    {
        try {
            $config = Db::name('notification_config')->find(1);
            if (!$config || empty($config['wechat_app_id'])) {
                return json([
                    'code' => 500,
                    'message' => '微信公众号AppID未配置'
                ]);
            }

            $redirectUri = $this->request->get('redirect_uri', '');
            if (empty($redirectUri)) {
                return json([
                    'code' => 400,
                    'message' => '缺少redirect_uri参数'
                ]);
            }

            $state = substr(md5(uniqid('', true)), 0, 16);
            $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query([
                'appid' => $config['wechat_app_id'],
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'snsapi_base',
                'state' => $state,
            ]) . '#wechat_redirect';

            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'auth_url' => $authUrl,
                    'state' => $state
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取微信授权URL失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 微信网页授权 code 换 openid（用于JSAPI）
     * GET /api/payment/wechat-openid
     */
    public function wechatOpenid()
    {
        try {
            $code = $this->request->get('code', '');
            if (empty($code)) {
                return json([
                    'code' => 400,
                    'message' => '缺少code参数'
                ]);
            }

            $config = Db::name('notification_config')->find(1);
            if (!$config || empty($config['wechat_app_id']) || empty($config['wechat_app_secret'])) {
                return json([
                    'code' => 500,
                    'message' => '微信公众号配置不完整，请检查AppID/AppSecret'
                ]);
            }

            $tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query([
                'appid' => $config['wechat_app_id'],
                'secret' => $config['wechat_app_secret'],
                'code' => $code,
                'grant_type' => 'authorization_code'
            ]);

            $resp = @file_get_contents($tokenUrl);
            if ($resp === false) {
                return json([
                    'code' => 500,
                    'message' => '请求微信授权服务失败'
                ]);
            }

            $result = json_decode($resp, true);
            if (!is_array($result)) {
                return json([
                    'code' => 500,
                    'message' => '微信授权返回格式错误'
                ]);
            }

            if (isset($result['errcode'])) {
                return json([
                    'code' => 500,
                    'message' => '获取openid失败',
                    'error_detail' => ($result['errmsg'] ?? '未知错误') . '（errcode: ' . $result['errcode'] . '）'
                ]);
            }

            if (empty($result['openid'])) {
                return json([
                    'code' => 500,
                    'message' => '微信返回缺少openid'
                ]);
            }

            return json([
                'code' => 200,
                'message' => '获取openid成功',
                'data' => [
                    'openid' => $result['openid']
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取openid失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 自动检测支付类型
     * 根据User-Agent判断是否在微信环境，以及是PC还是移动端
     * @return string jsapi|h5|native
     */
    private function detectPaymentType()
    {
        // 允许前端通过参数强制指定支付类型
        $forceType = $this->request->param('trade_type', '');
        if (in_array($forceType, ['jsapi', 'h5', 'native'])) {
            return $forceType;
        }
        
        $userAgent = $this->request->header('user-agent', '');
        
        // 检测是否在微信环境
        $isWeChat = stripos($userAgent, 'MicroMessenger') !== false;
        
        if ($isWeChat) {
            // 在微信内，使用JSAPI支付
            return 'jsapi';
        }
        
        // 不在微信内，检测是否为移动设备
        $isMobile = $this->isMobileDevice($userAgent);
        
        if ($isMobile) {
            // 移动端浏览器，使用H5支付
            return 'h5';
        } else {
            // PC端，使用Native扫码支付
            return 'native';
        }
    }
    
    /**
     * 检测是否为移动设备
     * @param string $userAgent
     * @return bool
     */
    private function isMobileDevice($userAgent)
    {
        $mobileKeywords = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod',
            'BlackBerry', 'Windows Phone', 'Opera Mini', 'IEMobile'
        ];
        
        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
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

    /**
     * 是否在支付创建接口中附带 payment_debug（避免生产长期暴露：仅 APP_DEBUG 或 .env PAYMENT_RETURN_DEBUG=1）
     */
    private function isPaymentDebugEnabled(): bool
    {
        if ((string) Env::get('PAYMENT_RETURN_DEBUG', '') === '1') {
            return true;
        }
        $d = Env::get('APP_DEBUG', false);

        return $d === true || $d === 1 || $d === '1' || strtolower((string) $d) === 'true';
    }
}

