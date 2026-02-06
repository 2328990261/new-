<?php
namespace app\model;

use think\Model;

/**
 * 服务协议模型
 */
class ServiceAgreement extends Model
{
    // 设置表名
    protected $name = 'service_agreement';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'title'       => 'string',
        'content'     => 'string',
        'version'     => 'string',
        'is_active'   => 'int',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    /**
     * 获取当前生效的协议
     */
    public static function getActive()
    {
        return self::where('is_active', 1)->order('id', 'desc')->find();
    }
}


