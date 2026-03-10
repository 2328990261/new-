<?php

namespace app\model;

use think\Model;

class Payment extends Model
{
    protected $name = 'payments';
    
    // 设置字段信息
    protected $schema = [
        'id'                    => 'int',
        'order_no'              => 'string',
        'tutor_order_id'        => 'int',
        'tutor_name'            => 'string',
        'teacher_name'          => 'string',
        'teacher_id'            => 'int',
        'contact_student'       => 'string',
        'amount'                => 'float',
        'deposit_amount'        => 'float',
        'refund_apply_amount'   => 'float',
        'refunded_amount'       => 'float',
        'actual_amount'         => 'float',
        'payment_method'        => 'string',
        'payer_name'            => 'string',
        'payer_contact'         => 'string',
        'transaction_id'        => 'string',
        'status'                => 'string',
        'refund_status'         => 'string',
        'dispatcher_id'         => 'int',
        'refund_reason'         => 'string',
        'reject_reason'         => 'string',
        'refund_voucher'        => 'string',
        'remark'                => 'string',
        'paid_time'             => 'datetime',
        'refund_time'           => 'datetime',
        'refund_apply_time'     => 'datetime',
        'create_time'           => 'datetime',
        'update_time'           => 'datetime'
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 追加属性
    protected $append = [];
    
    // 类型转换
    protected $type = [
        'amount'                => 'float',
        'deposit_amount'        => 'float',
        'refund_apply_amount'   => 'float',
        'refunded_amount'       => 'float',
        'actual_amount'         => 'float'
    ];
    
    /**
     * 关联派单员
     */
    public function dispatcher()
    {
        return $this->belongsTo(Admin::class, 'dispatcher_id');
    }
    
    /**
     * 关联老师
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
    
    /**
     * 关联家教订单
     */
    public function tutorOrder()
    {
        return $this->belongsTo(TutorOrder::class, 'tutor_order_id');
    }
    
    /**
     * 生成支付订单号
     * @return string
     */
    public static function generateOrderNo()
    {
        return 'PAY' . date('YmdHis') . rand(1000, 9999);
    }
}
