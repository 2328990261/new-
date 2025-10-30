<?php
namespace app\model;

use think\Model;

/**
 * 支付记录模型
 */
class Payment extends Model
{
    // 设置表名
    protected $name = 'payments';
    
    // 设置字段信息
    protected $schema = [
        'id'                  => 'int',
        'order_no'            => 'string',
        'tutor_order_id'      => 'int',
        'tutor_name'          => 'string',
        'teacher_name'        => 'string',
        'contact_student'     => 'string',
        'amount'              => 'float',
        'deposit_amount'      => 'float',
        'refund_apply_amount' => 'float',
        'refunded_amount'     => 'float',
        'actual_amount'       => 'float',
        'payment_method'      => 'string',
        'payer_name'          => 'string',
        'payer_contact'       => 'string',
        'status'              => 'string',
        'refund_status'       => 'string',
        'transaction_id'      => 'string',
        'paid_time'           => 'datetime',
        'refund_apply_time'   => 'datetime',
        'refund_time'         => 'datetime',
        'customer_service'    => 'string',
        'refund_reason'       => 'string',
        'reject_reason'       => 'string',
        'remark'              => 'string',
        'create_time'         => 'datetime',
        'update_time'         => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 只读字段
    protected $readonly = ['id', 'order_no', 'create_time'];
    
    /**
     * 生成支付订单号
     * 格式: PAY + YYYYMMDDHHMMSS + 四位随机数
     * 例如: PAY20251004123045001
     */
    public static function generateOrderNo()
    {
        // 获取时间前缀
        $datePrefix = 'PAY' . date('YmdHis');
        
        // 尝试生成不重复的订单号，最多尝试1000次
        for ($i = 0; $i < 1000; $i++) {
            // 生成四位随机数（0001-9999）
            $randomSuffix = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // 组合成完整订单号
            $orderNo = $datePrefix . $randomSuffix;
            
            // 检查是否已存在
            $exists = self::where('order_no', $orderNo)->find();
            
            if (!$exists) {
                return $orderNo;
            }
        }
        
        // 如果1000次都重复了，抛出异常
        throw new \Exception('无法生成唯一支付订单号，请稍后重试');
    }
    
    /**
     * 生成退款单号
     * 格式: REF + YYYYMMDDHHMMSS + 四位随机数
     * 例如: REF20251004123045001
     */
    public static function generateRefundNo()
    {
        // 获取时间前缀
        $datePrefix = 'REF' . date('YmdHis');
        
        // 尝试生成不重复的退款单号，最多尝试1000次
        for ($i = 0; $i < 1000; $i++) {
            // 生成四位随机数（0001-9999）
            $randomSuffix = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // 组合成完整退款单号
            $refundNo = $datePrefix . $randomSuffix;
            
            // 检查是否已存在（这里可以根据需要检查一个退款记录表）
            // 暂时只检查不重复即可
            return $refundNo;
        }
        
        // 如果1000次都重复了，抛出异常
        throw new \Exception('无法生成唯一退款单号，请稍后重试');
    }
    
    /**
     * 关联家教订单
     */
    public function tutorOrder()
    {
        return $this->belongsTo(TutorOrder::class, 'tutor_order_id');
    }
    
    /**
     * 状态获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            'pending'   => '待支付',
            'paid'      => '已支付',
            'cancelled' => '已取消'
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 退款状态获取器
     */
    public function getRefundStatusTextAttr($value, $data)
    {
        $status = [
            'pending'    => '退款待处理',
            'processing' => '处理中',
            'rejected'   => '退款驳回',
            'completed'  => '已退费'
        ];
        return $status[$data['refund_status']] ?? '-';
    }
    
    /**
     * 支付方式获取器
     */
    public function getPaymentMethodTextAttr($value, $data)
    {
        $methods = [
            'wechat' => '微信支付',
            'alipay' => '支付宝'
        ];
        return $methods[$data['payment_method']] ?? '未知';
    }
    
    /**
     * 搜索器：订单号
     */
    public function searchOrderNoAttr($query, $value)
    {
        if ($value) {
            $query->where('order_no', 'like', '%' . $value . '%');
        }
    }
    
    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value) {
            $query->where('status', $value);
        }
    }
    
    /**
     * 搜索器：支付方式
     */
    public function searchPaymentMethodAttr($query, $value)
    {
        if ($value) {
            $query->where('payment_method', $value);
        }
    }
    
    /**
     * 搜索器：创建时间范围
     */
    public function searchCreateTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('create_time', $value[0], $value[1]);
        }
    }
}

