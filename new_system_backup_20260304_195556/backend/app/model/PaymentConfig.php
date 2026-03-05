<?php
namespace app\model;

use think\Model;

/**
 * 支付配置模型
 */
class PaymentConfig extends Model
{
    // 设置表名
    protected $name = 'payment_config';
    
    // 设置字段信息
    protected $schema = [
        'id'             => 'int',
        'payment_method' => 'string',
        'app_id'         => 'string',
        'mch_id'         => 'string',
        'api_key'        => 'string',
        'cert_path'      => 'string',
        'notify_url'     => 'string',
        'is_enabled'     => 'int',
        'create_time'    => 'datetime',
        'update_time'    => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    /**
     * 获取指定支付方式的配置
     */
    public static function getConfig($method)
    {
        return self::where('payment_method', $method)->find();
    }
    
    /**
     * 检查支付方式是否可用
     */
    public static function isEnabled($method)
    {
        $config = self::getConfig($method);
        return $config && $config->is_enabled == 1;
    }
}


