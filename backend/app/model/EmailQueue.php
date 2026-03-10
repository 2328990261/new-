<?php
namespace app\model;

use think\Model;

/**
 * 邮件队列模型
 */
class EmailQueue extends Model
{
    protected $name = 'email_queue';
    
    protected $autoWriteTimestamp = false;
    
    // 状态常量
    const STATUS_PENDING = 'pending';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    
    // 邮件类型常量
    const TYPE_LEAD_ASSIGN = 'lead_assign';
    const TYPE_BOOKING = 'booking';
    const TYPE_ORDER = 'order';
}
