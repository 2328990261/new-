<?php
namespace app\model;

use think\Model;

/**
 * 邮箱日志模型
 */
class EmailLog extends Model
{
    protected $name = 'email_queue';  // 使用email_queue表
    
    // 邮件类型常量
    const TYPE_LEAD_ASSIGN = 'lead_assign';      // 线索指派
    const TYPE_BOOKING = 'booking';              // 预约通知
    const TYPE_ORDER = 'order';                  // 订单通知
    const TYPE_TEST = 'test';                    // 测试邮件
    
    // 发送状态常量（根据现有表结构）
    const STATUS_PENDING = 'pending';    // 待发送
    const STATUS_SENT = 'sent';          // 已发送
    const STATUS_FAILED = 'failed';      // 失败
    
    /**
     * 获取邮件类型文本
     */
    public function getEmailTypeTextAttr($value, $data)
    {
        $types = [
            self::TYPE_LEAD_ASSIGN => '线索指派通知',
            self::TYPE_BOOKING => '预约通知',
            self::TYPE_ORDER => '订单通知',
            self::TYPE_TEST => '测试邮件',
        ];
        
        return $types[$data['email_type']] ?? $data['email_type'];
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $statuses = [
            self::STATUS_PENDING => '待发送',
            self::STATUS_SENT => '发送成功',
            self::STATUS_FAILED => '发送失败',
        ];
        
        return $statuses[$data['status']] ?? '未知';
    }
    
    /**
     * 记录邮件发送日志
     */
    public static function log($data)
    {
        // body 在库中多为 NOT NULL；失败日志常不传正文，用空串避免 1048 Column 'body' cannot be null
        return self::create([
            'email_type' => $data['email_type'] ?? null,
            'recipient_email' => $data['recipient_email'],
            'recipient_name' => $data['recipient_name'] ?? null,
            'subject' => $data['subject'],
            'body' => $data['body'] ?? '',
            'related_id' => $data['related_id'] ?? null,
            'status' => $data['status'] ?? self::STATUS_PENDING,
            'error_message' => $data['error_msg'] ?? null,  // 使用 error_message 字段
            'sent_at' => $data['send_time'] ?? date('Y-m-d H:i:s'),  // 使用 sent_at 字段
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
