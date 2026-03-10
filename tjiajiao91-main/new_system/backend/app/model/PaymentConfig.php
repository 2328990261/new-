<?php

namespace app\model;

use think\Model;

/**
 * 支付配置模型
 */
class PaymentConfig extends Model
{
    protected $name = 'payment_config';
    
    // 设置字段信息
    protected $schema = [
        'id'             => 'int',
        'payment_method' => 'string',
        'app_id'         => 'string',
        'mch_id'         => 'string',
        'api_key'        => 'string',
        'app_secret'     => 'string',
        'cert_path'      => 'string',
        'key_path'       => 'string',
        'notify_url'     => 'string',
        'is_enabled'     => 'int',
        'create_time'    => 'datetime',
        'update_time'    => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    /**
     * 获取指定支付方式的配置
     * @param string $method 支付方式 wechat|alipay
     * @return array|null
     */
    public static function getConfig($method)
    {
        return self::where('payment_method', $method)->find();
    }
    
    /**
     * 检查支付方式是否启用
     * @param string $method 支付方式
     * @return bool
     */
    public static function isEnabled($method)
    {
        $config = self::where('payment_method', $method)
            ->where('is_enabled', 1)
            ->find();
        
        return $config !== null;
    }
}
