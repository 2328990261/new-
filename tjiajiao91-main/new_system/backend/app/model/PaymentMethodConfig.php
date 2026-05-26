<?php
namespace app\model;

use think\Model;

class PaymentMethodConfig extends Model
{
    protected $name = 'payment_method_config';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'sort'        => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';
    
    // 软删除
    use \think\model\concern\SoftDelete;
    
    // 软删除字段默认值为0（而不是null）
    protected $defaultSoftDelete = 0;
}
