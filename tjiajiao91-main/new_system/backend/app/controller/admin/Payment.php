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
    /** @var bool|null 当前请求内缓存：是否含 pinned_at */
    private $cachedPaymentsHasPinnedAt = null;
    /** @var bool|null 当前请求内缓存：是否含 is_deleted */
    private $cachedPaymentsHasIsDeleted = null;
    /** @var bool|null 当前请求内缓存：是否含 refund_remark */
    private $cachedPaymentsHasRefundRemark = null;

    /**
     * 获取支付列表
     */
    public function list()
    {
        try {
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 10);
            
            $query = PaymentModel::with(['dispatcher'])->order('is_pinned', 'desc');
            // 软删除过滤：仅在字段存在时启用；否则不影响“全部订单”显示
            if ($this->paymentsTableHasIsDeleted()) {
                // 兼容历史数据：is_deleted 可能为 NULL
                $query->whereRaw('(is_deleted IS NULL OR is_deleted = 0)');
            }
            if ($this->paymentsTableHasPinnedAt()) {
                $query->orderRaw('pinned_at IS NULL ASC, pinned_at DESC');
            }
            // 列表分组优先级：
            // 1) 退款待处理 2) 待支付 3) 已支付(无退款流) 4) 退款驳回 5) 已退费 6) 其他
            $query->orderRaw("
                CASE
                    WHEN refund_status = 'pending' THEN 1
                    WHEN status = 'pending' THEN 2
                    WHEN status IN ('paid', 'success') AND (refund_status IS NULL OR refund_status = '') THEN 3
                    WHEN refund_status = 'rejected' THEN 4
                    WHEN refund_status = 'completed' THEN 5
                    ELSE 6
                END ASC
            ");
            $query->order('paid_time', 'desc');
            
            // 筛选条件
            $tutorName = $this->request->param('tutor_name');
            if ($tutorName) {
                $query->where('tutor_name', 'like', "%{$tutorName}%");
            }
            
            $payerName = $this->request->param('payer_name');
            if ($payerName === null || $payerName === '') {
                $payerName = $this->request->param('teacher_name');
            }
            if ($payerName) {
                $query->where('payer_name', 'like', "%{$payerName}%");
            }
            
            $status = $this->request->param('status');
            if ($status) {
                $query->where('status', $status);
            }
            
            // 退款状态筛选
            $refundStatus = $this->request->param('refund_status');
            if ($refundStatus) {
                // 特殊值：none/empty 表示只看“无退款状态”的已支付单
                if ($refundStatus === 'none' || $refundStatus === 'empty') {
                    $query->whereRaw("(refund_status IS NULL OR refund_status = '')");
                } else {
                    $query->where('refund_status', $refundStatus);
                }
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
                // 仍为空时从关联家教单的派单客服补全（支付记录未写 dispatcher_id / contact_student 的历史数据）
                if (empty($item['contact_student']) && !empty($item['tutor_order_id'])) {
                    $row = Db::name('tutor_orders_new')->alias('o')
                        ->leftJoin('admin a', 'o.dispatcher_id = a.id')
                        ->where('o.id', $item['tutor_order_id'])
                        ->field('a.nickname,a.username')
                        ->find();
                    if ($row) {
                        $nick = $row['nickname'] ?? '';
                        $user = $row['username'] ?? '';
                        $item['contact_student'] = $nick !== '' ? $nick : $user;
                    }
                }
            }
            unset($item);
            
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
            // 统计也排除“已移除”的支付单；仅在字段存在时启用
            if ($this->paymentsTableHasIsDeleted()) {
                $query->whereRaw('(is_deleted IS NULL OR is_deleted = 0)');
            }
            
            // 应用相同的筛选条件
            $tutorName = $this->request->param('tutor_name');
            if ($tutorName) {
                $query->where('tutor_name', 'like', "%{$tutorName}%");
            }
            
            $payerName = $this->request->param('payer_name');
            if ($payerName === null || $payerName === '') {
                $payerName = $this->request->param('teacher_name');
            }
            if ($payerName) {
                $query->where('payer_name', 'like', "%{$payerName}%");
            }
            
            $refundStatus = $this->request->param('refund_status');
            if ($refundStatus) {
                if ($refundStatus === 'none' || $refundStatus === 'empty') {
                    $query->whereRaw("(refund_status IS NULL OR refund_status = '')");
                } else {
                    $query->where('refund_status', $refundStatus);
                }
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
            if (empty($payment['contact_student']) && !empty($payment['tutor_order_id'])) {
                $row = Db::name('tutor_orders_new')->alias('o')
                    ->leftJoin('admin a', 'o.dispatcher_id = a.id')
                    ->where('o.id', $payment['tutor_order_id'])
                    ->field('a.nickname,a.username')
                    ->find();
                if ($row) {
                    $nick = $row['nickname'] ?? '';
                    $user = $row['username'] ?? '';
                    $payment['contact_student'] = $nick !== '' ? $nick : $user;
                }
            }
            
            return json(['code' => 200, 'message' => '获取成功', 'data' => $payment]);
        } catch (\Exception $e) {
            Log::error('获取支付详情失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }

    /**
     * 更新支付记录备注（管理端）
     */
    public function updateRemark()
    {
        try {
            $id = (int)$this->request->param('id');
            $remark = (string)$this->request->post('remark', '');

            $payment = PaymentModel::find($id);
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }

            $payment->remark = $remark;
            $payment->save();

            return json(['code' => 200, 'message' => '保存成功']);
        } catch (\Exception $e) {
            Log::error('更新支付备注失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
        }
    }

    /**
     * 列表置顶 / 取消置顶
     * 需字段 is_pinned、pinned_at（见 database/patch_payments_is_pinned.sql、patch_payments_pinned_at.sql）
     */
    public function setPinned()
    {
        try {
            $id = (int)$this->request->param('id');
            // JSON Body 用 param 更稳妥
            $isPinned = (int)$this->request->param('is_pinned', 1);
            $isPinned = $isPinned ? 1 : 0;

            if ($id <= 0) {
                return json(['code' => 400, 'message' => '参数 id 无效']);
            }

            $exists = Db::name('payments')->where('id', $id)->value('id');
            if (!$exists) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }

            $now = date('Y-m-d H:i:s');
            $row = [
                'is_pinned'   => $isPinned,
                'update_time' => $now,
            ];
            if ($this->paymentsTableHasPinnedAt()) {
                $row['pinned_at'] = $isPinned ? $now : null;
            }

            try {
                $affected = Db::name('payments')->where('id', $id)->update($row);
            } catch (\Throwable $e) {
                $em = $e->getMessage();
                if (strpos($em, 'pinned_at') !== false || strpos($em, 'Unknown column') !== false) {
                    unset($row['pinned_at']);
                    $this->cachedPaymentsHasPinnedAt = false;
                    $affected = Db::name('payments')->where('id', $id)->update($row);
                } else {
                    throw $e;
                }
            }
            if ($affected === false) {
                return json(['code' => 500, 'message' => '数据库更新失败']);
            }

            $data = ['is_pinned' => $isPinned];
            if (array_key_exists('pinned_at', $row)) {
                $data['pinned_at'] = $row['pinned_at'];
            }

            return json([
                'code' => 200,
                'message' => $isPinned ? '已置顶' : '已取消置顶',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('支付置顶失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '操作失败：' . $e->getMessage()]);
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

            // 允许管理员对退款金额进行上下调整：
            // - 仍以“可退上限(canRefundAmount)”为唯一约束，避免多退整单。
            
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
                    // 退款审核备注：优先写入 refund_remark（字段不存在时回退到 remark）
                    $joined = implode(' | ', $remarkParts);
                    if ($this->paymentsTableHasRefundRemark()) {
                        $payment->refund_remark = $joined;
                    } else {
                        $payment->remark = $joined;
                    }
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
     * 更新订单备注（支付列表的“备注”应展示/编辑该字段）
     * POST /admin/api/payments/:id/order-remark
     */
    public function updateOrderRemark()
    {
        try {
            $id = (int)$this->request->param('id');
            $remark = (string)$this->request->post('remark', '');

            $payment = PaymentModel::find($id);
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            // 订单备注：写入 fa_payments.remark（列表“备注”展示该字段）
            $payment->remark = $remark;
            $payment->save();

            return json(['code' => 200, 'message' => '保存成功']);
        } catch (\Exception $e) {
            Log::error('更新订单备注失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
        }
    }

    /**
     * 支付单软删除：仅从管理端列表隐藏
     * POST /admin/api/payments/:id/remove
     */
    public function softDelete()
    {
        try {
            $id = (int)$this->request->param('id');
            if ($id <= 0) {
                return json(['code' => 400, 'message' => '参数 id 无效']);
            }
            $payment = PaymentModel::find($id);
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }

            if (!$this->paymentsTableHasIsDeleted()) {
                return json(['code' => 500, 'message' => '当前数据库未包含软删除字段，请先执行脚本 patch_payments_soft_delete_and_refund_remark.sql']);
            }

            $now = date('Y-m-d H:i:s');
            $payment->is_deleted = 1;
            $payment->deleted_at = $now;
            $payment->save();

            return json(['code' => 200, 'message' => '已移除（软删除）']);
        } catch (\Exception $e) {
            Log::error('支付软删除失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '操作失败：' . $e->getMessage()]);
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

    private function paymentsTableHasPinnedAt(): bool
    {
        if ($this->cachedPaymentsHasPinnedAt !== null) {
            return $this->cachedPaymentsHasPinnedAt;
        }
        try {
            $table = Db::name('payments')->getTable();
            $rows = Db::query("SHOW COLUMNS FROM `{$table}` LIKE 'pinned_at'");
            $this->cachedPaymentsHasPinnedAt = !empty($rows);
        } catch (\Throwable $e) {
            $this->cachedPaymentsHasPinnedAt = false;
        }

        return $this->cachedPaymentsHasPinnedAt;
    }

    private function paymentsTableHasIsDeleted(): bool
    {
        if ($this->cachedPaymentsHasIsDeleted !== null) {
            return $this->cachedPaymentsHasIsDeleted;
        }
        try {
            $table = Db::name('payments')->getTable();
            $rows = Db::query("SHOW COLUMNS FROM `{$table}` LIKE 'is_deleted'");
            $this->cachedPaymentsHasIsDeleted = !empty($rows);
        } catch (\Throwable $e) {
            $this->cachedPaymentsHasIsDeleted = false;
        }
        return $this->cachedPaymentsHasIsDeleted;
    }

    private function paymentsTableHasRefundRemark(): bool
    {
        if ($this->cachedPaymentsHasRefundRemark !== null) {
            return $this->cachedPaymentsHasRefundRemark;
        }
        try {
            $table = Db::name('payments')->getTable();
            $rows = Db::query("SHOW COLUMNS FROM `{$table}` LIKE 'refund_remark'");
            $this->cachedPaymentsHasRefundRemark = !empty($rows);
        } catch (\Throwable $e) {
            $this->cachedPaymentsHasRefundRemark = false;
        }
        return $this->cachedPaymentsHasRefundRemark;
    }
}
