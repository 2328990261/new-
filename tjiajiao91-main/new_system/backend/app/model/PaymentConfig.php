<?php

namespace app\model;

use think\Model;

/**
 * 支付配置模型
 */
class PaymentConfig extends Model
{
    protected $name = 'payment_config';
    
    // 设置字段信息（与 fa_payment_config 一致）
    protected $schema = [
        'id'             => 'int',
        'payment_method' => 'string',
        'scene'          => 'string',
        'name'           => 'string',
        'app_id'         => 'string',
        'mch_id'         => 'string',
        'api_key'        => 'string',
        'app_secret'     => 'string',
        'cert_path'      => 'string',
        'key_path'       => 'string',
        'notify_url'     => 'string',
        'refund_follow_qrcode' => 'string',
        'is_enabled'     => 'int',
        'is_default'     => 'int',
        'create_time'    => 'datetime',
        'update_time'    => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    /**
     * 按支付方式 + 场景取一条配置（多套微信：default / h5 等）
     * @param string $method wechat|alipay
     * @param string $scene  如 default、h5
     */
    public static function getConfigRow(string $method, string $scene = 'default'): ?self
    {
        $row = self::where('payment_method', $method)
            ->where('scene', $scene)
            ->order('is_default', 'desc')
            ->order('id', 'asc')
            ->find();

        if (!$row && $scene !== 'default') {
            return self::getConfigRow($method, 'default');
        }

        return $row ?: null;
    }

    /**
     * 获取指定支付方式的默认场景配置（兼容旧调用）
     * @param string $method 支付方式 wechat|alipay
     */
    public static function getConfig(string $method): ?self
    {
        return self::getConfigRow($method, 'default');
    }

    /**
     * 检查支付方式是否启用（看默认场景）
     * @param string $method 支付方式
     */
    public static function isEnabled(string $method): bool
    {
        $config = self::getConfigRow($method, 'default');

        return $config !== null && (int) $config->is_enabled === 1;
    }
}
