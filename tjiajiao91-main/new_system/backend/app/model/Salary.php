<?php
namespace app\model;

use think\Model;

class Salary extends Model
{
    protected $name = 'salary';
    
    // 设置字段信息
    protected $schema = [
        'id'                  => 'int',
        'expense_date'        => 'string',
        'expense_type'        => 'string',
        'quantity'            => 'float',
        'unit_price'          => 'float',
        'project_name'        => 'string',
        'amount'              => 'float',
        'invoice_attachment'  => 'string',
        'payment_attachment'  => 'string',
        'payment_status'      => 'string',
        'invoice_status'      => 'string',
        'receipt_method'      => 'string',
        'payment_method'      => 'string',
        'period'              => 'string',
        'month'               => 'string',
        'remark'              => 'string',
        'create_time'         => 'int',
        'update_time'         => 'int',
        'delete_time'         => 'int',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 软删除
    use \think\model\concern\SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    
    // 获取器 - 格式化金额
    public function getAmountAttr($value)
    {
        return number_format($value, 2, '.', '');
    }
    
    public function getQuantityAttr($value)
    {
        return number_format($value, 2, '.', '');
    }
    
    public function getUnitPriceAttr($value)
    {
        return number_format($value, 2, '.', '');
    }
}
